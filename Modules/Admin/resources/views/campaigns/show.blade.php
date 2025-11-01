<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <div class="flex items-center space-x-3">
                    <a href="{{ route('admin.campaigns.index') }}" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </a>
                    <h2 class="font-bold text-2xl text-gray-900">{{ $campaign->title }}</h2>
                </div>
                <p class="text-sm text-gray-600 mt-1">Campaign ID: {{ $campaign->id }} | Created: {{ $campaign->created_at->format('M d, Y') }}</p>
            </div>
            <div class="flex items-center space-x-2">
                <form method="POST" action="{{ route('admin.campaigns.update-status', $campaign->id) }}" class="inline">
                    @csrf
                    @method('PATCH')
                    @if($campaign->status !== 'active')
                        <button type="submit" name="status" value="active"
                                class="px-4 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition-colors">
                            Approve Campaign
                        </button>
                    @endif
                    @if($campaign->status !== 'suspended')
                        <button type="submit" name="status" value="suspended"
                                class="px-4 py-2 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 transition-colors">
                            Suspend
                        </button>
                    @endif
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- Campaign Overview Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
                    <p class="text-sm text-gray-500 mb-1">Goal Amount</p>
                    <p class="text-2xl font-bold text-gray-900">${{ number_format($campaign->goal_amount / 100, 2) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
                    <p class="text-sm text-gray-500 mb-1">Raised Amount</p>
                    <p class="text-2xl font-bold text-blue-600">${{ number_format($campaign->raised_amount / 100, 2) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
                    <p class="text-sm text-gray-500 mb-1">Progress</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($campaign->progress_percentage, 1) }}%</p>
                    <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-2 rounded-full"
                             style="width: {{ min(100, $campaign->progress_percentage) }}%"></div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
                    <p class="text-sm text-gray-500 mb-1">Total Backers</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $campaign->contributions_count ?? 0 }}</p>
                </div>
            </div>

            <!-- Campaign Details & Creator Info -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Campaign Details -->
                <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Campaign Details</h3>
                    <div class="space-y-4">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Category</p>
                            <p class="font-medium text-gray-900 capitalize">{{ str_replace('_', ' ', $campaign->category) }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Status</p>
                            @if($campaign->status === 'active')
                                <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-semibold">Active</span>
                            @elseif($campaign->status === 'successful')
                                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold">Successful</span>
                            @elseif($campaign->status === 'draft' || $campaign->status === 'pending')
                                <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm font-semibold">Under Review</span>
                            @else
                                <span class="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-sm font-semibold capitalize">{{ $campaign->status }}</span>
                            @endif
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Currency</p>
                            <p class="font-medium text-gray-900">{{ $campaign->currency }}</p>
                        </div>
                        @if($campaign->deadline)
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Deadline</p>
                                <p class="font-medium text-gray-900">{{ $campaign->deadline->format('F j, Y') }}</p>
                            </div>
                        @endif
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Description</p>
                            <p class="text-gray-900 leading-relaxed">{{ $campaign->description }}</p>
                        </div>
                    </div>
                </div>

                <!-- Creator Information -->
                <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Creator Information</h3>
                    @if($campaign->creator)
                        <div class="space-y-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-semibold">
                                    {{ strtoupper(substr($campaign->creator->name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">{{ $campaign->creator->name }}</p>
                                    <p class="text-sm text-gray-500">{{ $campaign->creator->email }}</p>
                                </div>
                            </div>
                            <div class="pt-4 border-t border-gray-200">
                                <a href="{{ route('admin.users.show', $campaign->creator->id) }}"
                                   class="inline-flex items-center text-blue-600 hover:text-blue-700 font-medium">
                                    View Full Profile →
                                </a>
                            </div>
                        </div>
                    @else
                        <p class="text-gray-500">Creator information not available</p>
                    @endif
                </div>
            </div>

            <!-- Backers List -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                    <h3 class="text-lg font-bold text-gray-900">Backers & Contributions</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Backer</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Payment Processor</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Date</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($contributions as $contribution)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $contribution->user->name ?? 'Anonymous' }}</div>
                                        <div class="text-xs text-gray-500">{{ $contribution->user->email ?? '' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                        ${{ number_format($contribution->amount / 100, 2) }} {{ $contribution->currency }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 capitalize">
                                        {{ $contribution->payment_processor ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($contribution->status === 'succeeded')
                                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-semibold">Completed</span>
                                        @elseif($contribution->status === 'pending')
                                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs font-semibold">Pending</span>
                                        @elseif($contribution->status === 'failed')
                                            <span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs font-semibold">Failed</span>
                                        @else
                                            <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs font-semibold capitalize">{{ $contribution->status }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $contribution->created_at->format('M d, Y H:i') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                        No contributions yet
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($contributions->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">
                        {{ $contributions->links() }}
                    </div>
                @endif
            </div>

            <!-- Internal Notes Section (Placeholder for future) -->
            <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Internal Admin Notes</h3>
                <p class="text-gray-500 text-sm">Internal notes feature coming soon. Use this space to track interactions with creators.</p>
            </div>
        </div>
    </div>
</x-app-layout>

