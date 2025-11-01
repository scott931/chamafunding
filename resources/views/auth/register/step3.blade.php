@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50">
	<div class="max-w-3xl mx-auto px-4 py-10">
		<div class="mb-6">
			<div class="w-full bg-gray-200 rounded-full h-2">
				<div class="bg-indigo-600 h-2 rounded-full" style="width: 100%;"></div>
			</div>
			<p class="text-sm text-gray-600 mt-2">Step 3 of 3 — Verification</p>
		</div>

	<div class="bg-white rounded shadow p-6">
		<form method="POST" action="{{ route('register.step3.post') }}" novalidate>
			@csrf
			<div class="space-y-4">
				@if (session('status'))
					<div class="bg-green-100 text-green-800 px-3 py-2 rounded">{{ session('status') }}</div>
				@endif
				<div>
					<label class="block text-sm font-medium text-gray-700">OTP Code (optional for now)</label>
					<input name="otp" value="{{ old('otp') }}" class="mt-1 w-full border rounded px-3 py-2" placeholder="Enter code sent to email/phone" />
					@error('otp')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
				</div>
				<div class="flex items-center gap-2">
					<input id="accept_terms" type="checkbox" name="accept_terms" class="border rounded" required>
					<label for="accept_terms" class="text-sm text-gray-700">I agree to the <a href="#" class="text-indigo-600">Terms of Service</a>.</label>
				</div>
				@error('accept_terms')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
				<div class="flex items-center gap-2">
					<input id="accept_privacy" type="checkbox" name="accept_privacy" class="border rounded" required>
					<label for="accept_privacy" class="text-sm text-gray-700">I agree to the <a href="#" class="text-indigo-600">Privacy Policy</a>.</label>
				</div>
				@error('accept_privacy')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
			</div>
			<div class="mt-6 flex items-center justify-between">
				<a href="{{ route('register.step2') }}" class="text-gray-600">Back</a>
				<div class="flex items-center gap-3">
					<form method="POST" action="{{ route('register.otp.resend') }}">
						@csrf
						<button type="submit" class="text-indigo-600">Resend code</button>
					</form>
					<button class="bg-green-600 text-white px-5 py-2 rounded">Create Account</button>
				</div>
			</div>
		</form>
	</div>

	<div class="text-center text-xs text-gray-500 mt-6">
		SSL secured • GDPR compliant • Your data is secure
	</div>
	</div>
</div>
@endsection


