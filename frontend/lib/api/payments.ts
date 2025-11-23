import apiClient from './client';

export const paymentsApi = {
  async createStripeIntent(amount: number, currency: string, campaignId: string | number, rewardTierId?: string | number) {
    const response = await apiClient.post('/v1/payments/create-intent', {
      amount,
      currency,
      campaign_id: campaignId,
      reward_tier_id: rewardTierId,
    });
    return response.data;
  },

  async createPayPalOrder(amount: number, currency: string, description: string, referenceId: string) {
    const response = await apiClient.post('/v1/paypal/order', {
      amount,
      currency,
      description,
      reference_id: referenceId,
    });
    return response.data;
  },

  async capturePayPalOrder(orderId: string) {
    const response = await apiClient.post('/v1/paypal/capture', { orderId });
    return response.data;
  },

  async initiateMpesaPayment(phoneNumber: string, amount: number, campaignId: string | number) {
    const response = await apiClient.post('/v1/mpesa/initiate-payment', {
      phone_number: phoneNumber,
      amount,
      campaign_id: campaignId,
      account_reference: `CAMP-${campaignId}-${Date.now()}`,
      transaction_description: `Campaign Contribution - Campaign #${campaignId}`,
    });
    return response.data;
  },

  async queryMpesaStatus(checkoutRequestId: string) {
    const response = await apiClient.post('/v1/mpesa/query-status', {
      checkout_request_id: checkoutRequestId,
    });
    return response.data;
  },

  async createContribution(campaignId: string | number, data: any) {
    const response = await apiClient.post(`/v1/campaigns/${campaignId}/contribute`, data);
    return response.data;
  },
};

