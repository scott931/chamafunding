<x-app-layout>
    <div class="py-4" x-data="contributorDashboard" x-init="init()">

        <!-- Header Section (moved inside Alpine component) -->
        <div class="mb-6 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">
                            <span x-text="getUserGreeting()"></span>
                        </h2>
                        <p class="text-sm text-gray-500 mt-1">Manage your contributions and track your impact</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            <!-- Loading State -->
            <div x-show="loading" class="flex items-center justify-center py-20">
                <div class="text-center">
                    <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-blue-600 border-t-transparent mb-4"></div>
                    <p class="text-gray-500">Loading your dashboard...</p>
                </div>
            </div>

            <!-- Main Content -->
            <div x-show="!loading" class="space-y-8">

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 lg:gap-8">
                    <div class="bg-gradient-to-br from-blue-500 to-cyan-600 rounded-2xl p-8 text-white shadow-xl shadow-blue-500/30 hover:shadow-2xl hover:shadow-blue-500/40 transition-all duration-300 transform hover:scale-[1.02] border border-blue-400/20">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-white/90 uppercase tracking-wide mb-3">Projects Backed</p>
                                <p class="text-4xl lg:text-5xl font-bold text-white mb-2" x-text="dashboardData?.summary?.total_projects_backed ?? 0"></p>
                            </div>
                            <div class="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center flex-shrink-0 ml-4">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-emerald-500 to-green-600 rounded-2xl p-8 text-white shadow-xl shadow-emerald-500/30 hover:shadow-2xl hover:shadow-emerald-500/40 transition-all duration-300 transform hover:scale-[1.02] border border-emerald-400/20">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-white/90 uppercase tracking-wide mb-3">Total Pledged</p>
                                <p class="text-4xl lg:text-5xl font-bold text-white mb-2"
                                   x-text="dashboardData?.summary ? formatCurrency(dashboardData.summary.total_amount_pledged, dashboardData.summary.currency) : '$0.00'"></p>
                            </div>
                            <div class="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center flex-shrink-0 ml-4">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Required Banner -->
                <div x-show="dashboardData?.action_items && dashboardData.action_items.length > 0"
                     class="bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 rounded-xl p-6">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Action Required</h3>
                            <div class="space-y-3">
                                <template x-for="action in (dashboardData?.action_items || [])" :key="action.contribution_id + '_' + action.type">
                                    <div class="bg-white rounded-lg p-4 border border-amber-200">
                                        <div class="flex items-start justify-between gap-4">
                                            <div class="flex-1">
                                                <p class="font-medium text-gray-900 mb-1" x-text="action.title"></p>
                                                <p class="text-sm text-gray-600 mb-3" x-text="action.message"></p>
                                                <a :href="action.action_url"
                                                   class="inline-flex items-center gap-2 px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 text-sm font-medium transition-colors">
                                                    Take Action
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                    </svg>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active Projects Section -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100">
                        <h2 class="text-xl font-semibold text-gray-900">Your Backed Campaigns</h2>
                        <p class="text-sm text-gray-500 mt-1">All projects you're supporting</p>
                    </div>

                    <!-- Empty State -->
                    <div x-show="!dashboardData?.active_backing || dashboardData.active_backing.length === 0"
                         class="text-center py-16 px-6">
                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-10 h-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No projects yet</h3>
                        <p class="text-sm text-gray-500 mb-6">Start supporting campaigns and track their progress here.</p>
                        <a href="/api/v1/campaigns"
                           class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            Browse Campaigns
                        </a>
                    </div>

                    <!-- Projects Table -->
                    <div class="overflow-x-auto" x-show="dashboardData?.active_backing && dashboardData.active_backing.length > 0">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Project</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pledge</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Funding Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Project Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progress</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="project in (dashboardData?.active_backing || [])" :key="project.id">
                                    <tr class="hover:bg-gray-50 transition-colors cursor-pointer" @click="viewProjectDetails(project)">
                                        <!-- Project Info -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-3">
                                                <div class="w-12 h-12 rounded-lg object-cover flex-shrink-0 bg-gray-200 flex items-center justify-center overflow-hidden relative">
                                                    <img x-show="project.campaign.featured_image"
                                                         :src="project.campaign.featured_image"
                                                         :alt="project.campaign.title"
                                                         class="w-full h-full object-cover"
                                                         @@error="$el.style.display='none'"
                                                         onerror="this.style.display='none'">
                                                    <div x-show="!project.campaign.featured_image" class="w-full h-full flex items-center justify-center absolute inset-0">
                                                        <svg class="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                        </svg>
                                                    </div>
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-sm font-semibold text-gray-900 truncate max-w-xs" x-text="project.campaign.title"></p>
                                                    <p class="text-xs text-gray-500" x-show="project.creator">
                                                        by <span class="font-medium" x-text="project.creator?.name || 'Unknown'"></span>
                                                    </p>
                                                    <p class="text-xs text-gray-400 mt-0.5" x-text="project.reward_tier?.name || 'General Contribution'"></p>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Pledge Amount -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <p class="text-sm font-bold text-blue-600" x-text="formatCurrency(project.pledge.amount, project.pledge.currency)"></p>
                                            <p class="text-xs text-gray-500 mt-0.5" x-text="new Date(project.pledge.date).toLocaleDateString()"></p>
                                        </td>

                                        <!-- Funding Status -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium"
                                                  :class="getFundingStatusBadgeClass(project.campaign.funding_status)"
                                                  x-text="getFundingStatusLabel(project.campaign.funding_status)"></span>
                                            <p class="text-xs text-gray-500 mt-1" x-show="project.campaign.days_remaining !== null && project.campaign.funding_status === 'live'">
                                                <span x-text="project.campaign.days_remaining"></span> days left
                                            </p>
                                        </td>

                                        <!-- Project Status -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium"
                                                  :class="getProjectStatusBadgeClass(project.campaign.project_status || 'pending')"
                                                  x-text="getProjectStatusLabel(project.campaign.project_status || 'pending')"></span>
                                            <div class="mt-1" x-show="project.fulfillment?.tracking_number">
                                                <a :href="getTrackingUrl(project.fulfillment.tracking_number, project.fulfillment.tracking_carrier)"
                                                   @click.stop
                                                   target="_blank"
                                                   class="text-xs text-blue-600 hover:text-blue-700 hover:underline inline-flex items-center gap-1">
                                                    Track
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                    </svg>
                                                </a>
                                            </div>
                                        </td>

                                        <!-- Progress -->
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="flex-1 min-w-[80px]">
                                                    <div class="flex justify-between text-xs mb-1" x-show="project.campaign.funding_status === 'live'">
                                                        <span class="text-gray-600">Progress</span>
                                                        <span class="font-semibold text-gray-900" x-text="project.campaign.progress_percentage + '%'"></span>
                                                    </div>
                                                    <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden" x-show="project.campaign.funding_status === 'live'">
                                                        <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-full rounded-full transition-all duration-500"
                                                             :style="'width: ' + project.campaign.progress_percentage + '%'"></div>
                                                    </div>
                                                    <p class="text-xs text-gray-400 mt-1" x-show="project.campaign.funding_status !== 'live'">—</p>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Actions -->
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a :href="'/api/v1/campaigns/' + project.campaign.id"
                                               @click.stop
                                               class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800 font-medium">
                                                View
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </a>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Quick Stats</h2>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center pb-3 border-b border-gray-100">
                            <span class="text-sm text-gray-600">Total Pledges</span>
                            <span class="text-lg font-semibold text-gray-900" x-text="dashboardData?.summary?.total_projects_backed ?? 0"></span>
                        </div>
                        <div class="flex justify-between items-center pb-3 border-b border-gray-100">
                            <span class="text-sm text-gray-600">Active Campaigns</span>
                            <span class="text-lg font-semibold text-gray-900" x-text="dashboardData?.stats?.active_campaigns ?? 0"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Pending Actions</span>
                            <span class="text-lg font-semibold text-gray-900" x-text="dashboardData?.stats?.pending_actions ?? 0"></span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Project Details Modal -->
        <div x-show="showProjectModal"
             class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4"
             @click.self="showProjectModal = false"
             x-cloak
             x-transition>
            <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto shadow-2xl"
                 @click.stop
                 x-transition>
                <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between z-10">
                    <h3 class="text-xl font-bold text-gray-900">Project Details</h3>
                    <button @click="showProjectModal = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="p-6" x-show="selectedProject">
                    <div class="space-y-6">
                        <!-- Campaign Image -->
                        <div x-show="selectedProject?.campaign?.featured_image">
                            <img :src="selectedProject?.campaign?.featured_image"
                                 :alt="selectedProject?.campaign?.title"
                                 class="w-full h-48 object-cover rounded-lg">
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-gray-500 mb-1">Campaign</h4>
                            <p class="text-lg font-semibold text-gray-900" x-text="selectedProject?.campaign?.title"></p>
                        </div>
                        <!-- Project Status -->
                        <div>
                            <h4 class="text-sm font-medium text-gray-500 mb-2">Project Status</h4>
                            <span class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium"
                                  :class="getProjectStatusBadgeClass(selectedProject?.campaign?.project_status || 'pending')"
                                  x-text="getProjectStatusLabel(selectedProject?.campaign?.project_status || 'pending')"></span>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-gray-500 mb-1">Your Pledge</h4>
                            <p class="text-3xl font-bold text-blue-600"
                               x-text="selectedProject ? formatCurrency(selectedProject.pledge.amount, selectedProject.pledge.currency) : ''"></p>
                        </div>
                        <div x-show="selectedProject?.reward_tier">
                            <h4 class="text-sm font-medium text-gray-500 mb-1">Reward Tier</h4>
                            <p class="text-lg font-medium text-gray-900" x-text="selectedProject?.reward_tier?.name"></p>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-gray-500 mb-2">Fulfillment Status</h4>
                            <span class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium"
                                  :class="getStatusBadgeClass(selectedProject?.fulfillment?.delivery_status)"
                                  x-text="selectedProject?.fulfillment?.delivery_status || 'pending'"></span>
                        </div>
                        <div class="flex gap-3 pt-4 border-t border-gray-200">
                            <a :href="'/api/v1/campaigns/' + selectedProject?.campaign?.id"
                               class="flex-1 text-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium transition-colors">
                                View Full Project
                            </a>
                            <button @click="showProjectModal = false"
                                    class="px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 font-medium text-gray-700 transition-colors">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('contributorDashboard', () => ({
                apiBase: '/api/v1/backer',
                token: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                authToken: window.authToken || null,

                loading: true,
                dashboardData: null,
                selectedProject: null,
                showProjectModal: false,

                init() {
                    this.loadDashboard();
                },

                async request(url, options = {}) {
                    const headers = {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.token,
                        ...(this.authToken && { 'Authorization': `Bearer ${this.authToken}` })
                    };

                    try {
                        const response = await fetch(`${this.apiBase}${url}`, {
                            ...options,
                            headers: { ...headers, ...options.headers },
                            credentials: 'same-origin'
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            throw new Error(data.message || 'Request failed');
                        }

                        return data;
                    } catch (error) {
                        console.error('Request error:', error);
                        this.showNotification(error.message, 'error');
                        throw error;
                    }
                },

                async loadDashboard() {
                    this.loading = true;
                    try {
                        const data = await this.request('/dashboard');
                        this.dashboardData = data.data;
                    } catch (error) {
                        console.error('Failed to load dashboard:', error);
                        this.showNotification('Failed to load dashboard data', 'error');
                    } finally {
                        this.loading = false;
                    }
                },

                viewProjectDetails(project) {
                    this.selectedProject = project;
                    this.showProjectModal = true;
                },

                getUserGreeting() {
                    if (this.dashboardData && this.dashboardData.user) {
                        const firstName = this.dashboardData.user.name.split(' ')[0];
                        return `Hello, ${firstName}!`;
                    }
                    return 'Dashboard';
                },

                formatCurrency(amount, currency = 'USD') {
                    if (!amount && amount !== 0) return '$0.00';
                    // Amount is in cents, so divide by 100
                    const numAmount = typeof amount === 'string'
                        ? parseFloat(amount.replace(/,/g, '')) / 100
                        : parseFloat(amount) / 100;
                    if (isNaN(numAmount)) return '$0.00';
                    return new Intl.NumberFormat('en-US', {
                        style: 'currency',
                        currency: currency
                    }).format(numAmount);
                },

                getFundingStatusBadgeClass(status) {
                    const classes = {
                        'live': 'bg-green-500 text-white',
                        'successful': 'bg-blue-500 text-white',
                        'unsuccessful': 'bg-red-500 text-white',
                        'active': 'bg-green-500 text-white',
                        'failed': 'bg-red-500 text-white',
                    };
                    return classes[status?.toLowerCase()] || 'bg-gray-500 text-white';
                },

                getFundingStatusLabel(status) {
                    const labels = {
                        'live': 'Live',
                        'successful': 'Funded',
                        'unsuccessful': 'Unsuccessful',
                        'active': 'Live',
                        'failed': 'Failed',
                    };
                    return labels[status?.toLowerCase()] || status || 'Unknown';
                },

                getProjectStatusBadgeClass(status) {
                    const classes = {
                        'in_production': 'bg-yellow-100 text-yellow-800',
                        'shipping': 'bg-blue-100 text-blue-800',
                        'delivered': 'bg-green-100 text-green-800',
                        'unsuccessful': 'bg-red-100 text-red-800',
                        'pending': 'bg-gray-100 text-gray-800',
                    };
                    return classes[status?.toLowerCase()] || 'bg-gray-100 text-gray-800';
                },

                getProjectStatusLabel(status) {
                    const labels = {
                        'in_production': 'In Production',
                        'shipping': 'Shipping',
                        'delivered': 'Delivered',
                        'unsuccessful': 'Unsuccessful',
                        'pending': 'Pending',
                    };
                    return labels[status?.toLowerCase()] || status || 'Unknown';
                },

                getStatusBadgeClass(status) {
                    const classes = {
                        'shipped': 'bg-blue-100 text-blue-800',
                        'delivered': 'bg-green-100 text-green-800',
                        'processing': 'bg-yellow-100 text-yellow-800',
                        'pending': 'bg-gray-100 text-gray-800',
                        'cancelled': 'bg-red-100 text-red-800',
                    };
                    return classes[status?.toLowerCase()] || 'bg-gray-100 text-gray-800';
                },

                getTrackingUrl(trackingNumber, carrier) {
                    if (!trackingNumber) return '#';

                    const carriers = {
                        'usps': `https://tools.usps.com/go/TrackConfirmAction?qtc_tLabels1=${trackingNumber}`,
                        'ups': `https://www.ups.com/track?tracknum=${trackingNumber}`,
                        'fedex': `https://www.fedex.com/fedextrack/?trknbr=${trackingNumber}`,
                        'dhl': `https://www.dhl.com/en/express/tracking.html?AWB=${trackingNumber}`,
                    };

                    if (carrier && carriers[carrier.toLowerCase()]) {
                        return carriers[carrier.toLowerCase()];
                    }

                    return `https://www.google.com/search?q=track+${trackingNumber}`;
                },

                showNotification(message, type = 'success') {
                    const notification = document.createElement('div');
                    notification.className = `fixed top-4 right-4 p-4 rounded-xl shadow-lg z-50 ${
                        type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
                    }`;
                    notification.textContent = message;
                    document.body.appendChild(notification);

                    setTimeout(() => {
                        notification.remove();
                    }, 3000);
                },
            }));
        });
    </script>
</x-app-layout>
