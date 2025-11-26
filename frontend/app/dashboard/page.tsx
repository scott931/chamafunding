'use client';

import { useEffect, useState } from 'react';
import Sidebar from '@/components/Sidebar';
import { backerApi } from '@/lib/api/backer';

interface DashboardData {
  summary?: {
    total_projects_backed: number;
    total_amount_pledged: number;
    currency: string;
  };
  active_backing?: Array<{
    id: string;
    campaign: {
      id: string;
      title: string;
      featured_image?: string;
      funding_status: string;
      project_status?: string;
      progress_percentage: number;
      days_remaining?: number;
    };
    pledge: {
      amount: number;
      currency: string;
      date: string;
    };
    reward_tier?: {
      name: string;
    };
    creator?: {
      name: string;
    };
    fulfillment?: {
      delivery_status: string;
      tracking_number?: string;
      tracking_carrier?: string;
    };
  }>;
  action_items?: Array<{
    contribution_id: string;
    type: string;
    title: string;
    message: string;
    action_url: string;
  }>;
  stats?: {
    active_campaigns: number;
    pending_actions: number;
  };
  user?: {
    name: string;
  };
}

export default function DashboardPage() {
  const [loading, setLoading] = useState(true);
  const [dashboardData, setDashboardData] = useState<DashboardData | null>(null);
  const [selectedProject, setSelectedProject] = useState<any>(null);
  const [showProjectModal, setShowProjectModal] = useState(false);

  useEffect(() => {
    loadDashboard();
  }, []);

  const loadDashboard = async () => {
    try {
      const response = await backerApi.getDashboard();
      setDashboardData(response.data);
    } catch (error) {
      console.error('Failed to load dashboard:', error);
    } finally {
      setLoading(false);
    }
  };

  const formatCurrency = (amount: number | string, currency: string = 'USD') => {
    if (!amount && amount !== 0) return '$0.00';
    const numAmount = typeof amount === 'string'
      ? parseFloat(amount.replace(/,/g, '')) / 100
      : parseFloat(String(amount)) / 100;
    if (isNaN(numAmount)) return '$0.00';
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: currency,
    }).format(numAmount);
  };

  const getFundingStatusBadgeClass = (status: string) => {
    const classes: Record<string, string> = {
      'live': 'bg-green-500 text-white',
      'successful': 'bg-blue-500 text-white',
      'unsuccessful': 'bg-red-500 text-white',
      'active': 'bg-green-500 text-white',
      'failed': 'bg-red-500 text-white',
    };
    return classes[status?.toLowerCase()] || 'bg-gray-500 text-white';
  };

  const getFundingStatusLabel = (status: string) => {
    const labels: Record<string, string> = {
      'live': 'Live',
      'successful': 'Funded',
      'unsuccessful': 'Unsuccessful',
      'active': 'Live',
      'failed': 'Failed',
    };
    return labels[status?.toLowerCase()] || status || 'Unknown';
  };

  const getProjectStatusBadgeClass = (status: string) => {
    const classes: Record<string, string> = {
      'in_production': 'bg-yellow-100 text-yellow-800',
      'shipping': 'bg-blue-100 text-blue-800',
      'delivered': 'bg-green-100 text-green-800',
      'unsuccessful': 'bg-red-100 text-red-800',
      'pending': 'bg-gray-100 text-gray-800',
    };
    return classes[status?.toLowerCase()] || 'bg-gray-100 text-gray-800';
  };

  const getProjectStatusLabel = (status: string) => {
    const labels: Record<string, string> = {
      'in_production': 'In Production',
      'shipping': 'Shipping',
      'delivered': 'Delivered',
      'unsuccessful': 'Unsuccessful',
      'pending': 'Pending',
    };
    return labels[status?.toLowerCase()] || status || 'Unknown';
  };

  const getUserGreeting = () => {
    if (dashboardData?.user) {
      const firstName = dashboardData.user.name.split(' ')[0];
      return `Hello, ${firstName}!`;
    }
    return 'Dashboard';
  };

  return (
    <Sidebar>
      <div className="py-4">
        <div className="mb-6 px-4 sm:px-6 lg:px-8">
          <div className="max-w-7xl mx-auto">
            <div className="flex items-center justify-between">
              <div>
                <h2 className="text-2xl font-bold text-gray-900">
                  {getUserGreeting()}
                </h2>
                <p className="text-sm text-gray-500 mt-1">Manage your contributions and track your impact</p>
              </div>
            </div>
          </div>
        </div>

        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
          {loading ? (
            <div className="flex items-center justify-center py-20">
              <div className="text-center">
                <div className="inline-block animate-spin rounded-full h-12 w-12 border-4 border-blue-600 border-t-transparent mb-4"></div>
                <p className="text-gray-500">Loading your dashboard...</p>
              </div>
            </div>
          ) : (
            <>
              {/* Stats Cards */}
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-6 lg:gap-8">
                <div className="bg-gradient-to-br from-blue-500 to-cyan-600 rounded-2xl p-8 text-white shadow-xl shadow-blue-500/30 hover:shadow-2xl hover:shadow-blue-500/40 transition-all duration-300 transform hover:scale-[1.02] border border-blue-400/20">
                  <div className="flex items-start justify-between mb-4">
                    <div className="flex-1">
                      <p className="text-sm font-semibold text-white/90 uppercase tracking-wide mb-3">Projects Backed</p>
                      <p className="text-4xl lg:text-5xl font-bold text-white mb-2">
                        {dashboardData?.summary?.total_projects_backed ?? 0}
                      </p>
                    </div>
                    <div className="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center flex-shrink-0 ml-4">
                      <svg className="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                      </svg>
                    </div>
                  </div>
                </div>

                <div className="bg-gradient-to-br from-emerald-500 to-green-600 rounded-2xl p-8 text-white shadow-xl shadow-emerald-500/30 hover:shadow-2xl hover:shadow-emerald-500/40 transition-all duration-300 transform hover:scale-[1.02] border border-emerald-400/20">
                  <div className="flex items-start justify-between mb-4">
                    <div className="flex-1">
                      <p className="text-sm font-semibold text-white/90 uppercase tracking-wide mb-3">Total Pledged</p>
                      <p className="text-4xl lg:text-5xl font-bold text-white mb-2">
                        {dashboardData?.summary
                          ? formatCurrency(dashboardData.summary.total_amount_pledged, dashboardData.summary.currency)
                          : '$0.00'}
                      </p>
                    </div>
                    <div className="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center flex-shrink-0 ml-4">
                      <svg className="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                    </div>
                  </div>
                </div>
              </div>

              {/* Action Required Banner */}
              {dashboardData?.action_items && dashboardData.action_items.length > 0 && (
                <div className="bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 rounded-xl p-6">
                  <div className="flex items-start gap-4">
                    <div className="flex-shrink-0">
                      <div className="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center">
                        <svg className="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                      </div>
                    </div>
                    <div className="flex-1">
                      <h3 className="text-lg font-semibold text-gray-900 mb-2">Action Required</h3>
                      <div className="space-y-3">
                        {dashboardData.action_items.map((action) => (
                          <div key={action.contribution_id + '_' + action.type} className="bg-white rounded-lg p-4 border border-amber-200">
                            <div className="flex items-start justify-between gap-4">
                              <div className="flex-1">
                                <p className="font-medium text-gray-900 mb-1">{action.title}</p>
                                <p className="text-sm text-gray-600 mb-3">{action.message}</p>
                                <a
                                  href={action.action_url}
                                  className="inline-flex items-center gap-2 px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 text-sm font-medium transition-colors"
                                >
                                  Take Action
                                  <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7" />
                                  </svg>
                                </a>
                              </div>
                            </div>
                          </div>
                        ))}
                      </div>
                    </div>
                  </div>
                </div>
              )}

              {/* Active Projects Section */}
              <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div className="px-6 py-5 border-b border-gray-100">
                  <h2 className="text-xl font-semibold text-gray-900">Your Backed Campaigns</h2>
                  <p className="text-sm text-gray-500 mt-1">All projects you&apos;re supporting</p>
                </div>

                {!dashboardData?.active_backing || dashboardData.active_backing.length === 0 ? (
                  <div className="text-center py-16 px-6">
                    <div className="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                      <svg className="w-10 h-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                      </svg>
                    </div>
                    <h3 className="text-lg font-medium text-gray-900 mb-2">No projects yet</h3>
                    <p className="text-sm text-gray-500 mb-6">Start supporting campaigns and track their progress here.</p>
                    <a
                      href="/campaigns"
                      className="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium transition-colors"
                    >
                      <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                      </svg>
                      Browse Campaigns
                    </a>
                  </div>
                ) : (
                  <>
                    {/* Mobile Card View */}
                    <div className="block md:hidden space-y-4">
                      {dashboardData.active_backing.map((project) => (
                        <div
                          key={project.id}
                          className="bg-white rounded-lg border border-gray-200 p-4 shadow-sm hover:shadow-md transition-shadow cursor-pointer"
                          onClick={() => {
                            setSelectedProject(project);
                            setShowProjectModal(true);
                          }}
                        >
                          <div className="flex items-start gap-3 mb-3">
                            <div className="w-16 h-16 rounded-lg object-cover flex-shrink-0 bg-gray-200 flex items-center justify-center overflow-hidden relative">
                              {project.campaign.featured_image ? (
                                <img
                                  src={project.campaign.featured_image}
                                  alt={project.campaign.title}
                                  className="w-full h-full object-cover"
                                  onError={(e) => {
                                    (e.target as HTMLImageElement).style.display = 'none';
                                  }}
                                />
                              ) : (
                                <div className="w-full h-full flex items-center justify-center absolute inset-0">
                                  <svg className="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                  </svg>
                                </div>
                              )}
                            </div>
                            <div className="flex-1 min-w-0">
                              <h3 className="text-base font-semibold text-gray-900 mb-1 line-clamp-2">{project.campaign.title}</h3>
                              {project.creator && (
                                <p className="text-xs text-gray-500 mb-1">
                                  by <span className="font-medium">{project.creator.name || 'Unknown'}</span>
                                </p>
                              )}
                              <p className="text-xs text-gray-400">{project.reward_tier?.name || 'General Contribution'}</p>
                            </div>
                          </div>
                          
                          <div className="space-y-2 mb-3">
                            <div className="flex items-center justify-between">
                              <span className="text-xs text-gray-500">Pledge</span>
                              <span className="text-sm font-bold text-blue-600">
                                {formatCurrency(project.pledge.amount, project.pledge.currency)}
                              </span>
                            </div>
                            <div className="flex items-center justify-between">
                              <span className="text-xs text-gray-500">Date</span>
                              <span className="text-xs text-gray-600">
                                {new Date(project.pledge.date).toLocaleDateString()}
                              </span>
                            </div>
                            <div className="flex items-center justify-between">
                              <span className="text-xs text-gray-500">Funding Status</span>
                              <span
                                className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${getFundingStatusBadgeClass(project.campaign.funding_status)}`}
                              >
                                {getFundingStatusLabel(project.campaign.funding_status)}
                              </span>
                            </div>
                            <div className="flex items-center justify-between">
                              <span className="text-xs text-gray-500">Project Status</span>
                              <span
                                className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${getProjectStatusBadgeClass(project.campaign.project_status || 'pending')}`}
                              >
                                {getProjectStatusLabel(project.campaign.project_status || 'pending')}
                              </span>
                            </div>
                            {project.campaign.funding_status === 'live' && project.campaign.progress_percentage !== undefined && (
                              <div>
                                <div className="flex justify-between text-xs mb-1">
                                  <span className="text-gray-600">Progress</span>
                                  <span className="font-semibold text-gray-900">{project.campaign.progress_percentage}%</span>
                                </div>
                                <div className="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                                  <div
                                    className="bg-gradient-to-r from-blue-500 to-blue-600 h-full rounded-full transition-all duration-500"
                                    style={{ width: `${project.campaign.progress_percentage}%` }}
                                  />
                                </div>
                              </div>
                            )}
                            {project.campaign.days_remaining !== null && project.campaign.funding_status === 'live' && (
                              <p className="text-xs text-gray-500 text-right">{project.campaign.days_remaining} days left</p>
                            )}
                          </div>
                          
                          <a
                            href={`/campaigns/${project.campaign.id}`}
                            onClick={(e) => e.stopPropagation()}
                            className="block w-full text-center px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors"
                          >
                            View Campaign
                          </a>
                        </div>
                      ))}
                    </div>

                    {/* Desktop Table View */}
                    <div className="hidden md:block overflow-x-auto table-responsive">
                    <table className="min-w-full divide-y divide-gray-200">
                      <thead className="bg-gray-50">
                        <tr>
                          <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Project</th>
                          <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pledge</th>
                          <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Funding Status</th>
                          <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Project Status</th>
                          <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progress</th>
                          <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                      </thead>
                      <tbody className="bg-white divide-y divide-gray-200">
                        {dashboardData.active_backing.map((project) => (
                          <tr
                            key={project.id}
                            className="hover:bg-gray-50 transition-colors cursor-pointer"
                            onClick={() => {
                              setSelectedProject(project);
                              setShowProjectModal(true);
                            }}
                          >
                            <td className="px-6 py-4 whitespace-nowrap">
                              <div className="flex items-center gap-3">
                                <div className="w-12 h-12 rounded-lg object-cover flex-shrink-0 bg-gray-200 flex items-center justify-center overflow-hidden relative">
                                  {project.campaign.featured_image ? (
                                    <img
                                      src={project.campaign.featured_image}
                                      alt={project.campaign.title}
                                      className="w-full h-full object-cover"
                                      onError={(e) => {
                                        (e.target as HTMLImageElement).style.display = 'none';
                                      }}
                                    />
                                  ) : (
                                    <div className="w-full h-full flex items-center justify-center absolute inset-0">
                                      <svg className="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                      </svg>
                                    </div>
                                  )}
                                </div>
                                <div className="min-w-0">
                                  <p className="text-sm font-semibold text-gray-900 truncate max-w-xs">{project.campaign.title}</p>
                                  {project.creator && (
                                    <p className="text-xs text-gray-500">
                                      by <span className="font-medium">{project.creator.name || 'Unknown'}</span>
                                    </p>
                                  )}
                                  <p className="text-xs text-gray-400 mt-0.5">{project.reward_tier?.name || 'General Contribution'}</p>
                                </div>
                              </div>
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap">
                              <p className="text-sm font-bold text-blue-600">
                                {formatCurrency(project.pledge.amount, project.pledge.currency)}
                              </p>
                              <p className="text-xs text-gray-500 mt-0.5">
                                {new Date(project.pledge.date).toLocaleDateString()}
                              </p>
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap">
                              <span
                                className={`inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium ${getFundingStatusBadgeClass(project.campaign.funding_status)}`}
                              >
                                {getFundingStatusLabel(project.campaign.funding_status)}
                              </span>
                              {project.campaign.days_remaining !== null && project.campaign.funding_status === 'live' && (
                                <p className="text-xs text-gray-500 mt-1">{project.campaign.days_remaining} days left</p>
                              )}
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap">
                              <span
                                className={`inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium ${getProjectStatusBadgeClass(project.campaign.project_status || 'pending')}`}
                              >
                                {getProjectStatusLabel(project.campaign.project_status || 'pending')}
                              </span>
                            </td>
                            <td className="px-6 py-4">
                              <div className="flex items-center gap-3">
                                <div className="flex-1 min-w-[80px]">
                                  {project.campaign.funding_status === 'live' && (
                                    <>
                                      <div className="flex justify-between text-xs mb-1">
                                        <span className="text-gray-600">Progress</span>
                                        <span className="font-semibold text-gray-900">{project.campaign.progress_percentage}%</span>
                                      </div>
                                      <div className="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                                        <div
                                          className="bg-gradient-to-r from-blue-500 to-blue-600 h-full rounded-full transition-all duration-500"
                                          style={{ width: `${project.campaign.progress_percentage}%` }}
                                        />
                                      </div>
                                    </>
                                  )}
                                  {project.campaign.funding_status !== 'live' && (
                                    <p className="text-xs text-gray-400 mt-1">—</p>
                                  )}
                                </div>
                              </div>
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                              <a
                                href={`/campaigns/${project.campaign.id}`}
                                onClick={(e) => e.stopPropagation()}
                                className="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800 font-medium"
                              >
                                View
                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7" />
                                </svg>
                              </a>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                  </>
                )}
              </div>

              {/* Quick Stats */}
              <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 className="text-xl font-semibold text-gray-900 mb-4">Quick Stats</h2>
                <div className="space-y-4">
                  <div className="flex justify-between items-center pb-3 border-b border-gray-100">
                    <span className="text-sm text-gray-600">Total Pledges</span>
                    <span className="text-lg font-semibold text-gray-900">{dashboardData?.summary?.total_projects_backed ?? 0}</span>
                  </div>
                  <div className="flex justify-between items-center pb-3 border-b border-gray-100">
                    <span className="text-sm text-gray-600">Active Campaigns</span>
                    <span className="text-lg font-semibold text-gray-900">{dashboardData?.stats?.active_campaigns ?? 0}</span>
                  </div>
                  <div className="flex justify-between items-center">
                    <span className="text-sm text-gray-600">Pending Actions</span>
                    <span className="text-lg font-semibold text-gray-900">{dashboardData?.stats?.pending_actions ?? 0}</span>
                  </div>
                </div>
              </div>
            </>
          )}
        </div>

        {/* Project Details Modal */}
        {showProjectModal && selectedProject && (
          <div
            className="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4 sm:p-6"
            onClick={() => setShowProjectModal(false)}
          >
            <div
              className="bg-white rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto shadow-2xl mx-2 sm:mx-4"
              onClick={(e) => e.stopPropagation()}
            >
              <div className="sticky top-0 bg-white border-b border-gray-200 px-4 sm:px-6 py-3 sm:py-4 flex items-center justify-between z-10">
                <h3 className="text-lg sm:text-xl font-bold text-gray-900">Project Details</h3>
                <button
                  onClick={() => setShowProjectModal(false)}
                  className="text-gray-400 hover:text-gray-600 transition-colors touch-target p-1"
                >
                  <svg className="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
              <div className="p-4 sm:p-6">
                <div className="space-y-6">
                  {selectedProject.campaign.featured_image && (
                    <div>
                      <img
                        src={selectedProject.campaign.featured_image}
                        alt={selectedProject.campaign.title}
                        className="w-full h-48 object-cover rounded-lg"
                      />
                    </div>
                  )}
                  <div>
                    <h4 className="text-sm font-medium text-gray-500 mb-1">Campaign</h4>
                    <p className="text-lg font-semibold text-gray-900">{selectedProject.campaign.title}</p>
                  </div>
                  <div>
                    <h4 className="text-sm font-medium text-gray-500 mb-2">Project Status</h4>
                    <span
                      className={`inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium ${getProjectStatusBadgeClass(selectedProject.campaign.project_status || 'pending')}`}
                    >
                      {getProjectStatusLabel(selectedProject.campaign.project_status || 'pending')}
                    </span>
                  </div>
                  <div>
                    <h4 className="text-sm font-medium text-gray-500 mb-1">Your Pledge</h4>
                    <p className="text-3xl font-bold text-blue-600">
                      {formatCurrency(selectedProject.pledge.amount, selectedProject.pledge.currency)}
                    </p>
                  </div>
                  {selectedProject.reward_tier && (
                    <div>
                      <h4 className="text-sm font-medium text-gray-500 mb-1">Reward Tier</h4>
                      <p className="text-lg font-medium text-gray-900">{selectedProject.reward_tier.name}</p>
                    </div>
                  )}
                  <div>
                    <h4 className="text-sm font-medium text-gray-500 mb-2">Fulfillment Status</h4>
                    <span className="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium bg-gray-100 text-gray-800">
                      {selectedProject.fulfillment?.delivery_status || 'pending'}
                    </span>
                  </div>
                  <div className="flex flex-col sm:flex-row gap-3 pt-4 border-t border-gray-200">
                    <a
                      href={`/campaigns/${selectedProject.campaign.id}`}
                      className="flex-1 text-center px-4 sm:px-6 py-2.5 sm:py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium transition-colors touch-target"
                    >
                      View Full Project
                    </a>
                    <button
                      onClick={() => setShowProjectModal(false)}
                      className="px-4 sm:px-6 py-2.5 sm:py-3 border border-gray-300 rounded-lg hover:bg-gray-50 font-medium text-gray-700 transition-colors touch-target"
                    >
                      Close
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    </Sidebar>
  );
}

