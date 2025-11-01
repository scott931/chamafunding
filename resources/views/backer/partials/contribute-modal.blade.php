<!-- Contribution Modal -->
<div x-show="showContributeModal"
     x-cloak
     class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
     @click.self="showContributeModal = false">
    <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto"
         x-data="contributeModal"
         x-show="selectedCampaign">
        <div class="p-6">
            <!-- Header -->
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h2 class="text-2xl font-bold" x-text="selectedCampaign?.title"></h2>
                    <p class="text-gray-600" x-text="'by ' + selectedCampaign?.creator?.name"></p>
                </div>
                <button @click="showContributeModal = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Reward Tiers -->
            <div class="mb-6" x-show="selectedCampaign?.reward_tiers?.length > 0">
                <h3 class="font-semibold mb-3">Select Reward Tier</h3>
                <div class="space-y-3">
                    <template x-for="tier in selectedCampaign.reward_tiers" :key="tier.id">
                        <label class="flex items-start p-4 border rounded-lg cursor-pointer hover:bg-gray-50"
                               :class="selectedRewardTier === tier.id ? 'border-blue-500 bg-blue-50' : 'border-gray-200'">
                            <input type="radio"
                                   name="reward_tier"
                                   :value="tier.id"
                                   x-model="selectedRewardTier"
                                   class="mt-1">
                            <div class="ml-3 flex-1">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="font-medium" x-text="tier.name"></p>
                                        <p class="text-sm text-gray-600 mt-1" x-text="tier.description"></p>
                                        <p class="text-sm text-gray-500 mt-2" x-show="tier.estimated_delivery">
                                            Est. Delivery: <span x-text="tier.estimated_delivery"></span>
                                        </p>
                                    </div>
                                    <p class="font-bold text-lg ml-4"
                                       x-text="formatCurrency(tier.minimum_amount, selectedCampaign.currency)"></p>
                                </div>
                                <div class="mt-2" x-show="tier.requires_shipping">
                                    <span class="text-xs text-blue-600">Requires Shipping</span>
                                </div>
                            </div>
                        </label>
                    </template>
                </div>
            </div>

            <!-- Custom Amount -->
            <div class="mb-6">
                <label class="block text-sm font-medium mb-2">Contribution Amount</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500"
                          x-text="selectedCampaign?.currency || 'USD'"></span>
                    <input type="number"
                           step="0.01"
                           min="1"
                           x-model="contributionAmount"
                           class="w-full pl-16 pr-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <!-- Payment Method Selection -->
            <div class="mb-6">
                <h3 class="font-semibold mb-3">Payment Method</h3>
                <div class="grid grid-cols-3 gap-3">
                    <button @click="selectedPaymentMethod = 'stripe'"
                            :class="selectedPaymentMethod === 'stripe' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'"
                            class="p-3 border rounded-lg hover:bg-gray-50">
                        <p class="font-medium">Card</p>
                        <p class="text-xs text-gray-600">Stripe</p>
                    </button>
                    <button @click="selectedPaymentMethod = 'paypal'"
                            :class="selectedPaymentMethod === 'paypal' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'"
                            class="p-3 border rounded-lg hover:bg-gray-50">
                        <p class="font-medium">PayPal</p>
                        <p class="text-xs text-gray-600">PayPal</p>
                    </button>
                    <button @click="selectedPaymentMethod = 'mpesa'"
                            :class="selectedPaymentMethod === 'mpesa' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'"
                            class="p-3 border rounded-lg hover:bg-gray-50">
                        <p class="font-medium">M-Pesa</p>
                        <p class="text-xs text-gray-600">Mobile Money</p>
                    </button>
                </div>
            </div>

            <!-- M-Pesa Phone Number -->
            <div class="mb-6" x-show="selectedPaymentMethod === 'mpesa'">
                <label class="block text-sm font-medium mb-2">Phone Number</label>
                <input type="tel"
                       x-model="mpesaPhoneNumber"
                       placeholder="254712345678"
                       class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- Shipping Address (if required) -->
            <div class="mb-6" x-show="selectedRewardTier && requiresShipping()">
                <h3 class="font-semibold mb-3">Shipping Address</h3>
                <div class="space-y-3">
                    <input type="text"
                           x-model="shippingData.shipping_name"
                           placeholder="Full Name"
                           class="w-full px-4 py-2 border rounded-lg">
                    <input type="text"
                           x-model="shippingData.shipping_address"
                           placeholder="Street Address"
                           class="w-full px-4 py-2 border rounded-lg">
                    <div class="grid grid-cols-2 gap-3">
                        <input type="text"
                               x-model="shippingData.shipping_city"
                               placeholder="City"
                               class="px-4 py-2 border rounded-lg">
                        <input type="text"
                               x-model="shippingData.shipping_state"
                               placeholder="State"
                               class="px-4 py-2 border rounded-lg">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <input type="text"
                               x-model="shippingData.shipping_country"
                               placeholder="Country"
                               class="px-4 py-2 border rounded-lg">
                        <input type="text"
                               x-model="shippingData.shipping_postal_code"
                               placeholder="Postal Code"
                               class="px-4 py-2 border rounded-lg">
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-3">
                <button @click="showContributeModal = false"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancel
                </button>
                <button @click="processPayment()"
                        :disabled="processing"
                        class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
                    <span x-show="!processing">Complete Payment</span>
                    <span x-show="processing">Processing...</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('contributeModal', () => ({
        selectedRewardTier: null,
        contributionAmount: 0,
        selectedPaymentMethod: 'stripe',
        mpesaPhoneNumber: '',
        shippingData: {
            shipping_name: '',
            shipping_address: '',
            shipping_city: '',
            shipping_state: '',
            shipping_country: '',
            shipping_postal_code: '',
            shipping_phone: ''
        },
        processing: false,

        init() {
            // Set default amount based on selected reward tier
            this.$watch('selectedRewardTier', (tierId) => {
                if (tierId && this.$data.selectedCampaign?.reward_tiers) {
                    const tier = this.$data.selectedCampaign.reward_tiers.find(t => t.id === tierId);
                    if (tier) {
                        this.contributionAmount = parseFloat(tier.minimum_amount) / 100;
                    }
                }
            });
        },

        requiresShipping() {
            if (!this.selectedRewardTier || !this.$data.selectedCampaign?.reward_tiers) return false;
            const tier = this.$data.selectedCampaign.reward_tiers.find(t => t.id === this.selectedRewardTier);
            return tier?.requires_shipping || false;
        },

        formatCurrency(amount, currency = 'USD') {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: currency
            }).format(parseFloat(amount) / 100);
        },

        async processPayment() {
            if (!this.validateInputs()) return;

            this.processing = true;

            try {
                const campaignId = this.$data.selectedCampaign.id;
                const amount = this.contributionAmount;
                const currency = this.$data.selectedCampaign.currency || 'USD';

                let result;

                switch (this.selectedPaymentMethod) {
                    case 'stripe':
                        result = await this.processStripePayment(campaignId, amount, currency);
                        break;
                    case 'paypal':
                        result = await this.processPayPalPayment(campaignId, amount, currency);
                        break;
                    case 'mpesa':
                        result = await this.processMpesaPayment(campaignId, amount, currency);
                        break;
                }

                if (result) {
                    this.$dispatch('payment-success', result);
                    this.$data.showContributeModal = false;
                    // Reload pledges
                    if (this.$data.loadPledges) {
                        this.$data.loadPledges();
                    }
                }

            } catch (error) {
                alert('Payment failed: ' + error.message);
            } finally {
                this.processing = false;
            }
        },

        validateInputs() {
            if (this.contributionAmount < 1) {
                alert('Please enter a valid contribution amount');
                return false;
            }

            if (this.selectedPaymentMethod === 'mpesa' && !this.mpesaPhoneNumber) {
                alert('Please enter your M-Pesa phone number');
                return false;
            }

            if (this.requiresShipping()) {
                if (!this.shippingData.shipping_name || !this.shippingData.shipping_address) {
                    alert('Please fill in all required shipping information');
                    return false;
                }
            }

            return true;
        },

        async processStripePayment(campaignId, amount, currency) {
            // This would integrate with Stripe
            // For now, we'll create the contribution directly
            const response = await fetch(`/api/v1/campaigns/${campaignId}/contribute`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    ...(window.authToken && { 'Authorization': `Bearer ${window.authToken}` })
                },
                body: JSON.stringify({
                    amount,
                    currency,
                    payment_method: 'card',
                    payment_processor: 'stripe',
                    transaction_id: 'stripe_' + Date.now(),
                    reward_tier_id: this.selectedRewardTier,
                    status: 'succeeded',
                    ...this.shippingData
                })
            });

            const data = await response.json();
            if (!response.ok) throw new Error(data.message);
            return data;
        },

        async processPayPalPayment(campaignId, amount, currency) {
            // This would integrate with PayPal
            // Similar to Stripe - create contribution after PayPal capture
            const response = await fetch(`/api/v1/campaigns/${campaignId}/contribute`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    ...(window.authToken && { 'Authorization': `Bearer ${window.authToken}` })
                },
                body: JSON.stringify({
                    amount,
                    currency,
                    payment_method: 'paypal',
                    payment_processor: 'paypal',
                    transaction_id: 'paypal_' + Date.now(),
                    reward_tier_id: this.selectedRewardTier,
                    status: 'succeeded',
                    ...this.shippingData
                })
            });

            const data = await response.json();
            if (!response.ok) throw new Error(data.message);
            return data;
        },

        async processMpesaPayment(campaignId, amount, currency) {
            const response = await fetch('/api/v1/mpesa/initiate-payment', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    ...(window.authToken && { 'Authorization': `Bearer ${window.authToken}` })
                },
                body: JSON.stringify({
                    phone_number: this.mpesaPhoneNumber,
                    amount,
                    campaign_id: campaignId,
                    account_reference: `CAMP-${campaignId}-${Date.now()}`,
                    transaction_description: `Campaign Contribution - Campaign #${campaignId}`
                })
            });

            const data = await response.json();
            if (!response.ok) throw new Error(data.message);

            alert('M-Pesa payment initiated. Please check your phone to complete the payment.');
            return data;
        }
    }));
});
</script>

