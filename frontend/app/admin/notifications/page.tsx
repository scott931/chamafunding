'use client';

import { useEffect, useState } from 'react';
import Sidebar from '@/components/Sidebar';
import { notificationsApi } from '@/lib/api/notifications';

export default function AdminNotificationsPage() {
  const [activeTab, setActiveTab] = useState<'transactions' | 'support'>('transactions');
  const [loading, setLoading] = useState(true);
  const [transactionNotifications, setTransactionNotifications] = useState<any[]>([]);
  const [unreadCount, setUnreadCount] = useState(0);
  const [supportItems, setSupportItems] = useState<any>({});
  const [showRead, setShowRead] = useState(false);
  const [markingRead, setMarkingRead] = useState<number | null>(null);

  useEffect(() => {
    loadData();
  }, [activeTab, showRead]);

  const loadData = async () => {
    setLoading(true);
    try {
      if (activeTab === 'transactions') {
        const data = await notificationsApi.transactions(50, showRead);
        setTransactionNotifications(data.notifications || []);
        setUnreadCount(data.unread_count || 0);
      } else {
        const data = await notificationsApi.support();
        setSupportItems(data);
      }
    } catch (error) {
      console.error('Failed to load notifications:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleMarkAsRead = async (campaignId: number) => {
    setMarkingRead(campaignId);
    try {
      await notificationsApi.markAsRead(campaignId);
      // Reload notifications
      await loadData();
    } catch (error) {
      console.error('Failed to mark as read:', error);
    } finally {
      setMarkingRead(null);
    }
  };

  const handleMarkAllAsRead = async () => {
    setMarkingRead(-1); // Use -1 to indicate "all"
    try {
      await notificationsApi.markAllAsRead();
      // Reload notifications
      await loadData();
    } catch (error) {
      console.error('Failed to mark all as read:', error);
    } finally {
      setMarkingRead(null);
    }
  };

  const formatCurrency = (amount: number, currency: string = 'USD') => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: currency,
    }).format(amount / 100);
  };

  if (loading) {
    return (
      <Sidebar>
        <div className="min-h-screen flex items-center justify-center">
          <div className="text-center">
            <div className="inline-block animate-spin rounded-full h-12 w-12 border-4 border-blue-600 border-t-transparent mb-4"></div>
            <p className="text-gray-600">Loading notifications...</p>
          </div>
        </div>
      </Sidebar>
    );
  }

  return (
    <Sidebar>
      <div className="min-h-screen bg-gray-50 py-8">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="mb-6 flex items-center justify-between">
            <div>
              <h1 className="text-3xl font-bold text-gray-900">Notifications & Support</h1>
              <p className="mt-2 text-sm text-gray-600">Monitor transactions and support requests</p>
            </div>
            {activeTab === 'transactions' && unreadCount > 0 && (
              <div className="flex items-center space-x-3">
                <span className="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-semibold">
                  {unreadCount} unread
                </span>
              </div>
            )}
          </div>

          <div className="bg-white rounded-lg shadow">
            <div className="border-b border-gray-200">
              <nav className="flex -mb-px">
                <button
                  onClick={() => setActiveTab('transactions')}
                  className={`px-6 py-4 text-sm font-medium border-b-2 ${
                    activeTab === 'transactions'
                      ? 'border-blue-500 text-blue-600'
                      : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                  }`}
                >
                  💰 Transaction Notifications
                  {activeTab === 'transactions' && unreadCount > 0 && (
                    <span className="ml-2 px-2 py-0.5 bg-red-500 text-white text-xs rounded-full">
                      {unreadCount}
                    </span>
                  )}
                </button>
                <button
                  onClick={() => setActiveTab('support')}
                  className={`px-6 py-4 text-sm font-medium border-b-2 ${
                    activeTab === 'support'
                      ? 'border-blue-500 text-blue-600'
                      : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                  }`}
                >
                  🎧 Support & Moderation
                </button>
              </nav>
            </div>

            <div className="p-6">
              {activeTab === 'transactions' && (
                <TransactionNotifications
                  notifications={transactionNotifications}
                  formatCurrency={formatCurrency}
                  showRead={showRead}
                  onToggleShowRead={() => setShowRead(!showRead)}
                  onMarkAsRead={handleMarkAsRead}
                  onMarkAllAsRead={handleMarkAllAsRead}
                  markingRead={markingRead}
                  unreadCount={unreadCount}
                />
              )}
              {activeTab === 'support' && (
                <SupportItems supportItems={supportItems} />
              )}
            </div>
          </div>
        </div>
      </div>
    </Sidebar>
  );
}

