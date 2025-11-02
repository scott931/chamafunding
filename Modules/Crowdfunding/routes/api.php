<?php

use Illuminate\Support\Facades\Route;
use Modules\Crowdfunding\Http\Controllers\CrowdfundingController;
use Modules\Crowdfunding\Http\Controllers\BackerDashboardController;

// Contribution endpoint - supports web session authentication for same-origin requests
Route::middleware(['web', 'auth'])->prefix('v1')->group(function () {
    Route::post('campaigns/{id}/contribute', [CrowdfundingController::class, 'contribute'])->name('campaigns.contribute');

    // Backer Dashboard endpoints - support web session auth
    Route::prefix('backer')->name('backer.')->group(function () {
        Route::get('dashboard', [BackerDashboardController::class, 'dashboard'])->name('dashboard');
        Route::get('dashboard/summary', [BackerDashboardController::class, 'dashboardSummary'])->name('dashboard.summary');

        // Project Discovery
        Route::get('recommendations', [BackerDashboardController::class, 'recommendations'])->name('recommendations');
        Route::get('trending', [BackerDashboardController::class, 'trending'])->name('trending');

        // Pledges
        Route::get('pledges', [BackerDashboardController::class, 'myPledges'])->name('pledges.index');
        Route::get('pledges/{contributionId}', [BackerDashboardController::class, 'pledgeDetails'])->name('pledges.show');
        Route::put('pledges/{contributionId}/shipping', [BackerDashboardController::class, 'updateShippingAddress'])->name('pledges.update-shipping');
        Route::put('pledges/{contributionId}/increase', [BackerDashboardController::class, 'increasePledge'])->name('pledges.increase');
        Route::put('pledges/{contributionId}/change-tier', [BackerDashboardController::class, 'changeRewardTier'])->name('pledges.change-tier');
        Route::post('pledges/{contributionId}/survey', [BackerDashboardController::class, 'completeSurvey'])->name('pledges.survey');

        // Updates
        Route::get('updates', [BackerDashboardController::class, 'updatesFeed'])->name('updates.feed');

        // Transactions
        Route::get('transactions', [BackerDashboardController::class, 'transactionHistory'])->name('transactions.index');
        Route::get('transactions/export', [BackerDashboardController::class, 'exportTransactions'])->name('transactions.export');
        Route::get('transactions/{contributionId}/receipt', [BackerDashboardController::class, 'downloadReceipt'])->name('transactions.receipt');

        // Saved Campaigns
        Route::post('save-campaign', [BackerDashboardController::class, 'saveCampaign'])->name('campaigns.save');
        Route::delete('unsave-campaign/{campaignId}', [BackerDashboardController::class, 'unsaveCampaign'])->name('campaigns.unsave');
        Route::get('saved-campaigns', [BackerDashboardController::class, 'savedCampaigns'])->name('campaigns.saved');

        // Profile & Account
        Route::get('profile', [BackerDashboardController::class, 'getProfile'])->name('profile.get');
        Route::put('profile', [BackerDashboardController::class, 'updateProfile'])->name('profile.update');
        Route::get('privacy-settings', [BackerDashboardController::class, 'getPrivacySettings'])->name('privacy.get');
        Route::put('privacy-settings', [BackerDashboardController::class, 'updatePrivacySettings'])->name('privacy.update');
        Route::put('change-password', [BackerDashboardController::class, 'changePassword'])->name('password.change');

        // Payment Methods
        Route::get('payment-methods', [BackerDashboardController::class, 'getPaymentMethods'])->name('payment-methods.index');

        // Payment Information
        Route::get('payment-history', [BackerDashboardController::class, 'paymentHistory'])->name('payment-history');
        Route::get('campaigns-count', [BackerDashboardController::class, 'campaignCount'])->name('campaigns-count');
        Route::get('campaigns/{campaignId}/total-payment', [BackerDashboardController::class, 'campaignTotalPayment'])->name('campaign-total-payment');
    });
});

// Public routes (no auth required)
Route::prefix('v1')->group(function () {
    Route::get('campaigns', [CrowdfundingController::class, 'index'])->name('campaigns.public.index');
    Route::get('campaigns/{id}', [CrowdfundingController::class, 'show'])->name('campaigns.public.show');
    Route::get('campaigns-search', [CrowdfundingController::class, 'search'])->name('campaigns.public.search');
});
