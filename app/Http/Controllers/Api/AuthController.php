<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Handle an incoming login request.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = auth()->user();
        $user->load('roles');

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $user,
            ],
        ]);
    }

    /**
     * Handle an incoming registration request.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'approval_status' => 'pending',
            'is_approved' => false,
        ]);

        // Assign default role
        try {
            $user->assignRole('Member');
        } catch (\Throwable $e) {
            // Role seeder may not have run yet; ignore silently
        }

        event(new \Illuminate\Auth\Events\Registered($user));

        Auth::login($user);
        $user->load('roles');

        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
            'data' => [
                'user' => $user,
            ],
        ], 201);
    }

    /**
     * Get the authenticated user.
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $user->load('roles');

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
            ],
        ]);
    }

    /**
     * Handle logout request.
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Clear all cookies related to authentication
        $response = response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);

        // Set aggressive cache prevention headers
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        $response->headers->set('X-Accel-Expires', '0');
        
        // Clear session cookie by setting it to expire immediately
        $sessionCookieName = config('session.cookie', 'laravel_session');
        $response->headers->clearCookie(
            $sessionCookieName,
            config('session.path', '/'),
            config('session.domain'),
            config('session.secure', false),
            config('session.http_only', true),
            config('session.same_site', 'lax')
        );
        
        // Clear XSRF-TOKEN cookie
        $response->headers->clearCookie(
            'XSRF-TOKEN',
            '/',
            config('session.domain'),
            config('session.secure', false),
            false, // httpOnly must be false for XSRF-TOKEN
            config('session.same_site', 'lax')
        );

        return $response;
    }
}

