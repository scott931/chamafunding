<?php

use Illuminate\Support\Facades\Route;
use Modules\Subscriptions\Http\Controllers\SubscriptionsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('subscriptions', SubscriptionsController::class)->names('subscriptions');

    // Additional subscription endpoints
    Route::post('subscriptions/{id}/cancel', [SubscriptionsController::class, 'cancel'])->name('subscriptions.cancel');
    Route::post('subscriptions/{id}/resume', [SubscriptionsController::class, 'resume'])->name('subscriptions.resume');
    Route::get('subscriptions/{id}/status', [SubscriptionsController::class, 'status'])->name('subscriptions.status');
    Route::get('subscriptions/{id}/billing-history', [SubscriptionsController::class, 'billingHistory'])->name('subscriptions.billing-history');
    Route::get('subscriptions/{id}/download-invoice/{invoiceId}', [SubscriptionsController::class, 'downloadInvoice'])->name('subscriptions.download-invoice');
    Route::put('subscriptions/{id}/payment-method', [SubscriptionsController::class, 'updatePaymentMethod'])->name('subscriptions.update-payment-method');
    Route::get('subscriptions/{id}/upcoming-invoice', [SubscriptionsController::class, 'upcomingInvoice'])->name('subscriptions.upcoming-invoice');
});
