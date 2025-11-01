<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <a href="{{ route('crowdfunding.index') }}" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <div>
                    <h2 class="font-bold text-2xl text-gray-900">{{ $campaign->title }}</h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Created by {{ $campaign->creator->name }} • {{ $campaign->created_at->diffForHumans() }}
                    </p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Campaign Image/Header -->
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                        <div class="h-64 bg-gradient-to-br from-blue-400 to-indigo-600 relative">
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full text-white font-semibold">
                                    {{ ucfirst($campaign->status) }}
                                </span>
                            </div>
                        </div>
                        <div class="p-6">
                            <h1 class="text-3xl font-bold text-gray-900 mb-4">{{ $campaign->title }}</h1>
                            <div class="prose max-w-none">
                                <p class="text-gray-700 whitespace-pre-line">{{ $campaign->description }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="bg-white rounded-2xl shadow-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <p class="text-sm text-gray-500">Raised</p>
                                <p class="text-2xl font-bold text-blue-600">{{ number_format($campaign->raised_amount / 100, 2) }} {{ $campaign->currency }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-500">Goal</p>
                                <p class="text-2xl font-bold text-gray-900">{{ number_format($campaign->goal_amount / 100, 2) }} {{ $campaign->currency }}</p>
                            </div>
                        </div>
                        <div class="mb-4">
                            <div class="w-full bg-gray-200 rounded-full h-4">
                                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-4 rounded-full transition-all duration-500"
                                     style="width: {{ min(100, ($campaign->raised_amount / $campaign->goal_amount) * 100) }}%"></div>
                            </div>
                            <p class="text-sm text-gray-500 mt-2 text-center">
                                {{ number_format(($campaign->raised_amount / $campaign->goal_amount) * 100, 1) }}% funded
                            </p>
                        </div>
                        <div class="grid grid-cols-3 gap-4 text-center">
                            <div>
                                <p class="text-2xl font-bold text-gray-900">{{ $campaign->contributions_count }}</p>
                                <p class="text-xs text-gray-500">Contributors</p>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-gray-900">{{ number_format($campaign->goal_amount / 100 - $campaign->raised_amount / 100, 2) }}</p>
                                <p class="text-xs text-gray-500">{{ $campaign->currency }} to go</p>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-gray-900">{{ $campaign->deadline ? $campaign->deadline->diffForHumans() : 'No deadline' }}</p>
                                <p class="text-xs text-gray-500">Time left</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar - Payment -->
                <div class="lg:col-span-1">
                    @if($campaign->status === 'active')
                        @auth
                            <div class="bg-white rounded-2xl shadow-lg p-6 sticky top-6">
                                <h3 class="text-xl font-bold text-gray-900 mb-4">Support This Campaign</h3>

                                <!-- Amount Input -->
                                <div class="mb-4">
                                    <label for="contribution_amount" class="block text-sm font-medium text-gray-700 mb-2">
                                        Amount ({{ $campaign->currency }})
                                    </label>
                                    <input type="number"
                                           id="contribution_amount"
                                           min="1"
                                           step="0.01"
                                           value="10.00"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <!-- Reward Tiers (if any) -->
                                @if($campaign->rewardTiers && $campaign->rewardTiers->count() > 0)
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Select Reward Tier (Optional)
                                        </label>
                                        <select id="reward_tier" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                            <option value="">No reward</option>
                                            @foreach($campaign->rewardTiers as $tier)
                                                <option value="{{ $tier->id }}" data-amount="{{ $tier->price / 100 }}">
                                                    {{ $tier->name }} - {{ number_format($tier->price / 100, 2) }} {{ $campaign->currency }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif

                                <!-- PayPal Button -->
                                <div class="mb-4">
                                    <div id="paypal-button-container" class="w-full min-h-[48px] flex items-center justify-center">
                                        <p class="text-sm text-gray-500">Loading payment button...</p>
                                    </div>
                                </div>

                                <!-- Venmo Button (if available) -->
                                <div class="mb-4 hidden" id="venmo-button-container">
                                    <p class="text-xs text-gray-500 mb-2 text-center">Or pay with</p>
                                    <div id="venmo-button-wrapper" class="w-full"></div>
                                </div>

                                <!-- Info -->
                                <div class="text-xs text-gray-500 text-center">
                                    <p>Secure payment via PayPal or Venmo</p>
                                    <p class="mt-1">You'll receive a confirmation email</p>
                                </div>
                            </div>
                        @else
                            <div class="bg-white rounded-2xl shadow-lg p-6 text-center">
                                <h3 class="text-xl font-bold text-gray-900 mb-4">Support This Campaign</h3>
                                <p class="text-gray-600 mb-4">Please log in to make a contribution</p>
                                <a href="{{ route('login') }}"
                                   class="inline-block px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                                    Log In to Contribute
                                </a>
                            </div>
                        @endauth
                    @else
                        <div class="bg-gray-50 rounded-2xl shadow-lg p-6 text-center">
                            <p class="text-gray-600">This campaign is {{ $campaign->status }}</p>
                            <p class="text-sm text-gray-500 mt-2">Contributions are not currently being accepted</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @auth
        @if($campaign->status === 'active')
        @php
            // Get PayPal Client ID with proper fallback
            $paypalClientId = config('services.paypal.client_id');
            if (empty($paypalClientId)) {
                $paypalClientId = env('PAYPAL_CLIENT_ID');
            }
            if (empty($paypalClientId)) {
                // Fallback to hardcoded test client ID
                $paypalClientId = 'AT16jl6nE2hAKGojRWT8_NsI7iVHl79Q_A7nNkysNVC_M2X0AYHbE_YKD7_YLcXs9X1BkMm7nXo2nEwt';
            }
            $paypalMode = config('services.paypal.mode', 'sandbox');
            $paypalCurrency = $campaign->currency ?? 'USD';
        @endphp

        <!-- PayPal SDK with Venmo support -->
        <script
            src="https://www.paypal.com/sdk/js?client-id={{ $paypalClientId }}&currency={{ $paypalCurrency }}&intent=capture&enable-funding=venmo,paypal"
            onerror="console.error('Failed to load PayPal SDK'); document.getElementById('paypal-button-container').innerHTML = '<div class=\"text-red-600 text-sm p-3 bg-red-50 rounded border border-red-200\"><p class=\"font-semibold mb-1\">Script Load Error</p><p class=\"text-xs\">Unable to load PayPal SDK. Please check your internet connection.</p></div>';">
        </script>

        <script>
            (function() {
                let contributionAmount = parseFloat(document.getElementById('contribution_amount').value) || 10.00;
                let selectedRewardTier = null;
                let retryCount = 0;
                const maxRetries = 50; // 5 seconds max wait

                // Verify PayPal script loaded
                const paypalScript = document.querySelector('script[src*="paypal.com/sdk"]');
                if (paypalScript) {
                    paypalScript.addEventListener('load', function() {
                        console.log('PayPal SDK script loaded');
                    });
                    paypalScript.addEventListener('error', function() {
                        console.error('PayPal SDK script failed to load');
                        const container = document.getElementById('paypal-button-container');
                        if (container) {
                            container.innerHTML = '<div class="text-red-600 text-sm p-3 bg-red-50 rounded border border-red-200">' +
                                '<p class="font-semibold mb-1">Script Load Failed</p>' +
                                '<p class="text-xs">Unable to load PayPal SDK. Please check your internet connection and firewall settings.</p>' +
                                '</div>';
                        }
                    });
                }

                // Update amount when input changes
                document.getElementById('contribution_amount').addEventListener('input', function() {
                    contributionAmount = parseFloat(this.value) || 10.00;
                });

                // Update amount when reward tier changes
                const rewardTierSelect = document.getElementById('reward_tier');
                if (rewardTierSelect) {
                    rewardTierSelect.addEventListener('change', function() {
                        selectedRewardTier = this.value;
                        if (this.value && this.options[this.selectedIndex].dataset.amount) {
                            contributionAmount = parseFloat(this.options[this.selectedIndex].dataset.amount);
                            document.getElementById('contribution_amount').value = contributionAmount.toFixed(2);
                        }
                    });
                }

                // Wait for PayPal SDK to load
                function initializePayPal() {
                    const container = document.getElementById('paypal-button-container');

                    if (typeof paypal === 'undefined') {
                        retryCount++;
                        if (retryCount >= maxRetries) {
                            // Timeout - show error message
                            if (container) {
                                container.innerHTML = '<div class="text-red-600 text-sm p-3 bg-red-50 rounded border border-red-200">' +
                                    '<p class="font-semibold mb-1">Unable to load PayPal</p>' +
                                    '<p class="text-xs">The payment button could not be loaded. Please check your internet connection and try refreshing the page.</p>' +
                                    '<button onclick="window.location.reload()" class="mt-2 px-3 py-1 bg-red-600 text-white text-xs rounded hover:bg-red-700">Refresh Page</button>' +
                                    '</div>';
                            }
                            console.error('PayPal SDK failed to load after ' + maxRetries + ' attempts');
                            return;
                        }
                        setTimeout(initializePayPal, 100);
                        return;
                    }

                    // Clear loading message
                    if (container) {
                        container.innerHTML = '';
                    }

                    try {
                        console.log('Initializing PayPal buttons...');

                        // PayPal Button Integration
                        paypal.Buttons({
                            style: {
                                layout: 'vertical',
                                color: 'blue',
                                shape: 'rect',
                                label: 'paypal'
                            },
                            createOrder: function(data, actions) {
                                console.log('Creating PayPal order for amount:', contributionAmount);
                                return fetch('/api/v1/paypal/order', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                        'Accept': 'application/json'
                                    },
                                    credentials: 'same-origin',
                                    body: JSON.stringify({
                                        amount: contributionAmount,
                                        currency: '{{ $paypalCurrency }}',
                                        reference_id: 'CAMPAIGN-{{ $campaign->id }}',
                                        description: 'Contribution to: {{ $campaign->title }}'
                                    })
                                })
                                .then(function(res) {
                                    if (!res.ok) {
                                        return res.json().then(err => {
                                            console.error('Order creation failed:', err);
                                            throw new Error(err.message || 'Failed to create order');
                                        });
                                    }
                                    return res.json();
                                })
                                .then(function(orderData) {
                                    console.log('Order created:', orderData.id);
                                    if (orderData.id) {
                                        return orderData.id;
                                    }
                                    throw new Error('Failed to create PayPal order');
                                });
                            },
                            onApprove: function(data, actions) {
                                console.log('Approving order:', data.orderID);
                                return fetch('/api/v1/paypal/capture', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                        'Accept': 'application/json'
                                    },
                                    credentials: 'same-origin',
                                    body: JSON.stringify({
                                        orderId: data.orderID
                                    })
                                })
                                .then(function(res) {
                                    if (!res.ok) {
                                        return res.json().then(err => {
                                            console.error('Capture failed:', err);
                                            throw new Error(err.message || 'Failed to capture payment');
                                        });
                                    }
                                    return res.json();
                                })
                                .then(function(captureData) {
                                    console.log('Payment captured:', captureData);
                                    // Create contribution after successful payment
                                    const capture = captureData.purchase_units[0].payments.captures[0];

                                    return fetch('/api/v1/campaigns/{{ $campaign->id }}/contribute', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                            'Accept': 'application/json'
                                        },
                                        credentials: 'same-origin',
                                        body: JSON.stringify({
                                            amount: parseFloat(capture.amount.value),
                                            currency: capture.amount.currency_code,
                                            payment_method: 'paypal',
                                            payment_processor: 'paypal',
                                            transaction_id: capture.id,
                                            reward_tier_id: selectedRewardTier || null,
                                            status: 'succeeded'
                                        })
                                    })
                                    .then(function(res) {
                                        if (!res.ok) {
                                            return res.json().then(err => {
                                                console.error('Contribution creation failed:', err);
                                                throw new Error(err.message || 'Failed to create contribution');
                                            });
                                        }
                                        return res.json();
                                    })
                                    .then(function(contributionData) {
                                        console.log('Contribution created:', contributionData);
                                        if (contributionData.success) {
                                            alert('Thank you for your contribution! Your payment has been processed successfully.');
                                            window.location.reload();
                                        } else {
                                            throw new Error(contributionData.message || 'Failed to create contribution');
                                        }
                                    });
                                });
                            },
                            onError: function(err) {
                                console.error('PayPal error:', err);
                                if (container) {
                                    container.innerHTML = '<div class="text-red-600 text-sm p-3 bg-red-50 rounded border border-red-200">' +
                                        '<p class="font-semibold mb-1">Payment Error</p>' +
                                        '<p class="text-xs">' + (err.message || 'An error occurred') + '</p>' +
                                        '</div>';
                                }
                                alert('Payment failed: ' + (err.message || 'Unknown error'));
                            },
                            onCancel: function(data) {
                                console.log('Payment cancelled by user');
                            }
                        }).render('#paypal-button-container');

                        console.log('PayPal buttons rendered successfully');

                        // Render Venmo button if available
                        if (typeof paypal.FUNDING !== 'undefined' && paypal.FUNDING.VENMO) {
                            try {
                                paypal.Buttons({
                                    fundingSource: paypal.FUNDING.VENMO,
                                    style: {
                                        layout: 'vertical',
                                        color: 'blue',
                                        shape: 'rect',
                                        label: 'venmo',
                                        height: 50
                                    },
                                    createOrder: function(data, actions) {
                                        console.log('Creating Venmo order for amount:', contributionAmount);
                                        return fetch('/api/v1/paypal/order', {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                                'Accept': 'application/json'
                                            },
                                            credentials: 'same-origin',
                                            body: JSON.stringify({
                                                amount: contributionAmount,
                                                currency: '{{ $paypalCurrency }}',
                                                reference_id: 'CAMPAIGN-{{ $campaign->id }}',
                                                description: 'Contribution to: {{ $campaign->title }}'
                                            })
                                        })
                                        .then(function(res) {
                                            if (!res.ok) {
                                                return res.json().then(err => {
                                                    console.error('Venmo order creation failed:', err);
                                                    throw new Error(err.message || 'Failed to create order');
                                                });
                                            }
                                            return res.json();
                                        })
                                        .then(function(orderData) {
                                            console.log('Venmo order created:', orderData.id);
                                            if (orderData.id) {
                                                return orderData.id;
                                            }
                                            throw new Error('Failed to create Venmo order');
                                        });
                                    },
                                    onApprove: function(data, actions) {
                                        console.log('Approving Venmo order:', data.orderID);
                                        return fetch('/api/v1/paypal/capture', {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                                'Accept': 'application/json'
                                            },
                                            credentials: 'same-origin',
                                            body: JSON.stringify({
                                                orderId: data.orderID
                                            })
                                        })
                                        .then(function(res) {
                                            if (!res.ok) {
                                                return res.json().then(err => {
                                                    console.error('Venmo capture failed:', err);
                                                    throw new Error(err.message || 'Failed to capture payment');
                                                });
                                            }
                                            return res.json();
                                        })
                                        .then(function(captureData) {
                                            console.log('Venmo payment captured:', captureData);
                                            // Create contribution after successful payment
                                            const capture = captureData.purchase_units[0].payments.captures[0];

                                            return fetch('/api/v1/campaigns/{{ $campaign->id }}/contribute', {
                                                method: 'POST',
                                                headers: {
                                                    'Content-Type': 'application/json',
                                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                                    'Accept': 'application/json'
                                                },
                                                credentials: 'same-origin',
                                                body: JSON.stringify({
                                                    amount: parseFloat(capture.amount.value),
                                                    currency: capture.amount.currency_code,
                                                    payment_method: 'venmo',
                                                    payment_processor: 'paypal',
                                                    transaction_id: capture.id,
                                                    reward_tier_id: selectedRewardTier || null,
                                                    status: 'succeeded'
                                                })
                                            })
                                            .then(function(res) {
                                                if (!res.ok) {
                                                    return res.json().then(err => {
                                                        console.error('Contribution creation failed:', err);
                                                        throw new Error(err.message || 'Failed to create contribution');
                                                    });
                                                }
                                                return res.json();
                                            })
                                            .then(function(contributionData) {
                                                console.log('Contribution created:', contributionData);
                                                if (contributionData.success) {
                                                    alert('Thank you for your contribution via Venmo! Your payment has been processed successfully.');
                                                    window.location.reload();
                                                } else {
                                                    throw new Error(contributionData.message || 'Failed to create contribution');
                                                }
                                            });
                                        });
                                    },
                                    onError: function(err) {
                                        console.error('Venmo error:', err);
                                        if (container) {
                                            container.innerHTML = '<div class="text-red-600 text-sm p-3 bg-red-50 rounded border border-red-200">' +
                                                '<p class="font-semibold mb-1">Venmo Payment Error</p>' +
                                                '<p class="text-xs">' + (err.message || 'An error occurred') + '</p>' +
                                                '</div>';
                                        }
                                        alert('Venmo payment failed: ' + (err.message || 'Unknown error'));
                                    },
                                    onCancel: function(data) {
                                        console.log('Venmo payment cancelled by user');
                                    }
                                }).render('#venmo-button-wrapper')
                                .then(function() {
                                    // Show Venmo button container if button rendered successfully
                                    document.getElementById('venmo-button-container').classList.remove('hidden');
                                    console.log('Venmo button rendered successfully');
                                })
                                .catch(function(err) {
                                    // Venmo not available - hide container
                                    console.log('Venmo not available:', err);
                                    document.getElementById('venmo-button-container').classList.add('hidden');
                                });
                            } catch (error) {
                                console.log('Venmo button initialization failed:', error);
                                document.getElementById('venmo-button-container').classList.add('hidden');
                            }
                        } else {
                            // Venmo not available
                            document.getElementById('venmo-button-container').classList.add('hidden');
                        }
                    } catch (error) {
                        console.error('Error initializing PayPal:', error);
                        if (container) {
                            container.innerHTML = '<div class="text-red-600 text-sm p-3 bg-red-50 rounded border border-red-200">' +
                                '<p class="font-semibold mb-1">Initialization Error</p>' +
                                '<p class="text-xs">' + error.message + '</p>' +
                                '<p class="text-xs mt-2">Please refresh the page and try again.</p>' +
                                '</div>';
                        }
                    }
                }

                // Initialize when DOM is ready
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', function() {
                        console.log('DOM loaded, initializing PayPal...');
                        initializePayPal();
                    });
                } else {
                    console.log('DOM ready, initializing PayPal...');
                    initializePayPal();
                }

                // Also check if script loaded
                window.addEventListener('load', function() {
                    console.log('Page fully loaded');
                    if (typeof paypal === 'undefined' && retryCount < maxRetries) {
                        console.log('PayPal still not loaded, retrying...');
                        initializePayPal();
                    }
                });
            })();
        </script>
        @endif
    @endauth
</x-app-layout>

