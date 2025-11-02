<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900">Platform Overview Dashboard</h2>
                <p class="text-sm text-gray-600 mt-1">Single-page summary of platform health</p>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('admin.reports.platform-overview', ['format' => 'csv']) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export CSV
                </a>
                <a href="{{ route('admin.reports.platform-overview', ['format' => 'print']) }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Print PDF
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-blue-50 rounded-lg p-4">
                        <p class="text-sm font-medium text-gray-600 mb-2">Total Money Pledged (All Time)</p>
                        <p class="text-2xl font-bold text-gray-900">${{ number_format($data['total_pledged_all_time'], 2) }}</p>
                    </div>
                    <div class="bg-green-50 rounded-lg p-4">
                        <p class="text-sm font-medium text-gray-600 mb-2">Total Money Pledged (This Month)</p>
                        <p class="text-2xl font-bold text-gray-900">${{ number_format($data['total_pledged_this_month'], 2) }}</p>
                    </div>
                    <div class="bg-purple-50 rounded-lg p-4">
                        <p class="text-sm font-medium text-gray-600 mb-2">Active Projects</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $data['active_projects'] }}</p>
                    </div>
                    <div class="bg-yellow-50 rounded-lg p-4">
                        <p class="text-sm font-medium text-gray-600 mb-2">Successful Projects</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $data['successful_projects'] }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="bg-indigo-50 rounded-lg p-4">
                        <p class="text-sm font-medium text-gray-600 mb-2">Platform Fees (All Time)</p>
                        <p class="text-2xl font-bold text-gray-900">${{ number_format($data['platform_fees_all_time'], 2) }}</p>
                    </div>
                    <div class="bg-pink-50 rounded-lg p-4">
                        <p class="text-sm font-medium text-gray-600 mb-2">Platform Fees (This Month)</p>
                        <p class="text-2xl font-bold text-gray-900">${{ number_format($data['platform_fees_this_month'], 2) }}</p>
                    </div>
                    <div class="bg-cyan-50 rounded-lg p-4">
                        <p class="text-sm font-medium text-gray-600 mb-2">New User Registrations (This Week)</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $data['new_users_this_week'] }}</p>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-gray-200">
                    <p class="text-sm text-gray-500">Report generated at: {{ $data['generated_at']->format('Y-m-d H:i:s') }}</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

