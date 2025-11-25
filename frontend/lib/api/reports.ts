import apiClient from './client';

export interface ReportFilters {
  start_date?: string;
  end_date?: string;
  status?: string;
  campaign_id?: number;
  user_type?: string;
  success_filter?: string;
  per_page?: number;
}

export const reportsApi = {
  async available() {
    const response = await apiClient.get('/v1/admin/reports-available');
    return response.data.data || {};
  },

  async platformOverview(filters: ReportFilters = {}) {
    const response = await apiClient.get('/v1/admin/reports/platform-overview', { params: filters });
    return response.data.data;
  },

  async allProjects(filters: ReportFilters = {}) {
    const response = await apiClient.get('/v1/admin/reports/all-projects', { params: filters });
    return response.data.data;
  },

  async financialSummary(filters: ReportFilters = {}) {
    const response = await apiClient.get('/v1/admin/reports/financial-summary', { params: filters });
    return response.data.data;
  },

  async backerReport(filters: ReportFilters = {}) {
    const response = await apiClient.get('/v1/admin/reports/backer-report', { params: filters });
    return response.data.data;
  },

  async userManagement(filters: ReportFilters = {}) {
    const response = await apiClient.get('/v1/admin/reports/user-management', { params: filters });
    return response.data.data;
  },

  async supportModeration() {
    const response = await apiClient.get('/v1/admin/reports/support-moderation');
    return response.data.data;
  },
};

