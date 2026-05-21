<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\Auth\GoogleController;
use Illuminate\Support\Facades\Route;

// Landing page
Route::get('/', fn() => view('welcome'))->name('home');

// Google OAuth
Route::prefix('auth/google')->group(function () {
    Route::get('redirect', [GoogleController::class, 'redirect'])->name('auth.google');
    Route::get('callback', [GoogleController::class, 'callback'])->name('auth.google.callback');
});

// Group invitation (public via token)
Route::get('/invite/{token}', [GroupController::class, 'acceptInvite'])
    ->middleware(['auth'])
    ->name('groups.invite.accept');

// Razorpay webhook (no auth, signature verified in controller)
Route::post('/webhooks/razorpay', [TransactionController::class, 'webhook'])
    ->name('webhooks.razorpay')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// Authenticated routes
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Groups
    Route::prefix('groups')->name('groups.')->group(function () {
        Route::get('/',              [GroupController::class, 'index'])->name('index');
        Route::get('/create',        [GroupController::class, 'create'])->name('create');
        Route::post('/',             [GroupController::class, 'store'])->name('store');
        Route::get('/{id}',          [GroupController::class, 'show'])->name('show');
        Route::get('/{id}/edit',     [GroupController::class, 'edit'])->name('edit');
        Route::patch('/{id}',        [GroupController::class, 'update'])->name('update');
        Route::delete('/{id}',       [GroupController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/invite',  [GroupController::class, 'inviteStore'])->name('invite');
        Route::post('/{id}/invitations/{inviteId}/resend', [GroupController::class, 'inviteResend'])->name('invitations.resend');
        Route::delete('/{id}/invitations/{inviteId}',      [GroupController::class, 'inviteCancel'])->name('invitations.cancel');
        Route::delete('/{id}/members/{memberId}', [GroupController::class, 'removeMember'])->name('members.remove');
        Route::post('/{id}/archive', [GroupController::class, 'archive'])->name('archive');
        Route::post('/{id}/nudge/{userId}', [GroupController::class, 'nudge'])->name('nudge');
    });

    // Expenses
    Route::prefix('expenses')->name('expenses.')->group(function () {
        Route::get('/',          [ExpenseController::class, 'index'])->name('index');
        Route::get('/export',    [ExpenseController::class, 'export'])->name('export');
        Route::get('/create',    [ExpenseController::class, 'create'])->name('create');
        Route::post('/',         [ExpenseController::class, 'store'])->name('store');
        Route::get('/{id}',      [ExpenseController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [ExpenseController::class, 'edit'])->name('edit');
        Route::patch('/{id}',    [ExpenseController::class, 'update'])->name('update');
        Route::delete('/{id}',   [ExpenseController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/comments', [ExpenseController::class, 'addComment'])->name('comments.store');
        Route::delete('/{id}/comments/{idx}', [ExpenseController::class, 'deleteComment'])->name('comments.destroy');
        Route::patch('/{id}/stop-recurring', [ExpenseController::class, 'stopRecurring'])->name('stop-recurring');
        Route::post('/ocr',      [ExpenseController::class, 'ocr'])->name('ocr');
    });

    // Transactions / Payments
    Route::prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/',             [TransactionController::class, 'index'])->name('index');
        Route::post('/initiate',    [TransactionController::class, 'initiatePayment'])->name('initiate');
        Route::post('/verify',      [TransactionController::class, 'verifyPayment'])->name('verify');
    });

    // Analytics
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', [AnalyticsController::class, 'index'])->name('index');
        Route::get('/group/{groupId}', [AnalyticsController::class, 'groupData'])->name('group');
    });

    // Notifications
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/',              [NotificationController::class, 'index'])->name('index');
        Route::get('/feed',          [NotificationController::class, 'feed'])->name('feed');
        Route::post('/mark-all',     [NotificationController::class, 'markAllRead'])->name('mark-all');
        Route::post('/{id}/read',    [NotificationController::class, 'markRead'])->name('read');
        Route::delete('/{id}',       [NotificationController::class, 'destroy'])->name('destroy');
    });

    // Profile
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/',              [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/',            [ProfileController::class, 'update'])->name('update');
        Route::patch('/theme',       [ProfileController::class, 'updateTheme'])->name('theme');
        Route::patch('/notifications', [ProfileController::class, 'updateNotifications'])->name('notifications');
        Route::delete('/',           [ProfileController::class, 'destroy'])->name('destroy');
    });

    // Admin
    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        Route::get('/',              [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/users',         [AdminController::class, 'users'])->name('users');
        Route::post('/users/{id}/toggle', [AdminController::class, 'toggleUser'])->name('users.toggle');
        Route::post('/users/{id}/admin',  [AdminController::class, 'makeAdmin'])->name('users.admin');
        Route::delete('/users/{id}',      [AdminController::class, 'deleteUser'])->name('users.delete');
        Route::get('/activity-logs', [AdminController::class, 'activityLogs'])->name('activity-logs');
    });
});

require __DIR__.'/auth.php';
