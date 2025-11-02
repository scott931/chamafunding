<?php

use Illuminate\Support\Facades\Route;
use Modules\Admin\App\Http\Controllers\AdminController;

Route::middleware(['web', 'auth:sanctum'])->prefix('v1')->group(function () {
    // Admin Dashboard Statistics
    Route::get('admin/dashboard-stats', [AdminController::class, 'dashboardStats'])->name('admin.dashboard-stats');
    Route::get('admin/payment-history', [AdminController::class, 'paymentHistory'])->name('admin.payment-history');
    Route::get('admin/campaigns-count', [AdminController::class, 'campaignCount'])->name('admin.campaigns-count');
    Route::get('admin/reports-available', [AdminController::class, 'reportsAvailable'])->name('admin.reports-available');
});
