'use client';

import { useEffect, useState } from 'react';
import { useParams, useRouter } from 'next/navigation';
import Sidebar from '@/components/Sidebar';
import apiClient from '@/lib/api/client';
import Link from 'next/link';
import PayPalButton from '@/components/PayPalButton';
import { authApi } from '@/lib/api/auth';

export default function CampaignDetailPage() {
  const params = useParams();
  const campaignId = params?.id as string;
  const [campaign, setCampaign] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [user, setUser] = useState<any>(null);
  const [showPaymentModal, setShowPaymentModal] = useState(false);
  const [contributionAmount, setContributionAmount] = useState<number>(25);
  const router = useRouter();

  useEffect(() => {
    const fetchData = async () => {
      // Fetch user
      try {
        const userData = await authApi.getUser();
        setUser(userData);
      } catch (err) {
        // User not logged in, that's okay
      }

      // Fetch campaign
      if (!campaignId) return;
      
      try {
        const response = await apiClient.get(`/v1/campaigns/${campaignId}`);
        const campaignData = response.data?.data || response.data;
        setCampaign(campaignData);
        setError(null);
      } catch (err: any) {
        console.error('Failed to fetch campaign:', err);
        if (err.response?.status === 404) {
          setError('Campaign not found');
        } else {
          setError('Failed to load campaign');
        }
      } finally {
        setLoading(false);
      }
    };
    fetchData();
  }, [campaignId]);

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(amount / 100);
  };

  if (loading) {
    return (
      <Sidebar>
        <div className="min-h-screen flex items-center justify-center bg-gray-50">
          <div className="text-center">
            <div className="inline-block animate-spin rounded-full h-12 w-12 border-4 border-blue-600 border-t-transparent mb-4"></div>
            <p className="text-gray-600">Loading campaign...</p>
          </div>
        </div>
      </Sidebar>
    );
  }

  if (error || !campaign) {
    return (
      <Sidebar>
        <div className="min-h-screen flex items-center justify-center bg-gray-50">
          <div className="text-center">
            <p className="text-gray-900 text-lg font-semibold mb-4">{error || 'Campaign not found'}</p>
            <Link
              href="/campaigns"
              className="inline-block px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
            >
              Back to Campaigns
            </Link>
          </div>
        </div>
      </Sidebar>
    );
  }

  const progress = campaign.goal_amount > 0 
    ? Math.min(100, ((campaign.raised_amount || 0) / campaign.goal_amount) * 100)
    : 0;

  return (
    <Sidebar>
      <div className="min-h-screen bg-gray-50 py-6">
        <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
          {/* Back Button */}
          <Link
            href="/campaigns"
            className="inline-flex items-center text-gray-600 hover:text-gray-900 mb-4 text-sm"
          >
            <svg className="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
            </svg>
            Back to Campaigns
          </Link>

          <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {/* Main Content */}
            <div className="lg:col-span-2 space-y-6">
              {/* Featured Image */}
              {campaign.featured_image ? (
                <img
                  src={campaign.featured_image}
                  alt={campaign.title}
                  className="w-full h-48 sm:h-64 object-cover rounded-lg shadow-md"
                />
              ) : (
                <div className="w-full h-48 sm:h-64 bg-gradient-to-br from-blue-400 to-purple-500 rounded-lg flex items-center justify-center">
                  <svg className="w-12 h-12 sm:w-16 sm:h-16 text-white opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                </div>
              )}

              {/* Campaign Info Card */}
              <div className="bg-white rounded-lg shadow-md p-4 sm:p-6">
                <div className="flex flex-col sm:flex-row sm:items-start sm:justify-between mb-4 gap-3">
                  <div className="flex-1">
                    <h1 className="text-xl sm:text-2xl font-bold text-gray-900 mb-2">{campaign.title}</h1>
                    {campaign.creator && (
                      <p className="text-sm text-gray-600">
                        by <span className="font-medium">{campaign.creator.name || 'Unknown'}</span>
                      </p>
                    )}
                  </div>
                  <span className={`text-xs font-semibold px-2.5 py-1 rounded-full self-start sm:self-auto ${
                    campaign.status === 'active' ? 'bg-green-100 text-green-800' :
                    campaign.status === 'successful' ? 'bg-blue-100 text-blue-800' :
                    campaign.status === 'draft' ? 'bg-yellow-100 text-yellow-800' :
                    campaign.status === 'closed' ? 'bg-gray-100 text-gray-800' :
                    'bg-gray-100 text-gray-800'
                  }`}>
                    {campaign.status || 'Active'}
                  </span>
                </div>

                {/* Description */}
                <div className="prose prose-sm max-w-none">
                  <p className="text-sm sm:text-base text-gray-700 whitespace-pre-wrap leading-relaxed">
                    {campaign.description || campaign.story || 'No description available.'}
                  </p>
                </div>
              </div>
            </div>

            {/* Sidebar */}
            <div className="lg:col-span-1">
              <div className="bg-white rounded-lg shadow-md p-4 sm:p-6 lg:sticky lg:top-6">
                {/* Funding Stats */}
                <div className="mb-6">
                  <div className="flex flex-col sm:flex-row sm:items-baseline sm:justify-between mb-2 gap-1">
                    <span className="text-xl sm:text-2xl font-bold text-gray-900">
                      {formatCurrency(campaign.raised_amount || 0)}
                    </span>
                    <span className="text-xs sm:text-sm text-gray-500">
                      of {formatCurrency(campaign.goal_amount || 0)}
                    </span>
                  </div>
                  <div className="w-full bg-gray-200 rounded-full h-2.5 sm:h-2 mb-2">
                    <div
                      className={`h-2.5 sm:h-2 rounded-full transition-all ${
                        progress >= 100 ? 'bg-green-500' : 'bg-blue-600'
                      }`}
                      style={{ width: `${progress}%` }}
                    ></div>
                  </div>
                  <p className="text-xs text-gray-600 text-center">
                    {progress.toFixed(1)}% funded
                  </p>
                </div>

                {/* Support Section */}
                {campaign.status === 'active' && (
                  <div className="mb-6">
                    {!user ? (
                      <div className="text-center py-4">
                        <p className="text-sm text-gray-600 mb-3">Please log in to contribute</p>
                        <Link
                          href="/login"
                          className="w-full inline-block px-4 py-3 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 transition-colors shadow-sm text-center touch-target"
                        >
                          Log In to Support
                        </Link>
                      </div>
                    ) : !showPaymentModal ? (
                      <div>
                        <div className="mb-3">
                          <label className="block text-sm font-medium text-gray-700 mb-2">
                            Contribution Amount
                          </label>
                          <div className="grid grid-cols-2 gap-2 mb-2">
                            {[25, 50, 100, 250].map((amt) => (
                              <button
                                key={amt}
                                onClick={() => setContributionAmount(amt)}
                                className={`px-3 py-2.5 text-sm font-medium rounded-md transition-colors touch-target ${
                                  contributionAmount === amt
                                    ? 'bg-blue-600 text-white'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                }`}
                              >
                                ${amt}
                              </button>
                            ))}
                          </div>
                          <input
                            type="number"
                            min="1"
                            step="0.01"
                            value={contributionAmount}
                            onChange={(e) => setContributionAmount(parseFloat(e.target.value) || 1)}
                            className="w-full px-3 py-2.5 border border-gray-300 rounded-md text-sm touch-target"
                            placeholder="Enter amount"
                          />
                        </div>
                        <button
                          onClick={() => setShowPaymentModal(true)}
                          className="w-full px-4 py-3 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 transition-colors shadow-sm touch-target"
                        >
                          Support with ${contributionAmount}
                        </button>
                      </div>
                    ) : (
                      <div>
                        <div className="flex items-center justify-between mb-3">
                          <h3 className="text-sm font-semibold text-gray-900">Complete Payment</h3>
                          <button
                            onClick={() => setShowPaymentModal(false)}
                            className="text-gray-400 hover:text-gray-600 touch-target p-1"
                          >
                            <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                            </svg>
                          </button>
                        </div>
                        <PayPalButton
                          amount={contributionAmount}
                          currency={campaign.currency || 'USD'}
                          campaignId={campaignId}
                          campaignTitle={campaign.title}
                          onSuccess={(data) => {
                            alert('Thank you for your contribution!');
                            setShowPaymentModal(false);
                            // Reload to show updated raised amount
                            window.location.reload();
                          }}
                          onError={(error) => {
                            console.error('Payment error:', error);
                          }}
                        />
                      </div>
                    )}
                  </div>
                )}

                {/* Campaign Details */}
                <div className="space-y-4 pt-6 border-t border-gray-200">
                  {campaign.category && (
                    <div>
                      <p className="text-xs font-medium text-gray-500 mb-1">Category</p>
                      <p className="text-sm text-gray-900 capitalize">{campaign.category}</p>
                    </div>
                  )}
                  {campaign.deadline && (
                    <div>
                      <p className="text-xs font-medium text-gray-500 mb-1">Deadline</p>
                      <p className="text-sm text-gray-900">
                        {new Date(campaign.deadline).toLocaleDateString('en-US', {
                          month: 'short',
                          day: 'numeric',
                          year: 'numeric',
                        })}
                      </p>
                    </div>
                  )}
                  {campaign.created_at && (
                    <div>
                      <p className="text-xs font-medium text-gray-500 mb-1">Created</p>
                      <p className="text-sm text-gray-900">
                        {new Date(campaign.created_at).toLocaleDateString('en-US', {
                          month: 'short',
                          day: 'numeric',
                          year: 'numeric',
                        })}
                      </p>
                    </div>
                  )}
                  {campaign.currency && (
                    <div>
                      <p className="text-xs font-medium text-gray-500 mb-1">Currency</p>
                      <p className="text-sm text-gray-900">{campaign.currency}</p>
                    </div>
                  )}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Sidebar>
  );
}
