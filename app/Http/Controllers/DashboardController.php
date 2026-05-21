<?php

namespace App\Http\Controllers;

use App\Models\Balance;
use App\Models\Expense;
use App\Models\Group;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user   = $request->user();
        $userId = (string) $user->_id;

        $groups = Group::where('members.user_id', $userId)
            ->where('is_archived', false)
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        $groupIds = $groups->map(fn($g) => (string) $g->_id)->toArray();

        $recentExpenses = empty($groupIds) ? collect() : Expense::where('status', 'active')
            ->whereIn('group_id', $groupIds)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Balance totals
        $totalOwed = Balance::where('to_user_id', $userId)->sum('amount');
        $totalOwes = Balance::where('from_user_id', $userId)->sum('amount');

        $recentTransactions = Transaction::where(function ($q) use ($userId) {
            $q->where('from_user_id', $userId)->orWhere('to_user_id', $userId);
        })->orderByDesc('created_at')->limit(5)->get();

        // ── Chart data ──────────────────────────────────────────────────────
        $sixMonthsAgo = Carbon::now()->subMonths(6)->startOfMonth();

        $allExpenses = empty($groupIds) ? collect() : Expense::where('status', 'active')
            ->whereIn('group_id', $groupIds)
            ->where('created_at', '>=', $sixMonthsAgo)
            ->get();

        // 1. Spending by category (normalize key to lowercase to handle old capitalised docs)
        $categoryBreakdown = $allExpenses
            ->groupBy(fn($exp) => strtolower($exp->category ?? 'other'))
            ->map(fn($exps) => $exps->sum('amount'))
            ->toArray();

        // 2. Monthly spending + 4. Balance trend (computed together in one pass)
        $monthlySpending = [];
        $balanceTrend    = [];
        for ($i = 5; $i >= 0; $i--) {
            $m     = Carbon::now()->subMonths($i);
            $key   = $m->format('M Y');
            $start = $m->copy()->startOfMonth();
            $end   = $m->copy()->endOfMonth();

            $monthExps = $allExpenses->filter(
                fn($exp) => Carbon::parse($exp->created_at)->between($start, $end)
            );

            $monthlySpending[$key] = $monthExps->sum('amount');

            // Net monthly cash flow: money paid out by user - user's own split obligation
            $paidOut = 0;
            $owedOut = 0;
            foreach ($monthExps as $exp) {
                $pb = $exp->paid_by ?? [];
                if (isset($pb['user_id'])) { $pb = [$pb]; }
                foreach ($pb as $payer) {
                    if (($payer['user_id'] ?? null) === $userId) {
                        $paidOut += $payer['amount'] ?? $exp->amount;
                    }
                }
                foreach ($exp->splits ?? [] as $split) {
                    if (($split['user_id'] ?? null) === $userId) {
                        $owedOut += $split['amount'] ?? 0;
                    }
                }
            }
            $balanceTrend[$key] = $paidOut - $owedOut;
        }

        // 3. Per-group totals
        $groupTotals = [];
        foreach ($groups as $group) {
            $total = Expense::where('group_id', (string) $group->_id)
                ->where('status', 'active')
                ->sum('amount');
            if ($total > 0) {
                $groupTotals[$group->name] = $total;
            }
        }

        $chartData = [
            'categoryBreakdown' => $categoryBreakdown,
            'monthlySpending'   => $monthlySpending,
            'groupTotals'       => $groupTotals,
            'balanceTrend'      => $balanceTrend,
        ];

        return view('dashboard', compact(
            'groups',
            'recentExpenses',
            'totalOwed',
            'totalOwes',
            'recentTransactions',
            'chartData'
        ));
    }
}
