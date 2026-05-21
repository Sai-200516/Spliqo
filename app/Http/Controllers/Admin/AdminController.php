<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Expense;
use App\Models\Group;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'users'        => User::where('is_active', true)->count(),
            'groups'       => Group::count(),
            'expenses'     => Expense::where('status', 'active')->count(),
            'transactions' => Transaction::where('status', 'completed')->count(),
            'total_volume' => Transaction::where('status', 'completed')->sum('amount'),
        ];

        $recentUsers = User::orderByDesc('created_at')->limit(10)->get();

        return view('admin.dashboard', compact('stats', 'recentUsers'));
    }

    public function users(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $term = preg_quote($request->search, '/');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'regex', "/$term/i")
                  ->orWhere('email', 'regex', "/$term/i");
            });
        }

        $users = $query->orderByDesc('created_at')->paginate(30);

        return view('admin.users', compact('users'));
    }

    public function toggleUser(string $id)
    {
        $user = User::findOrFail($id);
        abort_if((string) $user->_id === (string) auth()->id(), 403, 'Cannot deactivate yourself.');

        $user->update(['is_active' => !$user->is_active]);

        return back()->with('success', 'User status updated.');
    }

    public function makeAdmin(string $id)
    {
        $user = User::findOrFail($id);
        $user->update(['is_admin' => !$user->is_admin]);

        return back()->with('success', 'Admin status updated.');
    }

    public function deleteUser(string $id)
    {
        $user = User::findOrFail($id);
        abort_if((string) $user->_id === (string) auth()->id(), 403, 'Cannot delete yourself.');

        $user->delete();

        return back()->with('success', 'User deleted.');
    }

    public function activityLogs(Request $request)
    {
        $logs = ActivityLog::orderByDesc('created_at')->paginate(50);

        return view('admin.activity-logs', compact('logs'));
    }
}
