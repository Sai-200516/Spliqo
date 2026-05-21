<?php

namespace App\Services;

use App\Models\Balance;
use App\Models\Expense;
use App\Models\Group;
use App\Models\Transaction;

class BalanceService
{
    public function __construct(private DebtSimplifier $simplifier) {}

    public function recalculate(string $groupId): void
    {
        Balance::where('group_id', $groupId)->delete();

        $expenses = Expense::where('group_id', $groupId)
            ->where('status', 'active')
            ->get();

        $rawDebts = [];

        foreach ($expenses as $expense) {
            $paidBy = $expense->paid_by;
            $payerId = $paidBy['user_id'] ?? null;

            if (!$payerId) {
                continue;
            }

            foreach ($expense->splits ?? [] as $split) {
                $debtorId = $split['user_id'] ?? null;
                if (!$debtorId || $debtorId === $payerId) {
                    continue;
                }
                if ($split['is_settled'] ?? false) {
                    continue;
                }

                $rawDebts[] = [
                    'from'   => $debtorId,
                    'to'     => $payerId,
                    'amount' => $split['amount'],
                ];
            }
        }

        // Incorporate completed transactions (payments reduce debt)
        $settled = Transaction::where('group_id', $groupId)
            ->where('status', 'completed')
            ->get();

        foreach ($settled as $tx) {
            $rawDebts[] = [
                'from'   => $tx->to_user_id,   // recipient gets credit back
                'to'     => $tx->from_user_id,
                'amount' => $tx->amount,
            ];
        }

        $simplified = $this->simplifier->simplify($rawDebts);

        foreach ($simplified as $debt) {
            if ($debt['amount'] <= 0) {
                continue;
            }
            Balance::create([
                'group_id'     => $groupId,
                'from_user_id' => $debt['from'],
                'to_user_id'   => $debt['to'],
                'amount'       => $debt['amount'],
            ]);
        }
    }

    public function getUserBalance(string $userId, string $groupId): array
    {
        $owes = Balance::where('group_id', $groupId)
            ->where('from_user_id', $userId)
            ->get()
            ->map(fn($b) => ['to' => $b->to_user_id, 'amount' => $b->amount])
            ->toArray();

        $owed = Balance::where('group_id', $groupId)
            ->where('to_user_id', $userId)
            ->get()
            ->map(fn($b) => ['from' => $b->from_user_id, 'amount' => $b->amount])
            ->toArray();

        $totalOwes = array_sum(array_column($owes, 'amount'));
        $totalOwed = array_sum(array_column($owed, 'amount'));

        return [
            'owes'       => $owes,
            'owed'       => $owed,
            'total_owes' => $totalOwes,
            'total_owed' => $totalOwed,
            'net'        => $totalOwed - $totalOwes,
        ];
    }
}
