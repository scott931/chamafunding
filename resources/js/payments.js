/**
 * Payment Handlers for Crowdfunding Platform
 * Supports Stripe, PayPal, and M-Pesa
 */

class PaymentHandler {
    constructor(apiBase = '/api/v1', token = null) {
        this.apiBase = apiBase;
        this.token = token || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        this.headers = {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': this.token,
            ...(window.authToken && { 'Authorization': `Bearer ${window.authToken}` })
        };
    }

    async request(url, options = {}) {
        try {
            const response = await fetch(`${this.apiBase}${url}`, {
                ...options,
                headers: { ...this.headers, ...options.headers }
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Request failed');
            }

            return data;
        } catch (error) {
            console.error('Payment request error:', error);
            throw error;
        }
    }
}

class StripeHandler extends PaymentHandler {
    constructor(stripePublicKey, apiBase, token) {
        super(apiBase, token);
        this.stripe = null;
        this.elements = null;
        this.cardElement = null;

        if (typeof Stripe !== 'undefined') {
            this.stripe = Stripe(stripePublicKey);
        }
    }

    async createPaymentIntent(amount, currency, campaignId, rewardTierId = null) {
        return await this.request('/payments/create-intent', {
            method: 'POST',
            body: JSON.stringify({
                amount,
                currency,
                campaign_id: campaignId,
                reward_tier_id: rewardTierId
            })
        });
    }

    async confirmPayment(paymentIntentId, paymentMethodId) {
        return await this.stripe.confirmCardPayment(paymentIntentId, {
            payment_method: paymentMethodId
        });
    }

    setupCardElement(containerId) {
        if (!this.stripe) {
            throw new Error('Stripe not initialized');
        }

        this.elements = this.stripe.elements();
        this.cardElement = this.elements.create('card', {
            style: {
                base: {
                    fontSize: '16px',
                    color: '#424770',
                    '::placeholder': {
                        color: '#aab7c4',
                    },
                },
            },
        });

        this.cardElement.mount(`#${containerId}`);
        return this.cardElement;
    }

    async processPayment(amount, currency, campaignId, rewardTierId = null, shippingData = null) {
        try {
            // Create payment intent
            const intent = await this.createPaymentIntent(amount, currency, campaignId, rewardTierId);

            // Confirm payment with card
            const { paymentIntent, error } = await this.confirmPayment(
                intent.data.client_secret,
                intent.data.payment_method_id
            );

            if (error) {
                throw new Error(error.message);
            }

            // Create contribution
            return await this.createContribution(campaignId, {
                amount,
                currency,
                payment_processor: 'stripe',
                transaction_id: paymentIntent.id,
                reward_tier_id: rewardTierId,
                status: 'succeeded',
                ...shippingData
            });

        } catch (error) {
            console.error('Stripe payment error:', error);
            throw error;
        }
    }

    async createContribution(campaignId, data) {
        return await this.request(`/campaigns/${campaignId}/contribute`, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }
}

class PayPalHandler extends PaymentHandler {
    constructor(apiBase, token) {
        super(apiBase, token);
        this.paypal = null;
    }

    initializePayPalSDK(clientId, currency = 'USD') {
        return new Promise((resolve) => {
            if (window.paypal) {
                this.paypal = window.paypal;
                resolve();
                return;
            }

            const script = document.createElement('script');
            script.src = `https://www.paypal.com/sdk/js?client-id=${clientId}&currency=${currency}`;
            script.onload = () => {
                this.paypal = window.paypal;
                resolve();
            };
            document.body.appendChild(script);
        });
    }

    async createOrder(amount, currency, description, referenceId) {
        return await this.request('/paypal/order', {
            method: 'POST',
            body: JSON.stringify({
                amount,
                currency,
                description,
                reference_id: referenceId
            })
        });
    }

    async captureOrder(orderId) {
        return await this.request('/paypal/capture', {
            method: 'POST',
            body: JSON.stringify({ orderId })
        });
    }

    async processPayment(amount, currency, campaignId, rewardTierId = null, shippingData = null) {
        try {
            // Create PayPal order
            const order = await this.createOrder(
                amount,
                currency,
                `Campaign Contribution - ${campaignId}`,
                `CAMP-${campaignId}-${Date.now()}`
            );

            if (!order.id) {
                throw new Error('Failed to create PayPal order');
            }

            // Note: In a real implementation, the capture happens after user approval
            // This is a simplified version - you'd typically use PayPal buttons
            return { orderId: order.id, order };

        } catch (error) {
            console.error('PayPal payment error:', error);
            throw error;
        }
    }

    async createContributionAfterCapture(campaignId, captureData, rewardTierId = null, shippingData = null) {
        const capture = captureData.purchase_units[0].payments.captures[0];

        return await this.request(`/campaigns/${campaignId}/contribute`, {
            method: 'POST',
            body: JSON.stringify({
                amount: parseFloat(capture.amount.value),
                currency: capture.amount.currency_code,
                payment_processor: 'paypal',
                transaction_id: capture.id,
                reward_tier_id: rewardTierId,
                status: 'succeeded',
                ...shippingData
            })
        });
    }
}

class MpesaHandler extends PaymentHandler {
    async initiatePayment(phoneNumber, amount, campaignId, rewardTierId = null, shippingData = null) {
        try {
            const response = await this.request('/mpesa/initiate-payment', {
                method: 'POST',
                body: JSON.stringify({
                    phone_number: phoneNumber,
                    amount,
                    campaign_id: campaignId,
                    account_reference: `CAMP-${campaignId}-${Date.now()}`,
                    transaction_description: `Campaign Contribution - Campaign #${campaignId}`
                })
            });

            return response;

        } catch (error) {
            console.error('M-Pesa payment error:', error);
            throw error;
        }
    }

    async queryTransactionStatus(checkoutRequestId) {
        return await this.request('/mpesa/query-status', {
            method: 'POST',
            body: JSON.stringify({ checkout_request_id: checkoutRequestId })
        });
    }

    formatPhoneNumber(phoneNumber) {
        // Remove non-numeric characters
        let cleaned = phoneNumber.replace(/\D/g, '');

        // Add country code if needed (254 for Kenya)
        if (cleaned.startsWith('0')) {
            cleaned = '254' + cleaned.substring(1);
        } else if (!cleaned.startsWith('254')) {
            cleaned = '254' + cleaned;
        }

        return cleaned;
    }
}

// Export for use in other files
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { PaymentHandler, StripeHandler, PayPalHandler, MpesaHandler };
}

window.PaymentHandlers = {
    StripeHandler,
    PayPalHandler,
    MpesaHandler
};

