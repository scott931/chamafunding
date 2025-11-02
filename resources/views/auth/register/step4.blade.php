<x-guest-layout>
<div class="min-h-screen bg-gray-50">
	<div class="max-w-3xl mx-auto px-4 py-10">
		<div class="mb-6">
			<div class="w-full bg-gray-200 rounded-full h-2">
				<div class="bg-indigo-600 h-2 rounded-full" style="width: 100%;"></div>
			</div>
			<p class="text-sm text-gray-600 mt-2">Step 3 of 3 — Payment Methods (Optional)</p>
		</div>

	<div class="bg-white rounded shadow p-6">
		<div class="mb-4">
			<h2 class="text-lg font-semibold text-gray-900 mb-2">Add Payment Methods</h2>
			<p class="text-sm text-gray-600">Connect your PayPal account to enable both PayPal and Venmo payments. You can skip this step and add payment methods later.</p>
		</div>

		<form method="POST" action="{{ route('register.step4.post') }}" id="payment-form" novalidate>
			@csrf
			<input type="hidden" name="paypal_account_id" id="paypal_account_id" />
			<input type="hidden" name="paypal_email" id="paypal_email" />
			<input type="hidden" name="skip_payment_methods" id="skip_payment_methods" value="0" />

			<!-- PayPal Connection Section -->
			<div id="paypal-section" class="border rounded-lg p-4 mb-4">
				<div class="flex items-start justify-between mb-3">
					<div class="flex-1">
						<h3 class="font-medium text-gray-900 mb-1">PayPal & Venmo</h3>
						<p class="text-xs text-gray-500">Connect your PayPal account to enable both PayPal and Venmo payments</p>
					</div>
					<div id="paypal-status" class="text-sm text-gray-500">Not connected</div>
				</div>
				<div id="paypal-button-container" class="mb-3"></div>
				<div id="venmo-button-container" class="mb-2 hidden"></div>
				<div id="paypal-success" class="hidden bg-green-50 border border-green-200 rounded p-3 text-sm text-green-800">
					<span class="font-medium">✓ Connected!</span> Your PayPal account has been linked.
				</div>
			</div>

			<div class="mt-6 flex items-center justify-between">
				<a href="{{ route('register.step3') }}" class="text-gray-600">Back</a>
				<div class="flex items-center gap-3">
					<button type="button" id="skip-btn" class="text-gray-600 px-4 py-2 rounded border border-gray-300 hover:bg-gray-50">Skip for Now</button>
					<button type="submit" id="submit-btn" class="bg-green-600 text-white px-5 py-2 rounded hover:bg-green-700">Complete Registration</button>
				</div>
			</div>
		</form>
	</div>

	<div class="text-center text-xs text-gray-500 mt-6">
		SSL secured • Your payment information is encrypted
	</div>
	</div>
</div>

@php
	// Get PayPal client ID from config with fallbacks
	$paypalClientId = config('services.paypal.client_id');
	if (empty($paypalClientId)) {
		$paypalClientId = env('PAYPAL_CLIENT_ID');
	}
	if (empty($paypalClientId)) {
		// Fallback to test credentials (for development only)
		$paypalClientId = 'AT16jl6nE2hAKGojRWT8_NsI7iVHl79Q_A7nNkysNVC_M2X0AYHbE_YKD7_YLcXs9X1BkMm7nXo2nEwt';
	}
	$paypalMode = config('services.paypal.mode', 'sandbox');
	$paypalCurrency = 'USD'; // Default currency
@endphp

