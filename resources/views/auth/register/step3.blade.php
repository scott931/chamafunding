<x-guest-layout>
<div class="min-h-screen bg-gray-50">
	<div class="max-w-3xl mx-auto px-4 py-10">
		<div class="mb-6">
			<div class="w-full bg-gray-200 rounded-full h-2">
				<div class="bg-indigo-600 h-2 rounded-full" style="width: 67%;"></div>
			</div>
			<p class="text-sm text-gray-600 mt-2">Step 2 of 3 — Verification</p>
		</div>

	<div class="bg-white rounded shadow p-6">
		<form method="POST" action="{{ route('register.step3.post') }}" novalidate>
			@csrf
			<div class="space-y-4">
				@if (session('status'))
					<div class="bg-green-100 text-green-800 px-3 py-2 rounded">{{ session('status') }}</div>
				@endif
				{{-- OTP Section - COMMENTED OUT FOR NOW
				<div>
					<label class="block text-sm font-medium text-gray-700">OTP Code (optional for now)</label>
					<input name="otp" value="{{ old('otp') }}" class="mt-1 w-full border rounded px-3 py-2" placeholder="Enter code sent to email/phone" />
					@error('otp')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
				</div>
				--}}
				<p class="text-gray-600">Verification step. You can proceed to add payment methods.</p>
			</div>
			<div class="mt-6 flex items-center justify-between">
				<a href="{{ route('register.step1') }}" class="text-gray-600">Back</a>
				<div class="flex items-center gap-3">
					{{-- OTP Resend - COMMENTED OUT FOR NOW
					<form method="POST" action="{{ route('register.otp.resend') }}">
						@csrf
						<button type="submit" class="text-indigo-600">Resend code</button>
					</form>
					--}}
					<button class="bg-indigo-600 text-white px-5 py-2 rounded">Continue</button>
				</div>
			</div>
		</form>
	</div>

	<div class="text-center text-xs text-gray-500 mt-6">
		SSL secured • GDPR compliant • Your data is secure
	</div>
	</div>
</div>
</x-guest-layout>

