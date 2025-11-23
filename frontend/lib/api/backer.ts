import apiClient from './client';

export const backerApi = {
  async getDashboard() {
    const response = await apiClient.get('/v1/backer/dashboard');
    return response.data;
  },

  async getDashboardSummary() {
    const response = await apiClient.get('/v1/backer/dashboard/summary');
    return response.data;
  },

  async getPledges(page = 1, perPage = 15) {
    const response = await apiClient.get(`/v1/backer/pledges?page=${page}&per_page=${perPage}`);
    return response.data;
  },

  async getPledge(pledgeId: string | number) {
    const response = await apiClient.get(`/v1/backer/pledges/${pledgeId}`);
    return response.data;
  },

  async getUpdates(page = 1, perPage = 20) {
    const response = await apiClient.get(`/v1/backer/updates?page=${page}&per_page=${perPage}`);
    return response.data;
  },

  async getTransactions(page = 1, perPage = 20) {
    const response = await apiClient.get(`/v1/backer/transactions?page=${page}&per_page=${perPage}`);
    return response.data;
  },

  async getPaymentHistory(page = 1, perPage = 15) {
    const response = await apiClient.get(`/v1/backer/payment-history?page=${page}&per_page=${perPage}`);
    return response.data;
  },

  async getCampaignCount() {
    const response = await apiClient.get('/v1/backer/campaigns-count');
    return response.data;
  },

  async updateShippingAddress(contributionId: string | number, shippingData: any) {
    const response = await apiClient.put(`/v1/backer/pledges/${contributionId}/shipping`, shippingData);
    return response.data;
  },

  async saveCampaign(campaignId: string | number) {
    const response = await apiClient.post('/v1/backer/save-campaign', { campaign_id: campaignId });
    return response.data;
  },

  async unsaveCampaign(campaignId: string | number) {
    const response = await apiClient.delete(`/v1/backer/unsave-campaign/${campaignId}`);
    return response.data;
  },

  async downloadReceipt(contributionId: string | number) {
    const response = await apiClient.get(`/v1/backer/transactions/${contributionId}/receipt`);
    return response.data;
  },
};

