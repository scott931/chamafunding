'use client';

import { useEffect, useState } from 'react';
import Sidebar from '@/components/Sidebar';
import apiClient from '@/lib/api/client';
import { notificationsApi } from '@/lib/api/notifications';
import { reportsApi } from '@/lib/api/reports';
import Link from 'next/link';
import {
  LineChart,
  Line,
  BarChart,
  Bar,
  PieChart,
  Pie,
  Cell,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  Legend,
  ResponsiveContainer,
  AreaChart,
  Area,
} from 'recharts';

const COLORS = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899'];

export default function AdminDashboardPage() {
  const [stats, setStats] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [notifications, setNotifications] = useState<any>(null);
  const [recentCampaigns, setRecentCampaigns] = useState<any[]>([]);

  useEffect(() => {
    const fetchData = async () => {
      try {
        const [statsResponse, campaignsResponse] = await Promise.all([
          apiClient.get('/v1/admin/dashboard-stats'),
          apiClient.get('/v1/campaigns?per_page=5'),
        ]);

        setStats(statsResponse.data.data);
        
        if (campaignsResponse.data.data?.data) {
          setRecentCampaigns(campaignsResponse.data.data.data);
        }

        // Try to fetch notifications, but don't fail if it doesn't work
        try {
          const notificationsResponse = await notificationsApi.transactions(5, false);
          setNotifications(notificationsResponse);
        } catch (notifError) {
          console.warn('Notifications not available:', notifError);
          // Set empty notifications so UI doesn't break
          setNotifications({ notifications: [] });
        }
      } catch (error) {
        console.error('Failed to fetch dashboard data:', error);
      } finally {
        setLoading(false);
      }
    };
    fetchData();
  }, []);

  const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD',
    }).format(value);
  };

  if (loading) {
    return (
      <Sidebar>
        <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100">
          <div className="text-center">
            <div className="inline-block animate-spin rounded-full h-12 w-12 border-4 border-blue-600 border-t-transparent mb-4"></div>
            <p className="text-gray-600">Loading dashboard...</p>
          </div>
        </div>
      </Sidebar>
    );
  }

  if (!stats) {
    return (
      <Sidebar>
        <div className="min-h-screen flex items-center justify-center">
          <p className="text-gray-500">Failed to load dashboard data</p>
        </div>
      </Sidebar>
    );
  }

  // Prepare chart data
  const statusData = [
    { name: 'Active', value: stats.active_campaigns || 0, color: '#10B981' },
    { name: 'Successful', value: stats.successful_campaigns || 0, color: '#3B82F6' },
    { name: 'Draft', value: (stats.total_campaigns || 0) - (stats.active_campaigns || 0) - (stats.successful_campaigns || 0), color: '#F59E0B' },
  ].filter(item => item.value > 0);

  return (
    <Sidebar>
      <div className="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50/30 to-purple-50/30">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          {/* Header */}
          <div className="mb-8">
            <h1 className="text-4xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
              Admin Dashboard
            </h1>
            <p className="mt-2 text-gray-600">Welcome back! Here's what's happening on your platform.</p>
          </div>

          {/* Key Metrics Grid */}
          <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
            <MetricCard
              title="Total Raised"
              value={formatCurrency(stats.total_raised || 0)}
              icon="💰"
              trend={stats.monthly_stats?.volume ? `+${formatCurrency(stats.monthly_stats.volume)} this month` : null}
              gradient="from-green-500 to-emerald-600"
            />
            <MetricCard
              title="Active Campaigns"
              value={stats.active_campaigns || 0}
              icon="🚀"
              trend={stats.monthly_stats?.new_campaigns ? `+${stats.monthly_stats.new_campaigns} new this month` : null}
              gradient="from-blue-500 to-cyan-600"
            />
            <MetricCard
              title="Total Users"
              value={stats.total_users || 0}
              icon="👥"
              trend={stats.monthly_stats?.new_users ? `+${stats.monthly_stats.new_users} new this month` : null}
              gradient="from-purple-500 to-pink-600"
            />
            <MetricCard
              title="Platform Fees"
              value={formatCurrency(stats.platform_fees || 0)}
              icon="💵"
              trend="All time revenue"
              gradient="from-orange-500 to-red-600"
            />
          </div>

          {/* Charts and Activity Row */}
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            {/* Campaign Status Chart */}
            <div className="lg:col-span-1 bg-white rounded-xl shadow-lg p-6 border border-gray-100">
              <h3 className="text-lg font-semibold text-gray-900 mb-4">Campaign Status</h3>
              {statusData.length > 0 ? (
                <ResponsiveContainer width="100%" height={200}>
                  <PieChart>
                    <Pie
                      data={statusData}
                      cx="50%"
                      cy="50%"
                      labelLine={false}
                      label={({ name, value }) => `${name}: ${value}`}
                      outerRadius={80}
                      fill="#8884d8"
                      dataKey="value"
                    >
                      {statusData.map((entry, index) => (
                        <Cell key={`cell-${index}`} fill={entry.color} />
                      ))}
                    </Pie>
                    <Tooltip />
                  </PieChart>
                </ResponsiveContainer>
              ) : (
                <div className="flex items-center justify-center h-48 text-gray-400">
                  No campaign data
                </div>
              )}
            </div>

            {/* Quick Stats */}
            <div className="lg:col-span-2 bg-white rounded-xl shadow-lg p-6 border border-gray-100">
              <h3 className="text-lg font-semibold text-gray-900 mb-4">Platform Overview</h3>
              <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                <StatBox
                  label="Total Campaigns"
                  value={stats.total_campaigns || 0}
                  icon="📋"
                />
                <StatBox
                  label="Total Backers"
                  value={stats.total_backers || 0}
                  icon="🤝"
                />
                <StatBox
                  label="Total Payments"
                  value={stats.total_payments || 0}
                  icon="💳"
                />
                <StatBox
                  label="Total Volume"
                  value={formatCurrency(stats.total_volume || 0)}
                  icon="📊"
                />
              </div>
            </div>
          </div>

          {/* Recent Activity and Notifications */}
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            {/* Recent Campaigns */}
            <div className="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
              <div className="flex items-center justify-between mb-4">
                <h3 className="text-lg font-semibold text-gray-900">Recent Campaigns</h3>
                <Link
                  href="/admin/campaigns"
                  className="text-sm text-blue-600 hover:text-blue-700 font-medium"
                >
                  View all →
                </Link>
              </div>
              <div className="space-y-3">
                {recentCampaigns.length > 0 ? (
                  recentCampaigns.map((campaign: any) => (
                    <div
                      key={campaign.id}
                      className="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
                    >
                      <div className="flex-1 min-w-0">
                        <p className="font-medium text-gray-900 truncate">{campaign.title}</p>
                        <p className="text-sm text-gray-500">
                          {formatCurrency((campaign.raised_amount || 0) / 100)} raised
                        </p>
                      </div>
                      <span className={`ml-3 px-2 py-1 text-xs font-semibold rounded-full ${
                        campaign.status === 'active' ? 'bg-green-100 text-green-800' :
                        campaign.status === 'successful' ? 'bg-blue-100 text-blue-800' :
                        'bg-gray-100 text-gray-800'
                      }`}>
                        {campaign.status}
                      </span>
                    </div>
                  ))
                ) : (
                  <p className="text-gray-400 text-center py-4">No recent campaigns</p>
                )}
              </div>
            </div>

            {/* Transaction Notifications */}
            <div className="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
              <div className="flex items-center justify-between mb-4">
                <h3 className="text-lg font-semibold text-gray-900">Recent Transactions</h3>
                <Link
                  href="/admin/notifications"
                  className="text-sm text-blue-600 hover:text-blue-700 font-medium"
                >
                  View all →
                </Link>
              </div>
              <div className="space-y-3">
                {notifications?.notifications && notifications.notifications.length > 0 ? (
                  notifications.notifications.slice(0, 5).map((notification: any) => (
                    <div
                      key={notification.campaign_id}
                      className="flex items-center justify-between p-3 bg-blue-50 rounded-lg border border-blue-100"
                    >
                      <div className="flex-1 min-w-0">
                        <p className="font-medium text-gray-900 truncate">{notification.campaign_name}</p>
                        <p className="text-sm text-gray-500">
                          {notification.total_transactions} transaction{notification.total_transactions !== 1 ? 's' : ''}
                        </p>
                      </div>
                      <div className="ml-3 text-right">
                        <p className="font-semibold text-gray-900">
                          {formatCurrency(notification.total_amount / 100)}
                        </p>
                        <div className="w-2 h-2 bg-blue-600 rounded-full mt-1 ml-auto"></div>
                      </div>
                    </div>
                  ))
                ) : (
                  <p className="text-gray-400 text-center py-4">No recent transactions</p>
                )}
              </div>
            </div>
          </div>

          {/* Quick Actions */}
          <div className="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
              <QuickActionLink
                href="/admin/campaigns"
                label="Manage Campaigns"
                icon="📋"
                color="blue"
              />
              <QuickActionLink
                href="/admin/users"
                label="Manage Users"
                icon="👥"
                color="purple"
              />
              <QuickActionLink
                href="/admin/reports"
                label="View Reports"
                icon="📊"
                color="green"
              />
              <QuickActionLink
                href="/admin/settings"
                label="Settings"
                icon="⚙️"
                color="orange"
              />
            </div>
          </div>
        </div>
      </div>
    </Sidebar>
  );
}

