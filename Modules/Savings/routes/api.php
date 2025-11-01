<?php

use Illuminate\Support\Facades\Route;
use Modules\Savings\Http\Controllers\SavingsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('savings-accounts', SavingsController::class)->names('savings-accounts');

    // Additional savings endpoints
    Route::post('savings-accounts/{id}/deposit', [SavingsController::class, 'deposit'])->name('savings-accounts.deposit');
    Route::post('savings-accounts/{id}/withdraw', [SavingsController::class, 'withdraw'])->name('savings-accounts.withdraw');
    Route::get('savings-accounts/{id}/calculate-interest', [SavingsController::class, 'calculateInterest'])->name('savings-accounts.calculate-interest');
    Route::get('savings-accounts/{id}/history', [SavingsController::class, 'history'])->name('savings-accounts.history');
    Route::post('savings-accounts/{id}/close', [SavingsController::class, 'close'])->name('savings-accounts.close');
    Route::get('savings-goals', [SavingsController::class, 'goals'])->name('savings-goals.index');
});
