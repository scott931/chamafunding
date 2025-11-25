'use client';

import { FormEvent, useCallback, useEffect, useMemo, useState } from 'react';
import Sidebar from '@/components/Sidebar';
import { campaignsApi, CampaignPayload } from '@/lib/api/campaigns';

const defaultForm: CampaignPayload = {
  title: '',
  category: 'project',
  description: '',
  goal_amount: 1000,
  currency: 'USD',
  deadline: '',
  featured_image: '',
  images: [],
};

const statusStyles: Record<string, string> = {
  draft: 'bg-yellow-100 text-yellow-800',
  pending: 'bg-yellow-100 text-yellow-800',
  active: 'bg-green-100 text-green-800',
  successful: 'bg-green-100 text-green-800',
  closed: 'bg-gray-200 text-gray-800',
  failed: 'bg-red-100 text-red-800',
  suspended: 'bg-red-100 text-red-800',
};

export default function AdminCampaignsPage() {
  const [campaigns, setCampaigns] = useState<any[]>([]);
  const [pagination, setPagination] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [filters, setFilters] = useState({ status: '', search: '' });
  const [filterDraft, setFilterDraft] = useState(filters);
  const [formData, setFormData] = useState<CampaignPayload>(defaultForm);
  const [creating, setCreating] = useState(false);
  const [activatingId, setActivatingId] = useState<number | null>(null);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [editingCampaign, setEditingCampaign] = useState<any>(null);
  const [updating, setUpdating] = useState(false);
  const [feedback, setFeedback] = useState<{ type: 'success' | 'error'; message: string } | null>(null);

  const fetchCampaigns = useCallback(async () => {
    setLoading(true);
    try {
      const response = await campaignsApi.list({
        status: filters.status || undefined,
        search: filters.search || undefined,
        per_page: 20,
      });

      const payload = response?.data ?? response;
      setPagination(payload);
      setCampaigns(payload?.data ?? []);
    } catch (error) {
      console.error('Failed to fetch campaigns:', error);
      setFeedback({ type: 'error', message: 'Failed to load campaigns' });
    } finally {
      setLoading(false);
    }
  }, [filters]);

  useEffect(() => {
    fetchCampaigns();
  }, [fetchCampaigns]);

  const resetForm = () => {
    setFormData(defaultForm);
  };

  const handleCreate = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setCreating(true);
    setFeedback(null);
    try {
      await campaignsApi.create({
        ...formData,
        goal_amount: Number(formData.goal_amount),
      });
      setFeedback({ type: 'success', message: 'Campaign created as draft' });
      resetForm();
      await fetchCampaigns();
    } catch (error: any) {
      console.error('Failed to create campaign:', error);
      const message =
        error?.response?.data?.message ??
        'Unable to create campaign. Please review the form fields.';
      setFeedback({ type: 'error', message });
    } finally {
      setCreating(false);
    }
  };

  const handleActivate = async (id: number) => {
    setActivatingId(id);
    setFeedback(null);
    try {
      await campaignsApi.activate(id);
      setFeedback({ type: 'success', message: 'Campaign activated successfully' });
      await fetchCampaigns();
    } catch (error: any) {
      console.error('Failed to activate campaign:', error);
      const message =
        error?.response?.data?.message ?? 'Unable to activate campaign.';
      setFeedback({ type: 'error', message });
    } finally {
      setActivatingId(null);
    }
  };

  const handleEdit = async (campaign: any) => {
    setEditingId(campaign.id);
    setFeedback(null);
    try {
      const response = await campaignsApi.get(campaign.id);
      const campaignData = response?.data ?? response;
      setEditingCampaign({
        ...campaignData,
        goal_amount: campaignData.goal_amount ? campaignData.goal_amount / 100 : 0,
        featured_image: campaignData.featured_image || '',
        images: campaignData.images || [],
      });
    } catch (error: any) {
      console.error('Failed to fetch campaign:', error);
      setFeedback({ type: 'error', message: 'Failed to load campaign for editing' });
      setEditingId(null);
    }
  };

  const handleUpdate = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!editingCampaign) return;

    setUpdating(true);
    setFeedback(null);
    try {
      const updatePayload: Partial<CampaignPayload> = {
        title: editingCampaign.title,
        category: editingCampaign.category,
        description: editingCampaign.description,
        goal_amount: Number(editingCampaign.goal_amount),
        currency: editingCampaign.currency,
        deadline: editingCampaign.deadline || undefined,
        featured_image: editingCampaign.featured_image || undefined,
        images: editingCampaign.images && editingCampaign.images.length > 0 ? editingCampaign.images : undefined,
      };

      await campaignsApi.update(editingCampaign.id, updatePayload);
      setFeedback({ type: 'success', message: 'Campaign updated successfully' });
      setEditingId(null);
      setEditingCampaign(null);
      await fetchCampaigns();
    } catch (error: any) {
      console.error('Failed to update campaign:', error);
      const message =
        error?.response?.data?.message ??
        'Unable to update campaign. Please review the form fields.';
      setFeedback({ type: 'error', message });
    } finally {
      setUpdating(false);
    }
  };

  const handleCancelEdit = () => {
    setEditingId(null);
    setEditingCampaign(null);
  };

  const appliedStatus = useMemo(() => filters.status || 'all', [filters.status]);

  return (
    <Sidebar>
      <div className="min-h-screen bg-gray-50 py-8">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
          <div>
            <h1 className="text-3xl font-bold text-gray-900">Campaign Management</h1>
            <p className="mt-2 text-sm text-gray-600">
              Create new campaigns, manage drafts, and activate campaigns for the public.
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

          <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <section className="bg-white rounded-xl shadow-sm border border-gray-100 p-6 lg:col-span-2">
              <header className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                  <h2 className="text-xl font-semibold text-gray-900">Campaigns</h2>
                  <p className="text-sm text-gray-500">
                    Currently showing {campaigns.length}{' '}
                    {appliedStatus !== 'all' ? `'${appliedStatus}'` : ''} campaigns.
                  </p>
                </div>
                <form
                  className="flex flex-col sm:flex-row gap-3"
                  onSubmit={(event) => {
                    event.preventDefault();
                    setFilters(filterDraft);
                  }}
                >
                  <select
                    className="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                    value={filterDraft.status}
                    onChange={(event) =>
                      setFilterDraft((prev) => ({ ...prev, status: event.target.value }))
                    }
                  >
                    <option value="">All statuses</option>
                    <option value="draft">Draft</option>
                    <option value="active">Active</option>
                    <option value="successful">Successful</option>
                    <option value="failed">Failed</option>
                    <option value="closed">Closed</option>
                    <option value="suspended">Suspended</option>
                  </select>
                  <input
                    type="text"
                    placeholder="Search title or description"
                    className="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                    value={filterDraft.search}
                    onChange={(event) =>
                      setFilterDraft((prev) => ({ ...prev, search: event.target.value }))
                    }
                  />
                  <button
                    type="submit"
                    className="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition-colors"
                  >
                    Apply
                  </button>
                </form>
              </header>

              <div className="overflow-hidden border border-gray-100 rounded-xl">
                {loading ? (
                  <div className="py-16 flex justify-center">
                    <div className="text-center">
                      <div className="inline-block h-10 w-10 animate-spin rounded-full border-4 border-indigo-500 border-t-transparent" />
                      <p className="mt-4 text-sm text-gray-500">Loading campaigns…</p>
                    </div>
                  </div>
                ) : campaigns.length === 0 ? (
                  <div className="py-16 text-center text-gray-500">
                    No campaigns match the selected filters.
                  </div>
                ) : (
                  <ul className="divide-y divide-gray-100">
                    {campaigns.map((campaign) => (
                      <li key={campaign.id} className="p-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                          <p className="text-base font-semibold text-gray-900">{campaign.title}</p>
                          <p className="mt-1 text-sm text-gray-600 line-clamp-2">{campaign.description}</p>
                          <div className="mt-2 text-xs text-gray-500 flex flex-wrap gap-2">
                            <span>Category: {campaign.category}</span>
                            <span>
                              Goal:{' '}
                              {campaign.goal_amount
                                ? `${(campaign.goal_amount / 100).toLocaleString(undefined, {
                                    style: 'currency',
                                    currency: campaign.currency ?? 'USD',
                                  })}`
                                : 'N/A'}
                            </span>
                          </div>
                        </div>
                        <div className="flex items-center gap-3">
                          <span
                            className={`inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full ${
                              statusStyles[campaign.status] ?? 'bg-slate-100 text-slate-800'
                            }`}
                          >
                            {campaign.status ?? 'unknown'}
                          </span>
                          <button
                            onClick={() => handleEdit(campaign)}
                            className="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 disabled:opacity-60"
                            disabled={editingId === campaign.id || updating}
                          >
                            Edit
                          </button>
                          {campaign.status === 'draft' && (
                            <button
                              onClick={() => handleActivate(campaign.id)}
                              className="inline-flex items-center rounded-lg border border-transparent bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-60"
                              disabled={activatingId === campaign.id}
                            >
                              {activatingId === campaign.id ? 'Activating…' : 'Activate'}
                            </button>
                          )}
                        </div>
                      </li>
                    ))}
                  </ul>
                )}
              </div>
              {pagination && (
                <p className="mt-4 text-sm text-gray-500">
                  Page {pagination.current_page} of {pagination.last_page ?? '?'} — {pagination.total}{' '}
                  total campaigns.
                </p>
              )}
            </section>

            <section className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
              <h2 className="text-xl font-semibold text-gray-900">Create Campaign</h2>
              <p className="text-sm text-gray-500 mb-4">
                New campaigns start as drafts. Activate them once you are ready to launch.
              </p>
              <form className="space-y-4" onSubmit={handleCreate}>
                <div>
                  <label className="text-sm font-medium text-gray-700">Title</label>
                  <input
                    required
                    value={formData.title}
                    onChange={(event) => setFormData({ ...formData, title: event.target.value })}
                    className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    placeholder="Campaign title"
                  />
                </div>

                <div>
                  <label className="text-sm font-medium text-gray-700">Category</label>
                  <select
                    value={formData.category}
                    onChange={(event) => setFormData({ ...formData, category: event.target.value })}
                    className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                  >
                    <option value="project">Project</option>
                    <option value="community">Community</option>
                    <option value="education">Education</option>
                    <option value="health">Health</option>
                    <option value="emergency">Emergency</option>
                    <option value="environment">Environment</option>
                  </select>
                </div>

                <div>
                  <label className="text-sm font-medium text-gray-700">Description</label>
                  <textarea
                    required
                    minLength={50}
                    value={formData.description}
                    onChange={(event) => setFormData({ ...formData, description: event.target.value })}
                    className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    rows={4}
                    placeholder="Describe the campaign (minimum 50 characters)"
                  />
                </div>

                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <label className="text-sm font-medium text-gray-700">Goal Amount</label>
                    <input
                      type="number"
                      min={100}
                      required
                      value={formData.goal_amount}
                      onChange={(event) =>
                        setFormData({ ...formData, goal_amount: Number(event.target.value) })
                      }
                      className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                  </div>
                  <div>
                    <label className="text-sm font-medium text-gray-700">Currency</label>
                    <input
                      value={formData.currency}
                      onChange={(event) =>
                        setFormData({ ...formData, currency: event.target.value.toUpperCase() })
                      }
                      maxLength={3}
                      className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm uppercase focus:border-indigo-500 focus:ring-indigo-500"
                    />
                  </div>
                </div>

                <div>
                  <label className="text-sm font-medium text-gray-700">Deadline (optional)</label>
                  <input
                    type="date"
                    value={formData.deadline ?? ''}
                    onChange={(event) => setFormData({ ...formData, deadline: event.target.value })}
                    className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                  />
                </div>

                <div>
                  <label className="text-sm font-medium text-gray-700">
                    Featured Image URL (optional)
                  </label>
                  <input
                    type="url"
                    value={formData.featured_image ?? ''}
                    onChange={(event) => setFormData({ ...formData, featured_image: event.target.value })}
                    className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    placeholder="https://example.com/image.jpg"
                  />
                  <p className="mt-1 text-xs text-gray-500">
                    Enter a URL to an image for the campaign featured image
                  </p>
                </div>

                <div>
                  <label className="text-sm font-medium text-gray-700">
                    Additional Images (optional)
                  </label>
                  <textarea
                    value={(formData.images || []).join('\n')}
                    onChange={(event) => {
                      const urls = event.target.value
                        .split('\n')
                        .map((url) => url.trim())
                        .filter((url) => url.length > 0);
                      setFormData({ ...formData, images: urls });
                    }}
                    className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    rows={3}
                    placeholder="Enter image URLs, one per line&#10;https://example.com/image1.jpg&#10;https://example.com/image2.jpg"
                  />
                  <p className="mt-1 text-xs text-gray-500">
                    Enter image URLs, one per line
                  </p>
                </div>

                <button
                  type="submit"
                  className="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-60"
                  disabled={creating}
                >
                  {creating ? 'Creating…' : 'Create Draft Campaign'}
                </button>
              </form>
            </section>
          </div>

          {/* Edit Campaign Modal */}
          {editingCampaign && (
            <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
              <div className="bg-white rounded-xl shadow-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div className="p-6">
                  <div className="flex items-center justify-between mb-4">
                    <h2 className="text-xl font-semibold text-gray-900">Edit Campaign</h2>
                    <button
                      onClick={handleCancelEdit}
                      className="text-gray-400 hover:text-gray-600"
                    >
                      <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                      </svg>
                    </button>
                  </div>

                  <form className="space-y-4" onSubmit={handleUpdate}>
                    <div>
                      <label className="text-sm font-medium text-gray-700">Title</label>
                      <input
                        required
                        value={editingCampaign.title}
                        onChange={(event) =>
                          setEditingCampaign({ ...editingCampaign, title: event.target.value })
                        }
                        className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Campaign title"
                      />
                    </div>

                    <div>
                      <label className="text-sm font-medium text-gray-700">Category</label>
                      <select
                        value={editingCampaign.category}
                        onChange={(event) =>
                          setEditingCampaign({ ...editingCampaign, category: event.target.value })
                        }
                        className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                      >
                        <option value="project">Project</option>
                        <option value="community">Community</option>
                        <option value="education">Education</option>
                        <option value="health">Health</option>
                        <option value="emergency">Emergency</option>
                        <option value="environment">Environment</option>
                      </select>
                    </div>

                    <div>
                      <label className="text-sm font-medium text-gray-700">Description</label>
                      <textarea
                        required
                        minLength={10}
                        value={editingCampaign.description}
                        onChange={(event) =>
                          setEditingCampaign({ ...editingCampaign, description: event.target.value })
                        }
                        className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        rows={4}
                        placeholder="Describe the campaign (minimum 10 characters)"
                      />
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                      <div>
                        <label className="text-sm font-medium text-gray-700">Goal Amount</label>
                        <input
                          type="number"
                          min={100}
                          required
                          value={editingCampaign.goal_amount}
                          onChange={(event) =>
                            setEditingCampaign({
                              ...editingCampaign,
                              goal_amount: Number(event.target.value),
                            })
                          }
                          className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                      </div>
                      <div>
                        <label className="text-sm font-medium text-gray-700">Currency</label>
                        <input
                          value={editingCampaign.currency}
                          onChange={(event) =>
                            setEditingCampaign({
                              ...editingCampaign,
                              currency: event.target.value.toUpperCase(),
                            })
                          }
                          maxLength={3}
                          className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm uppercase focus:border-indigo-500 focus:ring-indigo-500"
                        />
                      </div>
                    </div>

                    <div>
                      <label className="text-sm font-medium text-gray-700">Deadline (optional)</label>
                      <input
                        type="date"
                        value={
                          editingCampaign.deadline
                            ? new Date(editingCampaign.deadline).toISOString().split('T')[0]
                            : ''
                        }
                        onChange={(event) =>
                          setEditingCampaign({ ...editingCampaign, deadline: event.target.value })
                        }
                        className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                      />
                    </div>

                    <div>
                      <label className="text-sm font-medium text-gray-700">
                        Featured Image URL (optional)
                      </label>
                      <input
                        type="url"
                        value={editingCampaign.featured_image || ''}
                        onChange={(event) =>
                          setEditingCampaign({ ...editingCampaign, featured_image: event.target.value })
                        }
                        className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="https://example.com/image.jpg"
                      />
                      <p className="mt-1 text-xs text-gray-500">
                        Enter a URL to an image for the campaign featured image
                      </p>
                    </div>

                    <div>
                      <label className="text-sm font-medium text-gray-700">
                        Additional Images (optional)
                      </label>
                      <textarea
                        value={(editingCampaign.images || []).join('\n')}
                        onChange={(event) => {
                          const urls = event.target.value
                            .split('\n')
                            .map((url: string) => url.trim())
                            .filter((url: string) => url.length > 0);
                          setEditingCampaign({ ...editingCampaign, images: urls });
                        }}
                        className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        rows={3}
                        placeholder="Enter image URLs, one per line&#10;https://example.com/image1.jpg&#10;https://example.com/image2.jpg"
                      />
                      <p className="mt-1 text-xs text-gray-500">
                        Enter image URLs, one per line
                      </p>
                    </div>

                    <div className="flex gap-3 pt-4">
                      <button
                        type="button"
                        onClick={handleCancelEdit}
                        className="flex-1 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                      >
                        Cancel
                      </button>
                      <button
                        type="submit"
                        className="flex-1 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-60"
                        disabled={updating}
                      >
                        {updating ? 'Updating…' : 'Save Changes'}
                      </button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          )}
        </div>
      </div>
    </Sidebar>
  );
}
