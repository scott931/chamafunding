<?php

use Illuminate\Support\Facades\Route;
use Modules\UserManagement\Http\Controllers\UserManagementController;

// Using 'web' middleware to enable sessions and CSRF protection, then 'auth' for authentication
// This allows web session authentication for same-origin requests from the admin panel
Route::middleware(['web', 'auth'])->prefix('v1')->group(function () {
    // User management routes
    Route::get('/users', [UserManagementController::class, 'index']);
    Route::get('/users/{id}', [UserManagementController::class, 'show']);

    // Approval actions
    Route::post('/users/{id}/approve', [UserManagementController::class, 'approve']);
    Route::post('/users/{id}/decline', [UserManagementController::class, 'decline']);

    // Campaign assignment
    Route::post('/users/{userId}/assign-campaign', [UserManagementController::class, 'assignToCampaign']);
    Route::post('/users/{userId}/remove-campaign', [UserManagementController::class, 'removeFromCampaign']);

    // Utilities
    Route::get('/campaigns', [UserManagementController::class, 'getCampaigns']);
});
