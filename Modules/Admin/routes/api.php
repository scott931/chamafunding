<?php

use Illuminate\Support\Facades\Route;
use Modules\Admin\Http\Controllers\AdminController;

Route::middleware(['web', 'auth'])->prefix('v1')->group(function () {
    // Admin Dashboard Statistics
    Route::get('admin/dashboard-stats', [AdminController::class, 'dashboardStats'])->name('admin.dashboard-stats');
    Route::get('admin/payment-history', [AdminController::class, 'paymentHistory'])->name('admin.payment-history');
    Route::get('admin/campaigns-count', [AdminController::class, 'campaignCount'])->name('admin.campaigns-count');
    Route::get('admin/reports-available', [AdminController::class, 'reportsAvailable'])->name('admin.reports-available');
    Route::get('admin/transaction-notifications', [AdminController::class, 'transactionNotifications'])->name('admin.transaction-notifications');
    Route::post('admin/notifications/{campaignId}/mark-read', [AdminController::class, 'markNotificationRead'])->name('admin.notifications.mark-read');
    Route::post('admin/notifications/mark-all-read', [AdminController::class, 'markAllNotificationsRead'])->name('admin.notifications.mark-all-read');
});