// Transaction Notifications Component
function TransactionNotifications({
  notifications,
  formatCurrency,
  showRead,
  onToggleShowRead,
  onMarkAsRead,
  onMarkAllAsRead,
  markingRead,
  unreadCount,
}: any) {
  const [expandedCampaigns, setExpandedCampaigns] = useState<Set<number>>(new Set());

  const toggleCampaign = (campaignId: number) => {
    const newExpanded = new Set(expandedCampaigns);
    if (newExpanded.has(campaignId)) {
      newExpanded.delete(campaignId);
    } else {
      newExpanded.add(campaignId);
    }
    setExpandedCampaigns(newExpanded);
  };

  const unreadNotifications = notifications.filter((n: any) => !n.is_read);
  const readNotifications = notifications.filter((n: any) => n.is_read);

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between mb-4">
        <div className="flex items-center space-x-3">
          <label className="flex items-center">
            <input
              type="checkbox"
              checked={showRead}
              onChange={onToggleShowRead}
              className="mr-2"
            />
            <span className="text-sm text-gray-600">Show read notifications</span>
          </label>
        </div>
        {unreadCount > 0 && (
          <button
            onClick={onMarkAllAsRead}
            disabled={markingRead === -1}
            className="px-4 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {markingRead === -1 ? 'Marking...' : 'Mark All as Read'}
          </button>
        )}
      </div>

      {notifications.length === 0 ? (
        <div className="text-center py-12">
          <p className="text-gray-500">No transaction notifications</p>
        </div>
      ) : (
        <>
          {unreadNotifications.length > 0 && (
            <div>
              <h3 className="text-sm font-semibold text-gray-700 mb-2">Unread ({unreadNotifications.length})</h3>
              <div className="space-y-3">
                {unreadNotifications.map((notification: any) => (
                  <NotificationCard
                    key={notification.campaign_id}
                    notification={notification}
                    formatCurrency={formatCurrency}
                    expandedCampaigns={expandedCampaigns}
                    toggleCampaign={toggleCampaign}
                    onMarkAsRead={onMarkAsRead}
                    markingRead={markingRead}
                    isRead={false}
                  />
                ))}
              </div>
            </div>
          )}

          {showRead && readNotifications.length > 0 && (
            <div className="mt-6">
              <h3 className="text-sm font-semibold text-gray-700 mb-2">Read ({readNotifications.length})</h3>
              <div className="space-y-3">
                {readNotifications.map((notification: any) => (
                  <NotificationCard
                    key={notification.campaign_id}
                    notification={notification}
                    formatCurrency={formatCurrency}
                    expandedCampaigns={expandedCampaigns}
                    toggleCampaign={toggleCampaign}
                    onMarkAsRead={onMarkAsRead}
                    markingRead={markingRead}
                    isRead={true}
                  />
                ))}
              </div>
            </div>
          )}
        </>
      )}
    </div>
  );
}

