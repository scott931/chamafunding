<?php

use Illuminate\Support\Facades\Route;
use Modules\Payments\Http\Controllers\PaymentsController;
use Modules\Payments\Http\Controllers\PayPalController;
use Modules\Payments\Http\Controllers\StripeController;
use Modules\Payments\Http\Controllers\MpesaController;

// PayPal routes - supports web session authentication for same-origin requests
// Using 'web' middleware to enable sessions and CSRF protection, then 'auth' for authentication
Route::middleware(['web', 'auth'])->prefix('v1/paypal')->group(function () {
    Route::get('/test', [PayPalController::class, 'testConnection'])->name('paypal.test');
    Route::post('/order', [PayPalController::class, 'createOrder'])->name('paypal.order.create');
    Route::post('/capture', [PayPalController::class, 'captureOrder'])->name('paypal.order.capture');
});

Route::middleware(['web', 'auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('payments', PaymentsController::class)->names('payments');

    // Additional payment endpoints
    Route::get('payments/{id}/status', [PaymentsController::class, 'status'])->name('payments.status');
    Route::post('payments/{id}/refund', [PaymentsController::class, 'refund'])->name('payments.refund');
    Route::get('payments-history', [PaymentsController::class, 'history'])->name('payments.history');
    Route::get('payments-summary', [PaymentsController::class, 'summary'])->name('payments.summary');
    Route::get('campaigns-count', [PaymentsController::class, 'campaignCount'])->name('payments.campaigns-count');
    Route::get('campaigns/{campaignId}/total-payment', [PaymentsController::class, 'campaignTotalPayment'])->name('payments.campaign-total-payment');

    // Payment methods
    Route::get('payment-methods', [PaymentsController::class, 'paymentMethods'])->name('payment-methods.index');
    Route::post('payment-methods', [PaymentsController::class, 'addPaymentMethod'])->name('payment-methods.store');
    Route::delete('payment-methods/{id}', [PaymentsController::class, 'removePaymentMethod'])->name('payment-methods.destroy');

    // M-Pesa routes
    Route::prefix('mpesa')->group(function () {
        Route::post('/initiate-payment', [MpesaController::class, 'initiatePayment'])->name('mpesa.initiate-payment');
        Route::post('/query-status', [MpesaController::class, 'queryTransactionStatus'])->name('mpesa.query-status');
        Route::get('/payment-methods', [MpesaController::class, 'getPaymentMethods'])->name('mpesa.payment-methods');
        Route::post('/payment-methods', [MpesaController::class, 'addPaymentMethod'])->name('mpesa.add-payment-method');
        Route::get('/supported-countries', [MpesaController::class, 'getSupportedCountries'])->name('mpesa.supported-countries');
    });
});

// Test routes (no auth required for testing)
Route::get('v1/paypal/test-connection', [PayPalController::class, 'testConnection'])->name('paypal.test-connection');
Route::post('v1/paypal/test-order', [PayPalController::class, 'createOrder'])->name('paypal.test-order');
Route::post('v1/paypal/test-capture', [PayPalController::class, 'captureOrder'])->name('paypal.test-capture');

// Webhooks (no auth required)
Route::post('v1/paypal/webhook', [PayPalController::class, 'webhook'])->name('paypal.webhook');
Route::post('/stripe/webhook', [StripeController::class, 'webhook'])->name('stripe.webhook');
Route::post('/mpesa/webhook', [MpesaController::class, 'webhook'])->name('mpesa.webhook');
