<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Checkout - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="{{ config('services.paypal.' . config('services.paypal.mode') . '.js_sdk_url') }}?client-id={{ config('services.paypal.client_id') }}&currency=USD&intent=capture&enable-funding=venmo,paylater,card&disable-funding=credit"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md mx-auto">
            <div class="bg-white shadow-lg rounded-lg p-8">
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Complete Your Payment</h1>
                    <p class="mt-2 text-gray-600">Secure payment with PayPal or Venmo</p>
                </div>

                <!-- Order Summary -->
                <div class="bg-gray-50 rounded-lg p-6 mb-8">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Order Summary</h2>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Amount:</span>
                            <span class="font-semibold" id="order-amount">$0.00</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Currency:</span>
                            <span class="font-semibold" id="order-currency">USD</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Description:</span>
                            <span class="font-semibold" id="order-description">Payment</span>
                        </div>
                    </div>
                    <div class="border-t border-gray-200 mt-4 pt-4">
                        <div class="flex justify-between text-lg font-bold">
                            <span>Total:</span>
                            <span id="order-total">$0.00</span>
                        </div>
                    </div>
                </div>

                <!-- Payment Methods -->
                <div class="space-y-4">
                    <!-- PayPal Button -->
                    <div id="paypal-button-container" class="w-full"></div>

                    <!-- Venmo Button (will be shown if available) -->
                    <div id="venmo-button-container" class="w-full hidden">
                        <div class="text-center text-sm text-gray-500 mb-2">
                            Or pay with Venmo
                        </div>
                    </div>
                </div>

                <!-- Loading State -->
                <div id="loading" class="hidden text-center py-4">
                    <div class="inline-flex items-center">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Processing payment...
                    </div>
                </div>

                <!-- Success Message -->
                <div id="success-message" class="hidden bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-green-800">Payment Successful!</h3>
                            <div class="mt-2 text-sm text-green-700">
                                <p>Your payment has been processed successfully.</p>
                                <p class="mt-1">Order ID: <span id="success-order-id"></span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Error Message -->
                <div id="error-message" class="hidden bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Payment Failed</h3>
                            <div class="mt-2 text-sm text-red-700">
                                <p id="error-text">There was an error processing your payment. Please try again.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Back Button -->
                <div class="mt-8 text-center">
                    <button onclick="window.history.back()" class="text-gray-600 hover:text-gray-800 underline">
                        ← Back to previous page
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Configuration
        const config = {
            amount: {{ $amount ?? 10.00 }},
            currency: '{{ $currency ?? "USD" }}',
            description: '{{ $description ?? "Payment" }}',
            returnUrl: '{{ $returnUrl ?? url()->current() }}',
            cancelUrl: '{{ $cancelUrl ?? url()->previous() }}',
            apiBaseUrl: '{{ url("api/v1/paypal") }}',
            csrfToken: '{{ csrf_token() }}'
        };

        // Update order summary
        document.getElementById('order-amount').textContent = '$' + config.amount.toFixed(2);
        document.getElementById('order-currency').textContent = config.currency;
        document.getElementById('order-description').textContent = config.description;
        document.getElementById('order-total').textContent = '$' + config.amount.toFixed(2);

        // PayPal SDK configuration
        paypal.Buttons({
            style: {
                layout: 'vertical',
                color: 'blue',
                shape: 'rect',
                label: 'paypal',
                height: 50
            },
            fundingSource: paypal.FUNDING.PAYPAL,
            createOrder: function(data, actions) {
                return createPayPalOrder();
            },
            onApprove: function(data, actions) {
                return capturePayPalOrder(data.orderID);
            },
            onError: function(err) {
                showError('PayPal payment failed: ' + err.message);
            },
            onCancel: function(data) {
                showError('Payment was cancelled');
            }
        }).render('#paypal-button-container');

        // Venmo button (if available)
        if (paypal.FUNDING.VENMO) {
            paypal.Buttons({
                style: {
                    layout: 'vertical',
                    color: 'blue',
                    shape: 'rect',
                    label: 'venmo',
                    height: 50
                },
                fundingSource: paypal.FUNDING.VENMO,
                createOrder: function(data, actions) {
                    return createPayPalOrder();
                },
                onApprove: function(data, actions) {
                    return capturePayPalOrder(data.orderID);
                },
                onError: function(err) {
                    showError('Venmo payment failed: ' + err.message);
                },
                onCancel: function(data) {
                    showError('Payment was cancelled');
                }
            }).render('#venmo-button-container');

            document.getElementById('venmo-button-container').classList.remove('hidden');
        }

        // Create PayPal order
        async function createPayPalOrder() {
            try {
                showLoading(true);
                hideMessages();

                const response = await fetch(`${config.apiBaseUrl}/orders`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken,
                        'Authorization': 'Bearer ' + getAuthToken()
                    },
                    body: JSON.stringify({
                        amount: config.amount,
                        currency: config.currency,
                        description: config.description,
                        return_url: config.returnUrl,
                        cancel_url: config.cancelUrl
                    })
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.error || 'Failed to create order');
                }

                return data.order_id;
            } catch (error) {
                showError('Failed to create payment order: ' + error.message);
                throw error;
            } finally {
                showLoading(false);
            }
        }

        // Capture PayPal order
        async function capturePayPalOrder(orderId) {
            try {
                showLoading(true);
                hideMessages();

                const response = await fetch(`${config.apiBaseUrl}/orders/${orderId}/capture`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken,
                        'Authorization': 'Bearer ' + getAuthToken()
                    }
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.error || 'Failed to capture payment');
                }

                showSuccess(data.order_id);
                return data;
            } catch (error) {
                showError('Payment capture failed: ' + error.message);
                throw error;
            } finally {
                showLoading(false);
            }
        }

        // Get authentication token (you may need to adjust this based on your auth setup)
        function getAuthToken() {
            // For demo purposes, we'll try to get from localStorage
            // In production, you should get this from your auth system
            return localStorage.getItem('auth_token') || 'demo-token';
        }

        // UI helper functions
        function showLoading(show) {
            const loading = document.getElementById('loading');
            if (show) {
                loading.classList.remove('hidden');
            } else {
                loading.classList.add('hidden');
            }
        }

        function showSuccess(orderId) {
            hideMessages();
            document.getElementById('success-order-id').textContent = orderId;
            document.getElementById('success-message').classList.remove('hidden');
        }

        function showError(message) {
            hideMessages();
            document.getElementById('error-text').textContent = message;
            document.getElementById('error-message').classList.remove('hidden');
        }

        function hideMessages() {
            document.getElementById('success-message').classList.add('hidden');
            document.getElementById('error-message').classList.add('hidden');
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            console.log('PayPal checkout initialized');
        });
    </script>
</body>
</html>
