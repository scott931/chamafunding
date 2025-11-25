import apiClient from './client';

export interface ActivityLog {
  id: number;
  user_id: number | null;
  activity_type: string;
  description: string | null;
  ip_address: string | null;
  user_agent: string | null;
  device_type: string | null;
  device_name: string | null;
  browser: string | null;
  browser_version: string | null;
  os: string | null;
  os_version: string | null;
  country: string | null;
  city: string | null;
  region: string | null;
  latitude: number | null;
  longitude: number | null;
  url: string | null;
  method: string | null;
  metadata: Record<string, any> | null;
  created_at: string;
  updated_at: string;
  user?: {
    id: number;
    name: string;
    email: string;
  };
}

export interface ActivityLogsResponse {
  success: boolean;
  data: {
    current_page: number;
    data: ActivityLog[];
    last_page: number;
    per_page: number;
    total: number;
  };
}

export interface ActivityStatistics {
  success: boolean;
  data: {
    total_activities: number;
    activities_by_type: Record<string, number>;
    devices_used: Record<string, number>;
    browsers_used: Record<string, number>;
    locations: Array<{
      country: string;
      city: string;
      count: number;
    }>;
    recent_logins: ActivityLog[];
  };
}

export interface ActivityLogsFilters {
  activity_type?: string;
  start_date?: string;
  end_date?: string;
  per_page?: number;
  page?: number;
}

export const activityLogsApi = {
  async list(filters: ActivityLogsFilters = {}): Promise<ActivityLogsResponse> {
    const params = new URLSearchParams();
    
    if (filters.activity_type) params.append('activity_type', filters.activity_type);
    if (filters.start_date) params.append('start_date', filters.start_date);
    if (filters.end_date) params.append('end_date', filters.end_date);
    if (filters.per_page) params.append('per_page', filters.per_page.toString());
    if (filters.page) params.append('page', filters.page.toString());
    
    const queryString = params.toString();
    const url = `/v1/activity-logs${queryString ? `?${queryString}` : ''}`;
    
    const response = await apiClient.get(url);
    return response.data;
  },

  async get(id: number): Promise<{ success: boolean; data: ActivityLog }> {
    const response = await apiClient.get(`/v1/activity-logs/${id}`);
    return response.data;
  },

  async statistics(): Promise<ActivityStatistics> {
    const response = await apiClient.get('/v1/activity-logs/statistics');
    return response.data;
  },
};

