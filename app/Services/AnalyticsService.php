<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Transaction;
use Carbon\Carbon;

class AnalyticsService
{
    public function groupSummary(string $groupId, int $months = 6): array
    {
        $from = now()->subMonths($months - 1)->startOfMonth();

        $expenses = Expense::where('group_id', $groupId)
            ->where('status', 'active')
            ->where('created_at', '>=', $from)
            ->get();

        return [
            'monthly'    => $this->monthlyTotals($expenses),
            'categories' => $this->categoryBreakdown($expenses),
            'per_member' => $this->perMemberSpend($expenses),
            'total'      => $expenses->sum('amount'),
        ];
    }

    public function userSummary(string $userId, int $months = 6): array
    {
        $from = now()->subMonths($months - 1)->startOfMonth();

        $expenses = Expense::where('created_at', '>=', $from)
            ->where('status', 'active')
            ->get()
            ->filter(fn($e) => collect($e->splits ?? [])->firstWhere('user_id', $userId) !== null);

        return [
            'monthly'    => $this->monthlyTotals($expenses),
            'categories' => $this->categoryBreakdown($expenses),
            'total'      => $expenses->sum(fn($e) => $this->userShareOfExpense($e, $userId)),
        ];
    }

    private function monthlyTotals($expenses): array
    {
        $data = [];
        foreach ($expenses as $expense) {
            $key = Carbon::parse($expense->created_at)->format('Y-m');
            $data[$key] = ($data[$key] ?? 0) + $expense->amount;
        }
        ksort($data);
        return $data;
    }

    private function categoryBreakdown($expenses): array
    {
        $data = [];
        foreach ($expenses as $expense) {
            $cat = $expense->category ?? 'other';
            $data[$cat] = ($data[$cat] ?? 0) + $expense->amount;
        }
        arsort($data);
        return $data;
    }

    private function perMemberSpend($expenses): array
    {
        $data = [];
        foreach ($expenses as $expense) {
            $payerId = $expense->paid_by['user_id'] ?? null;
            if ($payerId) {
                $data[$payerId] = ($data[$payerId] ?? 0) + $expense->amount;
            }
        }
        return $data;
    }

    private function userShareOfExpense(Expense $expense, string $userId): int
    {
        foreach ($expense->splits ?? [] as $split) {
            if (($split['user_id'] ?? '') === $userId) {
                return $split['amount'] ?? 0;
            }
        }
        return 0;
    }
}
