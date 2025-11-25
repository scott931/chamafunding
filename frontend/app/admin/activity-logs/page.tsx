'use client';

import { useCallback, useEffect, useState } from 'react';
import Sidebar from '@/components/Sidebar';
import { activityLogsApi, ActivityLog } from '@/lib/api/activityLogs';

const activityTypeStyles: Record<string, string> = {
  login: 'bg-green-100 text-green-800',
  logout: 'bg-blue-100 text-blue-800',
  register: 'bg-purple-100 text-purple-800',
  page_view: 'bg-gray-100 text-gray-800',
  dashboard_view: 'bg-indigo-100 text-indigo-800',
  admin_page_view: 'bg-yellow-100 text-yellow-800',
  create: 'bg-emerald-100 text-emerald-800',
  update: 'bg-amber-100 text-amber-800',
  delete: 'bg-red-100 text-red-800',
};

const deviceTypeIcons: Record<string, string> = {
  desktop: '🖥️',
  mobile: '📱',
  tablet: '📱',
};

export default function ActivityLogsPage() {
  const [logs, setLogs] = useState<ActivityLog[]>([]);
  const [pagination, setPagination] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [statistics, setStatistics] = useState<any>(null);
  const [showStatistics, setShowStatistics] = useState(false);
  const [filters, setFilters] = useState({
    activity_type: '',
    start_date: '',
    end_date: '',
  });
  const [filterDraft, setFilterDraft] = useState(filters);
  const [selectedLog, setSelectedLog] = useState<ActivityLog | null>(null);

  const fetchLogs = useCallback(async () => {
    setLoading(true);
    try {
      const response = await activityLogsApi.list({
        ...filters,
        per_page: 20,
      });
      console.log('Activity logs response:', response);
      if (response.success && response.data) {
        setLogs(response.data.data || []);
        setPagination(response.data);
      } else {
        console.error('Unexpected response format:', response);
        setLogs([]);
      }
    } catch (error: any) {
      console.error('Failed to fetch activity logs:', error);
      console.error('Error details:', error.response?.data || error.message);
      setLogs([]);
    } finally {
      setLoading(false);
    }
  }, [filters]);

  const fetchStatistics = useCallback(async () => {
    try {
      const stats = await activityLogsApi.statistics();
      console.log('Statistics response:', stats);
      if (stats.success && stats.data) {
        setStatistics(stats.data);
      }
    } catch (error: any) {
      console.error('Failed to fetch statistics:', error);
      console.error('Error details:', error.response?.data || error.message);
    }
  }, []);

  useEffect(() => {
    fetchLogs();
    fetchStatistics();
  }, [fetchLogs, fetchStatistics]);

  const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  const formatLocation = (log: ActivityLog) => {
    const parts = [];
    if (log.city) parts.push(log.city);
    if (log.region) parts.push(log.region);
    if (log.country) parts.push(log.country);
    if (parts.length > 0) return parts.join(', ');
    // Check if IP is local/private
    if (log.ip_address && (log.ip_address.startsWith('127.') || log.ip_address.startsWith('192.168.') || log.ip_address.startsWith('10.') || log.ip_address === '::1')) {
      return 'Local Network';
    }
    return 'Unknown';
  };

  const getActivityTypeLabel = (type: string) => {
    return type
      .split('_')
      .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
      .join(' ');
  };

  return (
    <Sidebar>
      <div className="min-h-screen bg-gray-50 py-8">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-3xl font-bold text-gray-900">Activity Logs</h1>
              <p className="mt-2 text-sm text-gray-600">
                Monitor user activities, device information, and location data.
              </p>
            </div>
            <button
              onClick={() => setShowStatistics(!showStatistics)}
              className="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
            >
              {showStatistics ? 'Hide' : 'Show'} Statistics
            </button>
          </div>

          {/* Statistics Panel */}
          {showStatistics && statistics && (
            <section className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
              <h2 className="text-xl font-bold text-gray-900 mb-4">Activity Statistics</h2>
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div className="bg-blue-50 rounded-lg p-4">
                  <div className="text-sm font-medium text-gray-600">Total Activities</div>
                  <div className="text-3xl font-bold text-blue-600 mt-2">
                    {statistics.total_activities?.toLocaleString() || 0}
                  </div>
                </div>
                <div className="bg-green-50 rounded-lg p-4">
                  <div className="text-sm font-medium text-gray-600">Device Types</div>
                  <div className="text-lg font-semibold text-green-600 mt-2">
                    {Object.keys(statistics.devices_used || {}).length} types
                  </div>
                </div>
                <div className="bg-purple-50 rounded-lg p-4">
                  <div className="text-sm font-medium text-gray-600">Browsers</div>
                  <div className="text-lg font-semibold text-purple-600 mt-2">
                    {Object.keys(statistics.browsers_used || {}).length} browsers
                  </div>
                </div>
                <div className="bg-amber-50 rounded-lg p-4">
                  <div className="text-sm font-medium text-gray-600">Locations</div>
                  <div className="text-lg font-semibold text-amber-600 mt-2">
                    {statistics.locations?.length || 0} locations
                  </div>
                </div>
              </div>

              <div className="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <h3 className="text-sm font-semibold text-gray-700 mb-3">Activities by Type</h3>
                  <div className="space-y-2">
                    {Object.entries(statistics.activities_by_type || {}).map(([type, count]) => (
                      <div key={type} className="flex items-center justify-between">
                        <span className="text-sm text-gray-600">{getActivityTypeLabel(type)}</span>
                        <span className="text-sm font-semibold text-gray-900">{count as number}</span>
                      </div>
                    ))}
                  </div>
                </div>
                <div>
                  <h3 className="text-sm font-semibold text-gray-700 mb-3">Top Locations</h3>
                  <div className="space-y-2">
                    {statistics.locations?.slice(0, 5).map((location: any, index: number) => (
                      <div key={index} className="flex items-center justify-between">
                        <span className="text-sm text-gray-600">
                          {location.city ? `${location.city}, ` : ''}
                          {location.country}
                        </span>
                        <span className="text-sm font-semibold text-gray-900">{location.count}</span>
                      </div>
                    ))}
                  </div>
                </div>
              </div>
            </section>
          )}

          {/* Filters */}
          <section className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <form
              className="grid grid-cols-1 md:grid-cols-4 gap-4"
              onSubmit={(event) => {
                event.preventDefault();
                setFilters(filterDraft);
              }}
            >
              <div className="col-span-1">
                <label className="text-sm font-medium text-gray-700">Activity Type</label>
                <select
                  value={filterDraft.activity_type}
                  onChange={(event) =>
                    setFilterDraft((prev) => ({ ...prev, activity_type: event.target.value }))
                  }
                  className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                  <option value="">All types</option>
                  <option value="login">Login</option>
                  <option value="logout">Logout</option>
                  <option value="register">Register</option>
                  <option value="page_view">Page View</option>
                  <option value="dashboard_view">Dashboard View</option>
                  <option value="admin_page_view">Admin Page View</option>
                  <option value="create">Create</option>
                  <option value="update">Update</option>
                  <option value="delete">Delete</option>
                </select>
              </div>
              <div className="col-span-1">
                <label className="text-sm font-medium text-gray-700">Start Date</label>
                <input
                  type="date"
                  value={filterDraft.start_date}
                  onChange={(event) =>
                    setFilterDraft((prev) => ({ ...prev, start_date: event.target.value }))
                  }
                  className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>
              <div className="col-span-1">
                <label className="text-sm font-medium text-gray-700">End Date</label>
                <input
                  type="date"
                  value={filterDraft.end_date}
                  onChange={(event) =>
                    setFilterDraft((prev) => ({ ...prev, end_date: event.target.value }))
                  }
                  className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>
              <div className="col-span-1 flex items-end gap-2">
                <button
                  type="submit"
                  className="inline-flex flex-1 items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                >
                  Apply Filters
                </button>
                <button
                  type="button"
                  className="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                  onClick={() => {
                    setFilterDraft({ activity_type: '', start_date: '', end_date: '' });
                    setFilters({ activity_type: '', start_date: '', end_date: '' });
                  }}
                >
                  Reset
                </button>
              </div>
            </form>
          </section>

          {/* Activity Logs Table */}
          <section className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            {loading ? (
              <div className="py-16 flex justify-center">
                <div className="text-center">
                  <div className="inline-block h-10 w-10 animate-spin rounded-full border-4 border-indigo-500 border-t-transparent" />
                  <p className="mt-4 text-sm text-gray-500">Loading activity logs…</p>
                </div>
              </div>
            ) : logs.length === 0 ? (
              <div className="py-16 text-center">
                <p className="text-gray-500">No activity logs found.</p>
                <p className="text-sm text-gray-400 mt-2">
                  Try logging in/out to generate activity logs, or check the browser console for errors.
                </p>
              </div>
            ) : (
              <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200">
                  <thead className="bg-gray-50">
                    <tr>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Time
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Activity
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        User
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Device
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Location
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        IP Address
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                      </th>
                    </tr>
                  </thead>
                  <tbody className="bg-white divide-y divide-gray-200">
                    {logs.map((log) => (
                      <tr key={log.id} className="hover:bg-gray-50">
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                          {formatDate(log.created_at)}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <span
                            className={`inline-flex px-2.5 py-1 text-xs font-semibold rounded-full ${
                              activityTypeStyles[log.activity_type] ||
                              'bg-gray-100 text-gray-800'
                            }`}
                          >
                            {getActivityTypeLabel(log.activity_type)}
                          </span>
                          {log.description && (
                            <p className="mt-1 text-xs text-gray-500">{log.description}</p>
                          )}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                          {log.user ? (
                            <div>
                              <div className="font-semibold">{log.user.name}</div>
                              <div className="text-xs text-gray-500">{log.user.email}</div>
                            </div>
                          ) : (
                            <span className="text-gray-400">Guest</span>
                          )}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                          {log.device_type || log.browser || log.os ? (
                            <div className="flex items-center gap-2">
                              {log.device_type && (
                                <span className="text-lg">{deviceTypeIcons[log.device_type] || '💻'}</span>
                              )}
                              <div>
                                {log.device_name && (
                                  <div className="font-medium">{log.device_name}</div>
                                )}
                                {log.browser && (
                                  <div className="text-xs text-gray-500">
                                    {log.browser}
                                    {log.browser_version && ` ${log.browser_version}`}
                                  </div>
                                )}
                                {log.os && (
                                  <div className="text-xs text-gray-500">
                                    {log.os}
                                    {log.os_version && ` ${log.os_version}`}
                                  </div>
                                )}
                                {!log.device_name && !log.browser && !log.os && log.device_type && (
                                  <div className="text-xs text-gray-500 capitalize">{log.device_type}</div>
                                )}
                              </div>
                            </div>
                          ) : (
                            <span className="text-gray-400 text-xs">Not available</span>
                          )}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                          {formatLocation(log)}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600 font-mono">
                          {log.ip_address || 'N/A'}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm">
                          <button
                            onClick={() => setSelectedLog(log)}
                            className="inline-flex items-center justify-center rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-blue-500"
                          >
                            View Details
                          </button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
            {pagination && (
              <div className="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                <p className="text-sm text-gray-500">
                  Page {pagination.current_page} of {pagination.last_page} — {pagination.total} logs
                  total.
                </p>
                <div className="flex gap-2">
                  {pagination.current_page > 1 && (
                    <button
                      onClick={async () => {
                        const response = await activityLogsApi.list({
                          ...filters,
                          per_page: 20,
                          page: pagination.current_page - 1,
                        });
                        setLogs(response.data.data);
                        setPagination(response.data);
                      }}
                      className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                    >
                      Previous
                    </button>
                  )}
                  {pagination.current_page < pagination.last_page && (
                    <button
                      onClick={async () => {
                        const response = await activityLogsApi.list({
                          ...filters,
                          per_page: 20,
                          page: pagination.current_page + 1,
                        });
                        setLogs(response.data.data);
                        setPagination(response.data);
                      }}
                      className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                    >
                      Next
                    </button>
                  )}
                </div>
              </div>
            )}
          </section>
        </div>
      </div>

      {/* Activity Log Detail Modal */}
      {selectedLog && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-xl shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <div className="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
              <h2 className="text-2xl font-bold text-gray-900">Activity Log Details</h2>
              <button
                onClick={() => setSelectedLog(null)}
                className="text-gray-400 hover:text-gray-600 transition-colors"
              >
                <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <div className="p-6 space-y-6">
              {/* Basic Information */}
              <div className="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-lg p-6">
                <h3 className="text-lg font-semibold text-gray-900 mb-4">Activity Information</h3>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="text-xs font-medium text-gray-500 uppercase">Activity Type</label>
                    <div className="mt-1">
                      <span
                        className={`inline-flex px-2.5 py-1 text-xs font-semibold rounded-full ${
                          activityTypeStyles[selectedLog.activity_type] ||
                          'bg-gray-100 text-gray-800'
                        }`}
                      >
                        {getActivityTypeLabel(selectedLog.activity_type)}
                      </span>
                    </div>
                  </div>
                  <div>
                    <label className="text-xs font-medium text-gray-500 uppercase">Timestamp</label>
                    <p className="text-sm font-semibold text-gray-900 mt-1">
                      {formatDate(selectedLog.created_at)}
                    </p>
                  </div>
                  {selectedLog.description && (
                    <div className="md:col-span-2">
                      <label className="text-xs font-medium text-gray-500 uppercase">Description</label>
                      <p className="text-sm text-gray-900 mt-1">{selectedLog.description}</p>
                    </div>
                  )}
                  {selectedLog.user && (
                    <div>
                      <label className="text-xs font-medium text-gray-500 uppercase">User</label>
                      <p className="text-sm font-semibold text-gray-900 mt-1">{selectedLog.user.name}</p>
                      <p className="text-xs text-gray-500">{selectedLog.user.email}</p>
                    </div>
                  )}
                  {selectedLog.url && (
                    <div>
                      <label className="text-xs font-medium text-gray-500 uppercase">URL</label>
                      <p className="text-sm text-gray-900 mt-1 break-all">{selectedLog.url}</p>
                    </div>
                  )}
                  {selectedLog.method && (
                    <div>
                      <label className="text-xs font-medium text-gray-500 uppercase">HTTP Method</label>
                      <p className="text-sm font-semibold text-gray-900 mt-1">{selectedLog.method}</p>
                    </div>
                  )}
                </div>
              </div>

              {/* Device Information */}
              <div className="border rounded-lg p-6">
                <h3 className="text-lg font-semibold text-gray-900 mb-4">Device Information</h3>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  {selectedLog.device_type && (
                    <div>
                      <label className="text-xs font-medium text-gray-500 uppercase">Device Type</label>
                      <p className="text-sm font-semibold text-gray-900 mt-1 capitalize">
                        {selectedLog.device_type}
                      </p>
                    </div>
                  )}
                  {selectedLog.device_name && (
                    <div>
                      <label className="text-xs font-medium text-gray-500 uppercase">Device Name</label>
                      <p className="text-sm font-semibold text-gray-900 mt-1">{selectedLog.device_name}</p>
                    </div>
                  )}
                  {selectedLog.browser && (
                    <div>
                      <label className="text-xs font-medium text-gray-500 uppercase">Browser</label>
                      <p className="text-sm font-semibold text-gray-900 mt-1">
                        {selectedLog.browser}
                        {selectedLog.browser_version && ` ${selectedLog.browser_version}`}
                      </p>
                    </div>
                  )}
                  {selectedLog.os && (
                    <div>
                      <label className="text-xs font-medium text-gray-500 uppercase">Operating System</label>
                      <p className="text-sm font-semibold text-gray-900 mt-1">
                        {selectedLog.os}
                        {selectedLog.os_version && ` ${selectedLog.os_version}`}
                      </p>
                    </div>
                  )}
                  {selectedLog.user_agent && (
                    <div className="md:col-span-2">
                      <label className="text-xs font-medium text-gray-500 uppercase">User Agent</label>
                      <p className="text-sm text-gray-900 mt-1 break-all font-mono text-xs bg-gray-50 p-2 rounded">
                        {selectedLog.user_agent}
                      </p>
                    </div>
                  )}
                </div>
              </div>

              {/* Location Information */}
              {(selectedLog.country || selectedLog.city || selectedLog.ip_address) && (
                <div className="border rounded-lg p-6">
                  <h3 className="text-lg font-semibold text-gray-900 mb-4">Location Information</h3>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {selectedLog.ip_address && (
                      <div>
                        <label className="text-xs font-medium text-gray-500 uppercase">IP Address</label>
                        <p className="text-sm font-semibold text-gray-900 mt-1 font-mono">
                          {selectedLog.ip_address}
                        </p>
                      </div>
                    )}
                    {selectedLog.country && (
                      <div>
                        <label className="text-xs font-medium text-gray-500 uppercase">Country</label>
                        <p className="text-sm font-semibold text-gray-900 mt-1">{selectedLog.country}</p>
                      </div>
                    )}
                    {selectedLog.city && (
                      <div>
                        <label className="text-xs font-medium text-gray-500 uppercase">City</label>
                        <p className="text-sm font-semibold text-gray-900 mt-1">{selectedLog.city}</p>
                      </div>
                    )}
                    {selectedLog.region && (
                      <div>
                        <label className="text-xs font-medium text-gray-500 uppercase">Region</label>
                        <p className="text-sm font-semibold text-gray-900 mt-1">{selectedLog.region}</p>
                      </div>
                    )}
                    {(selectedLog.latitude || selectedLog.longitude) && (
                      <div className="md:col-span-2">
                        <label className="text-xs font-medium text-gray-500 uppercase">Coordinates</label>
                        <p className="text-sm font-semibold text-gray-900 mt-1 font-mono">
                          {selectedLog.latitude}, {selectedLog.longitude}
                        </p>
                      </div>
                    )}
                  </div>
                </div>
              )}

              {/* Metadata */}
              {selectedLog.metadata && Object.keys(selectedLog.metadata).length > 0 && (
                <div className="border rounded-lg p-6">
                  <h3 className="text-lg font-semibold text-gray-900 mb-4">Additional Metadata</h3>
                  <pre className="bg-gray-50 p-4 rounded-lg text-xs text-gray-900 overflow-x-auto">
                    {JSON.stringify(selectedLog.metadata, null, 2)}
                  </pre>
                </div>
              )}
            </div>

            <div className="sticky bottom-0 bg-gray-50 border-t border-gray-200 px-6 py-4 flex justify-end">
              <button
                onClick={() => setSelectedLog(null)}
                className="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors"
              >
                Close
              </button>
            </div>
          </div>
        </div>
      )}
    </Sidebar>
  );
}

