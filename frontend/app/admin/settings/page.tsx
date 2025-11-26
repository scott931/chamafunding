'use client';

import { useEffect, useState, useCallback } from 'react';
import Sidebar from '@/components/Sidebar';
import { settingsApi } from '@/lib/api/settings';

type SettingsCategory = 'platform' | 'campaigns' | 'users' | 'financial';

export default function AdminSettingsPage() {
  const [activeTab, setActiveTab] = useState<SettingsCategory>('platform');
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);
  const [settings, setSettings] = useState<any>({});

  const tabs: { key: SettingsCategory; label: string; icon: string }[] = [
    { key: 'platform', label: 'Platform', icon: '⚙️' },
    { key: 'campaigns', label: 'Campaigns', icon: '📋' },
    { key: 'users', label: 'Users', icon: '👥' },
    { key: 'financial', label: 'Financial', icon: '💰' },
  ];

  const loadSettings = useCallback(async () => {
    setLoading(true);
    try {
      let data;
      switch (activeTab) {
        case 'platform':
          data = await settingsApi.getPlatform();
          break;
        case 'campaigns':
          data = await settingsApi.getCampaigns();
          break;
        case 'users':
          data = await settingsApi.getUsers();
          break;
        case 'financial':
          data = await settingsApi.getFinancial();
          // Log PayPal credentials status for debugging
          if (data?.payment_gateways?.paypal_client_id) {
            console.log('PayPal Client ID loaded from backend');
          }
          if (data?.payment_gateways?.paypal_secret) {
            console.log('PayPal Secret loaded from backend');
          }
          break;
      }
      setSettings(data || {});
    } catch (error) {
      console.error('Failed to load settings:', error);
      setMessage({ type: 'error', text: 'Failed to load settings' });
    } finally {
      setLoading(false);
    }
  }, [activeTab]);

  useEffect(() => {
    loadSettings();
  }, [loadSettings]);

  const handleSave = async () => {
    setSaving(true);
    setMessage(null);
    try {
      let response;
      switch (activeTab) {
        case 'platform':
          response = await settingsApi.updatePlatform(settings);
          break;
        case 'campaigns':
          response = await settingsApi.updateCampaigns(settings);
          break;
        case 'users':
          response = await settingsApi.updateUsers(settings);
          break;
        case 'financial':
          response = await settingsApi.updateFinancial(settings);
          break;
      }
      setMessage({ type: 'success', text: response.message || 'Settings saved successfully' });
      setTimeout(() => setMessage(null), 3000);
    } catch (error: any) {
      setMessage({ type: 'error', text: error.response?.data?.message || 'Failed to save settings' });
    } finally {
      setSaving(false);
    }
  };

  const updateSetting = (path: string, value: any) => {
    const keys = path.split('.');
    setSettings((prev: any) => {
      const newSettings = { ...prev };
      let current = newSettings;
      for (let i = 0; i < keys.length - 1; i++) {
        if (!current[keys[i]]) current[keys[i]] = {};
        current = current[keys[i]];
      }
      current[keys[keys.length - 1]] = value;
      return newSettings;
    });
  };

  if (loading) {
    return (
      <Sidebar>
        <div className="min-h-screen flex items-center justify-center">
          <div className="text-center">
            <div className="inline-block animate-spin rounded-full h-12 w-12 border-4 border-blue-600 border-t-transparent mb-4"></div>
            <p className="text-gray-600">Loading settings...</p>
          </div>
        </div>
      </Sidebar>
    );
  }

  return (
    <Sidebar>
      <div className="min-h-screen bg-gray-50 py-8">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="mb-6">
            <h1 className="text-3xl font-bold text-gray-900">Settings</h1>
            <p className="mt-2 text-sm text-gray-600">Manage platform configuration</p>
          </div>

          {message && (
            <div className={`mb-4 p-4 rounded-md ${
              message.type === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'
            }`}>
              {message.text}
            </div>
          )}

          <div className="bg-white rounded-lg shadow">
            <div className="border-b border-gray-200">
              <nav className="flex -mb-px">
                {tabs.map((tab) => (
                  <button
                    key={tab.key}
                    onClick={() => setActiveTab(tab.key)}
                    className={`px-6 py-4 text-sm font-medium border-b-2 ${
                      activeTab === tab.key
                        ? 'border-blue-500 text-blue-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                    }`}
                  >
                    <span className="mr-2">{tab.icon}</span>
                    {tab.label}
                  </button>
                ))}
              </nav>
                </div>

            <div className="p-6">
              {activeTab === 'platform' && (
                <PlatformSettings settings={settings} updateSetting={updateSetting} />
              )}
              {activeTab === 'campaigns' && (
                <CampaignSettings settings={settings} updateSetting={updateSetting} />
              )}
              {activeTab === 'users' && (
                <UserSettings settings={settings} updateSetting={updateSetting} />
              )}
              {activeTab === 'financial' && (
                <FinancialSettings settings={settings} updateSetting={updateSetting} />
              )}

              <div className="mt-6 flex justify-end">
                <button
                  onClick={handleSave}
                  disabled={saving}
                  className="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {saving ? 'Saving...' : 'Save Settings'}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Sidebar>
  );
}

