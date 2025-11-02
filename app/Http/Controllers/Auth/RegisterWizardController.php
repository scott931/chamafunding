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
use App\Models\PaymentMethod;

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
			'accept_terms' => ['accepted'],
			'accept_privacy' => ['accepted'],
		]);

		$wizard = $request->session()->get('register_wizard', []);
		$wizard['step1'] = [
			'name' => $data['name'],
			'email' => $data['email'],
			'phone' => $data['phone'],
			'password' => Hash::make($data['password']),
			'accept_terms' => $data['accept_terms'] ?? false,
			'accept_privacy' => $data['accept_privacy'] ?? false,
		];
		$request->session()->put('register_wizard', $wizard);

        // Generate and email OTP - COMMENTED OUT FOR NOW
        // $otp = (string) random_int(100000, 999999);
        // $ttlMinutes = 10;
        // $request->session()->put('register_otp', [
        //     'code' => $otp,
        //     'expires_at' => now()->addMinutes($ttlMinutes),
        //     'email' => $data['email'],
        // ]);
        // try {
        //     Mail::to($data['email'])->send(new OtpCodeMail($otp, $ttlMinutes));
        // } catch (\Throwable $e) {
        //     // swallow mail errors for now; user can resend later
        // }

		return redirect()->route('register.step3');
	}

	public function showStep3(): View
	{
		return view('auth.register.step3');
	}

	public function postStep3(Request $request): RedirectResponse
	{
		$wizard = $request->session()->get('register_wizard');
		if (!$wizard || !isset($wizard['step1'])) {
			return redirect()->route('register.step1');
		}

		// Step 3 is now just a pass-through (terms/privacy moved to step1)
		// Store step 3 data and move to step 4
		$wizard['step3'] = [];
		$request->session()->put('register_wizard', $wizard);

		return redirect()->route('register.step4');
	}

	public function showStep4(): View
	{
		return view('auth.register.step4');
	}

	public function postStep4(Request $request): RedirectResponse
	{
		// Payment methods are optional during registration
		$data = $request->validate([
			'paypal_account_id' => ['nullable', 'string', 'max:255'],
			'paypal_email' => ['nullable', 'email', 'max:255'],
			'skip_payment_methods' => ['nullable', 'boolean'],
		]);

		$wizard = $request->session()->get('register_wizard');
		if (!$wizard || !isset($wizard['step1'])) {
			return redirect()->route('register.step1');
		}

		// Store payment method data (even if empty, user can skip)
		$wizard['step4'] = $data;
		$request->session()->put('register_wizard', $wizard);

		// Now create the user
		$step1 = $wizard['step1'];
		// $sessionOtp = $request->session()->get('register_otp'); // COMMENTED OUT FOR NOW

		$user = User::create([
			'name' => $step1['name'],
			'email' => $step1['email'],
			'phone' => $step1['phone'] ?? null,
			'password' => $step1['password'],
			'terms_accepted_at' => ($step1['accept_terms'] ?? false) ? now() : null,
			'privacy_accepted_at' => ($step1['accept_privacy'] ?? false) ? now() : null,
            // 'otp_code' => $sessionOtp['code'] ?? null, // COMMENTED OUT FOR NOW
            // 'otp_expires_at' => $sessionOtp['expires_at'] ?? null, // COMMENTED OUT FOR NOW
		]);

		// Create payment methods if provided
		if (!($data['skip_payment_methods'] ?? false) && !empty($data['paypal_account_id'])) {
			PaymentMethod::create([
				'user_id' => $user->id,
				'type' => 'digital_wallet',
				'provider' => 'paypal',
				'external_id' => $data['paypal_account_id'],
				'brand' => 'PayPal',
				'is_default' => true,
				'is_verified' => true,
				'metadata' => [
					'email' => $data['paypal_email'] ?? null,
					'venmo_enabled' => true, // Venmo is enabled via PayPal
				],
			]);
		}

		event(new Registered($user));
		Auth::login($user);

		$request->session()->forget('register_wizard');
        $request->session()->forget('register_otp');

		// default Member role applied in RegisteredUserController, apply here too
		try { $user->assignRole('Member'); } catch (\Throwable $e) {}

		// Redirect regular users to backer dashboard
		return redirect()->route('backer.dashboard');
	}
}
