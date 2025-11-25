import apiClient from './client';

export interface FinancialOverview {
  stats: Record<string, number>;
  fee_revenue_over_time: Array<{ date: string; total: number }>;
  top_payment_methods: Array<{ method: string; count: number; volume: number }>;
  recent_transactions: Array<{
    id: number;
    reference: string;
    amount: number;
    currency: string;
    status: string;
    payment_method: string | null;
    created_at: string | null;
    user?: { id: number; name: string; email: string };
  }>;
}

export interface TransactionFilters {
  type?: string;
  status?: string;
  payment_method?: string;
  payment_provider?: string;
  search?: string;
  date_from?: string;
  date_to?: string;
  page?: number;
  per_page?: number;
}

export const financialApi = {
  async getOverview(): Promise<FinancialOverview> {
    const response = await apiClient.get('/v1/admin/financial/overview');
    return response.data?.data ?? response.data;
  },

  async listTransactions(filters: TransactionFilters = {}) {
    const response = await apiClient.get('/v1/admin/transactions', { params: filters });
    return response.data?.data ?? response.data;
  },
};

