<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestMail;

Route::get('/', function () {
    if (auth()->check()) {
        // Redirect admin users to admin dashboard
        if (auth()->user()->isAdmin()) {
            return redirect()->route('admin.index');
        }
        // Redirect regular users to backer dashboard
        return redirect()->route('backer.dashboard');
    }
    return redirect()->route('login');
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

// Development route to clear all caches including OPCache
Route::get('/dev/clear-cache', function () {
    if (!app()->environment('local')) {
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
