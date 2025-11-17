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

            <!-- Campaigns Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($campaigns as $campaign)
                    <div class="group bg-white rounded-3xl shadow-xl shadow-slate-200/60 border-2 border-slate-200/80 overflow-hidden hover:shadow-2xl hover:shadow-slate-300/70 transition-all duration-500 transform hover:scale-[1.02] hover:-translate-y-1">
                        <!-- Card Header -->
                        <div class="p-6 pb-4 border-b border-slate-100">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center gap-3 flex-1 min-w-0">
                                    <div class="flex-shrink-0 w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center ring-2 ring-indigo-50">
                                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-black text-slate-900 text-lg mb-1 truncate">{{ Str::limit($campaign->title, 35) }}</h3>
                                        <p class="text-xs text-slate-500 font-medium">ID: {{ $campaign->id }}</p>
                                    </div>
                                </div>
                                @if($campaign->status === 'active')
                                    <span class="px-3 py-1 bg-green-100 text-green-800 rounded-xl text-xs font-bold border border-green-200">Active</span>
                                @elseif($campaign->status === 'successful')
                                    <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-xl text-xs font-bold border border-blue-200">Successful</span>
                                @elseif($campaign->status === 'draft' || $campaign->status === 'pending')
                                    <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-xl text-xs font-bold border border-yellow-200">Review</span>
                                @elseif($campaign->status === 'suspended')
                                    <span class="px-3 py-1 bg-red-100 text-red-800 rounded-xl text-xs font-bold border border-red-200">Suspended</span>
                                @else
                                    <span class="px-3 py-1 bg-gray-100 text-gray-800 rounded-xl text-xs font-bold border border-gray-200 capitalize">{{ $campaign->status }}</span>
                                @endif
                            </div>

                            <!-- Creator Info -->
                            <div class="flex items-center gap-2 text-sm">
                                <div class="w-8 h-8 bg-slate-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-bold text-slate-900 truncate">{{ $campaign->creator->name ?? 'N/A' }}</p>
                                    <p class="text-xs text-slate-500 truncate">{{ $campaign->creator->email ?? '' }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="p-6 space-y-4">
                            <!-- Category -->
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-slate-600 uppercase tracking-wide">Category</span>
                                <span class="px-3 py-1 bg-slate-100 text-slate-700 rounded-lg text-xs font-bold capitalize">
                                    {{ str_replace('_', ' ', $campaign->category) }}
                                </span>
                            </div>

                            <!-- Funding Progress -->
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-semibold text-slate-600 uppercase tracking-wide">Progress</span>
                                    <span class="text-sm font-black text-slate-900">{{ number_format($campaign->progress_percentage, 1) }}%</span>
                                </div>
                                <div class="w-full bg-slate-200 rounded-full h-3 shadow-inner">
                                    <div class="bg-indigo-600 h-3 rounded-full transition-all duration-500 shadow-sm"
                                         style="width: {{ min(100, $campaign->progress_percentage) }}%"></div>
                                </div>
                            </div>

                            <!-- Stats Grid -->
                            <div class="grid grid-cols-3 gap-3 pt-2">
                                <div class="text-center p-3 bg-slate-50 rounded-xl border border-slate-100">
                                    <p class="text-xs font-semibold text-slate-600 mb-1">Goal</p>
                                    <p class="text-sm font-black text-slate-900">${{ number_format($campaign->goal_amount / 100, 0) }}</p>
                                </div>
                                <div class="text-center p-3 bg-emerald-50 rounded-xl border border-emerald-100">
                                    <p class="text-xs font-semibold text-emerald-700 mb-1">Raised</p>
                                    <p class="text-sm font-black text-emerald-900">${{ number_format($campaign->raised_amount / 100, 0) }}</p>
                                </div>
                                <div class="text-center p-3 bg-purple-50 rounded-xl border border-purple-100">
                                    <p class="text-xs font-semibold text-purple-700 mb-1">Backers</p>
                                    <p class="text-sm font-black text-purple-900">{{ $campaign->contributions_count ?? 0 }}</p>
                                </div>
                            </div>

                            <!-- Date -->
                            <div class="flex items-center gap-2 text-xs text-slate-500 pt-2 border-t border-slate-100">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span class="font-medium">Created {{ $campaign->created_at->format('M d, Y') }}</span>
                            </div>
                        </div>

                        <!-- Card Footer -->
                        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-2">
                            <a href="{{ route('admin.campaigns.show', $campaign->id) }}"
                               class="flex-1 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-sm text-center transition-colors shadow-md hover:shadow-lg">
                                View Details
                            </a>
                            <form method="POST" action="{{ route('admin.campaigns.update-status', $campaign->id) }}" class="flex gap-2">
                                @csrf
                                @method('PATCH')
                                @if($campaign->status !== 'active')
                                    <button type="submit" name="status" value="active"
                                            class="px-3 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-xl font-bold text-xs transition-colors shadow-md hover:shadow-lg"
                                            title="Approve">
                                        ✓
                                    </button>
                                @endif
                                @if($campaign->status !== 'suspended')
                                    <button type="submit" name="status" value="suspended"
                                            class="px-3 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold text-xs transition-colors shadow-md hover:shadow-lg"
                                            title="Suspend">
                                        ⚠
                                    </button>
                                @endif
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full">
                        <div class="bg-white rounded-3xl shadow-xl border-2 border-slate-200 p-12 text-center">
                            <svg class="w-16 h-16 mx-auto mb-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                            <p class="text-xl font-black text-slate-900 mb-2">No campaigns found</p>
                            <p class="text-sm text-slate-600">Try adjusting your filters</p>
                        </div>
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            @if($campaigns->hasPages())
                <div class="mt-6 flex justify-center">
                    <div class="bg-white rounded-2xl shadow-lg border border-slate-200 p-4">
                        {{ $campaigns->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

