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
      Cookies.remove('auth_token');
      Cookies.remove('token');
      if (typeof window !== 'undefined') {
        window.location.href = '/login';
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
    return response.data.data;
  },
};

