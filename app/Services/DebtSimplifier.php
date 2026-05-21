<?php

namespace App\Services;

class DebtSimplifier
{
    public function simplify(array $balances): array
    {
        // balances: [['from' => userId, 'to' => userId, 'amount' => int (paise)]]
        // Build net balance map
        $net = [];

        foreach ($balances as $b) {
            $from   = $b['from'];
            $to     = $b['to'];
            $amount = (int) $b['amount'];

            $net[$from] = ($net[$from] ?? 0) - $amount;
            $net[$to]   = ($net[$to]   ?? 0) + $amount;
        }

        // Separate creditors (positive) and debtors (negative)
        $creditors = [];
        $debtors   = [];

        foreach ($net as $userId => $balance) {
            if ($balance > 0) {
                $creditors[] = ['user_id' => $userId, 'amount' => $balance];
            } elseif ($balance < 0) {
                $debtors[] = ['user_id' => $userId, 'amount' => abs($balance)];
            }
        }

        usort($creditors, fn($a, $b) => $b['amount'] - $a['amount']);
        usort($debtors,   fn($a, $b) => $b['amount'] - $a['amount']);

        $transactions = [];
        $i = 0;
        $j = 0;

        while ($i < count($creditors) && $j < count($debtors)) {
            $settle = min($creditors[$i]['amount'], $debtors[$j]['amount']);

            $transactions[] = [
                'from'   => $debtors[$j]['user_id'],
                'to'     => $creditors[$i]['user_id'],
                'amount' => $settle,
            ];

            $creditors[$i]['amount'] -= $settle;
            $debtors[$j]['amount']   -= $settle;

            if ($creditors[$i]['amount'] === 0) {
                $i++;
            }
            if ($debtors[$j]['amount'] === 0) {
                $j++;
            }
        }

        return $transactions;
    }
}