// Notification Card Component
function NotificationCard({
  notification,
  formatCurrency,
  expandedCampaigns,
  toggleCampaign,
  onMarkAsRead,
  markingRead,
  isRead,
}: any) {
  return (
    <div
      className={`border rounded-lg overflow-hidden ${
        isRead ? 'bg-gray-50 opacity-75' : 'bg-white'
      }`}
    >
      <div
        className={`px-4 py-3 flex items-center justify-between cursor-pointer transition-colors ${
          isRead ? 'bg-gray-100 hover:bg-gray-200' : 'bg-blue-50 hover:bg-blue-100'
        }`}
        onClick={() => toggleCampaign(notification.campaign_id)}
      >
        <div className="flex items-center space-x-4 flex-1">
          {!isRead && (
            <div className="w-2 h-2 bg-blue-600 rounded-full"></div>
          )}
          <div className="flex-1">
            <div className="flex items-center space-x-2">
              <h3 className="font-semibold text-gray-900">{notification.campaign_name}</h3>
              {isRead && (
                <span className="px-2 py-0.5 text-xs font-medium bg-gray-200 text-gray-700 rounded">
                  Read
                </span>
              )}
            </div>
            <p className="text-sm text-gray-500">
              {notification.total_transactions} transaction{notification.total_transactions !== 1 ? 's' : ''}
            </p>
          </div>
        </div>
        <div className="flex items-center space-x-4">
          <div className="text-right">
            <p className="font-semibold text-gray-900">
              {formatCurrency(notification.total_amount, notification.currency)}
            </p>
            <p className="text-xs text-gray-500">
              {new Date(notification.latest_transaction_date).toLocaleDateString()}
            </p>
          </div>
          {!isRead && (
            <button
              onClick={(e) => {
                e.stopPropagation();
                onMarkAsRead(notification.campaign_id);
              }}
              disabled={markingRead === notification.campaign_id}
              className="px-3 py-1.5 text-xs bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
              title="Mark as read"
            >
              {markingRead === notification.campaign_id ? '...' : 'Mark Read'}
            </button>
          )}
          <svg
            className={`h-5 w-5 text-gray-400 transition-transform ${
              expandedCampaigns.has(notification.campaign_id) ? 'transform rotate-180' : ''
            }`}
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
          </svg>
        </div>
      </div>

      {expandedCampaigns.has(notification.campaign_id) && (
        <div className="px-4 py-3 border-t bg-white">
          <div className="space-y-2">
            {notification.transactions.map((transaction: any) => (
              <div
                key={transaction.id}
                className="flex items-center justify-between py-2 border-b last:border-0"
              >
                <div>
                  <p className="font-medium text-gray-900">{transaction.user_name}</p>
                  <p className="text-sm text-gray-500">{transaction.user_email}</p>
                  <p className="text-xs text-gray-400 mt-1">{transaction.formatted_date}</p>
                </div>
                <div className="text-right">
                  <p className="font-semibold text-gray-900">
                    {formatCurrency(transaction.amount, transaction.currency)}
                  </p>
                  <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                    transaction.status === 'completed' || transaction.status === 'succeeded'
                      ? 'bg-green-100 text-green-800'
                      : transaction.status === 'pending'
                      ? 'bg-yellow-100 text-yellow-800'
                      : 'bg-red-100 text-red-800'
                  }`}>
                    {transaction.status}
                  </span>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}

// Support Items Component
function SupportItems({ supportItems }: any) {
  const pendingCampaigns = supportItems.pending_campaigns || [];
  const flaggedProjects = supportItems.flagged_projects || [];
  const suspiciousCampaigns = supportItems.suspicious_campaigns || [];

  const hasItems = pendingCampaigns.length > 0 || flaggedProjects.length > 0 || suspiciousCampaigns.length > 0;

  if (!hasItems) {
    return (
      <div className="text-center py-12">
        <div className="inline-block p-4 bg-gray-100 rounded-full mb-4">
          <svg className="w-12 h-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <p className="text-gray-500 text-lg font-medium">All clear!</p>
        <p className="text-gray-400 text-sm mt-1">No pending support items at this time</p>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {pendingCampaigns.length > 0 && (
        <div>
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-lg font-semibold">Pending Campaign Reviews</h3>
            <span className="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm font-semibold">
              {pendingCampaigns.length} pending
            </span>
          </div>
          <div className="space-y-3">
            {pendingCampaigns.map((item: any) => (
              <div
                key={item.id}
                className="border rounded-lg p-4 hover:bg-gray-50 transition-colors"
              >
                <div className="flex items-center justify-between">
                  <div className="flex-1">
                    <div className="flex items-center space-x-2 mb-2">
                      <h4 className="font-semibold text-gray-900">{item.title}</h4>
                      <span className="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded">
                        Draft
                      </span>
                    </div>
                    <p className="text-sm text-gray-600">
                      <span className="font-medium">Creator:</span> {item.creator_name} ({item.creator_email})
                    </p>
                    <p className="text-xs text-gray-400 mt-1">{item.formatted_date}</p>
                  </div>
                  <div className="flex items-center space-x-2 ml-4">
                    <button className="px-4 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700">
                      Review
                    </button>
                    <button className="px-4 py-2 text-sm border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50">
                      View Details
                    </button>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {flaggedProjects.length > 0 && (
        <div>
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-lg font-semibold">Flagged Projects</h3>
            <span className="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-semibold">
              {flaggedProjects.length} flagged
            </span>
          </div>
          <div className="space-y-3">
            {flaggedProjects.map((item: any) => (
              <div
                key={item.id}
                className="border border-red-200 rounded-lg p-4 bg-red-50"
              >
                <div className="flex items-center justify-between">
                  <div className="flex-1">
                    <div className="flex items-center space-x-2 mb-2">
                      <h4 className="font-semibold text-gray-900">{item.title}</h4>
                      <span className="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded">
                        Flagged
                      </span>
                    </div>
                    <p className="text-sm text-gray-600">
                      <span className="font-medium">Creator:</span> {item.creator_name}
                    </p>
                    <p className="text-sm text-red-600 mt-1">
                      <span className="font-medium">Reason:</span> {item.reason || 'Multiple contributions from same user'}
                    </p>
                  </div>
                  <div className="flex items-center space-x-2 ml-4">
                    <button className="px-4 py-2 text-sm bg-red-600 text-white rounded-md hover:bg-red-700">
                      Investigate
                    </button>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {suspiciousCampaigns.length > 0 && (
        <div>
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-lg font-semibold">Suspicious Activity</h3>
            <span className="px-3 py-1 bg-orange-100 text-orange-800 rounded-full text-sm font-semibold">
              {suspiciousCampaigns.length} suspicious
            </span>
          </div>
          <div className="space-y-3">
            {suspiciousCampaigns.map((item: any) => (
              <div
                key={item.id}
                className="border border-orange-200 rounded-lg p-4 bg-orange-50"
              >
                <div className="flex items-center justify-between">
                  <div className="flex-1">
                    <div className="flex items-center space-x-2 mb-2">
                      <h4 className="font-semibold text-gray-900">{item.title}</h4>
                      <span className="px-2 py-1 text-xs font-medium bg-orange-100 text-orange-800 rounded">
                        Suspicious
                      </span>
                    </div>
                    <p className="text-sm text-gray-600">
                      <span className="font-medium">Creator:</span> {item.creator_name}
                    </p>
                    <p className="text-sm text-orange-600 mt-1">
                      <span className="font-medium">Reason:</span> {item.reason || 'Many small contributions detected'}
                    </p>
                  </div>
                  <div className="flex items-center space-x-2 ml-4">
                    <button className="px-4 py-2 text-sm bg-orange-600 text-white rounded-md hover:bg-orange-700">
                      Review
                    </button>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}
