<x-guest-layout>
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-indigo-50 to-purple-50">
	<div class="max-w-lg mx-auto px-4 sm:px-6 py-6 sm:py-8">
		<div class="text-center mb-6 sm:mb-8">
			<h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Join our Community Savings Platform</h1>
			<p class="text-gray-600 mt-2 text-sm sm:text-base">Recurring contributions, transparent campaigns, and quality governance.</p>
			<div class="flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-6 text-xs sm:text-sm text-gray-500 mt-3">
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

	<div class="bg-white rounded-2xl shadow-xl p-5 sm:p-6">
		<form method="POST" action="{{ route('register.step1.post') }}" novalidate>
			@csrf
			<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
				<div>
					<label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
					<input autofocus name="name" value="{{ old('name') }}" class="mt-1 w-full border rounded-lg px-4 py-2.5 text-base focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required />
					@error('name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
				</div>
				<div>
					<label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
					<input type="email" name="email" value="{{ old('email') }}" class="mt-1 w-full border rounded-lg px-4 py-2.5 text-base focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required />
					@error('email')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
				</div>
				<div>
					<label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
					<input name="phone" value="{{ old('phone') }}" class="mt-1 w-full border rounded-lg px-4 py-2.5 text-base focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required />
					@error('phone')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
				</div>
				<div>
					<label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
					<div class="relative">
						<input id="password" type="password" name="password" class="mt-1 w-full border rounded-lg px-4 py-2.5 pr-12 text-base focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required />
						<button type="button" onclick="togglePw('password', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-sm text-gray-500 hover:text-gray-700 py-2 px-2">Show</button>
					</div>
					<div id="pwStrength" class="text-xs text-gray-500 mt-1">Password strength: <span>—</span></div>
					@error('password')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
				</div>
				<div class="md:col-span-2">
					<label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
					<input type="password" name="password_confirmation" class="mt-1 w-full border rounded-lg px-4 py-2.5 text-base focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required />
				</div>
			</div>
			<div class="mt-6 space-y-4">
				<div class="flex items-start gap-2">
					<input id="accept_terms" type="checkbox" name="accept_terms" class="border rounded mt-1 flex-shrink-0" required>
					<label for="accept_terms" class="text-xs sm:text-sm text-gray-700 leading-relaxed">I agree to the <a href="#" class="text-indigo-600 hover:underline">Terms of Service</a>.</label>
				</div>
				@error('accept_terms')<p class="text-xs sm:text-sm text-red-600">{{ $message }}</p>@enderror
				<div class="flex items-start gap-2">
					<input id="accept_privacy" type="checkbox" name="accept_privacy" class="border rounded mt-1 flex-shrink-0" required>
					<label for="accept_privacy" class="text-xs sm:text-sm text-gray-700 leading-relaxed">I agree to the <a href="#" class="text-indigo-600 hover:underline">Privacy Policy</a>.</label>
				</div>
				@error('accept_privacy')<p class="text-xs sm:text-sm text-red-600">{{ $message }}</p>@enderror
			</div>
			<div class="mt-6 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
				<a href="{{ url('/') }}" class="text-gray-600 text-sm text-center sm:text-left py-2">Explore first</a>
				<button class="bg-indigo-600 text-white px-6 py-2.5 rounded-lg hover:bg-indigo-700 transition-colors text-sm sm:text-base w-full sm:w-auto font-medium">Continue</button>
			</div>
		</form>
	</div>

	<div class="text-center text-sm text-gray-600 mt-6 px-4">
		Already have an account? <a href="{{ route('login') }}" class="text-indigo-600 hover:underline">Login</a>
	</div>
	<div class="text-center text-xs text-gray-500 mt-2">Takes ~2 minutes</div>

	<div class="text-center text-xs text-gray-500 mt-6 px-4">
		SSL secured • GDPR compliant • Your data is secure
	</div>
	<div class="text-center text-xs text-gray-500 mt-1 px-4">
		<a href="#" class="hover:underline">Terms</a> • <a href="#" class="hover:underline">Privacy</a>
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

