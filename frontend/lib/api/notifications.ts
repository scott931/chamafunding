import apiClient from './client';

export const notificationsApi = {
  async transactions(limit = 50, includeRead = false) {
    const response = await apiClient.get('/v1/admin/notifications/transactions', {
      params: { limit, include_read: includeRead },
    });
    return response.data.data;
  },

  async support() {
    const response = await apiClient.get('/v1/admin/notifications/support');
    return response.data.data;
  },

  async all() {
    const response = await apiClient.get('/v1/admin/notifications/all');
    return response.data.data;
  },

  async markAsRead(campaignId: number | string) {
    const response = await apiClient.post(`/v1/admin/notifications/${campaignId}/mark-read`);
    return response.data;
  },

  async markAllAsRead() {
    const response = await apiClient.post('/v1/admin/notifications/mark-all-read');
    return response.data;
  },
};

