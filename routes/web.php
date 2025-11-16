<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestMail;

Route::get('/', function () {
    // Log that we're entering the root route
    \Log::debug('Root route accessed');
    
    try {
        // Check if user is authenticated (handle database/session errors gracefully)
        $isAuthenticated = false;
        try {
            \Log::debug('Checking authentication...');
            $isAuthenticated = auth()->check();
            \Log::debug('Auth check result: ' . ($isAuthenticated ? 'authenticated' : 'not authenticated'));
        } catch (\Exception $authException) {
            // Log authentication check errors but don't fail
            \Log::error('Auth check failed in root route: ' . $authException->getMessage(), [
                'exception' => $authException,
                'file' => $authException->getFile(),
                'line' => $authException->getLine(),
                'trace' => $authException->getTraceAsString(),
            ]);
            // Also output to stderr directly
            error_log('ROOT ROUTE AUTH ERROR: ' . $authException->getMessage());
            // Continue to login page
        }
        
        if ($isAuthenticated) {
            try {
                \Log::debug('Getting authenticated user...');
                $user = auth()->user();
                if ($user) {
                    \Log::debug('User found: ' . $user->id);
                    // Redirect admin users to admin dashboard
                    try {
                        if ($user->isAdmin()) {
                            \Log::debug('User is admin, redirecting to admin.index');
                            return redirect()->route('admin.index');
                        }
                        // Redirect regular users to backer dashboard
                        \Log::debug('User is not admin, redirecting to backer.dashboard');
                        return redirect()->route('backer.dashboard');
                    } catch (\Exception $redirectException) {
                        \Log::error('Redirect failed: ' . $redirectException->getMessage(), [
                            'exception' => $redirectException,
                        ]);
                        error_log('ROOT ROUTE REDIRECT ERROR: ' . $redirectException->getMessage());
                        // Fall through to login
                    }
                }
            } catch (\Exception $userException) {
                // Log user-related errors but don't fail
                \Log::error('User check failed in root route: ' . $userException->getMessage(), [
                    'exception' => $userException,
                    'file' => $userException->getFile(),
                    'line' => $userException->getLine(),
                    'trace' => $userException->getTraceAsString(),
                ]);
                error_log('ROOT ROUTE USER ERROR: ' . $userException->getMessage());
                // Fall through to login
            }
        }
    } catch (\Throwable $e) {
        // Catch any other unexpected errors
        \Log::error('Unexpected error in root route: ' . $e->getMessage(), [
            'exception' => $e,
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);
        error_log('ROOT ROUTE UNEXPECTED ERROR: ' . $e->getMessage());
        // Fall through to redirect to login
    }
    
    // Always redirect to login if not authenticated or on error
    try {
        \Log::debug('Redirecting to login page');
        return redirect()->route('login');
    } catch (\Exception $routeException) {
        // If even the login route fails, return a simple response
        \Log::error('Login route failed: ' . $routeException->getMessage(), [
            'exception' => $routeException,
            'file' => $routeException->getFile(),
            'line' => $routeException->getLine(),
        ]);
        error_log('ROOT ROUTE LOGIN ROUTE ERROR: ' . $routeException->getMessage());
        return response('Service temporarily unavailable. Please try again later.', 503);
    }
});

Route::get('/dashboard', function () {
    // Redirect admin users to admin dashboard
    if (auth()->user()->isAdmin()) {
        return redirect()->route('admin.index');
    }
    // Redirect regular users to backer dashboard
    return redirect()->route('backer.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/backer/dashboard', function () {
    // Redirect admin users to admin dashboard
    $user = auth()->user();
    if ($user && $user->isAdmin()) {
        return redirect()->route('admin.index');
    }
    return view('backer.dashboard');
})->middleware(['auth', 'verified'])->name('backer.dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    // Protected test mail route (restricted to admin roles)
    Route::get('/admin/test-mail', function () {
        $user = auth()->user();
        if (!$user || ! $user->isAdmin()) {
            abort(403);
        }

        Mail::to($user->email)->send(new TestMail());
        return back()->with('status', 'Test email sent to '.$user->email);
    })->name('admin.test-mail');
});

// PayPal Checkout Routes
Route::get('/checkout', function () {
    return view('payments.checkout');
})->name('checkout');

Route::get('/checkout/success', function () {
    return view('payments::checkout-success', [
        'orderId' => request('order_id'),
        'amount' => request('amount'),
        'currency' => request('currency')
    ]);
})->name('checkout.success');

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