<!-- PayPal SDK -->
<script src="https://www.paypal.com/sdk/js?client-id={{ $paypalClientId }}&currency={{ $paypalCurrency }}&intent=authorize&vault=true&enable-funding=venmo,paypal"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
	const paypalButtonContainer = document.getElementById('paypal-button-container');
	const paypalStatus = document.getElementById('paypal-status');
	const paypalSuccess = document.getElementById('paypal-success');
	const paypalAccountIdInput = document.getElementById('paypal_account_id');
	const paypalEmailInput = document.getElementById('paypal_email');
	const skipPaymentMethodsInput = document.getElementById('skip_payment_methods');
	const submitBtn = document.getElementById('submit-btn');
	const skipBtn = document.getElementById('skip-btn');
	const paymentForm = document.getElementById('payment-form');

	let paypalAccountConnected = false;

	// Initialize PayPal button for account connection
	if (typeof paypal !== 'undefined') {
		paypal.Buttons({
			style: {
				layout: 'vertical',
				color: 'blue',
				shape: 'rect',
				label: 'paypal',
				height: 40
			},
			createBillingAgreement: function(data, actions) {
				return actions.billingAgreement.create({
					description: 'Connect PayPal account for payments'
				});
			},
			onApprove: function(data, actions) {
				// Get billing agreement details
				return actions.billingAgreement.get().then(function(details) {
					// Extract account information
					const payerInfo = details.payer.payer_info;
					const billingAgreementId = details.id;

					// Store the billing agreement ID and email
					paypalAccountIdInput.value = billingAgreementId;
					paypalEmailInput.value = payerInfo.email || '';

					// Update UI
					paypalAccountConnected = true;
					paypalStatus.textContent = 'Connected';
					paypalStatus.className = 'text-sm text-green-600 font-medium';
					paypalSuccess.classList.remove('hidden');
					paypalButtonContainer.innerHTML = '';
					const venmoContainer = document.getElementById('venmo-button-container');
					if (venmoContainer) {
						venmoContainer.innerHTML = '';
						venmoContainer.classList.add('hidden');
					}

					console.log('PayPal account connected:', {
						billingAgreementId: billingAgreementId,
						email: payerInfo.email
					});
				});
			},
			onError: function(err) {
				console.error('PayPal connection error:', err);
				alert('Failed to connect PayPal account. Please try again.');
			},
			onCancel: function(data) {
				console.log('PayPal connection cancelled');
			}
		}).render('#paypal-button-container').catch(function(err) {
			console.error('PayPal button render failed:', err);
			paypalButtonContainer.innerHTML = '<p class="text-sm text-gray-500">PayPal connection not available. You can skip this step.</p>';
		});

		// Venmo button (if available)
		if (typeof paypal.FUNDING !== 'undefined' && paypal.FUNDING.VENMO) {
			const venmoContainer = document.getElementById('venmo-button-container');
			paypal.Buttons({
				fundingSource: paypal.FUNDING.VENMO,
				style: {
					layout: 'vertical',
					color: 'blue',
					shape: 'rect',
					label: 'venmo',
					height: 50
				},
				createBillingAgreement: function(data, actions) {
					return actions.billingAgreement.create({
						description: 'Connect Venmo account for payments'
					});
				},
				onApprove: function(data, actions) {
					// Get billing agreement details
					return actions.billingAgreement.get().then(function(details) {
						// Extract account information
						const payerInfo = details.payer.payer_info;
						const billingAgreementId = details.id;

						// Store the billing agreement ID and email
						paypalAccountIdInput.value = billingAgreementId;
						paypalEmailInput.value = payerInfo.email || '';

						// Update UI
						paypalAccountConnected = true;
						paypalStatus.textContent = 'Connected';
						paypalStatus.className = 'text-sm text-green-600 font-medium';
						paypalSuccess.classList.remove('hidden');
						paypalButtonContainer.innerHTML = '';
						if (venmoContainer) {
							venmoContainer.innerHTML = '';
							venmoContainer.classList.add('hidden');
						}

						console.log('Venmo account connected:', {
							billingAgreementId: billingAgreementId,
							email: payerInfo.email
						});
					});
				},
				onError: function(err) {
					console.error('Venmo connection error:', err);
					alert('Failed to connect Venmo account. Please try again.');
				},
				onCancel: function(data) {
					console.log('Venmo connection cancelled');
				}
			}).render('#venmo-button-container').then(function() {
				if (venmoContainer) {
					venmoContainer.classList.remove('hidden');
				}
			}).catch(function(err) {
				console.log('Venmo not available:', err);
				// Venmo may not be available in all regions/devices, so we just hide it
				if (venmoContainer) {
					venmoContainer.classList.add('hidden');
				}
			});
		}
	} else {
		console.error('PayPal SDK failed to load');
		paypalButtonContainer.innerHTML = '<p class="text-sm text-gray-500">PayPal connection not available. You can skip this step.</p>';
	}

	// Skip button handler
	skipBtn.addEventListener('click', function() {
		skipPaymentMethodsInput.value = '1';
		paymentForm.submit();
	});

	// Allow form submission even without PayPal connection (payment methods are optional)
	// If PayPal is connected, it will be saved; if not, user can add payment methods later
	});
</script>
</x-guest-layout>