// Metric Card Component
function MetricCard({ title, value, icon, trend, gradient }: any) {
  return (
    <div className="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100 hover:shadow-xl transition-shadow">
      <div className={`bg-gradient-to-br ${gradient} p-6`}>
        <div className="flex items-center justify-between">
          <div>
            <p className="text-white/80 text-sm font-medium mb-1">{title}</p>
            <p className="text-3xl font-bold text-white">{value}</p>
          </div>
          <div className="text-4xl opacity-80">{icon}</div>
        </div>
      </div>
      {trend && (
        <div className="px-6 py-3 bg-gray-50">
          <p className="text-xs text-gray-600">{trend}</p>
        </div>
      )}
    </div>
  );
}

// Stat Box Component
function StatBox({ label, value, icon }: any) {
  return (
    <div className="text-center p-4 bg-gradient-to-br from-gray-50 to-gray-100 rounded-lg">
      <div className="text-2xl mb-2">{icon}</div>
      <p className="text-2xl font-bold text-gray-900">{value}</p>
      <p className="text-xs text-gray-500 mt-1">{label}</p>
    </div>
  );
}

// Quick Action Link Component
function QuickActionLink({ href, label, icon, color }: any) {
  const colorClasses = {
    blue: 'from-blue-500 to-cyan-500 hover:from-blue-600 hover:to-cyan-600',
    purple: 'from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600',
    green: 'from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600',
    orange: 'from-orange-500 to-red-500 hover:from-orange-600 hover:to-red-600',
  };

  return (
    <Link
      href={href}
      className={`block p-4 bg-gradient-to-br ${colorClasses[color as keyof typeof colorClasses]} rounded-lg text-white hover:shadow-lg transition-all transform hover:scale-105`}
    >
      <div className="text-3xl mb-2">{icon}</div>
      <p className="font-semibold">{label}</p>
    </Link>
  );
}
