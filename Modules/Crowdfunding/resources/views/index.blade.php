<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900">Crowdfunding Campaigns</h2>
                <p class="text-sm text-gray-600 mt-1">
                    Discover and support amazing projects
                    @if(isset($campaigns))
                        <span class="text-gray-400">• {{ $campaigns->total() }} {{ Str::plural('campaign', $campaigns->total()) }}</span>
                    @endif
                </p>
            </div>
            @auth
                @if(auth()->user()->hasAnyRole(['Super Admin', 'Moderator', 'Financial Admin', 'Treasurer', 'Secretary', 'Auditor']))
                    <a href="{{ route('crowdfunding.create') }}"
                       class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-md transition-colors duration-200">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Create Campaign
                    </a>
                @endif
            @endauth
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4 flex items-center">
                    <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-green-800 font-medium">{{ session('success') }}</p>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 flex items-center">
                    <svg class="w-5 h-5 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-red-800 font-medium">{{ session('error') }}</p>
                </div>
            @endif

            @if(isset($campaigns) && $campaigns->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($campaigns as $campaign)
                        <div class="bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden border border-gray-100 group">
                            <div class="h-48 bg-gradient-to-br from-blue-400 to-indigo-600 relative overflow-hidden">
                                <div class="absolute inset-0 bg-black/20 group-hover:bg-black/10 transition-colors"></div>
                                <div class="absolute top-4 left-4">
                                    <span class="px-3 py-1 bg-white/90 backdrop-blur-sm rounded-full text-xs font-semibold text-gray-900">
                                        {{ ucfirst($campaign->status) }}
                                    </span>
                                </div>
                                <div class="absolute bottom-4 left-4 right-4">
                                    <h3 class="text-xl font-bold text-white mb-1">{{ $campaign->title }}</h3>
                                    <p class="text-white/90 text-sm line-clamp-2">{{ Str::limit($campaign->description, 80) }}</p>
                                </div>
                            </div>
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <div>
                                        <p class="text-sm text-gray-500">Goal</p>
                                        <p class="text-lg font-bold text-gray-900">{{ number_format($campaign->goal_amount / 100, 2) }} {{ $campaign->currency }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm text-gray-500">Raised</p>
                                        <p class="text-lg font-bold text-blue-600">{{ number_format($campaign->raised_amount / 100, 2) }} {{ $campaign->currency }}</p>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                                        <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-2.5 rounded-full" style="width: {{ min(100, ($campaign->raised_amount / $campaign->goal_amount) * 100) }}%"></div>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">{{ number_format(($campaign->raised_amount / $campaign->goal_amount) * 100, 1) }}% funded</p>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs px-3 py-1 bg-gray-100 text-gray-700 rounded-full font-medium">
                                        {{ ucfirst(str_replace('_', ' ', $campaign->category)) }}
                                    </span>
                                    <span class="text-xs text-gray-500">
                                        {{ $campaign->contributions_count ?? 0 }} contributions
                                    </span>
                                </div>
                                @if($campaign->status === 'active')
                                    <div class="mt-4">
                                        <a href="{{ route('crowdfunding.show', $campaign->id) }}"
                                           class="block w-full text-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                                            Support This Campaign
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-8">
                    {{ $campaigns->links() }}
                </div>
            @else
                <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
                    <div class="w-24 h-24 bg-gradient-to-br from-blue-100 to-indigo-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">No campaigns yet</h3>
                    <p class="text-gray-600 mb-6">Browse campaigns to discover and support amazing projects!</p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
