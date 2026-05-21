<?php

namespace App\Http\Controllers;

use App\Models\Balance;
use App\Models\Expense;
use App\Models\Group;
use App\Models\Transaction;
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

        $recentExpenses = Expense::where('status', 'active')
            ->whereIn('group_id', $groups->map(fn($g) => (string) $g->_id)->toArray())
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Total owed to user across all groups
        $totalOwed = Balance::where('to_user_id', $userId)->sum('amount');
        // Total user owes
        $totalOwes = Balance::where('from_user_id', $userId)->sum('amount');

        $recentTransactions = Transaction::where(function ($q) use ($userId) {
            $q->where('from_user_id', $userId)->orWhere('to_user_id', $userId);
        })->orderByDesc('created_at')->limit(5)->get();

        return view('dashboard', compact(
            'groups',
            'recentExpenses',
            'totalOwed',
            'totalOwes',
            'recentTransactions'
        ));
    }
}
