@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50">
	<div class="max-w-3xl mx-auto px-4 py-10">
		<div class="mb-6">
			<div class="w-full bg-gray-200 rounded-full h-2">
				<div class="bg-indigo-600 h-2 rounded-full" style="width: 66%;"></div>
			</div>
			<p class="text-sm text-gray-600 mt-2">Step 2 of 3 — Membership Details</p>
		</div>

	<div class="bg-white rounded shadow p-6">
		<form method="POST" action="{{ route('register.step2.post') }}" novalidate>
			@csrf
			<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
				<div>
					<label class="block text-sm font-medium text-gray-700">Membership Type</label>
					<input name="membership_type" value="{{ old('membership_type') }}" class="mt-1 w-full border rounded px-3 py-2" placeholder="e.g., Regular, Premium" />
					@error('membership_type')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
				</div>
				<div>
					<label class="block text-sm font-medium text-gray-700">Preferred Contribution Amount</label>
					<input name="preferred_contribution_amount" value="{{ old('preferred_contribution_amount') }}" class="mt-1 w-full border rounded px-3 py-2" placeholder="e.g., 25.00" />
					@error('preferred_contribution_amount')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
				</div>
				<div>
					<label class="block text-sm font-medium text-gray-700">Payment Frequency</label>
					<select name="payment_frequency" class="mt-1 w-full border rounded px-3 py-2">
						<option value="">Select...</option>
						<option value="monthly" @selected(old('payment_frequency')==='monthly')>Monthly</option>
						<option value="weekly" @selected(old('payment_frequency')==='weekly')>Weekly</option>
						<option value="quarterly" @selected(old('payment_frequency')==='quarterly')>Quarterly</option>
					</select>
					@error('payment_frequency')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
				</div>
				<div>
					<label class="block text-sm font-medium text-gray-700">Referral Code (optional)</label>
					<input name="referral_code" value="{{ old('referral_code') }}" class="mt-1 w-full border rounded px-3 py-2" />
					@error('referral_code')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
				</div>
			</div>
			<div class="mt-6 flex items-center justify-between">
				<a href="{{ route('register.step1') }}" class="text-gray-600">Back</a>
				<button class="bg-indigo-600 text-white px-5 py-2 rounded">Continue</button>
			</div>
		</form>
	</div>

	<div class="text-center text-xs text-gray-500 mt-6">
		<a href="#">Terms</a> • <a href="#">Privacy</a>
	</div>
	</div>
</div>
@endsection


