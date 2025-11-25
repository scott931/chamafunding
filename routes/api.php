<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cookie;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\AdminFinancialController;
use App\Http\Controllers\Api\AdminReportsController;
use App\Http\Controllers\Api\AdminSettingsController;
use App\Http\Controllers\Api\AdminNotificationsController;
use App\Http\Controllers\Api\ActivityLogController;
use Modules\Payments\Http\Controllers\PayPalController;

// Sanctum CSRF cookie route (for SPA authentication)
// This route sets the CSRF cookie needed for authenticated requests
Route::get('/sanctum/csrf-cookie', function (Request $request) {
    // Ensure session is started
    $request->session()->start();
    
    // Get the CSRF token from the session
    $token = $request->session()->token();
    
    // Set the XSRF-TOKEN cookie (must not be httpOnly so JavaScript can read it)
    // For local development, we use sameSite: 'lax' to allow cross-origin requests
    $cookie = Cookie::make(
        'XSRF-TOKEN',
        $token,
        config('session.lifetime', 120), // minutes
        '/',
        null, // domain - null uses default
        false, // secure - set to true in production with HTTPS
        false, // httpOnly - must be false so JavaScript can read it
        false, // raw
        'lax'  // sameSite - 'lax' allows same-site and top-level navigation requests
    );
    
    return response()->json([
        'message' => 'CSRF cookie set',
    ])->cookie($cookie);
})->middleware(['web', 'throttle:60,1']);

Route::prefix('v1')->group(function () {
    // Health check endpoint
    Route::get('/health', [HealthController::class, 'index']);

    // Auth routes (using web middleware for session-based auth with Sanctum)
    Route::middleware('web')->group(function () {
        Route::post('/auth/login', [AuthController::class, 'login']);
        Route::post('/auth/register', [AuthController::class, 'register']);
        Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
        Route::get('/auth/user', [AuthController::class, 'user'])->middleware('auth:sanctum');
        
        // Activity logs routes
        Route::middleware('auth:sanctum')->prefix('activity-logs')->group(function () {
            Route::get('/', [ActivityLogController::class, 'index']);
            Route::get('/statistics', [ActivityLogController::class, 'statistics']);
            Route::get('/{id}', [ActivityLogController::class, 'show']);
        });
    });

    // Future app-level API endpoints can be added here
    // Route::middleware(['auth:sanctum'])->group(function () {
    //     Route::get('/profile', [ProfileController::class, 'show']);
    //     Route::put('/profile', [ProfileController::class, 'update']);
    // });
});

Route::prefix('v1')->middleware('web')->group(function () {
    Route::get('/campaigns', [CampaignController::class, 'index']);
    Route::get('/campaigns/{campaign}', [CampaignController::class, 'show']);
    
    // Public PayPal client ID endpoint
    Route::get('/paypal/client-id', [AdminSettingsController::class, 'paypalClientId']);

    Route::middleware('auth')->group(function () {
        Route::post('/campaigns', [CampaignController::class, 'store']);
        Route::put('/campaigns/{campaign}', [CampaignController::class, 'update']);
        Route::delete('/campaigns/{campaign}', [CampaignController::class, 'destroy']);
        Route::post('/campaigns/{campaign}/activate', [CampaignController::class, 'activate']);
        Route::post('/campaigns/{campaign}/contribute', [CampaignController::class, 'contribute']);
    });
    
    // PayPal routes
    Route::middleware('auth')->prefix('paypal')->group(function () {
        Route::get('/test', [PayPalController::class, 'testConnection']);
        Route::post('/order', [PayPalController::class, 'createOrder']);
        Route::post('/capture', [PayPalController::class, 'captureOrder']);
    });
});

Route::prefix('v1/admin')->middleware(['web', 'auth'])->group(function () {
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::get('/users/{user}', [AdminUserController::class, 'show']);
    Route::post('/users/{user}/role', [AdminUserController::class, 'updateRole']);
    Route::post('/users/{user}/approval', [AdminUserController::class, 'updateApproval']);
    Route::get('/financial/overview', [AdminFinancialController::class, 'overview']);
    Route::get('/transactions', [AdminFinancialController::class, 'transactions']);
    
    // Reports routes
    Route::get('/reports-available', [AdminReportsController::class, 'available']);
    Route::get('/reports/platform-overview', [AdminReportsController::class, 'platformOverview']);
    Route::get('/reports/all-projects', [AdminReportsController::class, 'allProjects']);
    Route::get('/reports/financial-summary', [AdminReportsController::class, 'financialSummary']);
    Route::get('/reports/backer-report', [AdminReportsController::class, 'backerReport']);
    Route::get('/reports/user-management', [AdminReportsController::class, 'userManagement']);
    Route::get('/reports/support-moderation', [AdminReportsController::class, 'supportModeration']);
    
    // Settings routes
    Route::get('/settings/categories', [AdminSettingsController::class, 'categories']);
    Route::get('/settings/platform', [AdminSettingsController::class, 'platform']);
    Route::post('/settings/platform', [AdminSettingsController::class, 'updatePlatform']);
    Route::get('/settings/campaigns', [AdminSettingsController::class, 'campaigns']);
    Route::post('/settings/campaigns', [AdminSettingsController::class, 'updateCampaigns']);
    Route::get('/settings/users', [AdminSettingsController::class, 'users']);
    Route::post('/settings/users', [AdminSettingsController::class, 'updateUsers']);
    Route::get('/settings/financial', [AdminSettingsController::class, 'financial']);
    Route::post('/settings/financial', [AdminSettingsController::class, 'updateFinancial']);
    
    // Notifications & Support routes
    Route::get('/notifications/transactions', [AdminNotificationsController::class, 'transactions']);
    Route::get('/notifications/support', [AdminNotificationsController::class, 'support']);
    Route::get('/notifications/all', [AdminNotificationsController::class, 'all']);
    Route::post('/notifications/{campaignId}/mark-read', [AdminNotificationsController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-read', [AdminNotificationsController::class, 'markAllAsRead']);
});
