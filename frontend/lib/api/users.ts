import apiClient from './client';

export interface UserFilters {
  search?: string;
  approval_status?: string;
  role?: string;
  page?: number;
  per_page?: number;
}

export interface UpdateRolePayload {
  role: string;
}

export interface UpdateApprovalPayload {
  status: 'approved' | 'declined' | 'pending';
  notes?: string;
}

export const usersApi = {
  async list(filters: UserFilters = {}) {
    const response = await apiClient.get('/v1/admin/users', { params: filters });
    return response.data?.data ?? response.data;
  },

  async get(userId: number | string) {
    const response = await apiClient.get(`/v1/admin/users/${userId}`);
    return response.data?.data ?? response.data;
  },

  async updateRole(userId: number | string, payload: UpdateRolePayload) {
    const response = await apiClient.post(`/v1/admin/users/${userId}/role`, payload);
    return response.data;
  },

  async updateApproval(userId: number | string, payload: UpdateApprovalPayload) {
    const response = await apiClient.post(`/v1/admin/users/${userId}/approval`, payload);
    return response.data;
  },
};

