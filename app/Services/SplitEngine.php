<?php

namespace App\Services;

use App\Models\Expense;

class SplitEngine
{
    public function compute(array $data, array $memberIds): array
    {
        $totalPaise = (int) round($data['amount'] * 100);
        $splitType  = $data['split_type'];
        $splits     = [];

        return match ($splitType) {
            'equal'      => $this->equalSplit($totalPaise, $memberIds),
            'percentage' => $this->percentageSplit($totalPaise, $data['percentages'] ?? [], $memberIds),
            'exact'      => $this->exactSplit($data['amounts'] ?? [], $memberIds),
            'shares'     => $this->sharesSplit($totalPaise, $data['shares'] ?? [], $memberIds),
            default      => $this->equalSplit($totalPaise, $memberIds),
        };
    }

    private function equalSplit(int $totalPaise, array $memberIds): array
    {
        $count = count($memberIds);
        if ($count === 0) {
            return [];
        }

        $base      = intdiv($totalPaise, $count);
        $remainder = $totalPaise % $count;
        $splits    = [];

        foreach ($memberIds as $index => $userId) {
            $splits[] = [
                'user_id'   => (string) $userId,
                'amount'    => $base + ($index < $remainder ? 1 : 0),
                'is_settled' => false,
            ];
        }

        return $splits;
    }

    private function percentageSplit(int $totalPaise, array $percentages, array $memberIds): array
    {
        $splits = [];
        $accumulated = 0;
        $count = count($memberIds);

        foreach ($memberIds as $index => $userId) {
            $pct = (float) ($percentages[(string) $userId] ?? (100 / $count));
            $amount = ($index < $count - 1)
                ? (int) round($totalPaise * $pct / 100)
                : $totalPaise - $accumulated;

            $splits[] = [
                'user_id'    => (string) $userId,
                'amount'     => $amount,
                'percentage' => $pct,
                'is_settled' => false,
            ];
            $accumulated += $amount;
        }

        return $splits;
    }

    private function exactSplit(array $amounts, array $memberIds): array
    {
        $splits = [];
        foreach ($memberIds as $userId) {
            $amount = (int) round(($amounts[(string) $userId] ?? 0) * 100);
            $splits[] = [
                'user_id'   => (string) $userId,
                'amount'    => $amount,
                'is_settled' => false,
            ];
        }
        return $splits;
    }

    private function sharesSplit(int $totalPaise, array $shares, array $memberIds): array
    {
        $totalShares = array_sum($shares);
        if ($totalShares <= 0) {
            return $this->equalSplit($totalPaise, $memberIds);
        }

        $splits      = [];
        $accumulated = 0;
        $count       = count($memberIds);

        foreach ($memberIds as $index => $userId) {
            $userShares = (float) ($shares[(string) $userId] ?? 1);
            $amount = ($index < $count - 1)
                ? (int) round($totalPaise * $userShares / $totalShares)
                : $totalPaise - $accumulated;

            $splits[] = [
                'user_id'   => (string) $userId,
                'shares'    => $userShares,
                'amount'    => $amount,
                'is_settled' => false,
            ];
            $accumulated += $amount;
        }

        return $splits;
    }
}
