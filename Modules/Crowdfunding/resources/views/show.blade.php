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
                        <div class="p-6 sm:p-8">
                            <h1 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-5">{{ $campaign->title }}</h1>
                            <div class="prose max-w-none">
                                @php
                                    $description = $campaign->description;
                                    $previewLength = 300;
                                    $isLong = strlen($description) > $previewLength;
                                    $preview = $isLong ? substr($description, 0, $previewLength) . '...' : $description;
                                @endphp
                                <p class="text-slate-700 whitespace-pre-line text-base leading-relaxed" id="campaign-description-preview">{{ $preview }}</p>
                                @if($isLong)
                                    <button
                                        onclick="openCampaignDetailsModal()"
                                        class="mt-5 inline-flex items-center gap-2.5 px-6 py-3 bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 text-white font-bold rounded-xl hover:from-indigo-700 hover:via-purple-700 hover:to-pink-700 shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105 group">
                                        <svg class="w-5 h-5 group-hover:rotate-12 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <span>Read Full Details</span>
                                        <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                @endif
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
                <div class="lg:col-span-1" id="support-section">
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

    <!-- Campaign Details Modal -->
    <div id="campaign-details-modal"
         x-data="campaignDetailsModal()"
         x-show="open"
         @keydown.escape.window="closeModal()"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-md transition-opacity"
             x-show="open"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="closeModal()">
        </div>

        <!-- Modal Panel -->
        <div class="flex min-h-full items-center justify-center p-4 sm:p-6 lg:p-8"
             @click.away="closeModal()">
            <div class="relative w-full max-w-4xl transform overflow-hidden rounded-3xl bg-white shadow-2xl transition-all ring-1 ring-slate-200/60"
                 x-show="open"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

                <!-- Header -->
                <div class="bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600 px-6 sm:px-8 lg:px-10 py-7 sm:py-8 lg:py-10 relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br from-white/10 to-transparent"></div>
                    <div class="relative z-10">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1 pr-6">
                                <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-white mb-3 leading-tight drop-shadow-[0_2px_8px_rgba(0,0,0,0.4)]" style="text-shadow: 0 2px 8px rgba(0,0,0,0.5), 0 0 20px rgba(0,0,0,0.3);">{{ $campaign->title }}</h2>
                                <div class="flex flex-wrap items-center gap-3 sm:gap-4 text-sm text-white/95">
                                    <span class="flex items-center gap-2 bg-white/20 backdrop-blur-sm px-3 py-1.5 rounded-lg">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        <span class="font-medium">{{ $campaign->creator->name }}</span>
                                    </span>
                                    <span class="flex items-center gap-2 bg-white/20 backdrop-blur-sm px-3 py-1.5 rounded-lg">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <span class="font-medium">{{ $campaign->created_at->format('M d, Y') }}</span>
                                    </span>
                                    <span class="px-4 py-1.5 bg-white/30 backdrop-blur-sm rounded-lg text-xs font-bold uppercase tracking-wide">
                                        {{ ucfirst($campaign->status) }}
                                    </span>
                                </div>
                            </div>
                            <button @click="closeModal()"
                                    aria-label="Close modal"
                                    class="flex-shrink-0 p-2.5 text-white/80 hover:text-white hover:bg-white/20 rounded-xl transition-all duration-200 transform hover:scale-110">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="px-6 sm:px-8 lg:px-10 py-6 sm:py-8 lg:py-10 max-h-[65vh] sm:max-h-[70vh] overflow-y-auto custom-scrollbar bg-gradient-to-b from-white to-slate-50/30">
                    <div class="prose prose-slate max-w-none">
                        <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200/60 shadow-sm">
                            @php
                                $description = $campaign->description;
                                // Split by double newlines or numbered sections
                                $paragraphs = preg_split('/(\n\s*\n+|\d+\.\s+[A-Z][^\.]+:)/', $description, -1, PREG_SPLIT_DELIM_CAPTURE);
                                $processed = [];
                                foreach ($paragraphs as $para) {
                                    $trimmed = trim($para);
                                    if (empty($trimmed)) continue;

                                    // Check if it's a section header (numbered with colon)
                                    if (preg_match('/^\d+\.\s+[A-Z][^\.]+:$/', $trimmed)) {
                                        $processed[] = ['type' => 'header', 'content' => $trimmed];
                                    } else {
                                        $processed[] = ['type' => 'paragraph', 'content' => $trimmed];
                                    }
                                }

                                // If no processing worked, just use the original
                                if (empty($processed)) {
                                    $processed = [['type' => 'paragraph', 'content' => $description]];
                                }
                            @endphp

                            <div class="space-y-6">
                                @foreach($processed as $index => $item)
                                    @if($item['type'] === 'header')
                                        <h3 class="text-xl sm:text-2xl font-bold text-slate-900 mt-6 first:mt-0 mb-3 pb-3 border-b-2 border-indigo-200">{{ $item['content'] }}</h3>
                                    @else
                                        <p class="text-slate-700 text-base sm:text-lg leading-relaxed mb-4">{{ $item['content'] }}</p>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Campaign Stats -->
                    <div class="mt-8 pt-8 border-t border-slate-200/60">
                        <h3 class="text-lg sm:text-xl font-bold text-slate-900 mb-6 text-center">Campaign Progress</h3>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 sm:gap-6">
                            <div class="text-center p-4 bg-gradient-to-br from-indigo-50 to-purple-50 rounded-2xl border border-indigo-100/60 hover:shadow-lg transition-all duration-200">
                                <p class="text-2xl sm:text-3xl lg:text-4xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent mb-2">
                                    {{ number_format($campaign->raised_amount / 100, 2) }}
                                </p>
                                <p class="text-xs sm:text-sm text-slate-600 font-semibold uppercase tracking-wide">{{ $campaign->currency }} Raised</p>
                            </div>
                            <div class="text-center p-4 bg-gradient-to-br from-purple-50 to-pink-50 rounded-2xl border border-purple-100/60 hover:shadow-lg transition-all duration-200">
                                <p class="text-2xl sm:text-3xl lg:text-4xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent mb-2">
                                    {{ number_format($campaign->goal_amount / 100, 2) }}
                                </p>
                                <p class="text-xs sm:text-sm text-slate-600 font-semibold uppercase tracking-wide">{{ $campaign->currency }} Goal</p>
                            </div>
                            <div class="text-center p-4 bg-gradient-to-br from-emerald-50 to-teal-50 rounded-2xl border border-emerald-100/60 hover:shadow-lg transition-all duration-200">
                                <p class="text-2xl sm:text-3xl lg:text-4xl font-bold bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent mb-2">
                                    {{ $campaign->contributions_count ?? 0 }}
                                </p>
                                <p class="text-xs sm:text-sm text-slate-600 font-semibold uppercase tracking-wide">Contributors</p>
                            </div>
                            <div class="text-center p-4 bg-gradient-to-br from-amber-50 to-orange-50 rounded-2xl border border-amber-100/60 hover:shadow-lg transition-all duration-200">
                                <p class="text-2xl sm:text-3xl lg:text-4xl font-bold bg-gradient-to-r from-amber-600 to-orange-600 bg-clip-text text-transparent mb-2">
                                    {{ number_format(($campaign->raised_amount / $campaign->goal_amount) * 100, 1) }}%
                                </p>
                                <p class="text-xs sm:text-sm text-slate-600 font-semibold uppercase tracking-wide">Funded</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 sm:px-8 lg:px-10 py-5 sm:py-6 bg-gradient-to-r from-slate-50 to-indigo-50/30 border-t border-slate-200/60 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-3 text-sm text-slate-700 bg-white/60 backdrop-blur-sm px-4 py-2 rounded-xl border border-slate-200/60">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        <span class="font-semibold">{{ ucfirst(str_replace('_', ' ', $campaign->category)) }}</span>
                    </div>
                    @if($campaign->status === 'active')
                        <a href="#support-section"
                           @click="open = false"
                           class="inline-flex items-center gap-2.5 px-6 sm:px-8 py-3 sm:py-3.5 bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 text-white font-bold rounded-xl hover:from-indigo-700 hover:via-purple-700 hover:to-pink-700 shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                            Support This Campaign
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize modal state
        document.addEventListener('alpine:init', () => {
            Alpine.data('campaignDetailsModal', () => ({
                open: false,
                init() {
                    this.$watch('open', value => {
                        if (value) {
                            document.body.style.overflow = 'hidden';
                            // Focus trap for accessibility
                            this.$nextTick(() => {
                                const closeBtn = this.$el.querySelector('button[aria-label="Close"]');
                                if (closeBtn) closeBtn.focus();
                            });
                        } else {
                            document.body.style.overflow = '';
                        }
                    });
                },
                openModal() {
                    this.open = true;
                },
                closeModal() {
                    this.open = false;
                }
            }));
        });

        function openCampaignDetailsModal() {
            // Dispatch event to open modal
            window.dispatchEvent(new CustomEvent('open-campaign-modal'));
        }

        // Listen for the open event
        window.addEventListener('open-campaign-modal', () => {
            const modal = document.querySelector('[x-data*="campaignDetailsModal"]');
            if (modal) {
                // Use Alpine's $dispatch or direct access
                if (modal._x_dataStack && modal._x_dataStack[0]) {
                    modal._x_dataStack[0].open = true;
                } else {
                    // Fallback: trigger Alpine update
                    Alpine.initTree(modal);
                    const data = Alpine.$data(modal);
                    if (data) {
                        data.open = true;
                    }
                }
            }
        });

        // Close modal on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const modal = document.querySelector('[x-data*="campaignDetailsModal"]');
                if (modal) {
                    const data = Alpine.$data(modal);
                    if (data && data.open) {
                        data.closeModal();
                    }
                }
            }
        });
    </script>
</x-app-layout>

