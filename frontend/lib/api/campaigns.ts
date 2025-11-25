import apiClient from './client';

export interface CampaignFilters {
  status?: string;
  category?: string;
  search?: string;
  page?: number;
  per_page?: number;
}

export interface CampaignPayload {
  title: string;
  category: string;
  description: string;
  goal_amount: number;
  currency: string;
  deadline?: string;
  starts_at?: string;
  ends_at?: string;
  featured_image?: string;
  images?: string[];
}

export const campaignsApi = {
  async list(filters: CampaignFilters = {}) {
    const response = await apiClient.get('/v1/campaigns', { params: filters });
    return response.data;
  },

  async get(id: number | string) {
    const response = await apiClient.get(`/v1/campaigns/${id}`);
    return response.data;
  },

  async create(payload: CampaignPayload) {
    const response = await apiClient.post('/v1/campaigns', payload);
    return response.data;
  },

  async update(id: number | string, payload: Partial<CampaignPayload>) {
    const response = await apiClient.put(`/v1/campaigns/${id}`, payload);
    return response.data;
  },

  async activate(id: number | string) {
    const response = await apiClient.post(`/v1/campaigns/${id}/activate`);
    return response.data;
  },
};

