<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900">Campaign Management</h2>
                <p class="text-sm text-gray-600 mt-1">Master campaigns list with powerful filtering</p>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Filters -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-6 border border-gray-100">
                <form method="GET" action="{{ route('admin.campaigns.index') }}" class="flex flex-wrap items-end gap-4">
                    <!-- Search -->
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="Campaign title or description..."
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <!-- Status Filter -->
                    <div class="flex-1 min-w-[180px]">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Statuses</option>
                            @foreach($statuses as $status)
                                <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>
                                    {{ ucfirst($status) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Category Filter -->
                    <div class="flex-1 min-w-[180px]">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select name="category" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category }}" {{ request('category') === $category ? 'selected' : '' }}>
                                    {{ ucfirst(str_replace('_', ' ', $category)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors whitespace-nowrap">
                            Apply Filters
                        </button>
                        <a href="{{ route('admin.campaigns.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors whitespace-nowrap">
                            Reset
                        </a>
                    </div>
                </form>

                <!-- Quick Filter Buttons -->
                <div class="mt-4 flex items-center space-x-2 flex-wrap gap-2">
                    <a href="{{ route('admin.campaigns.index', ['flagged' => 1]) }}"
                       class="px-3 py-1 bg-red-100 text-red-700 rounded-lg text-sm font-medium hover:bg-red-200 transition-colors">
                        ⚠️ Flagged for Review
                    </a>
                    <a href="{{ route('admin.campaigns.index', ['status' => 'active']) }}"
                       class="px-3 py-1 bg-green-100 text-green-700 rounded-lg text-sm font-medium hover:bg-green-200 transition-colors">
                        ✓ Active Campaigns
                    </a>
                    <a href="{{ route('admin.campaigns.index', ['status' => 'successful']) }}"
                       class="px-3 py-1 bg-blue-100 text-blue-700 rounded-lg text-sm font-medium hover:bg-blue-200 transition-colors">
                        🎉 Successful
                    </a>
                </div>
            </div>

            <!-- Campaigns Table -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Campaign</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Creator</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Goal</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Raised</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">% Funded</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Backers</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Created</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($campaigns as $campaign)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-blue-100 to-indigo-100 rounded-lg flex items-center justify-center mr-3">
                                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="font-medium text-gray-900">{{ Str::limit($campaign->title, 40) }}</div>
                                                <div class="text-xs text-gray-500">ID: {{ $campaign->id }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $campaign->creator->name ?? 'N/A' }}</div>
                                        <div class="text-xs text-gray-500">{{ $campaign->creator->email ?? '' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs font-medium capitalize">
                                            {{ str_replace('_', ' ', $campaign->category) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        ${{ number_format($campaign->goal_amount / 100, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        ${{ number_format($campaign->raised_amount / 100, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-2 rounded-full"
                                                     style="width: {{ min(100, $campaign->progress_percentage) }}%"></div>
                                            </div>
                                            <span class="text-sm text-gray-700">{{ number_format($campaign->progress_percentage, 1) }}%</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $campaign->contributions_count ?? 0 }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($campaign->status === 'active')
                                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-semibold">Active</span>
                                        @elseif($campaign->status === 'successful')
                                            <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-semibold">Successful</span>
                                        @elseif($campaign->status === 'draft' || $campaign->status === 'pending')
                                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs font-semibold">Under Review</span>
                                        @elseif($campaign->status === 'suspended')
                                            <span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs font-semibold">Suspended</span>
                                        @else
                                            <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs font-semibold capitalize">{{ $campaign->status }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $campaign->created_at->format('M d, Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center space-x-2">
                                            <a href="{{ route('admin.campaigns.show', $campaign->id) }}"
                                               class="text-blue-600 hover:text-blue-900">View</a>
                                            <span class="text-gray-300">|</span>
                                            <form method="POST" action="{{ route('admin.campaigns.update-status', $campaign->id) }}" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                @if($campaign->status !== 'active')
                                                    <button type="submit" name="status" value="active"
                                                            class="text-green-600 hover:text-green-900">Approve</button>
                                                @endif
                                                @if($campaign->status !== 'suspended')
                                                    <button type="submit" name="status" value="suspended"
                                                            class="text-red-600 hover:text-red-900 ml-2">Suspend</button>
                                                @endif
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="px-6 py-12 text-center text-gray-500">
                                        <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                        </svg>
                                        <p class="text-lg font-medium">No campaigns found</p>
                                        <p class="text-sm">Try adjusting your filters</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($campaigns->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">
                        {{ $campaigns->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

