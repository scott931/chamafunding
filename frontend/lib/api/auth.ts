import apiClient from './client';
import Cookies from 'js-cookie';

export interface LoginCredentials {
  email: string;
  password: string;
  remember?: boolean;
}

export interface RegisterData {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  phone?: string;
}

export interface AuthResponse {
  success: boolean;
  message?: string;
  data?: {
    user: any;
    token?: string;
  };
}

export const authApi = {
  async login(credentials: LoginCredentials): Promise<AuthResponse> {
    const response = await apiClient.post('/v1/auth/login', credentials);
    
    if (response.data.data?.token) {
      Cookies.set('auth_token', response.data.data.token, { expires: 7 });
    }
    
    return response.data;
  },

  async register(data: RegisterData): Promise<AuthResponse> {
    const response = await apiClient.post('/v1/auth/register', data);
    
    if (response.data.data?.token) {
      Cookies.set('auth_token', response.data.data.token, { expires: 7 });
    }
    
    return response.data;
  },

  async logout(): Promise<void> {
    try {
      await apiClient.post('/v1/auth/logout');
    } finally {
      // Clear all authentication-related cookies
      Cookies.remove('auth_token');
      Cookies.remove('token');
      Cookies.remove('XSRF-TOKEN');
      
      // Clear all cookies that might be set by the application
      if (typeof document !== 'undefined' && typeof window !== 'undefined') {
        // Get all cookies and clear them
        document.cookie.split(';').forEach((cookie) => {
          const eqPos = cookie.indexOf('=');
          const name = eqPos > -1 ? cookie.substr(0, eqPos).trim() : cookie.trim();
          // Clear cookies that might be related to auth/session
          if (name.includes('auth') || name.includes('token') || name.includes('session') || name.includes('csrf')) {
            Cookies.remove(name);
            // Also try to clear with different path/domain combinations
            Cookies.remove(name, { path: '/' });
            try {
              Cookies.remove(name, { path: '/', domain: window.location.hostname });
            } catch (e) {
              // Ignore domain-related errors
            }
          }
        });
      }
      
      if (typeof window !== 'undefined') {
        // Clear browser cache and prevent back button
        // Replace current history entry to prevent back navigation
        window.history.replaceState(null, '', '/login');
        
        // Clear any cached data
        if ('caches' in window) {
          caches.keys().then((names) => {
            names.forEach((name) => {
              caches.delete(name);
            });
          });
        }
        
        // Force redirect to login with cache busting
        window.location.href = '/login?logout=' + Date.now();
      }
    }
  },

  async forgotPassword(email: string): Promise<{ success: boolean; message: string }> {
    const response = await apiClient.post('/v1/auth/forgot-password', { email });
    return response.data;
  },

  async resetPassword(token: string, email: string, password: string, password_confirmation: string): Promise<{ success: boolean; message: string }> {
    const response = await apiClient.post('/v1/auth/reset-password', {
      token,
      email,
      password,
      password_confirmation,
    });
    return response.data;
  },

  async getUser(): Promise<any> {
    const response = await apiClient.get('/v1/auth/user');
    return response.data?.data?.user ?? response.data?.data ?? response.data ?? null;
  },
};

