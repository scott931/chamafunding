import axios, { AxiosInstance, AxiosError } from 'axios';
import Cookies from 'js-cookie';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

class ApiClient {
  private client: AxiosInstance;

  constructor() {
    this.client = axios.create({
      baseURL: API_URL,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      withCredentials: true,
    });

    // Request interceptor to add CSRF token and auth token
    this.client.interceptors.request.use(
      async (config) => {
        // Ensure CSRF cookie is set (Laravel Sanctum)
        // The cookie is set by visiting /sanctum/csrf-cookie endpoint
        // This is handled by CSRFProvider component
        
        const authToken = Cookies.get('auth_token') || Cookies.get('token');
        if (authToken) {
          config.headers['Authorization'] = `Bearer ${authToken}`;
        }

        // Get CSRF token from cookie and send it in header
        // Laravel expects X-XSRF-TOKEN header for CSRF validation
        const csrfToken = Cookies.get('XSRF-TOKEN');
        if (csrfToken) {
          config.headers['X-XSRF-TOKEN'] = csrfToken;
        }

        return config;
      },
      (error) => {
        return Promise.reject(error);
      }
    );

    // Response interceptor for error handling
    // Note: Cache prevention headers are set by the backend (PreventCache middleware)
    this.client.interceptors.response.use(
      (response) => response,
      (error: AxiosError) => {
        if (error.response?.status === 401) {
          // Handle unauthorized - redirect to login
          if (typeof window !== 'undefined') {
            // Clear all authentication cookies
            Cookies.remove('auth_token');
            Cookies.remove('token');
            Cookies.remove('XSRF-TOKEN');
            
            // Prevent back button navigation
            window.history.replaceState(null, '', '/login');
            
            // Force redirect with cache busting
            window.location.href = '/login?unauthorized=' + Date.now();
          }
        }
        return Promise.reject(error);
      }
    );
  }

  get instance() {
    return this.client;
  }

  async get(url: string, config?: any) {
    return this.client.get(url, config);
  }

  async post(url: string, data?: any, config?: any) {
    return this.client.post(url, data, config);
  }

  async put(url: string, data?: any, config?: any) {
    return this.client.put(url, data, config);
  }

  async delete(url: string, config?: any) {
    return this.client.delete(url, config);
  }
}

export const apiClient = new ApiClient();
export default apiClient;

