<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\AuthController;

// Sanctum CSRF cookie route (for SPA authentication)
// This route sets the CSRF cookie needed for authenticated requests
Route::get('/sanctum/csrf-cookie', function (Request $request) {
    // Ensure session is started
    $request->session()->start();
    
    // Get the CSRF token from the session
    $token = $request->session()->token();
    
    // Set the XSRF-TOKEN cookie (must not be httpOnly so JavaScript can read it)
    return response()->json([
        'message' => 'CSRF cookie set',
    ])->cookie(
        'XSRF-TOKEN',
        $token,
        config('session.lifetime', 120) * 60, // Convert minutes to seconds
        '/',
        null, // Use default domain
        false, // secure - set to true in production with HTTPS
        false  // httpOnly - must be false so JavaScript can read it
    );
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
    });

    // Future app-level API endpoints can be added here
    // Route::middleware(['auth:sanctum'])->group(function () {
    //     Route::get('/profile', [ProfileController::class, 'show']);
    //     Route::put('/profile', [ProfileController::class, 'update']);
    // });
});
