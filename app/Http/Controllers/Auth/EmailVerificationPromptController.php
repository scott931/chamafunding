<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationPromptController extends Controller
{
    /**
     * Display the email verification prompt.
     */
    public function __invoke(Request $request): RedirectResponse|View
    {
        if ($request->user()->hasVerifiedEmail()) {
            $user = $request->user();
            $redirectRoute = $user->isAdmin()
                ? route('admin.index', absolute: false)
                : route('backer.dashboard', absolute: false);
            return redirect()->intended($redirectRoute);
        }
        return view('auth.verify-email');
    }
}
