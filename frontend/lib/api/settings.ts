import apiClient from './client';

export const settingsApi = {
  async categories() {
    const response = await apiClient.get('/v1/admin/settings/categories');
    return response.data.data;
  },

  async getPlatform() {
    const response = await apiClient.get('/v1/admin/settings/platform');
    return response.data.data;
  },

  async updatePlatform(data: any) {
    const response = await apiClient.post('/v1/admin/settings/platform', data);
    return response.data;
  },

  async getCampaigns() {
    const response = await apiClient.get('/v1/admin/settings/campaigns');
    return response.data.data;
  },

  async updateCampaigns(data: any) {
    const response = await apiClient.post('/v1/admin/settings/campaigns', data);
    return response.data;
  },

  async getUsers() {
    const response = await apiClient.get('/v1/admin/settings/users');
    return response.data.data;
  },

  async updateUsers(data: any) {
    const response = await apiClient.post('/v1/admin/settings/users', data);
    return response.data;
  },

  async getFinancial() {
    const response = await apiClient.get('/v1/admin/settings/financial');
    return response.data.data;
  },

  async updateFinancial(data: any) {
    const response = await apiClient.post('/v1/admin/settings/financial', data);
    return response.data;
  },
};

