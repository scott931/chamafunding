<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HealthController;

Route::prefix('v1')->group(function () {
    // Health check endpoint
    Route::get('/health', [HealthController::class, 'index']);

    // Future app-level API endpoints can be added here
    // Route::middleware(['auth:sanctum'])->group(function () {
    //     Route::get('/profile', [ProfileController::class, 'show']);
    //     Route::put('/profile', [ProfileController::class, 'update']);
    // });
});
