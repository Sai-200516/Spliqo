<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(private AnalyticsService $analytics) {}

    public function index(Request $request)
    {
        $user   = $request->user();
        $userId = (string) $user->_id;

        $groupIds = Group::where('members.user_id', $userId)
            ->where('is_archived', false)
            ->get()
            ->map(fn($g) => (string) $g->_id)
            ->toArray();

        $selectedGroupId = $request->input('group', null);
        $months          = (int) $request->input('months', 6);
        $months          = in_array($months, [3, 6, 12]) ? $months : 6;

        if ($selectedGroupId && in_array($selectedGroupId, $groupIds)) {
            $summary = $this->analytics->groupSummary($selectedGroupId, $months);
        } else {
            $summary = $this->analytics->userSummary($userId, $months);
        }

        $groups = Group::whereIn('_id', $groupIds)->get();

        return view('analytics.index', compact('summary', 'groups', 'selectedGroupId', 'months'));
    }

    public function groupData(Request $request, string $groupId)
    {
        $userId = (string) $request->user()->_id;
        $group  = Group::findOrFail($groupId);
        abort_unless($group->hasMember($userId), 403);

        $months = min(max((int) $request->input('months', 6), 1), 24);
        $stats  = $this->analytics->groupSummary($groupId, $months);

        return response()->json($stats);
    }
}
