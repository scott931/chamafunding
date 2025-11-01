<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <div class="flex items-center space-x-3">
                    <a href="{{ route('admin.users.index') }}" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </a>
                    <h2 class="font-bold text-2xl text-gray-900">{{ $user->name }}</h2>
                </div>
                <p class="text-sm text-gray-600 mt-1">{{ $user->email }} | User ID: {{ $user->id }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- User Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
                    <p class="text-sm text-gray-500 mb-1">Campaigns Created</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $user->campaigns_count ?? 0 }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
                    <p class="text-sm text-gray-500 mb-1">Total Contributed</p>
                    <p class="text-2xl font-bold text-blue-600">${{ number_format($totalContributed, 2) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
                    <p class="text-sm text-gray-500 mb-1">Total Raised</p>
                    <p class="text-2xl font-bold text-green-600">${{ number_format($totalRaised, 2) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
                    <p class="text-sm text-gray-500 mb-1">Contributions Made</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $user->contributions_count ?? 0 }}</p>
                </div>
            </div>

            <!-- User Campaigns -->
            @if($user->campaigns->count() > 0)
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-bold text-gray-900">Campaigns Created</h3>
                    </div>
                    <div class="divide-y divide-gray-200">
                        @foreach($user->campaigns as $campaign)
                            <div class="px-6 py-4 hover:bg-gray-50">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <a href="{{ route('admin.campaigns.show', $campaign->id) }}" class="font-medium text-gray-900 hover:text-blue-600">
                                            {{ $campaign->title }}
                                        </a>
                                        <p class="text-sm text-gray-500 mt-1">
                                            Goal: ${{ number_format($campaign->goal_amount / 100, 2) }} |
                                            Raised: ${{ number_format($campaign->raised_amount / 100, 2) }} |
                                            Status: <span class="capitalize">{{ $campaign->status }}</span>
                                        </p>
                                    </div>
                                    <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-xs font-medium capitalize">
                                        {{ $campaign->status }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- User Contributions -->
            @if($user->contributions->count() > 0)
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-bold text-gray-900">Contributions Made</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Campaign</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($user->contributions as $contribution)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <a href="{{ route('admin.campaigns.show', $contribution->campaign_id) }}" class="text-blue-600 hover:text-blue-800">
                                                {{ $contribution->campaign->title ?? 'N/A' }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 text-sm font-bold text-gray-900">
                                            ${{ number_format($contribution->amount / 100, 2) }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            {{ $contribution->created_at->format('M d, Y') }}
                                        </td>
                                        <td class="px-6 py-4">
                                            @if($contribution->status === 'succeeded')
                                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-semibold">Completed</span>
                                            @elseif($contribution->status === 'pending')
                                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs font-semibold">Pending</span>
                                            @else
                                                <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs font-semibold capitalize">{{ $contribution->status }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

