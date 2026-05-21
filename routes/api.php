<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {

    Route::get('/user', fn(Request $request) => $request->user());

    // Groups
    Route::apiResource('groups', GroupController::class)
        ->except(['create', 'edit'])
        ->names([
            'index'   => 'api.groups.index',
            'store'   => 'api.groups.store',
            'show'    => 'api.groups.show',
            'update'  => 'api.groups.update',
            'destroy' => 'api.groups.destroy',
        ]);

    // Expenses
    Route::apiResource('expenses', ExpenseController::class)
        ->except(['create', 'edit'])
        ->names([
            'index'   => 'api.expenses.index',
            'store'   => 'api.expenses.store',
            'show'    => 'api.expenses.show',
            'update'  => 'api.expenses.update',
            'destroy' => 'api.expenses.destroy',
        ]);
    Route::post('/expenses/ocr', [ExpenseController::class, 'ocr']);

    // Transactions
    Route::get('/transactions',          [TransactionController::class, 'index']);
    Route::post('/transactions/initiate', [TransactionController::class, 'initiatePayment']);
    Route::post('/transactions/verify',   [TransactionController::class, 'verifyPayment']);

    // Analytics
    Route::get('/analytics',                     [AnalyticsController::class, 'index']);
    Route::get('/analytics/group/{groupId}',     [AnalyticsController::class, 'groupData']);

    // Notifications
    Route::get('/notifications',              [NotificationController::class, 'index']);
    Route::post('/notifications/mark-all',    [NotificationController::class, 'markAllRead']);
    Route::post('/notifications/{id}/read',   [NotificationController::class, 'markRead']);
    Route::delete('/notifications/{id}',      [NotificationController::class, 'destroy']);
});
