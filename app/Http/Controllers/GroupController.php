<?php

namespace App\Http\Controllers;

use App\Models\Balance;
use App\Models\Expense;
use App\Models\Group;
use App\Models\Invitation;
use App\Models\User;
use App\Services\BalanceService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GroupController extends Controller
{
    public function __construct(
        private BalanceService $balances,
        private NotificationService $notifications
    ) {}

    public function index(Request $request)
    {
        $userId = (string) $request->user()->_id;

        $groups = Group::where('members.user_id', $userId)
            ->orderByDesc('updated_at')
            ->get()
            ->map(function ($group) use ($userId) {
                $group->my_balance = $this->balances->getUserBalance($userId, (string) $group->_id);
                return $group;
            });

        return view('groups.index', compact('groups'));
    }

    public function create()
    {
        return view('groups.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'currency'    => 'nullable|string|size:3',
            'image'       => 'nullable|image|max:2048',
        ]);

        $userId = (string) $request->user()->_id;

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('groups', 'public');
        }

        $data['created_by'] = $userId;
        $data['members']    = [[
            'user_id'  => $userId,
            'role'     => 'admin',
            'name'     => $request->user()->name,
            'email'    => $request->user()->email,
            'joined_at' => now()->toISOString(),
        ]];

        $group = Group::create($data);

        return redirect()->route('groups.show', $group->_id)
            ->with('success', 'Group created successfully.');
    }

    public function show(Request $request, string $id)
    {
        $group  = Group::findOrFail($id);
        $userId = (string) $request->user()->_id;

        abort_unless($group->hasMember($userId), 403);

        $balance            = $this->balances->getUserBalance($userId, $id);
        $balances           = Balance::where('group_id', $id)->get();
        $memberIds          = collect($group->members)->pluck('user_id')->toArray();
        $members            = User::findMany($memberIds);
        $expenses           = Expense::where('group_id', $id)
            ->where('status', 'active')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();
        $pendingInvitations = Invitation::where('group_id', $id)
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->get()
            ->filter(fn($inv) => !$inv->isExpired());

        return view('groups.show', compact('group', 'balance', 'balances', 'members', 'expenses', 'pendingInvitations'));
    }

    public function edit(Request $request, string $id)
    {
        $group = Group::findOrFail($id);
        abort_unless($group->isAdmin((string) $request->user()->_id), 403);
        return view('groups.edit', compact('group'));
    }

    public function update(Request $request, string $id)
    {
        $group = Group::findOrFail($id);
        abort_unless($group->isAdmin((string) $request->user()->_id), 403);

        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'image'       => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('groups', 'public');
        }

        $group->update($data);

        return redirect()->route('groups.show', $id)->with('success', 'Group updated.');
    }

    public function destroy(Request $request, string $id)
    {
        $group = Group::findOrFail($id);
        abort_unless($group->isAdmin((string) $request->user()->_id), 403);

        $group->delete();

        return redirect()->route('groups.index')->with('success', 'Group deleted.');
    }

    public function inviteStore(Request $request, string $id)
    {
        $group = Group::findOrFail($id);
        abort_unless($group->isAdmin((string) $request->user()->_id), 403);

        $data = $request->validate(['email' => 'required|email|max:255']);

        $existing = Invitation::where('group_id', $id)
            ->where('email', $data['email'])
            ->where('status', 'pending')
            ->first();

        if ($existing && !$existing->isExpired()) {
            return back()->with('error', 'An active invitation already exists for that email.');
        }

        $invitation = Invitation::create([
            'group_id'   => $id,
            'email'      => $data['email'],
            'invited_by' => (string) $request->user()->_id,
            'token'      => Str::random(64),
            'expires_at' => now()->addDays(7),
            'status'     => 'pending',
        ]);

        \Illuminate\Support\Facades\Mail::to($data['email'])->send(
            new \App\Mail\GroupInvitationMail($group, $invitation, $request->user()->name)
        );

        return back()->with('success', 'Invitation sent to ' . $data['email']);
    }

    public function inviteResend(Request $request, string $id, string $inviteId)
    {
        $group      = Group::findOrFail($id);
        abort_unless($group->isAdmin((string) $request->user()->_id), 403);

        $invitation = Invitation::findOrFail($inviteId);
        abort_unless((string) $invitation->group_id === $id, 404);

        $invitation->update(['expires_at' => now()->addDays(7)]);

        \Illuminate\Support\Facades\Mail::to($invitation->email)->send(
            new \App\Mail\GroupInvitationMail($group, $invitation, $request->user()->name)
        );

        return back()->with('success', 'Invitation resent to ' . $invitation->email);
    }

    public function inviteCancel(Request $request, string $id, string $inviteId)
    {
        $group      = Group::findOrFail($id);
        abort_unless($group->isAdmin((string) $request->user()->_id), 403);

        $invitation = Invitation::findOrFail($inviteId);
        abort_unless((string) $invitation->group_id === $id, 404);

        $invitation->update(['status' => 'cancelled']);

        return back()->with('success', 'Invitation cancelled.');
    }

    public function acceptInvite(string $token)
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();

        if (!$invitation->isPending()) {
            return redirect()->route('groups.index')
                ->with('error', 'This invitation has expired or already been used.');
        }

        $user  = auth()->user();
        $group = $invitation->group();

        if (!$group) {
            abort(404);
        }

        if (!$group->hasMember((string) $user->_id)) {
            $members   = $group->members ?? [];
            $members[] = [
                'user_id'   => (string) $user->_id,
                'role'      => 'member',
                'name'      => $user->name,
                'email'     => $user->email,
                'joined_at' => now()->toISOString(),
            ];
            $group->update(['members' => $members]);
        }

        $invitation->update(['status' => 'accepted']);

        $this->notifications->notifyGroupMembers(
            collect($group->members)->pluck('user_id')->toArray(),
            (string) $user->_id,
            'member_joined',
            $user->name . ' joined ' . $group->name,
            $user->name . ' accepted your invitation and joined the group.',
            ['group_id' => (string) $group->_id]
        );

        return redirect()->route('groups.show', $group->_id)
            ->with('success', 'Welcome to ' . $group->name . '!');
    }

    public function removeMember(Request $request, string $groupId, string $memberId)
    {
        $group = Group::findOrFail($groupId);
        abort_unless($group->isAdmin((string) $request->user()->_id), 403);

        $members = collect($group->members ?? [])
            ->filter(fn($m) => $m['user_id'] !== $memberId)
            ->values()
            ->toArray();

        $group->update(['members' => $members]);

        // Recalculate balances after member removal
        $this->balances->recalculate($groupId);

        return back()->with('success', 'Member removed.');
    }

    public function archive(Request $request, string $id)
    {
        $group = Group::findOrFail($id);
        abort_unless($group->isAdmin((string) $request->user()->_id), 403);

        $group->update(['is_archived' => true, 'archived_at' => now()]);

        return redirect()->route('groups.index')->with('success', 'Group archived.');
    }
}
