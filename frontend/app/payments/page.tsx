'use client';

import { useEffect, useState } from 'react';
import Sidebar from '@/components/Sidebar';
import apiClient from '@/lib/api/client';

export default function PaymentsPage() {
  const [payments, setPayments] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchPayments = async () => {
      try {
        // TODO: Replace with actual payments API endpoint
        const response = await apiClient.get('/v1/payments/history');
        setPayments(response.data.data || []);
      } catch (error) {
        console.error('Failed to fetch payments:', error);
      } finally {
        setLoading(false);
      }
    };
    fetchPayments();
  }, []);

  if (loading) {
    return (
      <Sidebar>
        <div className="min-h-screen flex items-center justify-center">
          <div className="text-center">
            <div className="inline-block animate-spin rounded-full h-12 w-12 border-4 border-blue-600 border-t-transparent mb-4"></div>
            <p className="text-gray-600">Loading payments...</p>
          </div>
        </div>
      </Sidebar>
    );
  }

  return (
    <Sidebar>
      <div className="min-h-screen bg-gray-50 py-4 sm:py-8">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="mb-4 sm:mb-6">
            <h1 className="text-2xl sm:text-3xl font-bold text-gray-900">Payments</h1>
            <p className="mt-1 sm:mt-2 text-xs sm:text-sm text-gray-600">View your payment history</p>
          </div>
          {payments.length === 0 ? (
            <div className="bg-white rounded-lg shadow p-8 sm:p-12 text-center">
              <p className="text-sm sm:text-base text-gray-500">No payment history available.</p>
            </div>
          ) : (
            <div className="bg-white shadow overflow-hidden sm:rounded-md">
              <ul className="divide-y divide-gray-200">
                {payments.map((payment) => (
                  <li key={payment.id} className="px-4 sm:px-6 py-4">
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                      <div className="flex-1 min-w-0">
                        <p className="text-sm font-medium text-gray-900 truncate">{payment.description || 'Payment'}</p>
                        <p className="text-xs sm:text-sm text-gray-500 mt-1">{new Date(payment.created_at).toLocaleDateString()}</p>
                      </div>
                      <div className="flex items-center justify-between sm:flex-col sm:items-end sm:justify-start gap-2">
                        <p className="text-sm sm:text-base font-medium text-gray-900">
                          {payment.amount} {payment.currency || 'USD'}
                        </p>
                        <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full whitespace-nowrap ${
                          payment.status === 'completed' 
                            ? 'bg-green-100 text-green-800' 
                            : payment.status === 'pending'
                            ? 'bg-yellow-100 text-yellow-800'
                            : 'bg-red-100 text-red-800'
                        }`}>
                          {payment.status || 'Unknown'}
                        </span>
                      </div>
                    </div>
                  </li>
                ))}
              </ul>
            </div>
          )}
        </div>
      </div>
    </Sidebar>
  );
}


