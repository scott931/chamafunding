'use client';

import { useEffect, useState } from 'react';
import { useParams } from 'next/navigation';
import Sidebar from '@/components/Sidebar';
import apiClient from '@/lib/api/client';

export default function CampaignDetailPage() {
  const params = useParams();
  const campaignId = params?.id as string;
  const [campaign, setCampaign] = useState<any>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (!campaignId) return;
    
    const fetchCampaign = async () => {
      try {
        // TODO: Replace with actual campaign detail API endpoint
        const response = await apiClient.get(`/v1/crowdfunding/campaigns/${campaignId}`);
        setCampaign(response.data.data);
      } catch (error) {
        console.error('Failed to fetch campaign:', error);
      } finally {
        setLoading(false);
      }
    };
    fetchCampaign();
  }, [campaignId]);

  if (loading) {
    return (
      <Sidebar>
        <div className="min-h-screen flex items-center justify-center">
          <div className="text-center">
            <div className="inline-block animate-spin rounded-full h-12 w-12 border-4 border-blue-600 border-t-transparent mb-4"></div>
            <p className="text-gray-600">Loading campaign...</p>
          </div>
        </div>
      </Sidebar>
    );
  }

  if (!campaign) {
    return (
      <Sidebar>
        <div className="min-h-screen flex items-center justify-center">
          <div className="text-center">
            <p className="text-gray-600">Campaign not found</p>
          </div>
        </div>
      </Sidebar>
    );
  }

  return (
    <Sidebar>
      <div className="min-h-screen bg-gray-50 py-8">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="bg-white rounded-lg shadow overflow-hidden">
            {campaign.featured_image && (
              <img
                src={campaign.featured_image}
                alt={campaign.title}
                className="w-full h-64 object-cover"
              />
            )}
            <div className="p-6">
              <h1 className="text-3xl font-bold text-gray-900 mb-4">{campaign.title}</h1>
              <div className="prose max-w-none mb-6">
                <p className="text-gray-700">{campaign.description}</p>
              </div>
              <div className="border-t pt-6">
                <dl className="grid grid-cols-2 gap-4">
                  <div>
                    <dt className="text-sm font-medium text-gray-500">Status</dt>
                    <dd className="mt-1 text-sm text-gray-900">{campaign.funding_status || 'Active'}</dd>
                  </div>
                  {campaign.progress_percentage !== undefined && (
                    <div>
                      <dt className="text-sm font-medium text-gray-500">Progress</dt>
                      <dd className="mt-1 text-sm text-gray-900">{campaign.progress_percentage}%</dd>
                    </div>
                  )}
                </dl>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Sidebar>
  );
}


