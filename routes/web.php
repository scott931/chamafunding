<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestMail;

// API-only routes - frontend is handled by Next.js
// Health check endpoint (used by Render)
Route::get('/up', function () {
    return response()->json(['status' => 'ok']);
});

// Serve Next.js static files and handle SPA routing
Route::get('/{any}', function () {
    // In production, Next.js will be served via reverse proxy or static files
    // For now, return a simple response indicating API is available
    return response()->json([
        'message' => 'ChamaFunding API',
        'version' => '1.0.0',
        'frontend' => 'Next.js application should be served separately'
    ]);
})->where('any', '^(?!api|up).*');

// Dashboard routes removed - handled by Next.js frontend
// These routes are now API-only

// Profile routes removed - handled by Next.js frontend
// API endpoints for profile are in API routes

// Checkout routes removed - handled by Next.js frontend

// Route to clear all caches including OPCache (works in both local and production)
Route::get('/dev/clear-cache', function () {
    // Allow in local environment or if explicitly enabled in production
    $allowInProduction = env('ALLOW_CACHE_CLEAR_ROUTE', false);
    if (!app()->environment('local') && !$allowInProduction) {
        abort(404);
    }

    $results = [];

    // Clear Laravel caches
    try {
        \Artisan::call('optimize:clear');
        $results[] = '✓ Laravel caches cleared';
    } catch (\Exception $e) {
        $results[] = '✗ Laravel cache clear failed: ' . $e->getMessage();
    }
    
    // Aggressively clear compiled views including modules
    try {
        $viewPath = storage_path('framework/views');
        if (is_dir($viewPath)) {
            $files = glob($viewPath . '/*.php');
            $deleted = 0;
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                    $deleted++;
                }
            }
            $results[] = "✓ Deleted $deleted compiled view files";
        }
    } catch (\Exception $e) {
        $results[] = '⚠ View file deletion: ' . $e->getMessage();
    }

    // Clear OPCache
    if (function_exists('opcache_reset')) {
        if (opcache_reset()) {
            $results[] = '✓ OPCache cleared';
        } else {
            $results[] = '✗ OPCache reset failed';
        }

        // Also invalidate opcache for all files
        if (function_exists('opcache_invalidate')) {
            $files = get_included_files();
            $invalidated = 0;
            foreach ($files as $file) {
                if (opcache_invalidate($file, true)) {
                    $invalidated++;
                }
            }
            $results[] = "✓ Invalidated OPCache for $invalidated files";
        }
    } else {
        $results[] = '⚠ OPCache not available (may need web server restart)';
    }

    // Get OPCache status
    if (function_exists('opcache_get_status')) {
        $status = opcache_get_status();
        if ($status) {
            $results[] = 'OPCache enabled: ' . ($status['opcache_enabled'] ? 'Yes' : 'No');
            $results[] = 'Cached scripts: ' . $status['opcache_statistics']['num_cached_scripts'];
        }
    }

    return response()->json([
        'success' => true,
        'message' => 'Cache clearing completed',
        'results' => $results,
        'timestamp' => now()->toDateTimeString()
    ]);
})->name('dev.clear-cache');

require __DIR__.'/auth.php';
