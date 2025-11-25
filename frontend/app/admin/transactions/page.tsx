'use client';

import { useCallback, useEffect, useMemo, useState } from 'react';
import Sidebar from '@/components/Sidebar';
import { financialApi } from '@/lib/api/financial';

const formatCurrency = (value?: number, currency = 'USD') => {
  if (value === undefined || value === null) return '—';
  return new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency,
  }).format(value);
};

const statusPill: Record<string, string> = {
  completed: 'bg-green-100 text-green-800',
  pending: 'bg-yellow-100 text-yellow-800',
  processing: 'bg-yellow-100 text-yellow-800',
  failed: 'bg-red-100 text-red-800',
  cancelled: 'bg-gray-200 text-gray-800',
  refunded: 'bg-blue-100 text-blue-800',
};

export default function AdminTransactionsPage() {
  const [transactions, setTransactions] = useState<any[]>([]);
  const [pagination, setPagination] = useState<any>(null);
  const [metrics, setMetrics] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [filters, setFilters] = useState({
    type: '',
    status: '',
    payment_method: '',
    payment_provider: '',
    search: '',
  });
  const [filterDraft, setFilterDraft] = useState(filters);

  const fetchTransactions = useCallback(
    async (page?: number) => {
      setLoading(true);
      setError(null);
      try {
        const data = await financialApi.listTransactions({
          ...filters,
          page,
          type: filters.type || undefined,
          status: filters.status || undefined,
          payment_method: filters.payment_method || undefined,
          payment_provider: filters.payment_provider || undefined,
          search: filters.search || undefined,
          per_page: 30,
        });

        setTransactions(data.transactions?.data ?? []);
        setPagination(data.transactions);
        setMetrics(data.metrics);
      } catch (err) {
        console.error('Failed to load transactions:', err);
        setError('Unable to load transactions');
      } finally {
        setLoading(false);
      }
    },
    [filters]
  );

  useEffect(() => {
    fetchTransactions();
  }, [fetchTransactions]);

  const appliedFilters = useMemo(() => {
    const active: string[] = [];
    (Object.keys(filters) as Array<keyof typeof filters>).forEach((key) => {
      if (filters[key]) {
        active.push(`${key.replace('_', ' ')}: ${filters[key]}`);
      }
    });
    return active;
  }, [filters]);

  return (
    <Sidebar>
      <div className="min-h-screen bg-gray-50 py-8">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
          <header>
            <h1 className="text-3xl font-bold text-gray-900">Transaction Log</h1>
            <p className="mt-2 text-sm text-gray-600">
              Inspect every financial event flowing through the platform.
            </p>
          </header>

          {metrics && (
            <section className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <article className="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                <p className="text-sm text-gray-500">Transactions recorded</p>
                <p className="mt-2 text-2xl font-semibold text-gray-900">{metrics.total_count}</p>
              </article>
              <article className="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                <p className="text-sm text-gray-500">Completed today</p>
                <p className="mt-2 text-2xl font-semibold text-gray-900">{metrics.completed_today}</p>
              </article>
              <article className="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                <p className="text-sm text-gray-500">Volume today</p>
                <p className="mt-2 text-2xl font-semibold text-gray-900">
                  {formatCurrency(metrics.volume_today)}
                </p>
              </article>
            </section>
          )}

          <section className="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
            <form
              className="grid grid-cols-1 md:grid-cols-5 gap-4"
              onSubmit={(event) => {
                event.preventDefault();
                setFilters(filterDraft);
              }}
            >
              <div>
                <label className="text-sm font-medium text-gray-700">Type</label>
                <select
                  value={filterDraft.type}
                  onChange={(event) => setFilterDraft((prev) => ({ ...prev, type: event.target.value }))}
                  className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                  <option value="">All</option>
                  <option value="payment">Payment</option>
                  <option value="refund">Refund</option>
                  <option value="fee">Fee</option>
                  <option value="interest">Interest</option>
                  <option value="transfer">Transfer</option>
                </select>
              </div>
              <div>
                <label className="text-sm font-medium text-gray-700">Status</label>
                <select
                  value={filterDraft.status}
                  onChange={(event) => setFilterDraft((prev) => ({ ...prev, status: event.target.value }))}
                  className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                  <option value="">All</option>
                  <option value="completed">Completed</option>
                  <option value="pending">Pending</option>
                  <option value="processing">Processing</option>
                  <option value="failed">Failed</option>
                  <option value="cancelled">Cancelled</option>
                  <option value="refunded">Refunded</option>
                </select>
              </div>
              <div>
                <label className="text-sm font-medium text-gray-700">Payment method</label>
                <input
                  value={filterDraft.payment_method}
                  onChange={(event) =>
                    setFilterDraft((prev) => ({ ...prev, payment_method: event.target.value }))
                  }
                  placeholder="card, bank_transfer…"
                  className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>
              <div>
                <label className="text-sm font-medium text-gray-700">Provider</label>
                <input
                  value={filterDraft.payment_provider}
                  onChange={(event) =>
                    setFilterDraft((prev) => ({ ...prev, payment_provider: event.target.value }))
                  }
                  placeholder="stripe, mpesa…"
                  className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>
              <div>
                <label className="text-sm font-medium text-gray-700">Search</label>
                <input
                  value={filterDraft.search}
                  onChange={(event) => setFilterDraft((prev) => ({ ...prev, search: event.target.value }))}
                  placeholder="Reference, user, email"
                  className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>
              <div className="md:col-span-5 flex justify-end gap-2">
                <button
                  type="button"
                  onClick={() => {
                    const reset = { type: '', status: '', payment_method: '', payment_provider: '', search: '' };
                    setFilterDraft(reset);
                    setFilters(reset);
                  }}
                  className="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50"
                >
                  Reset
                </button>
                <button
                  type="submit"
                  className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                >
                  Apply filters
                </button>
              </div>
            </form>
            {appliedFilters.length > 0 && (
              <p className="mt-2 text-xs text-gray-500">Active filters: {appliedFilters.join(' · ')}</p>
            )}
          </section>

          <section className="rounded-xl border border-gray-100 bg-white shadow-sm overflow-hidden">
            {loading ? (
              <div className="py-16 flex justify-center">
                <div className="text-center">
                  <div className="inline-block h-10 w-10 animate-spin rounded-full border-4 border-indigo-500 border-t-transparent" />
                  <p className="mt-4 text-sm text-gray-500">Loading transactions…</p>
                </div>
              </div>
            ) : error ? (
              <div className="py-12 text-center text-sm text-red-700">{error}</div>
            ) : transactions.length === 0 ? (
              <div className="py-12 text-center text-sm text-gray-500">No transactions found.</div>
            ) : (
              <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200">
                  <thead className="bg-gray-50">
                    <tr>
                      <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Reference
                      </th>
                      <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Type
                      </th>
                      <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        User
                      </th>
                      <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Campaign
                      </th>
                      <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Amount
                      </th>
                      <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                      </th>
                      <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Method
                      </th>
                      <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Created
                      </th>
                    </tr>
                  </thead>
                  <tbody className="bg-white divide-y divide-gray-200">
                    {transactions.map((transaction) => (
                      <tr key={transaction.id}>
                        <td className="px-4 py-3 text-sm text-gray-900">{transaction.reference}</td>
                        <td className="px-4 py-3 text-sm capitalize text-gray-600">{transaction.transaction_type}</td>
                        <td className="px-4 py-3 text-sm text-gray-600">
                          {transaction.user?.name ?? '—'}
                          <div className="text-xs text-gray-400">{transaction.user?.email}</div>
                        </td>
                        <td className="px-4 py-3 text-sm text-gray-600">
                          {transaction.campaign?.title ?? '—'}
                        </td>
                        <td className="px-4 py-3 text-sm font-semibold text-gray-900">
                          {formatCurrency(transaction.amount, transaction.currency)}
                          <div className="text-xs text-gray-400">
                            Net {formatCurrency(transaction.net_amount, transaction.currency)}
                          </div>
                        </td>
                        <td className="px-4 py-3 text-sm">
                          <span
                            className={`inline-flex px-2.5 py-1 text-xs font-semibold rounded-full ${
                              statusPill[transaction.status] ?? 'bg-slate-100 text-slate-800'
                            }`}
                          >
                            {transaction.status}
                          </span>
                        </td>
                        <td className="px-4 py-3 text-sm text-gray-600 capitalize">
                          {transaction.payment_provider ? `${transaction.payment_provider}` : '—'}
                          <div className="text-xs text-gray-400">
                            {transaction.payment_method ?? 'method n/a'}
                          </div>
                        </td>
                        <td className="px-4 py-3 text-sm text-gray-500">
                          {transaction.created_at ? new Date(transaction.created_at).toLocaleString() : '—'}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
            {pagination && (
              <div className="px-6 py-4 flex items-center justify-between text-sm text-gray-500">
                <span>
                  Page {pagination.current_page} of {pagination.last_page}
                </span>
                <div className="flex gap-2">
                  <button
                    disabled={pagination.current_page <= 1}
                    onClick={() => fetchTransactions(pagination.current_page - 1)}
                    className="rounded-lg border border-gray-300 px-3 py-1 disabled:opacity-60"
                  >
                    Previous
                  </button>
                  <button
                    disabled={pagination.current_page >= pagination.last_page}
                    onClick={() => fetchTransactions(pagination.current_page + 1)}
                    className="rounded-lg border border-gray-300 px-3 py-1 disabled:opacity-60"
                  >
                    Next
                  </button>
                </div>
              </div>
            )}
          </section>
        </div>
      </div>
    </Sidebar>
  );
}
