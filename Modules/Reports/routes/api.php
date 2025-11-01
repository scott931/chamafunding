<?php

use Illuminate\Support\Facades\Route;
use Modules\Reports\Http\Controllers\ReportsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Reports endpoints
    Route::get('reports/campaigns', [ReportsController::class, 'campaignReports'])->name('reports.campaigns');
    Route::get('reports/financial', [ReportsController::class, 'financialReports'])->name('reports.financial');
    Route::get('reports/users', [ReportsController::class, 'userReports'])->name('reports.users');
    Route::get('reports/analytics', [ReportsController::class, 'analytics'])->name('reports.analytics');
    Route::post('reports/export', [ReportsController::class, 'export'])->name('reports.export');
});
