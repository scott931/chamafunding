'use client';

import { useState } from 'react';
import Sidebar from '@/components/Sidebar';

export default function AdminSettingsPage() {
  const [saved, setSaved] = useState(false);

  const handleSave = () => {
    // TODO: Implement settings save functionality
    setSaved(true);
    setTimeout(() => setSaved(false), 3000);
  };

  return (
    <Sidebar>
      <div className="min-h-screen bg-gray-50 py-8">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="mb-6">
            <h1 className="text-3xl font-bold text-gray-900">Settings</h1>
            <p className="mt-2 text-sm text-gray-600">Manage platform settings and configuration</p>
          </div>
          <div className="bg-white shadow rounded-lg">
            <div className="px-6 py-5 border-b border-gray-200">
              <h2 className="text-lg font-medium text-gray-900">Platform Settings</h2>
            </div>
            <div className="px-6 py-5">
              <p className="text-sm text-gray-500">Settings configuration will be available here.</p>
              {saved && (
                <div className="mt-4 p-3 bg-green-50 text-green-700 rounded-lg">
                  Settings saved successfully!
                </div>
              )}
              <div className="mt-6">
                <button
                  onClick={handleSave}
                  className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
                >
                  Save Settings
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Sidebar>
  );
}


