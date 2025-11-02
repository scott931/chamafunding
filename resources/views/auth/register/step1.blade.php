<x-guest-layout>
<div class="min-h-screen bg-gray-50">
	<div class="max-w-3xl mx-auto px-4 py-10">
		<div class="text-center mb-8">
			<h1 class="text-3xl font-bold text-gray-900">Join our Community Savings Platform</h1>
			<p class="text-gray-600 mt-2">Recurring contributions, transparent campaigns, and quality governance.</p>
			<div class="flex items-center justify-center gap-6 text-sm text-gray-500 mt-3">
				<span>🔒 Bank-level encryption</span>
				<span>✅ Registered</span>
				<span>👥 Join 5,000+ members</span>
			</div>
		</div>

	<div class="mb-6">
		<div class="w-full bg-gray-200 rounded-full h-2">
			<div class="bg-indigo-600 h-2 rounded-full" style="width: 33%;"></div>
		</div>
		<p class="text-sm text-gray-600 mt-2">Step 1 of 3 — Basic Information</p>
	</div>

	<div class="bg-white rounded shadow p-6">
		<form method="POST" action="{{ route('register.step1.post') }}" novalidate>
			@csrf
			<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
				<div>
					<label class="block text-sm font-medium text-gray-700">Full Name</label>
					<input autofocus name="name" value="{{ old('name') }}" class="mt-1 w-full border rounded px-3 py-2" required />
					@error('name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
				</div>
				<div>
					<label class="block text-sm font-medium text-gray-700">Email Address</label>
					<input type="email" name="email" value="{{ old('email') }}" class="mt-1 w-full border rounded px-3 py-2" required />
					@error('email')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
				</div>
				<div>
					<label class="block text-sm font-medium text-gray-700">Phone Number</label>
					<input name="phone" value="{{ old('phone') }}" class="mt-1 w-full border rounded px-3 py-2" required />
					@error('phone')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
				</div>
				<div>
					<label class="block text-sm font-medium text-gray-700">Password</label>
					<div class="relative">
						<input id="password" type="password" name="password" class="mt-1 w-full border rounded px-3 py-2 pr-10" required />
						<button type="button" onclick="togglePw('password', this)" class="absolute right-2 top-1/2 -translate-y-1/2 text-sm text-gray-500">Show</button>
					</div>
					<div id="pwStrength" class="text-xs text-gray-500 mt-1">Password strength: <span>—</span></div>
					@error('password')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
				</div>
				<div class="md:col-span-2">
					<label class="block text-sm font-medium text-gray-700">Confirm Password</label>
					<input type="password" name="password_confirmation" class="mt-1 w-full border rounded px-3 py-2" required />
				</div>
			</div>
			<div class="mt-6 space-y-4">
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
				<a href="{{ url('/') }}" class="text-gray-600 text-sm">Explore first</a>
				<button class="bg-indigo-600 text-white px-5 py-2 rounded">Continue</button>
			</div>
		</form>
	</div>

	<div class="text-center text-sm text-gray-600 mt-6">
		Already have an account? <a href="{{ route('login') }}" class="text-indigo-600">Login</a>
	</div>
	<div class="text-center text-xs text-gray-500 mt-2">Takes ~2 minutes</div>

	<div class="text-center text-xs text-gray-500 mt-6">
		SSL secured • GDPR compliant • Your data is secure
	</div>
	<div class="text-center text-xs text-gray-500 mt-1">
		<a href="#">Terms</a> • <a href="#">Privacy</a>
	</div>

	<script>
	function togglePw(id, btn){
		const i = document.getElementById(id); if(!i) return; i.type = i.type === 'password' ? 'text' : 'password';
		btn.textContent = i.type === 'password' ? 'Show' : 'Hide';
	}
	const pw = document.getElementById('password');
	const strength = document.getElementById('pwStrength').querySelector('span');
	if(pw){
		pw.addEventListener('input', () => {
			const v = pw.value || '';
			let s = 0; if(v.length>7) s++; if(/[A-Z]/.test(v)) s++; if(/[0-9]/.test(v)) s++; if(/[^A-Za-z0-9]/.test(v)) s++;
			strength.textContent = ['Very weak','Weak','Fair','Strong','Very strong'][s] || '—';
		});
	}
	</script>

	</div>
</div>
</x-guest-layout>

