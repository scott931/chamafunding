<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900">Admin Dashboard</h2>
                <p class="text-sm text-gray-600 mt-1">Platform at a Glance - {{ now()->format('F j, Y') }}</p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.campaigns.index') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors">
                    Manage Campaigns
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- Key Live Metrics (Big Number Widgets) -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Platform Raised -->
                <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl p-6 text-white shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-emerald-100 text-sm font-medium mb-1">Total Platform Raised</p>
                            <p class="text-3xl font-bold">${{ number_format($stats['total_raised'], 2) }}</p>
                            <p class="text-emerald-100 text-xs mt-1">Lifetime</p>
                        </div>
                        <div class="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Active Campaigns -->
                <a href="{{ route('admin.campaigns.index', ['status' => 'active']) }}" class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl p-6 text-white shadow-lg hover:shadow-xl transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-100 text-sm font-medium mb-1">Active Campaigns</p>
                            <p class="text-3xl font-bold">{{ $stats['active_campaigns'] }}</p>
                            <p class="text-blue-100 text-xs mt-1">Live right now</p>
                        </div>
                        <div class="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                    </div>
                </a>

                <!-- Total Backers -->
                <div class="bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl p-6 text-white shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-purple-100 text-sm font-medium mb-1">Total Backers</p>
                            <p class="text-3xl font-bold">{{ number_format($stats['total_backers_alltime']) }}</p>
                            <p class="text-purple-100 text-xs mt-1">{{ $stats['total_backers_month'] }} this month</p>
                        </div>
                        <div class="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Platform Fees -->
                <a href="{{ route('admin.financial.index') }}" class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl p-6 text-white shadow-lg hover:shadow-xl transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-amber-100 text-sm font-medium mb-1">Platform Fees</p>
                            <p class="text-3xl font-bold">${{ number_format($stats['platform_fees_month'], 2) }}</p>
                            <p class="text-amber-100 text-xs mt-1">This month</p>
                        </div>
                        <div class="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                    </div>
                </a>

                <!-- Pending Payouts -->
                <div class="bg-gradient-to-br from-red-500 to-rose-600 rounded-xl p-6 text-white shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-red-100 text-sm font-medium mb-1">Pending Payouts</p>
                            <p class="text-3xl font-bold">${{ number_format($stats['pending_payouts'], 2) }}</p>
                            <p class="text-red-100 text-xs mt-1">Awaiting processing</p>
                        </div>
                        <div class="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Open Support Tickets -->
                <div class="bg-gradient-to-br from-indigo-500 to-blue-600 rounded-xl p-6 text-white shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-indigo-100 text-sm font-medium mb-1">Support Tickets</p>
                            <p class="text-3xl font-bold">{{ $stats['open_support_tickets'] }}</p>
                            <p class="text-indigo-100 text-xs mt-1">Open</p>
                        </div>
                        <div class="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Total Users -->
                <a href="{{ route('admin.users.index') }}" class="bg-gradient-to-br from-teal-500 to-cyan-600 rounded-xl p-6 text-white shadow-lg hover:shadow-xl transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-teal-100 text-sm font-medium mb-1">Total Users</p>
                            <p class="text-3xl font-bold">{{ number_format($stats['total_users']) }}</p>
                            <p class="text-teal-100 text-xs mt-1">{{ $stats['new_users_this_month'] }} new this month</p>
                        </div>
                        <div class="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                    </div>
                </a>

                <!-- New Campaigns -->
                <a href="{{ route('admin.campaigns.index') }}" class="bg-gradient-to-br from-violet-500 to-purple-600 rounded-xl p-6 text-white shadow-lg hover:shadow-xl transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-violet-100 text-sm font-medium mb-1">New Campaigns</p>
                            <p class="text-3xl font-bold">{{ $stats['new_campaigns_this_month'] }}</p>
                            <p class="text-violet-100 text-xs mt-1">This month</p>
                        </div>
                        <div class="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Funding Over Time Chart -->
                <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-900">Funding Over Time</h3>
                        <span class="text-xs text-gray-500">Last 30 days</span>
                    </div>
                    <div class="h-64 flex items-end justify-between space-x-1">
                        @if($fundingOverTime->count() > 0)
                            @php
                                $maxAmount = $fundingOverTime->max('total');
                            @endphp
                            @foreach($fundingOverTime as $day)
                                <div class="flex-1 flex flex-col items-center">
                                    <div class="w-full bg-gradient-to-t from-blue-500 to-indigo-600 rounded-t"
                                         style="height: {{ $maxAmount > 0 ? ($day->total / $maxAmount * 240) : 0 }}px; min-height: 2px;">
                                    </div>
                                    <span class="text-xs text-gray-500 mt-2 transform -rotate-45 origin-top-left">{{ \Carbon\Carbon::parse($day->date)->format('M j') }}</span>
                                </div>
                            @endforeach
                        @else
                            <div class="w-full h-full flex items-center justify-center text-gray-400">
                                <p>No funding data available</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Campaigns by Status -->
                <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Campaigns by Status</h3>
                    <div class="space-y-3">
                        @foreach($campaignsByStatus as $status)
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-sm font-medium text-gray-700 capitalize">{{ $status->status }}</span>
                                    <span class="text-sm font-bold text-gray-900">{{ $status->count }}</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    @php
                                        $total = $campaignsByStatus->sum('count');
                                        $percentage = $total > 0 ? ($status->count / $total * 100) : 0;
                                    @endphp
                                    <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Top Categories & Growth Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Top Performing Categories -->
                <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Top Performing Categories</h3>
                    <div class="space-y-3">
                        @foreach($topCategories as $category)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <div class="w-3 h-3 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600"></div>
                                    <span class="font-medium text-gray-900 capitalize">{{ str_replace('_', ' ', $category->category) }}</span>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-gray-900">${{ number_format($category->total_raised / 100, 0) }}</p>
                                    <p class="text-xs text-gray-500">{{ $category->count }} campaigns</p>
                                </div>
                            </div>
                        @endforeach
                        @if($topCategories->count() === 0)
                            <p class="text-gray-500 text-center py-4">No category data available</p>
                        @endif
                    </div>
                </div>

                <!-- Growth: New Campaigns & Users -->
                <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Platform Growth (Last 30 Days)</h3>
                    <div class="space-y-4">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700">New Campaigns</span>
                                <span class="text-sm font-bold text-blue-600">{{ $stats['new_campaigns_this_month'] }}</span>
                            </div>
                            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-full rounded-full" style="width: {{ min(100, ($stats['new_campaigns_this_month'] / max($stats['total_campaigns'], 1)) * 100) }}%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700">New Users</span>
                                <span class="text-sm font-bold text-purple-600">{{ $stats['new_users_this_month'] }}</span>
                            </div>
                            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div class="bg-gradient-to-r from-purple-500 to-pink-600 h-full rounded-full" style="width: {{ min(100, ($stats['new_users_this_month'] / max($stats['total_users'], 1)) * 100) }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Critical Activity Feed -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-gray-900">Recent Critical Activity</h3>
                        <span class="text-xs text-gray-500">Real-time updates</span>
                    </div>
                </div>
                <div class="divide-y divide-gray-200">
                    @if($recentActivity->count() > 0)
                        @foreach($recentActivity as $activity)
                            <div class="px-6 py-4 hover:bg-gray-50 transition-colors">
                                <div class="flex items-start space-x-3">
                                    @if($activity['type'] === 'large_pledge')
                                        <div class="w-2 h-2 bg-green-500 rounded-full mt-2"></div>
                                    @elseif($activity['type'] === 'high_value_campaign')
                                        <div class="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                                    @elseif($activity['type'] === 'flagged')
                                        <div class="w-2 h-2 bg-red-500 rounded-full mt-2"></div>
                                    @else
                                        <div class="w-2 h-2 bg-yellow-500 rounded-full mt-2"></div>
                                    @endif
                                    <div class="flex-1">
                                        <p class="text-sm text-gray-900">{{ $activity['message'] }}</p>
                                        <p class="text-xs text-gray-500 mt-1">{{ $activity['time']->diffForHumans() }}</p>
                                    </div>
                                    @if($activity['url'])
                                        <a href="{{ $activity['url'] }}" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                                            View →
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="px-6 py-8 text-center text-gray-500">
                            <p>No recent critical activity</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="{{ route('admin.campaigns.index', ['flagged' => 1]) }}" class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 hover:border-red-300 hover:shadow-xl transition-all group">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center group-hover:bg-red-200 transition-colors">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900">Review Flagged</h4>
                            <p class="text-sm text-gray-600">Campaigns requiring attention</p>
                        </div>
                    </div>
                </a>

                <a href="{{ route('admin.transactions.index') }}" class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 hover:border-blue-300 hover:shadow-xl transition-all group">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center group-hover:bg-blue-200 transition-colors">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900">Transaction Log</h4>
                            <p class="text-sm text-gray-600">View all financial transactions</p>
                        </div>
                    </div>
                </a>

                <a href="{{ route('admin.users.index') }}" class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 hover:border-purple-300 hover:shadow-xl transition-all group">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center group-hover:bg-purple-200 transition-colors">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900">User Management</h4>
                            <p class="text-sm text-gray-600">Manage all platform users</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
