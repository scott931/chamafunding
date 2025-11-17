<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900">
                    {{ __('Welcome, :name', ['name' => Auth::user()->name]) }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">Here's an overview of your account</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-10">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-8 text-white shadow-xl shadow-blue-500/30 hover:shadow-2xl hover:shadow-blue-500/40 transform hover:scale-[1.02] transition-all duration-300 border border-blue-400/20">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <p class="text-white/90 text-sm font-semibold uppercase tracking-wide mb-3">Total Campaigns</p>
                            <p class="text-4xl lg:text-5xl font-bold mb-2">0</p>
                        </div>
                        <div class="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center flex-shrink-0 ml-4">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl p-8 text-white shadow-xl shadow-purple-500/30 hover:shadow-2xl hover:shadow-purple-500/40 transform hover:scale-[1.02] transition-all duration-300 border border-purple-400/20">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <p class="text-white/90 text-sm font-semibold uppercase tracking-wide mb-3">Total Payments</p>
                            <p class="text-4xl lg:text-5xl font-bold mb-2">0</p>
                        </div>
                        <div class="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center flex-shrink-0 ml-4">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl p-8 text-white shadow-xl shadow-orange-500/30 hover:shadow-2xl hover:shadow-orange-500/40 transform hover:scale-[1.02] transition-all duration-300 border border-orange-400/20">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <p class="text-white/90 text-sm font-semibold uppercase tracking-wide mb-3">Contributions</p>
                            <p class="text-4xl lg:text-5xl font-bold mb-2">0</p>
                        </div>
                        <div class="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center flex-shrink-0 ml-4">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Feature Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
                <!-- Crowdfunding Card -->
                <a href="{{ route('crowdfunding.index') }}" class="group relative bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden border border-gray-100 hover:border-blue-300 transform hover:scale-[1.02]">
                    <div class="absolute top-0 right-0 w-40 h-40 bg-gradient-to-br from-blue-500/10 to-indigo-500/10 rounded-full -mr-20 -mt-20 group-hover:scale-150 transition-transform duration-500"></div>
                    <div class="relative p-8 lg:p-10">
                        <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center mb-6 shadow-lg shadow-blue-500/30 group-hover:scale-110 transition-transform">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-3 group-hover:text-blue-600 transition-colors">Crowdfunding</h3>
                        <p class="text-gray-600 text-base leading-relaxed mb-6">Discover and support amazing crowdfunding campaigns and projects.</p>
                        <div class="flex items-center text-blue-600 font-semibold text-sm">
                            <span>Explore</span>
                            <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </div>
                </a>

                <!-- Payments Card -->
                <a href="{{ route('payments.index') }}" class="group relative bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden border border-gray-100 hover:border-indigo-300 transform hover:scale-[1.02]">
                    <div class="absolute top-0 right-0 w-40 h-40 bg-gradient-to-br from-indigo-500/10 to-blue-500/10 rounded-full -mr-20 -mt-20 group-hover:scale-150 transition-transform duration-500"></div>
                    <div class="relative p-8 lg:p-10">
                        <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-blue-600 rounded-xl flex items-center justify-center mb-6 shadow-lg shadow-indigo-500/30 group-hover:scale-110 transition-transform">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-3 group-hover:text-indigo-600 transition-colors">Payments</h3>
                        <p class="text-gray-600 text-base leading-relaxed mb-6">Manage payment methods, view transaction history, and process payments securely.</p>
                        <div class="flex items-center text-indigo-600 font-semibold text-sm">
                            <span>Explore</span>
                            <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </div>
                </a>

                <!-- Reports Card -->
                <a href="{{ route('reports.index') }}" class="group relative bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden border border-gray-100 hover:border-amber-300 transform hover:scale-[1.02]">
                    <div class="absolute top-0 right-0 w-40 h-40 bg-gradient-to-br from-amber-500/10 to-orange-500/10 rounded-full -mr-20 -mt-20 group-hover:scale-150 transition-transform duration-500"></div>
                    <div class="relative p-8 lg:p-10">
                        <div class="w-16 h-16 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center mb-6 shadow-lg shadow-amber-500/30 group-hover:scale-110 transition-transform">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-3 group-hover:text-amber-600 transition-colors">Reports</h3>
                        <p class="text-gray-600 text-base leading-relaxed mb-6">Generate comprehensive reports and analytics to track your financial performance.</p>
                        <div class="flex items-center text-amber-600 font-semibold text-sm">
                            <span>Explore</span>
                            <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </div>
                </a>

                <!-- Admin Card (only for authorized users) -->
                @if(auth()->user()->hasAnyRole(['Treasurer', 'Secretary', 'Auditor']))
                <a href="{{ route('admin.index') }}" class="group relative bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden border border-gray-100 hover:border-red-300 transform hover:scale-[1.02]">
                    <div class="absolute top-0 right-0 w-40 h-40 bg-gradient-to-br from-red-500/10 to-rose-500/10 rounded-full -mr-20 -mt-20 group-hover:scale-150 transition-transform duration-500"></div>
                    <div class="relative p-8 lg:p-10">
                        <div class="w-16 h-16 bg-gradient-to-br from-red-500 to-rose-600 rounded-xl flex items-center justify-center mb-6 shadow-lg shadow-red-500/30 group-hover:scale-110 transition-transform">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-3 group-hover:text-red-600 transition-colors">Admin</h3>
                        <p class="text-gray-600 text-base leading-relaxed mb-6">Access administrative controls, manage users, and configure system settings.</p>
                        <div class="flex items-center text-red-600 font-semibold text-sm">
                            <span>Explore</span>
                            <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </div>
                </a>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
