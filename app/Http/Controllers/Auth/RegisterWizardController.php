<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use App\Mail\OtpCodeMail;

class RegisterWizardController extends Controller
{
	public function showStep1(): View
	{
		return view('auth.register.step1');
	}

	public function postStep1(Request $request): RedirectResponse
	{
		$data = $request->validate([
			'name' => ['required', 'string', 'max:255'],
			'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
			'phone' => ['required', 'string', 'max:30'],
			'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
		]);

		$wizard = $request->session()->get('register_wizard', []);
		$wizard['step1'] = [
			'name' => $data['name'],
			'email' => $data['email'],
			'phone' => $data['phone'],
			'password' => Hash::make($data['password']),
		];
		$request->session()->put('register_wizard', $wizard);

        // Generate and email OTP
        $otp = (string) random_int(100000, 999999);
        $ttlMinutes = 10;
        $request->session()->put('register_otp', [
            'code' => $otp,
            'expires_at' => now()->addMinutes($ttlMinutes),
            'email' => $data['email'],
        ]);
        try {
            Mail::to($data['email'])->send(new OtpCodeMail($otp, $ttlMinutes));
        } catch (\Throwable $e) {
            // swallow mail errors for now; user can resend later
        }

		return redirect()->route('register.step2');
	}

	public function showStep2(): View
	{
		return view('auth.register.step2');
	}

	public function postStep2(Request $request): RedirectResponse
	{
		$data = $request->validate([
			'membership_type' => ['nullable', 'string', 'max:100'],
			'preferred_contribution_amount' => ['nullable', 'numeric', 'min:0'],
			'payment_frequency' => ['nullable', Rule::in(['monthly','weekly','quarterly'])],
			'referral_code' => ['nullable', 'string', 'max:50'],
		]);

		$wizard = $request->session()->get('register_wizard', []);
		$wizard['step2'] = $data;
		$request->session()->put('register_wizard', $wizard);

		return redirect()->route('register.step3');
	}

	public function showStep3(): View
	{
		return view('auth.register.step3');
	}

	public function postStep3(Request $request): RedirectResponse
	{
		$data = $request->validate([
			'accept_terms' => ['accepted'],
			'accept_privacy' => ['accepted'],
			'otp' => ['nullable', 'string', 'max:10'],
		]);

		$wizard = $request->session()->get('register_wizard');
		if (!$wizard || !isset($wizard['step1'])) {
			return redirect()->route('register.step1');
		}

        // Validate OTP
        $sessionOtp = $request->session()->get('register_otp');
        if (!$sessionOtp || $sessionOtp['email'] !== ($wizard['step1']['email'] ?? null)) {
            return back()->withErrors(['otp' => 'Verification code not found. Please resend.'])->withInput();
        }
        if (now()->greaterThan($sessionOtp['expires_at'])) {
            return back()->withErrors(['otp' => 'Verification code expired. Please resend.'])->withInput();
        }
        if (($data['otp'] ?? '') !== ($sessionOtp['code'] ?? '')) {
            return back()->withErrors(['otp' => 'Invalid verification code.'])->withInput();
        }

		$step1 = $wizard['step1'];
		$step2 = $wizard['step2'] ?? [];

		$user = User::create([
			'name' => $step1['name'],
			'email' => $step1['email'],
			'phone' => $step1['phone'] ?? null,
			'password' => $step1['password'],
			'membership_type' => $step2['membership_type'] ?? null,
			'preferred_contribution_amount' => isset($step2['preferred_contribution_amount']) ? (int) round(((float) $step2['preferred_contribution_amount']) * 100) : null,
			'payment_frequency' => $step2['payment_frequency'] ?? null,
			'referral_code' => $step2['referral_code'] ?? null,
			'terms_accepted_at' => now(),
			'privacy_accepted_at' => now(),
            'otp_code' => $sessionOtp['code'] ?? null,
            'otp_expires_at' => $sessionOtp['expires_at'] ?? null,
		]);

		event(new Registered($user));
		Auth::login($user);

		$request->session()->forget('register_wizard');
        $request->session()->forget('register_otp');

		// default Member role applied in RegisteredUserController, apply here too
		try { $user->assignRole('Member'); } catch (\Throwable $e) {}

		return redirect()->route('dashboard');
	}
}
