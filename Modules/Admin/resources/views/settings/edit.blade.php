@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
	<h1 class="text-2xl font-semibold mb-4">Platform Settings</h1>

	@if (session('status'))
		<div class="bg-green-100 text-green-800 px-3 py-2 rounded mb-4">{{ session('status') }}</div>
	@endif

	<form method="POST" action="{{ route('admin.settings.update') }}" class="bg-white rounded shadow p-6">
		@csrf
		<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
			<div>
				<label class="block text-sm font-medium text-gray-700">Fee Percentage (%)</label>
				<input name="fee_percentage" type="number" step="0.01" min="0" max="100" value="{{ old('fee_percentage', $settings['fee_percentage']) }}" class="mt-1 w-full border rounded px-3 py-2" />
				@error('fee_percentage')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
			</div>
			<div>
				<label class="block text-sm font-medium text-gray-700">Fixed Fee (cents)</label>
				<input name="fixed_fee_cents" type="number" min="0" value="{{ old('fixed_fee_cents', $settings['fixed_fee_cents']) }}" class="mt-1 w-full border rounded px-3 py-2" />
				@error('fixed_fee_cents')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
			</div>
			<div>
				<label class="block text-sm font-medium text-gray-700">Interest Rate (annual %)</label>
				<input name="interest_rate_annual" type="number" step="0.01" min="0" max="100" value="{{ old('interest_rate_annual', $settings['interest_rate_annual']) }}" class="mt-1 w-full border rounded px-3 py-2" />
				@error('interest_rate_annual')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
			</div>
			<div>
				<label class="block text-sm font-medium text-gray-700">Default Currency (ISO 4217)</label>
				<input name="default_currency" maxlength="3" value="{{ old('default_currency', $settings['default_currency']) }}" class="mt-1 w-full border rounded px-3 py-2 uppercase" />
				@error('default_currency')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
			</div>
			<div class="md:col-span-2">
				<label class="inline-flex items-center gap-2">
					<input type="checkbox" name="maintenance_mode" value="1" @checked(old('maintenance_mode', $settings['maintenance_mode']))>
					<span>Enable maintenance mode</span>
				</label>
			</div>
		</div>
		<div class="mt-6 flex justify-end">
			<button class="bg-indigo-600 text-white px-5 py-2 rounded">Save Settings</button>
		</div>
	</form>
</div>
@endsection


