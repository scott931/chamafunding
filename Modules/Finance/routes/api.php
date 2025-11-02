<?php

use Illuminate\Support\Facades\Route;
use Modules\Finance\Http\Controllers\FinanceController;

Route::middleware(['web', 'auth:sanctum'])->prefix('v1')->group(function () {
    // Financial reports and analytics
    Route::get('finance/reports', [FinanceController::class, 'reports'])->name('finance.reports');
    Route::get('finance/transaction-history', [FinanceController::class, 'transactionHistory'])->name('finance.transaction-history');
    Route::get('finance/balance', [FinanceController::class, 'balance'])->name('finance.balance');

    // Financial calculations
    Route::post('finance/calculate-fees', [FinanceController::class, 'calculateFees'])->name('finance.calculate-fees');
    Route::post('finance/calculate-interest', [FinanceController::class, 'calculateInterest'])->name('finance.calculate-interest');

    // Payment Information
    Route::get('finance/payment-history', [FinanceController::class, 'paymentHistory'])->name('finance.payment-history');
    Route::get('finance/campaigns-count', [FinanceController::class, 'campaignCount'])->name('finance.campaigns-count');
    Route::get('finance/campaigns/{campaignId}/total-payment', [FinanceController::class, 'campaignTotalPayment'])->name('finance.campaign-total-payment');
});
