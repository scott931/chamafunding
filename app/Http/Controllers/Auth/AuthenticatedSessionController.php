<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        // If user is already authenticated and wants to login again (force logout)
        // Allow access to login page but clear the session first
        if (auth()->check() && request()->has('force')) {
            Auth::guard('web')->logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return view('auth.login')->with('status', 'You have been logged out. Please login again.');
        }

        // If user is already authenticated but no force parameter, show login page with info
        // They can still login again which will regenerate their session
        if (auth()->check()) {
            return view('auth.login')->with('info', 'You are already logged in. You can login again to refresh your session, or use ?force=true to logout first.');
        }

        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = auth()->user();

        // Refresh user roles from database to ensure they're up to date
        $user->load('roles');
        $user->refresh();

        // Clear any intended URL first to prevent conflicts
        $request->session()->forget('url.intended');

        // Redirect admin users to admin dashboard (force redirect, ignore intended URL)
        if ($user->isAdmin()) {
            return redirect()->route('admin.index');
        }

        // Redirect regular users (backers/contributors) to backer dashboard
        return redirect()->route('backer.dashboard', absolute: false);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        // Return a response that will clear client-side storage
        $response = redirect('/');

        // Add JavaScript to clear client-side storage
        $response->with('clear_storage', true);

        return $response;
    }
}