// Platform Settings Component
function PlatformSettings({ settings, updateSetting }: any) {
  return (
    <div className="space-y-6">
      <div>
        <h3 className="text-lg font-semibold mb-4">Funding Models</h3>
        <div className="space-y-3">
          <label className="flex items-center">
            <input
              type="checkbox"
              checked={settings.funding_models?.all_or_nothing_enabled || false}
              onChange={(e) => updateSetting('funding_models.all_or_nothing_enabled', e.target.checked)}
              className="mr-2"
            />
            <span>All or Nothing (campaigns must reach goal)</span>
          </label>
          <label className="flex items-center">
            <input
              type="checkbox"
              checked={settings.funding_models?.keep_it_all_enabled || false}
              onChange={(e) => updateSetting('funding_models.keep_it_all_enabled', e.target.checked)}
              className="mr-2"
            />
            <span>Keep It All (creators keep all funds)</span>
          </label>
          <label className="flex items-center">
            <input
              type="checkbox"
              checked={settings.funding_models?.tipping_enabled || false}
              onChange={(e) => updateSetting('funding_models.tipping_enabled', e.target.checked)}
              className="mr-2"
            />
            <span>Tipping Enabled</span>
          </label>
        </div>
      </div>

      <div>
        <h3 className="text-lg font-semibold mb-4">Fee Structure</h3>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Platform Fee Percentage (%)
            </label>
            <input
              type="number"
              step="0.1"
              value={settings.fee_structure?.platform_fee_percentage || 0}
              onChange={(e) => updateSetting('fee_structure.platform_fee_percentage', parseFloat(e.target.value))}
              className="w-full px-3 py-2 border border-gray-300 rounded-md"
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Platform Fee Fixed ($)
            </label>
            <input
              type="number"
              step="0.01"
              value={settings.fee_structure?.platform_fee_fixed || 0}
              onChange={(e) => updateSetting('fee_structure.platform_fee_fixed', parseFloat(e.target.value))}
              className="w-full px-3 py-2 border border-gray-300 rounded-md"
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Payout Threshold ($)
            </label>
            <input
              type="number"
              step="0.01"
              value={settings.fee_structure?.payout_threshold || 0}
              onChange={(e) => updateSetting('fee_structure.payout_threshold', parseFloat(e.target.value))}
              className="w-full px-3 py-2 border border-gray-300 rounded-md"
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Payout Schedule (days)
            </label>
            <input
              type="number"
              value={settings.fee_structure?.payout_schedule_days || 0}
              onChange={(e) => updateSetting('fee_structure.payout_schedule_days', parseInt(e.target.value))}
              className="w-full px-3 py-2 border border-gray-300 rounded-md"
            />
          </div>
        </div>
        <label className="flex items-center mt-4">
          <input
            type="checkbox"
            checked={settings.fee_structure?.payment_processor_fee_passthrough || false}
            onChange={(e) => updateSetting('fee_structure.payment_processor_fee_passthrough', e.target.checked)}
            className="mr-2"
          />
          <span>Pass payment processor fees to creators</span>
        </label>
      </div>

      <div>
        <h3 className="text-lg font-semibold mb-4">Currency & Regions</h3>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Base Currency
            </label>
            <input
              type="text"
              value={settings.currency?.base_currency || 'USD'}
              onChange={(e) => updateSetting('currency.base_currency', e.target.value.toUpperCase())}
              className="w-full px-3 py-2 border border-gray-300 rounded-md"
              placeholder="USD"
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Supported Currencies (comma-separated)
            </label>
            <input
              type="text"
              value={Array.isArray(settings.currency?.supported_currencies) 
                ? settings.currency.supported_currencies.join(', ')
                : settings.currency?.supported_currencies || ''}
              onChange={(e) => updateSetting('currency.supported_currencies', e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-md"
              placeholder="USD, EUR, GBP"
            />
          </div>
        </div>
      </div>
    </div>
  );
}

// Campaign Settings Component
function CampaignSettings({ settings, updateSetting }: any) {
  return (
    <div className="space-y-6">
      <div>
        <h3 className="text-lg font-semibold mb-4">Campaign Requirements</h3>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Min Funding Goal ($)
            </label>
            <input
              type="number"
              step="0.01"
              value={settings.campaign_requirements?.min_funding_goal || 0}
              onChange={(e) => updateSetting('campaign_requirements.min_funding_goal', parseFloat(e.target.value))}
              className="w-full px-3 py-2 border border-gray-300 rounded-md"
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Max Funding Goal ($)
            </label>
            <input
              type="number"
              step="0.01"
              value={settings.campaign_requirements?.max_funding_goal || 0}
              onChange={(e) => updateSetting('campaign_requirements.max_funding_goal', parseFloat(e.target.value))}
              className="w-full px-3 py-2 border border-gray-300 rounded-md"
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Min Duration (days)
            </label>
            <input
              type="number"
              value={settings.campaign_requirements?.min_duration_days || 0}
              onChange={(e) => updateSetting('campaign_requirements.min_duration_days', parseInt(e.target.value))}
              className="w-full px-3 py-2 border border-gray-300 rounded-md"
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Max Duration (days)
            </label>
            <input
              type="number"
              value={settings.campaign_requirements?.max_duration_days || 0}
              onChange={(e) => updateSetting('campaign_requirements.max_duration_days', parseInt(e.target.value))}
              className="w-full px-3 py-2 border border-gray-300 rounded-md"
            />
          </div>
        </div>
        <div className="mt-4 space-y-2">
          <label className="flex items-center">
            <input
              type="checkbox"
              checked={settings.campaign_requirements?.required_video || false}
              onChange={(e) => updateSetting('campaign_requirements.required_video', e.target.checked)}
              className="mr-2"
            />
            <span>Require Video</span>
          </label>
          <label className="flex items-center">
            <input
              type="checkbox"
              checked={settings.campaign_requirements?.required_image_gallery || false}
              onChange={(e) => updateSetting('campaign_requirements.required_image_gallery', e.target.checked)}
              className="mr-2"
            />
            <span>Require Image Gallery</span>
          </label>
          <label className="flex items-center">
            <input
              type="checkbox"
              checked={settings.campaign_requirements?.required_story_text || false}
              onChange={(e) => updateSetting('campaign_requirements.required_story_text', e.target.checked)}
              className="mr-2"
            />
            <span>Require Story Text</span>
          </label>
        </div>
      </div>

      <div>
        <h3 className="text-lg font-semibold mb-4">Approval Workflow</h3>
        <label className="flex items-center">
          <input
            type="checkbox"
            checked={settings.approval_workflow?.require_approval || false}
            onChange={(e) => updateSetting('approval_workflow.require_approval', e.target.checked)}
            className="mr-2"
          />
          <span>Require Admin Approval for Campaigns</span>
        </label>
      </div>

      <div>
        <h3 className="text-lg font-semibold mb-4">Content Restrictions</h3>
        <div className="mb-4">
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Prohibited Categories (one per line)
          </label>
          <textarea
            value={Array.isArray(settings.content_restrictions?.prohibited_categories)
              ? settings.content_restrictions.prohibited_categories.join('\n')
              : ''}
            onChange={(e) => updateSetting('content_restrictions.prohibited_categories', e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md"
            rows={4}
            placeholder="Category 1&#10;Category 2"
          />
        </div>
        <div className="mb-4">
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Banned Keywords (one per line)
          </label>
          <textarea
            value={Array.isArray(settings.content_restrictions?.banned_keywords)
              ? settings.content_restrictions.banned_keywords.join('\n')
              : ''}
            onChange={(e) => updateSetting('content_restrictions.banned_keywords', e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md"
            rows={4}
            placeholder="keyword1&#10;keyword2"
          />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Manual Review Threshold ($)
          </label>
          <input
            type="number"
            step="0.01"
            value={settings.content_restrictions?.manual_review_threshold || 0}
            onChange={(e) => updateSetting('content_restrictions.manual_review_threshold', parseFloat(e.target.value))}
            className="w-full px-3 py-2 border border-gray-300 rounded-md"
          />
        </div>
      </div>
    </div>
  );
}

// User Settings Component
function UserSettings({ settings, updateSetting }: any) {
  return (
    <div className="space-y-6">
      <div>
        <h3 className="text-lg font-semibold mb-4">Registration</h3>
        <div className="space-y-2">
          <label className="flex items-center">
            <input
              type="checkbox"
              checked={settings.registration?.allow_public_signups || false}
              onChange={(e) => updateSetting('registration.allow_public_signups', e.target.checked)}
              className="mr-2"
            />
            <span>Allow Public Signups</span>
          </label>
          <label className="flex items-center">
            <input
              type="checkbox"
              checked={settings.registration?.invite_only_mode || false}
              onChange={(e) => updateSetting('registration.invite_only_mode', e.target.checked)}
              className="mr-2"
            />
            <span>Invite Only Mode</span>
          </label>
          <label className="flex items-center">
            <input
              type="checkbox"
              checked={settings.registration?.require_email_verification || false}
              onChange={(e) => updateSetting('registration.require_email_verification', e.target.checked)}
              className="mr-2"
            />
            <span>Require Email Verification</span>
          </label>
          <label className="flex items-center">
            <input
              type="checkbox"
              checked={settings.registration?.require_phone_number || false}
              onChange={(e) => updateSetting('registration.require_phone_number', e.target.checked)}
              className="mr-2"
            />
            <span>Require Phone Number</span>
          </label>
        </div>
      </div>

      <div>
        <h3 className="text-lg font-semibold mb-4">Verification</h3>
        <div className="space-y-2">
          <label className="flex items-center">
            <input
              type="checkbox"
              checked={settings.verification?.require_identity_verification || false}
              onChange={(e) => updateSetting('verification.require_identity_verification', e.target.checked)}
              className="mr-2"
            />
            <span>Require Identity Verification</span>
          </label>
          <label className="flex items-center">
            <input
              type="checkbox"
              checked={settings.verification?.require_bank_details || false}
              onChange={(e) => updateSetting('verification.require_bank_details', e.target.checked)}
              className="mr-2"
            />
            <span>Require Bank Details</span>
          </label>
        </div>
      </div>

      <div>
        <h3 className="text-lg font-semibold mb-4">Security</h3>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Password Min Length
            </label>
            <input
              type="number"
              value={settings.security?.password_min_length || 8}
              onChange={(e) => updateSetting('security.password_min_length', parseInt(e.target.value))}
              className="w-full px-3 py-2 border border-gray-300 rounded-md"
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Session Timeout (minutes)
            </label>
            <input
              type="number"
              value={settings.security?.session_timeout_minutes || 60}
              onChange={(e) => updateSetting('security.session_timeout_minutes', parseInt(e.target.value))}
              className="w-full px-3 py-2 border border-gray-300 rounded-md"
            />
          </div>
        </div>
        <div className="space-y-2">
          <label className="flex items-center">
            <input
              type="checkbox"
              checked={settings.security?.require_2fa_admins || false}
              onChange={(e) => updateSetting('security.require_2fa_admins', e.target.checked)}
              className="mr-2"
            />
            <span>Require 2FA for Admins</span>
          </label>
          <label className="flex items-center">
            <input
              type="checkbox"
              checked={settings.security?.require_2fa_users || false}
              onChange={(e) => updateSetting('security.require_2fa_users', e.target.checked)}
              className="mr-2"
            />
            <span>Require 2FA for Users</span>
          </label>
          <label className="flex items-center">
            <input
              type="checkbox"
              checked={settings.security?.api_access_enabled || false}
              onChange={(e) => updateSetting('security.api_access_enabled', e.target.checked)}
              className="mr-2"
            />
            <span>Enable API Access</span>
          </label>
        </div>
      </div>
    </div>
  );
}

// Financial Settings Component
function FinancialSettings({ settings, updateSetting }: any) {
  return (
    <div className="space-y-6">
      <div>
        <h3 className="text-lg font-semibold mb-4">Payment Gateways</h3>
        <div className="space-y-4">
          <div className="border rounded-lg p-4">
            <div className="flex items-center justify-between mb-3">
              <h4 className="font-medium">Stripe</h4>
              <label className="flex items-center">
                <input
                  type="checkbox"
                  checked={settings.payment_gateways?.stripe_enabled || false}
                  onChange={(e) => updateSetting('payment_gateways.stripe_enabled', e.target.checked)}
                  className="mr-2"
                />
                <span className="text-sm">Enabled</span>
              </label>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  API Key
                </label>
                <input
                  type="text"
                  value={settings.payment_gateways?.stripe_api_key || ''}
                  onChange={(e) => updateSetting('payment_gateways.stripe_api_key', e.target.value)}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md"
                  placeholder="pk_..."
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Secret Key
                </label>
                <input
                  type="password"
                  value={settings.payment_gateways?.stripe_secret_key || ''}
                  onChange={(e) => updateSetting('payment_gateways.stripe_secret_key', e.target.value)}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md"
                  placeholder="sk_..."
                />
              </div>
            </div>
          </div>

          <div className="border rounded-lg p-4 bg-gradient-to-br from-blue-50 to-indigo-50">
            <div className="flex items-center justify-between mb-4">
              <div>
                <h4 className="font-semibold text-gray-900">PayPal Configuration</h4>
                <p className="text-xs text-gray-600 mt-1">Configure PayPal payment gateway credentials</p>
              </div>
              <label className="flex items-center">
                <input
                  type="checkbox"
                  checked={settings.payment_gateways?.paypal_enabled || false}
                  onChange={(e) => updateSetting('payment_gateways.paypal_enabled', e.target.checked)}
                  className="mr-2"
                />
                <span className="text-sm font-medium">Enabled</span>
              </label>
            </div>
            
            <div className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  PayPal Client ID
                  {settings.payment_gateways?.paypal_client_id && (
                    <span className="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                      ✓ Configured
                    </span>
                  )}
                </label>
                <div className="relative">
                  <input
                    type="text"
                    value={settings.payment_gateways?.paypal_client_id || ''}
                    onChange={(e) => updateSetting('payment_gateways.paypal_client_id', e.target.value)}
                    placeholder="Enter PayPal Client ID"
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono text-sm"
                  />
                  {settings.payment_gateways?.paypal_client_id && (
                    <div className="mt-1 text-xs text-gray-500">
                      <span className="font-medium">Loaded from backend:</span>{' '}
                      <span className="font-mono bg-gray-100 px-1 py-0.5 rounded">
                        {settings.payment_gateways.paypal_client_id.substring(0, 30)}
                        {settings.payment_gateways.paypal_client_id.length > 30 ? '...' : ''}
                      </span>
                    </div>
                  )}
                </div>
                <p className="mt-1 text-xs text-gray-500">
                  Your PayPal Client ID from the PayPal Developer Dashboard. Update this for production.
                </p>
              </div>
              
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  PayPal Secret
                  {settings.payment_gateways?.paypal_secret && (
                    <span className="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                      ✓ Configured
                    </span>
                  )}
                </label>
                <div className="relative">
                  <input
                    type="password"
                    value={settings.payment_gateways?.paypal_secret || ''}
                    onChange={(e) => updateSetting('payment_gateways.paypal_secret', e.target.value)}
                    placeholder="Enter PayPal Secret"
                    className="w-full px-3 py-2 pr-10 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    id="paypal-secret-input"
                  />
                  {settings.payment_gateways?.paypal_secret && (
                    <div className="mt-1 flex items-center justify-between">
                      <span className="text-xs text-gray-500">
                        <span className="font-medium">Loaded from backend</span> - Secret is set (hidden for security)
                      </span>
                      <button
                        type="button"
                        onClick={() => {
                          const input = document.getElementById('paypal-secret-input') as HTMLInputElement;
                          if (input) {
                            input.type = input.type === 'password' ? 'text' : 'password';
                          }
                        }}
                        className="text-xs text-blue-600 hover:text-blue-800 font-medium"
                      >
                        Show/Hide
                      </button>
                    </div>
                  )}
                </div>
                <p className="mt-1 text-xs text-gray-500">
                  Your PayPal Client Secret from the PayPal Developer Dashboard
                </p>
              </div>
              
              <div className="pt-3 border-t border-gray-200">
                <div className="bg-blue-50 border border-blue-200 rounded-md p-3">
                  <div className="flex items-start">
                    <svg className="w-5 h-5 text-blue-600 mt-0.5 mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div className="flex-1">
                      <p className="text-xs font-medium text-blue-900 mb-1">Production Setup</p>
                      <p className="text-xs text-blue-700">
                        Update the Client ID and Secret above with your production PayPal credentials from the{' '}
                        <a 
                          href="https://developer.paypal.com/dashboard" 
                          target="_blank" 
                          rel="noopener noreferrer"
                          className="underline hover:text-blue-900"
                        >
                          PayPal Developer Dashboard
                        </a>
                        . Make sure to enable PayPal after entering your credentials.
                      </p>
                    </div>
                  </div>
                </div>
                <div className="flex items-center justify-between mt-3">
                  <div>
                    <p className="text-xs font-medium text-gray-700 mb-1">Status</p>
                    <p className="text-xs text-gray-500">
                      {settings.payment_gateways?.paypal_enabled 
                        ? 'PayPal is enabled and ready to use' 
                        : 'PayPal is disabled - enable to activate'}
                    </p>
                  </div>
                  <div className="text-right">
                    <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                      settings.payment_gateways?.paypal_enabled 
                        ? 'bg-green-100 text-green-800' 
                        : 'bg-gray-100 text-gray-800'
                    }`}>
                      {settings.payment_gateways?.paypal_enabled ? 'Active' : 'Inactive'}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Default Gateway
            </label>
            <select
              value={settings.payment_gateways?.default_gateway || 'stripe'}
              onChange={(e) => updateSetting('payment_gateways.default_gateway', e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-md"
            >
              <option value="stripe">Stripe</option>
              <option value="paypal">PayPal</option>
            </select>
          </div>
        </div>
      </div>

      <div>
        <h3 className="text-lg font-semibold mb-4">Payout Method</h3>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Method
          </label>
          <select
            value={settings.payout_method?.method || 'bank'}
            onChange={(e) => updateSetting('payout_method.method', e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md"
          >
            <option value="bank">Bank Transfer</option>
            <option value="paypal">PayPal</option>
            <option value="stripe">Stripe Connect</option>
          </select>
        </div>
      </div>

      <div>
        <h3 className="text-lg font-semibold mb-4">Tax Settings</h3>
        <div className="space-y-3">
          <label className="flex items-center">
            <input
              type="checkbox"
              checked={settings.tax?.tax_collection_enabled || false}
              onChange={(e) => updateSetting('tax.tax_collection_enabled', e.target.checked)}
              className="mr-2"
            />
            <span>Enable Tax Collection</span>
          </label>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              1099-K Threshold ($)
            </label>
            <input
              type="number"
              step="0.01"
              value={settings.tax?.form_1099k_threshold || 0}
              onChange={(e) => updateSetting('tax.form_1099k_threshold', parseFloat(e.target.value))}
              className="w-full px-3 py-2 border border-gray-300 rounded-md"
            />
          </div>
        </div>
      </div>

      <div>
        <h3 className="text-lg font-semibold mb-4">Refund Policy</h3>
        <div className="space-y-3">
          <label className="flex items-center">
            <input
              type="checkbox"
              checked={settings.refund_policy?.allow_creator_refunds || false}
              onChange={(e) => updateSetting('refund_policy.allow_creator_refunds', e.target.checked)}
              className="mr-2"
            />
            <span>Allow Creator-Initiated Refunds</span>
          </label>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Platform-Managed Refund Window (days)
            </label>
            <input
              type="number"
              value={settings.refund_policy?.platform_managed_refund_days || 0}
              onChange={(e) => updateSetting('refund_policy.platform_managed_refund_days', parseInt(e.target.value))}
              className="w-full px-3 py-2 border border-gray-300 rounded-md"
            />
          </div>
        </div>
      </div>
    </div>
  );
}
