<?php

namespace App\Http\Controllers;

use App\Events\ExpenseCreated;
use App\Models\Expense;
use App\Models\Group;
use App\Models\User;
use App\Services\BalanceService;
use App\Services\NotificationService;
use App\Services\OcrService;
use App\Services\SplitEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
{
    public function __construct(
        private SplitEngine $splitter,
        private BalanceService $balances,
        private NotificationService $notifications,
        private OcrService $ocr
    ) {}

    public function index(Request $request)
    {
        $userId = (string) $request->user()->_id;

        $groupIds = Group::where('members.user_id', $userId)
            ->get()
            ->map(fn($g) => (string) $g->_id)
            ->toArray();

        $query = Expense::whereIn('group_id', $groupIds)->where('status', 'active');

        if ($request->filled('group')) {
            $query->where('group_id', $request->group);
        }
        if ($request->filled('category')) {
            $query->where('category', strtolower($request->category));
        }
        if ($request->filled('search')) {
            $query->where('title', 'regex', '/' . preg_quote($request->search, '/') . '/i');
        }

        $expenses = $query->orderByDesc('created_at')->paginate(20);

        $groups = Group::whereIn('_id', $groupIds)->get();

        return view('expenses.index', compact('expenses', 'groups'));
    }

    public function create(Request $request)
    {
        $userId = (string) $request->user()->_id;

        $groups = Group::where('members.user_id', $userId)
            ->where('is_archived', false)
            ->get();

        $selectedGroup = $request->filled('group') ? Group::find($request->group) : null;

        return view('expenses.create', compact('groups', 'selectedGroup'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'      => 'required|string|max:200',
            'amount'     => 'required|numeric|min:0.01|max:999999.99',
            'category'   => 'nullable|string|in:' . implode(',', Expense::CATEGORIES),
            'group_id'   => 'required|string',
            'split_type' => 'required|in:' . implode(',', Expense::SPLIT_TYPES),
            'notes'      => 'nullable|string|max:1000',
            'tags'       => 'nullable|string',
            'members'    => 'nullable|array',
            'members.*'  => 'string',
            'receipt'    => 'nullable|image|max:5120',
            'splits'          => 'nullable|array',
            'percentages'     => 'nullable|array',
            'percentages.*'   => 'nullable|numeric',
            'exact_amounts'   => 'nullable|array',
            'exact_amounts.*' => 'nullable|numeric',
            'shares_count'    => 'nullable|array',
            'shares_count.*'  => 'nullable|numeric',
        ]);

        $group = Group::findOrFail($data['group_id']);
        abort_unless($group->hasMember((string) $request->user()->_id), 403);

        $userId = (string) $request->user()->_id;

        // Derive members from group when form doesn't send them explicitly
        $members = !empty($data['members'])
            ? array_map('strval', $data['members'])
            : collect($group->members)->pluck('user_id')->map(fn($id) => (string) $id)->toArray();

        // Build per-type, user-keyed arrays the SplitEngine expects.
        // Forms send splits[i][user_id] + splits[i][value]; map to the right bucket.
        $percentages  = $request->input('percentages', []);
        $exactAmounts = $request->input('exact_amounts', []);
        $sharesCount  = $request->input('shares_count', []);

        foreach ($request->input('splits', []) as $s) {
            if (!isset($s['user_id'], $s['value'])) continue;
            $uid = (string) $s['user_id'];
            $val = $s['value'];
            match ($data['split_type']) {
                'percentage' => $percentages[$uid]  = $val,
                'exact'      => $exactAmounts[$uid] = $val,
                'shares'     => $sharesCount[$uid]  = $val,
                default      => null,
            };
        }

        $attachments = [];
        if ($request->hasFile('receipt')) {
            $path = $request->file('receipt')->store('receipts/' . $data['group_id'], 'public');
            $attachments[] = ['path' => $path, 'type' => 'receipt'];
        }

        $tags = !empty($data['tags'] ?? null)
            ? array_filter(array_map('trim', explode(',', $data['tags'])))
            : [];

        $splits = $this->splitter->compute(
            [
                'amount'      => $data['amount'],
                'split_type'  => $data['split_type'],
                'percentages' => $percentages,
                'amounts'     => $exactAmounts,
                'shares'      => $sharesCount,
            ],
            $members
        );

        $expense = Expense::create([
            'title'       => $data['title'],
            'amount'      => (int) round($data['amount'] * 100),
            'category'    => strtolower($data['category'] ?? 'other'),
            'group_id'    => $data['group_id'],
            'split_type'  => $data['split_type'],
            'splits'      => $splits,
            'paid_by'     => [['user_id' => $userId, 'amount' => (int) round($data['amount'] * 100)]],
            'notes'       => $data['notes'] ?? null,
            'tags'        => $tags,
            'attachments' => $attachments,
            'created_by'  => $userId,
        ]);

        $this->balances->recalculate($data['group_id']);

        $memberIds = collect($group->members)->pluck('user_id')->toArray();
        $this->notifications->notifyGroupMembers(
            $memberIds,
            $userId,
            'expense_added',
            $request->user()->name . ' added an expense',
            '"' . $expense->title . '" — ' . $expense->amount_formatted,
            ['group_id' => $data['group_id'], 'expense_id' => (string) $expense->_id]
        );

        try {
            broadcast(new ExpenseCreated($data['group_id'], [
                'id'         => (string) $expense->_id,
                'title'      => $expense->title,
                'amount'     => $expense->amount,
                'created_by' => $userId,
            ]));
        } catch (\Throwable) {}

        if ($request->wantsJson()) {
            return response()->json(['id' => (string) $expense->_id], 201);
        }

        return redirect()->route('groups.show', $data['group_id'])
            ->with('success', 'Expense added.');
    }

    public function show(string $id)
    {
        $expense = Expense::findOrFail($id);
        $group   = Group::find($expense->group_id);
        abort_unless($group?->hasMember((string) auth()->id()), 403);

        $memberIds = collect($expense->splits)->pluck('user_id')->toArray();
        $members   = User::findMany($memberIds)->keyBy(fn($u) => (string) $u->_id);

        return view('expenses.show', compact('expense', 'group', 'members'));
    }

    public function edit(string $id)
    {
        $expense = Expense::findOrFail($id);
        $group   = Group::find($expense->group_id);
        abort_unless($group?->hasMember((string) auth()->id()), 403);
        abort_unless($expense->created_by === (string) auth()->id() || $group->isAdmin((string) auth()->id()), 403);

        $members = User::findMany(collect($group->members)->pluck('user_id')->toArray());

        return view('expenses.edit', compact('expense', 'group', 'members'));
    }

    public function update(Request $request, string $id)
    {
        $expense = Expense::findOrFail($id);
        $group   = Group::find($expense->group_id);
        abort_unless($group?->hasMember((string) $request->user()->_id), 403);
        abort_unless($expense->created_by === (string) $request->user()->_id || $group->isAdmin((string) $request->user()->_id), 403);

        $data = $request->validate([
            'title'    => 'required|string|max:200',
            'amount'   => 'required|numeric|min:0.01|max:999999.99',
            'category' => 'nullable|string|in:' . implode(',', Expense::CATEGORIES),
            'notes'    => 'nullable|string|max:1000',
            'tags'     => 'nullable|string',
        ]);

        $tags = !empty($data['tags'] ?? null)
            ? array_filter(array_map('trim', explode(',', $data['tags'])))
            : [];

        $expense->update([
            'title'      => $data['title'],
            'amount'     => (int) round($data['amount'] * 100),
            'category'   => strtolower($data['category'] ?? 'other'),
            'notes'      => $data['notes'] ?? null,
            'tags'       => $tags,
            'updated_by' => (string) $request->user()->_id,
        ]);

        $this->balances->recalculate($expense->group_id);

        return redirect()->route('groups.show', $expense->group_id)->with('success', 'Expense updated.');
    }

    public function destroy(Request $request, string $id)
    {
        $expense = Expense::findOrFail($id);
        $group   = Group::find($expense->group_id);
        abort_unless($group?->hasMember((string) $request->user()->_id), 403);
        abort_unless($expense->created_by === (string) $request->user()->_id || $group->isAdmin((string) $request->user()->_id), 403);

        $groupId = $expense->group_id;
        $expense->delete();

        $this->balances->recalculate($groupId);

        if ($request->wantsJson()) {
            return response()->json(null, 204);
        }

        return redirect()->route('groups.show', $groupId)->with('success', 'Expense deleted.');
    }

    public function ocr(Request $request)
    {
        $request->validate(['receipt' => 'required|image|max:5120']);

        $amountPaise = $this->ocr->extractAmount($request->file('receipt'));

        return response()->json([
            'amount' => $amountPaise ? $amountPaise / 100 : null,
        ]);
    }
}
