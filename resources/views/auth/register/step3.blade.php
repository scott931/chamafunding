<x-guest-layout>
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-indigo-50 to-purple-50">
	<div class="max-w-lg mx-auto px-4 sm:px-6 py-6 sm:py-8">
		<div class="mb-6">
			<div class="w-full bg-gray-200 rounded-full h-2">
				<div class="bg-indigo-600 h-2 rounded-full" style="width: 67%;"></div>
			</div>
			<p class="text-sm text-gray-600 mt-2">Step 2 of 3 — Verification</p>
		</div>

	<div class="bg-white rounded-2xl shadow-xl p-5 sm:p-6">
		<form method="POST" action="{{ route('register.step3.post') }}" novalidate>
			@csrf
			<div class="space-y-4">
				@if (session('status'))
					<div class="bg-green-100 text-green-800 px-3 py-2 rounded text-sm sm:text-base">{{ session('status') }}</div>
				@endif
				{{-- OTP Section - COMMENTED OUT FOR NOW
				<div>
					<label class="block text-sm font-medium text-gray-700">OTP Code (optional for now)</label>
					<input name="otp" value="{{ old('otp') }}" class="mt-1 w-full border rounded px-3 py-2" placeholder="Enter code sent to email/phone" />
					@error('otp')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
				</div>
				--}}
				<p class="text-gray-600 text-sm sm:text-base">Verification step. You can proceed to add payment methods.</p>
			</div>
			<div class="mt-6 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
				<a href="{{ route('register.step1') }}" class="text-gray-600 text-center sm:text-left text-sm sm:text-base py-2">Back</a>
				<div class="flex items-center justify-end gap-3">
					{{-- OTP Resend - COMMENTED OUT FOR NOW
					<form method="POST" action="{{ route('register.otp.resend') }}">
						@csrf
						<button type="submit" class="text-indigo-600">Resend code</button>
					</form>
					--}}
					<button class="bg-indigo-600 text-white px-6 py-2.5 rounded-lg hover:bg-indigo-700 transition-colors text-sm sm:text-base w-full sm:w-auto font-medium">Continue</button>
				</div>
			</div>
		</form>
	</div>

	<div class="text-center text-xs text-gray-500 mt-6 px-4">
		SSL secured • GDPR compliant • Your data is secure
	</div>
	</div>
</div>
</x-guest-layout>

