'use client';

import { useEffect, useState } from 'react';
import Sidebar from '@/components/Sidebar';
import Link from 'next/link';
import { campaignsApi } from '@/lib/api/campaigns';

export default function CampaignsPage() {
  const [campaigns, setCampaigns] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchCampaigns = async () => {
      try {
        const response = await campaignsApi.list({ per_page: 50 });
        // Handle paginated response - Laravel returns { success: true, data: { data: [...], ...pagination } }
        const paginatedData = response?.data || response;
        const campaignsData = paginatedData?.data || [];
        setCampaigns(Array.isArray(campaignsData) ? campaignsData : []);
      } catch (error: any) {
        console.error('Failed to fetch campaigns:', error);
        // If error response has data, try to use it
        if (error?.response?.data?.data?.data) {
          setCampaigns(error.response.data.data.data);
        } else {
          setCampaigns([]);
        }
      } finally {
        setLoading(false);
      }
    };
    fetchCampaigns();
  }, []);

  if (loading) {
    return (
      <Sidebar>
        <div className="min-h-screen flex items-center justify-center">
          <div className="text-center">
            <div className="inline-block animate-spin rounded-full h-12 w-12 border-4 border-blue-600 border-t-transparent mb-4"></div>
            <p className="text-gray-600">Loading campaigns...</p>
          </div>
        </div>
      </Sidebar>
    );
  }

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(amount / 100);
  };

  return (
    <Sidebar>
      <div className="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50/30 to-purple-50/30 py-8">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="mb-8">
            <h1 className="text-4xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
              Discover Campaigns
            </h1>
            <p className="mt-2 text-gray-600">Explore and support amazing projects</p>
          </div>
          {campaigns.length === 0 ? (
            <div className="bg-white rounded-xl shadow-lg p-12 text-center border border-gray-100">
              <div className="inline-block p-4 bg-gray-100 rounded-full mb-4">
                <svg className="w-12 h-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
              </div>
              <p className="text-gray-500 text-lg font-medium">No campaigns available</p>
              <p className="text-gray-400 text-sm mt-1">Check back later for new campaigns</p>
            </div>
          ) : (
            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
              {campaigns.map((campaign) => {
                const progress = campaign.goal_amount > 0 
                  ? Math.min(100, ((campaign.raised_amount || 0) / campaign.goal_amount) * 100)
                  : 0;
                
                return (
                  <Link
                    key={campaign.id}
                    href={`/campaigns/${campaign.id}`}
                    className="bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-100 transform hover:-translate-y-1"
                  >
                    {campaign.featured_image ? (
                      <img
                        src={campaign.featured_image}
                        alt={campaign.title}
                        className="w-full h-48 object-cover"
                      />
                    ) : (
                      <div className="w-full h-48 bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center">
                        <svg className="w-16 h-16 text-white opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                      </div>
                    )}
                    <div className="p-6">
                      <div className="flex items-start justify-between mb-2">
                        <h3 className="text-lg font-semibold text-gray-900 line-clamp-2 flex-1">{campaign.title}</h3>
                        <span className={`ml-2 text-xs font-semibold px-2 py-1 rounded-full whitespace-nowrap ${
                          campaign.status === 'active' ? 'bg-green-100 text-green-800' :
                          campaign.status === 'successful' ? 'bg-blue-100 text-blue-800' :
                          campaign.status === 'draft' ? 'bg-yellow-100 text-yellow-800' :
                          'bg-gray-100 text-gray-800'
                        }`}>
                          {campaign.status || 'Active'}
                        </span>
                      </div>
                      <p className="text-sm text-gray-600 line-clamp-2 mb-4 min-h-[2.5rem]">
                        {campaign.description || campaign.story || 'No description available'}
                      </p>
                      <div className="mb-4">
                        <div className="flex items-center justify-between mb-2">
                          <span className="text-lg font-bold text-gray-900">
                            {formatCurrency(campaign.raised_amount || 0)}
                          </span>
                          <span className="text-sm text-gray-500">
                            of {formatCurrency(campaign.goal_amount || 0)}
                          </span>
                        </div>
                        <div className="w-full bg-gray-200 rounded-full h-2.5">
                          <div
                            className={`h-2.5 rounded-full transition-all ${
                              progress >= 100 ? 'bg-green-500' : 'bg-gradient-to-r from-blue-500 to-purple-500'
                            }`}
                            style={{ width: `${progress}%` }}
                          ></div>
                        </div>
                        <p className="text-xs text-gray-500 mt-1 text-right">
                          {progress.toFixed(1)}% funded
                        </p>
                      </div>
                      {campaign.creator && (
                        <div className="flex items-center text-xs text-gray-500 pt-3 border-t border-gray-100">
                          <span>By {campaign.creator.name || 'Unknown'}</span>
                        </div>
                      )}
                    </div>
                  </Link>
                );
              })}
            </div>
          )}
        </div>
      </div>
    </Sidebar>
  );
}


