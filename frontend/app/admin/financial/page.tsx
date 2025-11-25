'use client';

import { useEffect, useMemo, useState } from 'react';
import Sidebar from '@/components/Sidebar';
import { financialApi, FinancialOverview } from '@/lib/api/financial';

const formatCurrency = (value?: number, currency = 'USD') => {
  if (value === undefined || value === null) return '—';
  return new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency,
  }).format(value);
};

export default function AdminFinancialPage() {
  const [overview, setOverview] = useState<FinancialOverview | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const fetchOverview = async () => {
      setLoading(true);
      setError(null);
      try {
        const data = await financialApi.getOverview();
        setOverview(data);
      } catch (err) {
        console.error('Failed to load financial overview:', err);
        setError('Unable to load financial overview');
      } finally {
        setLoading(false);
      }
    };

    fetchOverview();
  }, []);

  const feeTrendMax = useMemo(() => {
    if (!overview?.fee_revenue_over_time?.length) return 0;
    return Math.max(...overview.fee_revenue_over_time.map((point) => point.total));
  }, [overview]);

  const statCards = [
    { key: 'total_volume_this_month', label: 'Volume (This Month)' },
    { key: 'total_fees_this_month', label: 'Fees (This Month)' },
    { key: 'total_fees_year', label: 'Fees (Year)' },
    { key: 'pending_payouts', label: 'Pending Payouts' },
    { key: 'failed_volume', label: 'Failed Volume' },
  ];

  return (
    <Sidebar>
      <div className="min-h-screen bg-gray-50 py-8">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
          <header>
            <h1 className="text-3xl font-bold text-gray-900">Financial Overview</h1>
            <p className="mt-2 text-sm text-gray-600">
              Monitor platform revenue, payment flows, and transaction health.
            </p>
          </header>

          {loading ? (
            <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-16 flex justify-center">
              <div className="text-center">
                <div className="inline-block h-10 w-10 animate-spin rounded-full border-4 border-indigo-500 border-t-transparent" />
                <p className="mt-4 text-sm text-gray-500">Loading financial data…</p>
              </div>
            </div>
          ) : error ? (
            <div className="bg-red-50 border border-red-100 text-red-800 rounded-xl p-4">
              {error}
            </div>
          ) : overview ? (
            <>
              <section className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                {statCards.map(({ key, label }) => (
                  <article key={key} className="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                    <p className="text-sm text-gray-500">{label}</p>
                    <p className="mt-2 text-2xl font-semibold text-gray-900">
                      {formatCurrency(overview.stats?.[key])}
                    </p>
                  </article>
                ))}
              </section>

              <section className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div className="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm lg:col-span-2">
                  <div className="flex items-center justify-between mb-4">
                    <div>
                      <h2 className="text-lg font-semibold text-gray-900">Fee Revenue Trend</h2>
                      <p className="text-sm text-gray-500">Last 45 days</p>
                    </div>
                  </div>
                  {overview.fee_revenue_over_time.length === 0 ? (
                    <p className="text-sm text-gray-500">No fee revenue recorded yet.</p>
                  ) : (
                    <div className="flex items-end gap-2 h-48">
                      {overview.fee_revenue_over_time.map((point) => (
                        <div key={point.date} className="flex-1 flex flex-col items-center">
                          <div
                            className="w-full bg-gradient-to-t from-indigo-200 to-indigo-600 rounded-t-lg"
                            style={{
                              height: `${feeTrendMax ? (point.total / feeTrendMax) * 100 : 0}%`,
                              minHeight: '4px',
                            }}
                          />
                          <span className="mt-2 text-[10px] text-gray-500">
                            {new Date(point.date).toLocaleDateString(undefined, { month: 'short', day: 'numeric' })}
                          </span>
                        </div>
                      ))}
                    </div>
                  )}
                </div>

                <div className="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                  <h2 className="text-lg font-semibold text-gray-900">Top Payment Methods</h2>
                  <p className="text-sm text-gray-500 mb-4">Ranked by processed volume</p>
                  <ul className="space-y-4">
                    {overview.top_payment_methods.length === 0 ? (
                      <li className="text-sm text-gray-500">No payment data available.</li>
                    ) : (
                      overview.top_payment_methods.map((method) => (
                        <li key={method.method}>
                          <div className="flex items-center justify-between">
                            <div>
                              <p className="text-sm font-medium text-gray-900">
                                {method.method ? method.method.replace('_', ' ') : 'Unknown'}
                              </p>
                              <p className="text-xs text-gray-500">{method.count} transactions</p>
                            </div>
                            <p className="text-sm font-semibold text-gray-900">
                              {formatCurrency(method.volume)}
                            </p>
                          </div>
                        </li>
                      ))
                    )}
                  </ul>
                </div>
              </section>

              <section className="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <div className="flex items-center justify-between mb-4">
                  <div>
                    <h2 className="text-lg font-semibold text-gray-900">Recent High-Value Transactions</h2>
                    <p className="text-sm text-gray-500">Top five completed transactions</p>
                  </div>
                </div>
                {overview.recent_transactions.length === 0 ? (
                  <p className="text-sm text-gray-500">No transactions recorded yet.</p>
                ) : (
                  <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200">
                      <thead className="bg-gray-50">
                        <tr>
                          <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Reference
                          </th>
                          <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            User
                          </th>
                          <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Method
                          </th>
                          <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Amount
                          </th>
                          <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Date
                          </th>
                        </tr>
                      </thead>
                      <tbody className="bg-white divide-y divide-gray-200">
                        {overview.recent_transactions.map((transaction) => (
                          <tr key={transaction.id}>
                            <td className="px-4 py-3 text-sm text-gray-900">{transaction.reference}</td>
                            <td className="px-4 py-3 text-sm text-gray-600">
                              {transaction.user?.name ?? '—'}
                              <div className="text-xs text-gray-400">{transaction.user?.email}</div>
                            </td>
                            <td className="px-4 py-3 text-sm capitalize text-gray-600">
                              {transaction.payment_method ?? '—'}
                            </td>
                            <td className="px-4 py-3 text-sm font-semibold text-gray-900">
                              {formatCurrency(transaction.amount, transaction.currency)}
                            </td>
                            <td className="px-4 py-3 text-sm text-gray-600">
                              {transaction.created_at
                                ? new Date(transaction.created_at).toLocaleString()
                                : '—'}
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                )}
              </section>
            </>
          ) : null}
        </div>
      </div>
    </Sidebar>
  );
}

