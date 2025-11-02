<?php

use Illuminate\Support\Facades\Route;
use Modules\Reports\Http\Controllers\ReportsController;

Route::middleware(['web', 'auth:sanctum'])->prefix('v1')->group(function () {
    // Reports endpoints
    Route::get('reports/campaigns', [ReportsController::class, 'campaignReports'])->name('reports.campaigns');
    Route::get('reports/financial', [ReportsController::class, 'financialReports'])->name('reports.financial');
    Route::get('reports/users', [ReportsController::class, 'userReports'])->name('reports.users');
    Route::get('reports/analytics', [ReportsController::class, 'analytics'])->name('reports.analytics');
    Route::post('reports/export', [ReportsController::class, 'export'])->name('reports.export');

    // Payment Information
    Route::get('reports/payment-history', [ReportsController::class, 'paymentHistory'])->name('reports.payment-history');
    Route::get('reports/campaigns-count', [ReportsController::class, 'campaignCount'])->name('reports.campaigns-count');
    Route::get('reports/campaigns/{campaignId}/total-payment', [ReportsController::class, 'campaignTotalPayment'])->name('reports.campaign-total-payment');
});
