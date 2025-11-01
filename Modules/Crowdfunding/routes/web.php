<?php

use Illuminate\Support\Facades\Route;
use Modules\Crowdfunding\Http\Controllers\CrowdfundingController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('crowdfundings/create', [CrowdfundingController::class, 'create'])->name('crowdfunding.create');
    Route::post('crowdfundings', [CrowdfundingController::class, 'store'])->name('crowdfunding.store');
    Route::resource('crowdfundings', CrowdfundingController::class)->names('crowdfunding')->except(['create', 'store']);
});
