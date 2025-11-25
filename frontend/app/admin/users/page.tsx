'use client';

import { useCallback, useEffect, useMemo, useState } from 'react';
import Sidebar from '@/components/Sidebar';
import { usersApi } from '@/lib/api/users';

const approvalStyles: Record<string, string> = {
  approved: 'bg-green-100 text-green-800',
  pending: 'bg-yellow-100 text-yellow-800',
  declined: 'bg-red-100 text-red-800',
};

export default function AdminUsersPage() {
  const [users, setUsers] = useState<any[]>([]);
  const [pagination, setPagination] = useState<any>(null);
  const [roles, setRoles] = useState<string[]>([]);
  const [loading, setLoading] = useState(true);
  const [filters, setFilters] = useState({ search: '', approval_status: '', role: '' });
  const [filterDraft, setFilterDraft] = useState(filters);
  const [feedback, setFeedback] = useState<{ type: 'success' | 'error'; message: string } | null>(null);
  const [updatingRoleId, setUpdatingRoleId] = useState<number | null>(null);
  const [updatingApprovalId, setUpdatingApprovalId] = useState<number | null>(null);
  const [selectedUser, setSelectedUser] = useState<any>(null);
  const [loadingProfile, setLoadingProfile] = useState(false);

  const fetchUsers = useCallback(async () => {
    setLoading(true);
    try {
      const payload = await usersApi.list({
        search: filters.search || undefined,
        approval_status: filters.approval_status || undefined,
        role: filters.role || undefined,
        per_page: 20,
      });

      const usersData = payload?.users;
      setUsers(usersData?.data ?? []);
      setPagination(usersData);
      setRoles(payload?.roles ?? []);
      } catch (error) {
        console.error('Failed to fetch users:', error);
      setFeedback({ type: 'error', message: 'Failed to load users' });
      } finally {
        setLoading(false);
      }
  }, [filters]);

  useEffect(() => {
    fetchUsers();
  }, [fetchUsers]);

  const handleRoleChange = async (userId: number, role: string) => {
    if (!role) return;
    setUpdatingRoleId(userId);
    setFeedback(null);
    try {
      await usersApi.updateRole(userId, { role });
      setFeedback({ type: 'success', message: 'Role updated successfully' });
      await fetchUsers();
      // Update selected user if modal is open
      if (selectedUser && selectedUser.id === userId) {
        const updatedUser = await usersApi.get(userId);
        setSelectedUser(updatedUser);
      }
    } catch (error: any) {
      console.error('Failed to update role:', error);
      const message = error?.response?.data?.message ?? 'Unable to update role.';
      setFeedback({ type: 'error', message });
    } finally {
      setUpdatingRoleId(null);
    }
  };

  const handleApproval = async (userId: number, status: 'approved' | 'declined' | 'pending') => {
    let notes: string | undefined;
    if (status === 'declined') {
      notes = window.prompt('Optional: add decline notes', '') ?? undefined;
    }

    setUpdatingApprovalId(userId);
    setFeedback(null);
    try {
      await usersApi.updateApproval(userId, { status, notes });
      setFeedback({ type: 'success', message: 'Approval status updated' });
      await fetchUsers();
    } catch (error: any) {
      console.error('Failed to update approval status:', error);
      const message =
        error?.response?.data?.message ?? 'Unable to update approval status.';
      setFeedback({ type: 'error', message });
    } finally {
      setUpdatingApprovalId(null);
    }
  };

  const handleViewProfile = async (userId: number) => {
    setLoadingProfile(true);
    try {
      const userData = await usersApi.get(userId);
      setSelectedUser(userData);
    } catch (error: any) {
      console.error('Failed to load user profile:', error);
      setFeedback({ type: 'error', message: 'Failed to load user profile' });
    } finally {
      setLoadingProfile(false);
    }
  };

  const appliedFilters = useMemo(() => {
    const active: string[] = [];
    if (filters.approval_status) active.push(`Status: ${filters.approval_status}`);
    if (filters.role) active.push(`Role: ${filters.role}`);
    if (filters.search) active.push(`Search: "${filters.search}"`);
    return active;
  }, [filters]);

  return (
    <Sidebar>
      <div className="min-h-screen bg-gray-50 py-8">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
          <div>
            <h1 className="text-3xl font-bold text-gray-900">User Management</h1>
            <p className="mt-2 text-sm text-gray-600">
              Review user accounts, adjust permissions, and handle approvals.
            </p>
          </div>

          {feedback && (
            <div
              className={`rounded-lg p-4 ${
                feedback.type === 'success'
                  ? 'bg-green-50 text-green-800 border border-green-100'
                  : 'bg-red-50 text-red-800 border border-red-100'
              }`}
            >
              {feedback.message}
            </div>
          )}

          <section className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <form
              className="grid grid-cols-1 md:grid-cols-4 gap-4"
              onSubmit={(event) => {
                event.preventDefault();
                setFilters(filterDraft);
              }}
            >
              <div className="col-span-1">
                <label className="text-sm font-medium text-gray-700">Search</label>
                <input
                  type="text"
                  value={filterDraft.search}
                  onChange={(event) => setFilterDraft((prev) => ({ ...prev, search: event.target.value }))}
                  placeholder="Name, email, phone"
                  className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>
              <div className="col-span-1">
                <label className="text-sm font-medium text-gray-700">Approval status</label>
                <select
                  value={filterDraft.approval_status}
                  onChange={(event) =>
                    setFilterDraft((prev) => ({ ...prev, approval_status: event.target.value }))
                  }
                  className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                  <option value="">All statuses</option>
                  <option value="pending">Pending</option>
                  <option value="approved">Approved</option>
                  <option value="declined">Declined</option>
                </select>
              </div>
              <div className="col-span-1">
                <label className="text-sm font-medium text-gray-700">Role</label>
                <select
                  value={filterDraft.role}
                  onChange={(event) =>
                    setFilterDraft((prev) => ({ ...prev, role: event.target.value }))
                  }
                  className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                  <option value="">All roles</option>
                  {roles.map((role) => (
                    <option key={role} value={role}>
                      {role}
                    </option>
                  ))}
                </select>
              </div>
              <div className="col-span-1 flex items-end gap-2">
                <button
                  type="submit"
                  className="inline-flex flex-1 items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                >
                  Apply filters
                </button>
                <button
                  type="button"
                  className="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                  onClick={() => {
                    setFilterDraft({ search: '', approval_status: '', role: '' });
                    setFilters({ search: '', approval_status: '', role: '' });
                  }}
                >
                  Reset
                </button>
              </div>
            </form>
            {appliedFilters.length > 0 && (
              <p className="mt-3 text-xs text-gray-500">
                Active filters: {appliedFilters.join(' · ')}
              </p>
            )}
          </section>

          <section className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            {loading ? (
              <div className="py-16 flex justify-center">
                <div className="text-center">
                  <div className="inline-block h-10 w-10 animate-spin rounded-full border-4 border-indigo-500 border-t-transparent" />
                  <p className="mt-4 text-sm text-gray-500">Loading users…</p>
                </div>
              </div>
            ) : users.length === 0 ? (
              <div className="py-16 text-center text-gray-500">No users match the current filters.</div>
            ) : (
              <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200">
                  <thead className="bg-gray-50">
                    <tr>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        User
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Role
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Approval
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Activity
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                      </th>
                    </tr>
                  </thead>
                  <tbody className="bg-white divide-y divide-gray-200">
                {users.map((user) => (
                      <tr key={user.id}>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <div className="text-sm font-semibold text-gray-900">{user.name}</div>
                          <div className="text-sm text-gray-500">{user.email}</div>
                          {user.phone && <div className="text-xs text-gray-400">{user.phone}</div>}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <select
                            className="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                            value={user.roles?.[0]?.name || ''}
                            onChange={(event) => handleRoleChange(user.id, event.target.value)}
                            disabled={updatingRoleId === user.id}
                          >
                            <option value="">Select role</option>
                            {roles.map((role) => (
                              <option key={role} value={role}>
                                {role}
                              </option>
                            ))}
                          </select>
                          {updatingRoleId === user.id && (
                            <p className="mt-1 text-xs text-gray-400">Updating role…</p>
                          )}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <span
                            className={`inline-flex px-2.5 py-1 text-xs font-semibold rounded-full ${
                              approvalStyles[user.approval_status] ?? 'bg-slate-100 text-slate-800'
                            }`}
                          >
                            {user.approval_status ?? 'unknown'}
                          </span>
                          {user.approval_notes && (
                            <p className="mt-1 text-xs text-gray-500 line-clamp-2">{user.approval_notes}</p>
                          )}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                          <div className="flex flex-col space-y-1">
                            <span>Created: {user.campaigns_count ?? 0}</span>
                            <span>Assigned: {user.assigned_campaigns_count ?? 0}</span>
                            <span>Contributions: {user.contributions_count ?? 0}</span>
                          </div>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                          <div className="flex flex-col gap-2">
                            <button
                              onClick={() => handleViewProfile(user.id)}
                              className="inline-flex items-center justify-center rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-blue-500"
                              disabled={loadingProfile}
                            >
                              {loadingProfile ? 'Loading...' : 'View Profile'}
                            </button>
                            <button
                              onClick={() => handleApproval(user.id, 'approved')}
                              className="inline-flex items-center justify-center rounded-lg bg-green-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-green-500 disabled:opacity-60"
                              disabled={updatingApprovalId === user.id || user.approval_status === 'approved'}
                            >
                              Approve
                            </button>
                            <button
                              onClick={() => handleApproval(user.id, 'declined')}
                              className="inline-flex items-center justify-center rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-red-500 disabled:opacity-60"
                              disabled={updatingApprovalId === user.id || user.approval_status === 'declined'}
                            >
                              Decline
                            </button>
                            <button
                              onClick={() => handleApproval(user.id, 'pending')}
                              className="inline-flex items-center justify-center rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-60"
                              disabled={updatingApprovalId === user.id || user.approval_status === 'pending'}
                            >
                              Reset to Pending
                            </button>
                            {updatingApprovalId === user.id && (
                              <p className="text-xs text-gray-400">Updating status…</p>
                            )}
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
            {pagination && (
              <p className="px-6 py-4 text-sm text-gray-500">
                Page {pagination.current_page} of {pagination.last_page} — {pagination.total} users total.
              </p>
            )}
          </section>
        </div>
      </div>

      {/* User Profile Modal */}
      {selectedUser && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-xl shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <div className="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
              <h2 className="text-2xl font-bold text-gray-900">User Profile</h2>
              <button
                onClick={() => setSelectedUser(null)}
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
                <h3 className="text-lg font-semibold text-gray-900 mb-4">Basic Information</h3>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="text-xs font-medium text-gray-500 uppercase">Name</label>
                    <p className="text-sm font-semibold text-gray-900 mt-1">{selectedUser.name}</p>
                  </div>
                  <div>
                    <label className="text-xs font-medium text-gray-500 uppercase">Email</label>
                    <p className="text-sm font-semibold text-gray-900 mt-1">{selectedUser.email}</p>
                  </div>
                  {selectedUser.phone && (
                    <div>
                      <label className="text-xs font-medium text-gray-500 uppercase">Phone</label>
                      <p className="text-sm font-semibold text-gray-900 mt-1">{selectedUser.phone}</p>
                    </div>
                  )}
                  {selectedUser.date_of_birth && (
                    <div>
                      <label className="text-xs font-medium text-gray-500 uppercase">Date of Birth</label>
                      <p className="text-sm font-semibold text-gray-900 mt-1">{selectedUser.date_of_birth}</p>
                    </div>
                  )}
                  <div>
                    <label className="text-xs font-medium text-gray-500 uppercase">User ID</label>
                    <p className="text-sm font-semibold text-gray-900 mt-1">#{selectedUser.id}</p>
                  </div>
                  <div>
                    <label className="text-xs font-medium text-gray-500 uppercase">Roles</label>
                    <div className="flex flex-wrap gap-2 mt-1">
                      {selectedUser.roles?.map((role: string) => (
                        <span key={role} className="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-800">
                          {role}
                        </span>
                      ))}
                    </div>
                  </div>
                </div>
              </div>

              {/* Address Information */}
              {(selectedUser.address || selectedUser.city || selectedUser.state || selectedUser.country || selectedUser.postal_code) && (
                <div className="border rounded-lg p-6">
                  <h3 className="text-lg font-semibold text-gray-900 mb-4">Address Information</h3>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {selectedUser.address && (
                      <div className="md:col-span-2">
                        <label className="text-xs font-medium text-gray-500 uppercase">Address</label>
                        <p className="text-sm text-gray-900 mt-1">{selectedUser.address}</p>
                      </div>
                    )}
                    {selectedUser.city && (
                      <div>
                        <label className="text-xs font-medium text-gray-500 uppercase">City</label>
                        <p className="text-sm text-gray-900 mt-1">{selectedUser.city}</p>
                      </div>
                    )}
                    {selectedUser.state && (
                      <div>
                        <label className="text-xs font-medium text-gray-500 uppercase">State</label>
                        <p className="text-sm text-gray-900 mt-1">{selectedUser.state}</p>
                      </div>
                    )}
                    {selectedUser.country && (
                      <div>
                        <label className="text-xs font-medium text-gray-500 uppercase">Country</label>
                        <p className="text-sm text-gray-900 mt-1">{selectedUser.country}</p>
                      </div>
                    )}
                    {selectedUser.postal_code && (
                      <div>
                        <label className="text-xs font-medium text-gray-500 uppercase">Postal Code</label>
                        <p className="text-sm text-gray-900 mt-1">{selectedUser.postal_code}</p>
                      </div>
                    )}
                  </div>
                </div>
              )}

              {/* Account Status */}
              <div className="border rounded-lg p-6">
                <h3 className="text-lg font-semibold text-gray-900 mb-4">Account Status</h3>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="text-xs font-medium text-gray-500 uppercase">Email Verified</label>
                    <div className="mt-1">
                        <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                        selectedUser.is_verified || selectedUser.email_verified_at
                            ? 'bg-green-100 text-green-800' 
                          : 'bg-yellow-100 text-yellow-800'
                      }`}>
                        {selectedUser.is_verified || selectedUser.email_verified_at ? 'Verified' : 'Not Verified'}
                      </span>
                      {selectedUser.email_verified_at && (
                        <p className="text-xs text-gray-500 mt-1">{selectedUser.email_verified_at}</p>
                      )}
                    </div>
                  </div>
                  <div>
                    <label className="text-xs font-medium text-gray-500 uppercase">Approval Status</label>
                    <div className="mt-1">
                      <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                        approvalStyles[selectedUser.approval_status] ?? 'bg-slate-100 text-slate-800'
                      }`}>
                        {selectedUser.approval_status ?? 'unknown'}
                      </span>
                      {selectedUser.approved_at && (
                        <p className="text-xs text-gray-500 mt-1">Approved: {selectedUser.approved_at}</p>
                      )}
                      {selectedUser.approver && (
                        <p className="text-xs text-gray-500 mt-1">By: {selectedUser.approver.name}</p>
                      )}
                    </div>
                  </div>
                  {selectedUser.approval_notes && (
                    <div className="md:col-span-2">
                      <label className="text-xs font-medium text-gray-500 uppercase">Approval Notes</label>
                      <p className="text-sm text-gray-900 mt-1 bg-gray-50 p-3 rounded-md">{selectedUser.approval_notes}</p>
                    </div>
                  )}
                </div>
              </div>

              {/* Membership & Preferences */}
              {(selectedUser.membership_type || selectedUser.preferred_contribution_amount || selectedUser.payment_frequency || selectedUser.referral_code) && (
                <div className="border rounded-lg p-6">
                  <h3 className="text-lg font-semibold text-gray-900 mb-4">Membership & Preferences</h3>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {selectedUser.membership_type && (
                      <div>
                        <label className="text-xs font-medium text-gray-500 uppercase">Membership Type</label>
                        <p className="text-sm text-gray-900 mt-1">{selectedUser.membership_type}</p>
                      </div>
                    )}
                    {selectedUser.preferred_contribution_amount && (
                      <div>
                        <label className="text-xs font-medium text-gray-500 uppercase">Preferred Contribution</label>
                        <p className="text-sm text-gray-900 mt-1">
                          ${(selectedUser.preferred_contribution_amount / 100).toFixed(2)}
                        </p>
                      </div>
                    )}
                    {selectedUser.payment_frequency && (
                      <div>
                        <label className="text-xs font-medium text-gray-500 uppercase">Payment Frequency</label>
                        <p className="text-sm text-gray-900 mt-1 capitalize">{selectedUser.payment_frequency}</p>
                      </div>
                    )}
                    {selectedUser.referral_code && (
                      <div>
                        <label className="text-xs font-medium text-gray-500 uppercase">Referral Code</label>
                        <p className="text-sm font-mono text-gray-900 mt-1">{selectedUser.referral_code}</p>
                      </div>
                    )}
                  </div>
                </div>
              )}

              {/* Activity Summary */}
              <div className="border rounded-lg p-6">
                <h3 className="text-lg font-semibold text-gray-900 mb-4">Activity Summary</h3>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div className="bg-blue-50 rounded-lg p-4">
                    <label className="text-xs font-medium text-gray-500 uppercase">Campaigns Created</label>
                    <p className="text-2xl font-bold text-blue-600 mt-1">{selectedUser.campaigns_count ?? 0}</p>
                  </div>
                  <div className="bg-green-50 rounded-lg p-4">
                    <label className="text-xs font-medium text-gray-500 uppercase">Campaigns Assigned</label>
                    <p className="text-2xl font-bold text-green-600 mt-1">{selectedUser.assigned_campaigns_count ?? 0}</p>
                  </div>
                  <div className="bg-purple-50 rounded-lg p-4">
                    <label className="text-xs font-medium text-gray-500 uppercase">Contributions</label>
                    <p className="text-2xl font-bold text-purple-600 mt-1">{selectedUser.contributions_count ?? 0}</p>
                  </div>
                </div>
              </div>

              {/* Recent Campaigns */}
              {selectedUser.recent_campaigns && selectedUser.recent_campaigns.length > 0 && (
                <div className="border rounded-lg p-6">
                  <h3 className="text-lg font-semibold text-gray-900 mb-4">Recent Campaigns</h3>
                  <div className="space-y-2">
                    {selectedUser.recent_campaigns.map((campaign: any) => (
                      <div key={campaign.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-md">
                        <div>
                          <p className="text-sm font-semibold text-gray-900">{campaign.title}</p>
                          <p className="text-xs text-gray-500">Created: {campaign.created_at}</p>
                        </div>
                        <div className="text-right">
                          <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                            campaign.status === 'active' ? 'bg-green-100 text-green-800' :
                            campaign.status === 'successful' ? 'bg-blue-100 text-blue-800' :
                            'bg-gray-100 text-gray-800'
                          }`}>
                            {campaign.status}
                          </span>
                          <p className="text-xs text-gray-500 mt-1">
                            ${campaign.raised_amount.toFixed(2)} / ${campaign.goal_amount.toFixed(2)}
                          </p>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* Recent Contributions */}
              {selectedUser.recent_contributions && selectedUser.recent_contributions.length > 0 && (
                <div className="border rounded-lg p-6">
                  <h3 className="text-lg font-semibold text-gray-900 mb-4">Recent Contributions</h3>
                  <div className="space-y-2">
                    {selectedUser.recent_contributions.map((contribution: any) => (
                      <div key={contribution.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-md">
                        <div>
                          <p className="text-sm font-semibold text-gray-900">{contribution.campaign_title}</p>
                          <p className="text-xs text-gray-500">{contribution.created_at}</p>
                        </div>
                        <div className="text-right">
                          <p className="text-sm font-semibold text-gray-900">
                            {contribution.currency} ${contribution.amount.toFixed(2)}
                          </p>
                          <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full mt-1 ${
                            contribution.status === 'succeeded' ? 'bg-green-100 text-green-800' :
                            contribution.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                            'bg-gray-100 text-gray-800'
                          }`}>
                            {contribution.status}
                        </span>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* Account Dates */}
              <div className="border rounded-lg p-6">
                <h3 className="text-lg font-semibold text-gray-900 mb-4">Account Dates</h3>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="text-xs font-medium text-gray-500 uppercase">Account Created</label>
                    <p className="text-sm text-gray-900 mt-1">{selectedUser.created_at}</p>
                  </div>
                  <div>
                    <label className="text-xs font-medium text-gray-500 uppercase">Last Updated</label>
                    <p className="text-sm text-gray-900 mt-1">{selectedUser.updated_at}</p>
                  </div>
                  {selectedUser.terms_accepted_at && (
                    <div>
                      <label className="text-xs font-medium text-gray-500 uppercase">Terms Accepted</label>
                      <p className="text-sm text-gray-900 mt-1">{selectedUser.terms_accepted_at}</p>
                    </div>
                  )}
                  {selectedUser.privacy_accepted_at && (
                    <div>
                      <label className="text-xs font-medium text-gray-500 uppercase">Privacy Accepted</label>
                      <p className="text-sm text-gray-900 mt-1">{selectedUser.privacy_accepted_at}</p>
            </div>
          )}
        </div>
      </div>
            </div>

            <div className="sticky bottom-0 bg-gray-50 border-t border-gray-200 px-6 py-4 flex justify-end">
              <button
                onClick={() => setSelectedUser(null)}
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
