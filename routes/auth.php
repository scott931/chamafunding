<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\RegisterWizardController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    // Redirect legacy page to wizard step 1
    Route::get('register', function () { return redirect()->route('register.step1'); })
        ->name('register');

    // Keep POST /register for API compatibility if needed
    Route::post('register', [RegisteredUserController::class, 'store']);

    // Wizard routes
    Route::get('register/step-1', [RegisterWizardController::class, 'showStep1'])->name('register.step1');
    Route::post('register/step-1', [RegisterWizardController::class, 'postStep1'])->name('register.step1.post');
    Route::get('register/step-2', [RegisterWizardController::class, 'showStep2'])->name('register.step2');
    Route::post('register/step-2', [RegisterWizardController::class, 'postStep2'])->name('register.step2.post');
    Route::get('register/step-3', [RegisterWizardController::class, 'showStep3'])->name('register.step3');
    Route::post('register/step-3', [RegisterWizardController::class, 'postStep3'])->name('register.step3.post');
    Route::post('register/otp/resend', function () {
        $wizard = session('register_wizard');
        if (!$wizard || !isset($wizard['step1']['email'])) {
            return redirect()->route('register.step1');
        }
        $email = $wizard['step1']['email'];
        $otp = (string) random_int(100000, 999999);
        $ttlMinutes = 10;
        session()->put('register_otp', [
            'code' => $otp,
            'expires_at' => now()->addMinutes($ttlMinutes),
            'email' => $email,
        ]);
        try { \Mail::to($email)->send(new \App\Mail\OtpCodeMail($otp, $ttlMinutes)); } catch (\Throwable $e) {}
        return back()->with('status', 'Verification code resent.');
    })->name('register.otp.resend');

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
