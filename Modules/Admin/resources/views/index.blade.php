<x-app-layout>
    <x-slot name="header">
        <div class="bg-white border-b border-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <!-- Top Row: Welcome & Actions -->
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-4 gap-4">
                    <div class="flex-1 min-w-0">
                        <h2 class="text-xl sm:text-2xl font-bold text-gray-900">Welcome Back, {{ Auth::user()->name }}</h2>
                        <div class="text-xs sm:text-sm text-gray-500 mt-1">
                            <span class="block sm:inline">{{ now()->subDays(30)->format('M j, Y') }} - {{ now()->format('M j, Y') }}</span>
                            <span class="hidden sm:inline"> | </span>
                            <span class="block sm:inline">Last 30 days</span>
                        </div>
                    </div>

                    <!-- Right side: Search, Notifications, Profile -->
                    <div class="flex items-center space-x-2 sm:space-x-3 w-full sm:w-auto">
                        <!-- Search Bar -->
                        <div class="relative flex-1 sm:flex-none sm:block hidden md:block">
                            <input type="text" placeholder="Search anything..." class="w-full sm:w-64 pl-10 pr-4 py-2.5 bg-gray-50 border-0 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-sm transition-all duration-200">
                            <svg class="absolute left-3 top-3 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>

                        <!-- Notifications Dropdown -->
                        <div class="relative" x-data="notificationDropdown()" @click.away="dropdownOpen = false">
                            <button
                                @click="toggleDropdown()"
                                class="relative p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-xl transition-all duration-200"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                                <span x-show="unreadCount > 0" class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                                <span x-show="unreadCount > 0" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center" x-text="unreadCount > 99 ? '99+' : unreadCount"></span>
                            </button>

                            <!-- Dropdown Panel -->
                            <div
                                x-show="dropdownOpen"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="transform opacity-0 scale-95"
                                x-transition:enter-end="transform opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="transform opacity-100 scale-100"
                                x-transition:leave-end="transform opacity-0 scale-95"
                                class="absolute right-0 mt-2 w-[calc(100vw-2rem)] sm:w-96 max-w-md bg-white rounded-xl shadow-lg border border-gray-100 z-50 max-h-[600px] overflow-hidden flex flex-col"
                                style="display: none;"
                            >
                                <!-- Header -->
                                <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                                    <h3 class="text-lg font-semibold text-gray-900">Transaction Notifications</h3>
                                    <button
                                        @click="markAllAsRead()"
                                        class="text-sm text-blue-600 hover:text-blue-800 font-medium transition-colors"
                                        x-show="unreadCount > 0"
                                    >
                                        Mark all read
                                    </button>
                                </div>

                                <!-- Loading State -->
                                <div x-show="loading" class="p-8 text-center">
                                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                                    <p class="mt-2 text-sm text-gray-500">Loading notifications...</p>
                                </div>

                                <!-- Empty State -->
                                <div x-show="!loading && notifications.length === 0" class="p-8 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-500">No transaction notifications</p>
                                </div>

                                <!-- Notifications List -->
                                <div x-show="!loading && notifications.length > 0" class="overflow-y-auto flex-1">
                                    <template x-for="(campaign, index) in notifications" :key="campaign.campaign_id">
                                        <div class="border-b border-gray-50 last:border-b-0 hover:bg-gray-50 transition-colors">
                                            <!-- Campaign Header -->
                                            <div class="px-4 py-3 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
                                                <div class="flex-1 min-w-0">
                                                    <h4 class="text-sm font-semibold text-gray-900 truncate" x-text="campaign.campaign_name"></h4>
                                                    <p class="text-xs text-gray-500 mt-1">
                                                        <span x-text="campaign.total_transactions"></span> transaction<span x-show="campaign.total_transactions > 1">s</span> •
                                                        <span x-text="campaign.formatted_total_amount"></span> <span x-text="campaign.currency"></span>
                                                    </p>
                                                </div>
                                                <button
                                                    @click="markCampaignAsRead(campaign.campaign_id)"
                                                    class="ml-2 text-xs text-blue-600 hover:text-blue-800 font-medium transition-colors"
                                                    title="Mark as read"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                </button>
                                            </div>

                                            <!-- Transactions in Campaign -->
                                            <div class="px-4 py-2">
                                                <template x-for="(transaction, tIndex) in campaign.transactions" :key="transaction.id">
                                                    <div class="py-2 border-b border-gray-50 last:border-b-0">
                                                        <div class="flex items-start justify-between">
                                                            <div class="flex-1 min-w-0">
                                                                <div class="flex items-center space-x-2">
                                                                    <span class="text-sm font-medium text-gray-900" x-text="transaction.user_name"></span>
                                                                    <span class="text-xs text-gray-500" x-text="transaction.formatted_date"></span>
                                                                </div>
                                                                <p class="text-xs text-gray-500 mt-1" x-text="transaction.reference"></p>
                                                            </div>
                                                            <div class="ml-4 text-right">
                                                                <p class="text-sm font-semibold text-gray-900" x-text="transaction.formatted_amount"></p>
                                                                <p class="text-xs text-gray-500" x-text="transaction.currency"></p>
                                                            </div>
                                                        </div>
                                                        <div class="mt-1">
                                                            <span
                                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                                                :class="{
                                                                    'bg-green-100 text-green-800': transaction.status === 'completed' || transaction.status === 'succeeded',
                                                                    'bg-yellow-100 text-yellow-800': transaction.status === 'pending' || transaction.status === 'processing',
                                                                    'bg-red-100 text-red-800': transaction.status === 'failed'
                                                                }"
                                                                x-text="transaction.status.charAt(0).toUpperCase() + transaction.status.slice(1)"
                                                            ></span>
                                                        </div>
                                                    </div>
                                                </template>

                                                <div x-show="campaign.total_transactions > campaign.transactions.length" class="pt-2 text-center">
                                                    <p class="text-xs text-gray-500">
                                                        +<span x-text="campaign.total_transactions - campaign.transactions.length"></span> more transaction<span x-show="(campaign.total_transactions - campaign.transactions.length) > 1">s</span>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <!-- Footer -->
                                <div class="px-4 py-3 border-t border-gray-100 bg-gray-50">
                                    <a href="{{ route('admin.transactions.index') }}" class="block text-center text-sm text-blue-600 hover:text-blue-800 font-medium transition-colors">
                                        View all transactions
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-slot>

    <script>
        (function() {
            function registerAdminDashboard() {
                Alpine.data('adminDashboard', () => ({
                    apiBase: '/api/v1/admin',
                    token: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    authToken: window.authToken || null,
                    loading: false,
                    paymentHistory: [],
                    campaignCount: null,
                    dashboardStats: null,
                    reportsAvailable: null,
                    charts: {},

                    init() {
                        this.loadDashboardStats();
                        this.loadPaymentHistory();
                        this.loadCampaignCount();
                        this.loadReportsAvailable();
                        // Wait for Chart.js to load before initializing charts
                        this.waitForChartJS(() => {
                            this.initCharts();
                        });
                    },

                    waitForChartJS(callback, attempts = 0) {
                        const maxAttempts = 50; // Wait up to 5 seconds for CDN to load
                        if (typeof Chart !== 'undefined') {
                            this.$nextTick(callback);
                        } else if (window.chartJsLoaded === false) {
                            console.error('Chart.js failed to load from all CDN sources. Charts will not be displayed.');
                        } else if (attempts < maxAttempts) {
                            setTimeout(() => {
                                this.waitForChartJS(callback, attempts + 1);
                            }, 100);
                        } else {
                            console.error('Chart.js loading timeout. Charts will not be displayed.');
                            // Show a message to the user
                            const chartContainers = document.querySelectorAll('[id$="Chart"]');
                            chartContainers.forEach(container => {
                                if (container && !container.querySelector('canvas')) {
                                    container.innerHTML = '<div class="flex items-center justify-center h-full text-gray-400"><p>Chart unavailable - CDN connection issue</p></div>';
                                }
                            });
                        }
                    },

                    initCharts() {
                        if (typeof Chart === 'undefined') {
                            console.error('Chart.js is not available. Cannot initialize charts.');
                            return;
                        }

                        // Funding Over Time Chart
                        this.initFundingChart();
                        // Campaigns by Status Chart
                        this.initCampaignsByStatusChart();
                        // Growth Chart
                        this.initGrowthChart();
                        // Top Categories Chart
                        this.initTopCategoriesChart();
                    },

                    async request(url, options = {}) {
                        const headers = {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.token,
                            'X-Requested-With': 'XMLHttpRequest'
                        };

                        try {
                            const fullUrl = `${this.apiBase}${url}`;
                            console.log('Admin API request:', fullUrl);

                            const response = await fetch(fullUrl, {
                                ...options,
                                headers: { ...headers, ...options.headers },
                                credentials: 'same-origin'
                            });

                            if (!response.ok) {
                                const errorText = await response.text();
                                console.error(`Admin API Error (${response.status}):`, errorText);
                                throw new Error(`Request failed with status ${response.status}: ${errorText}`);
                            }

                            const data = await response.json();
                            return data;
                        } catch (error) {
                            console.error('Admin request error for', url, ':', error);
                            throw error;
                        }
                    },

                    async loadDashboardStats() {
                        try {
                            const data = await this.request('/dashboard-stats');
                            this.dashboardStats = data.data;
                        } catch (error) {
                            console.error('Failed to load dashboard stats:', error);
                        }
                    },

                    async loadPaymentHistory() {
                        this.loading = true;
                        try {
                            const response = await this.request('/payment-history?per_page=50');
                            console.log('Admin payment history response:', response);

                            if (response.success && response.data) {
                                if (response.data.data && Array.isArray(response.data.data)) {
                                    this.paymentHistory = response.data.data;
                                } else if (Array.isArray(response.data)) {
                                    this.paymentHistory = response.data;
                                } else {
                                    this.paymentHistory = [];
                                }
                            } else {
                                this.paymentHistory = [];
                            }

                            console.log('Admin payment history items:', this.paymentHistory);
                            console.log('Payment history count:', this.paymentHistory.length);
                        } catch (error) {
                            console.error('Failed to load payment history:', error);
                            this.paymentHistory = [];
                        } finally {
                            this.loading = false;
                        }
                    },

                    async loadCampaignCount() {
                        try {
                            const data = await this.request('/campaigns-count');
                            this.campaignCount = data.data.total_campaigns;
                        } catch (error) {
                            console.error('Failed to load campaign count:', error);
                        }
                    },

                    async loadReportsAvailable() {
                        try {
                            const response = await this.request('/reports-available');
                            if (response.success && response.data) {
                                // Count the number of available reports
                                this.reportsAvailable = Object.keys(response.data).length;
                            } else {
                                this.reportsAvailable = 0;
                            }
                        } catch (error) {
                            console.error('Failed to load reports available:', error);
                            this.reportsAvailable = 0;
                        }
                    },

                    formatDate(dateString) {
                        if (!dateString) return 'N/A';
                        const date = new Date(dateString);
                        return date.toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                    },

                    formatCurrency(amount, currency = 'USD') {
                        return new Intl.NumberFormat('en-US', {
                            style: 'currency',
                            currency: currency
                        }).format(parseFloat(amount));
                    },

                    getStatusBadgeClass(status) {
                        const classes = {
                            'completed': 'bg-green-100 text-green-800',
                            'pending': 'bg-yellow-100 text-yellow-800',
                            'failed': 'bg-red-100 text-red-800',
                            'processing': 'bg-blue-100 text-blue-800',
                        };
                        return classes[status?.toLowerCase()] || 'bg-gray-100 text-gray-800';
                    },

                    initFundingChart() {
                        if (typeof Chart === 'undefined') {
                            console.error('Chart.js not loaded');
                            return;
                        }

                        const ctx = document.getElementById('fundingChart');
                        if (!ctx) return;

                        const fundingData = @json($fundingOverTime);
                        const dates = fundingData.map(item => {
                            const date = new Date(item.date);
                            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                        });
                        const amounts = fundingData.map(item => parseFloat(item.total) / 100);

                        this.charts.funding = new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: dates,
                                datasets: [{
                                    label: 'Funding ($)',
                                    data: amounts,
                                    borderColor: 'rgb(99, 102, 241)',
                                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                                    tension: 0.4,
                                    fill: true,
                                    borderWidth: 2
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                return '$' + context.parsed.y.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            callback: function(value) {
                                                return '$' + value.toLocaleString();
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    },

                    initCampaignsByStatusChart() {
                        if (typeof Chart === 'undefined') {
                            console.error('Chart.js not loaded');
                            return;
                        }

                        const ctx = document.getElementById('campaignsByStatusChart');
                        if (!ctx) return;

                        const statusData = @json($campaignsByStatus);
                        const labels = statusData.map(item => {
                            return item.status.charAt(0).toUpperCase() + item.status.slice(1);
                        });
                        const counts = statusData.map(item => item.count);
                        const colors = [
                            'rgba(59, 130, 246, 0.8)',  // blue
                            'rgba(16, 185, 129, 0.8)',  // green
                            'rgba(245, 158, 11, 0.8)',  // amber
                            'rgba(239, 68, 68, 0.8)',   // red
                            'rgba(139, 92, 246, 0.8)',  // purple
                            'rgba(236, 72, 153, 0.8)',  // pink
                        ];

                        this.charts.campaignsByStatus = new Chart(ctx, {
                            type: 'doughnut',
                            data: {
                                labels: labels,
                                datasets: [{
                                    data: counts,
                                    backgroundColor: colors.slice(0, counts.length),
                                    borderWidth: 2,
                                    borderColor: '#fff'
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        position: 'right',
                                        labels: {
                                            padding: 15,
                                            font: {
                                                size: 12
                                            }
                                        }
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                                return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    },

                    initGrowthChart() {
                        if (typeof Chart === 'undefined') {
                            console.error('Chart.js not loaded');
                            return;
                        }

                        const ctx = document.getElementById('growthChart');
                        if (!ctx) return;

                        const growthData = @json($growthData);
                        const campaignData = growthData.campaigns || [];
                        const userData = growthData.users || [];

                        // Create date range for last 30 days
                        const dates = [];
                        const today = new Date();
                        for (let i = 29; i >= 0; i--) {
                            const date = new Date(today);
                            date.setDate(date.getDate() - i);
                            dates.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
                        }

                        // Map data to dates
                        const campaignCounts = dates.map(date => {
                            const match = campaignData.find(item => {
                                const itemDate = new Date(item.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                                return itemDate === date;
                            });
                            return match ? match.count : 0;
                        });

                        const userCounts = dates.map(date => {
                            const match = userData.find(item => {
                                const itemDate = new Date(item.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                                return itemDate === date;
                            });
                            return match ? match.count : 0;
                        });

                        this.charts.growth = new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: dates,
                                datasets: [{
                                    label: 'New Campaigns',
                                    data: campaignCounts,
                                    borderColor: 'rgb(139, 92, 246)',
                                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                                    tension: 0.4,
                                    fill: true
                                }, {
                                    label: 'New Users',
                                    data: userCounts,
                                    borderColor: 'rgb(236, 72, 153)',
                                    backgroundColor: 'rgba(236, 72, 153, 0.1)',
                                    tension: 0.4,
                                    fill: true
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: true,
                                        position: 'top'
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            stepSize: 1
                                        }
                                    }
                                }
                            }
                        });
                    },

                    initTopCategoriesChart() {
                        if (typeof Chart === 'undefined') {
                            console.error('Chart.js not loaded');
                            return;
                        }

                        const ctx = document.getElementById('topCategoriesChart');
                        if (!ctx) return;

                        const categoriesData = @json($topCategories);
                        const labels = categoriesData.map(item => {
                            return item.category.charAt(0).toUpperCase() + item.category.slice(1).replace(/_/g, ' ');
                        });
                        const amounts = categoriesData.map(item => parseFloat(item.total_raised) / 100);

                        this.charts.topCategories = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: labels,
                                datasets: [{
                                    label: 'Total Raised ($)',
                                    data: amounts,
                                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                                    borderColor: 'rgb(59, 130, 246)',
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                return '$' + context.parsed.y.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            callback: function(value) {
                                                return '$' + value.toLocaleString();
                                            }
                                        }
                                    },
                                    x: {
                                        ticks: {
                                            maxRotation: 45,
                                            minRotation: 45
                                        }
                                    }
                                }
                            }
                        });
                    }
                }));
            }

            if (typeof Alpine !== 'undefined') {
                registerAdminDashboard();
            } else {
                document.addEventListener('alpine:init', registerAdminDashboard);
            }
        })();
    </script>

    <!-- Notification Dropdown Component -->
    <script>
        function notificationDropdown() {
            return {
                dropdownOpen: false,
                notifications: [],
                unreadCount: 0,
                loading: false,
                readCampaigns: new Set(), // Track read campaigns

                async loadNotifications() {
                    this.loading = true;
                    try {
                        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        const response = await fetch('/api/v1/admin/transaction-notifications', {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': token,
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            credentials: 'same-origin'
                        });

                        if (!response.ok) {
                            throw new Error('Failed to load notifications');
                        }

                        const data = await response.json();
                        if (data.success && data.data) {
                            // Filter out read campaigns
                            this.notifications = data.data.notifications.filter(
                                campaign => !this.readCampaigns.has(campaign.campaign_id.toString())
                            );
                            // Recalculate unread count
                            this.unreadCount = this.notifications.reduce((sum, campaign) => sum + campaign.total_transactions, 0);
                        }
                    } catch (error) {
                        console.error('Error loading notifications:', error);
                        this.notifications = [];
                        this.unreadCount = 0;
                    } finally {
                        this.loading = false;
                    }
                },

                toggleDropdown() {
                    this.dropdownOpen = !this.dropdownOpen;
                    if (this.dropdownOpen) {
                        this.loadNotifications();
                    }
                },

                async markCampaignAsRead(campaignId) {
                    try {
                        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        const response = await fetch(`/api/v1/admin/notifications/${campaignId}/mark-read`, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': token,
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            credentials: 'same-origin'
                        });

                        if (response.ok) {
                            // Mark campaign as read locally
                            this.readCampaigns.add(campaignId.toString());
                            // Remove from notifications list
                            this.notifications = this.notifications.filter(
                                campaign => campaign.campaign_id.toString() !== campaignId.toString()
                            );
                            // Update unread count
                            this.unreadCount = this.notifications.reduce((sum, campaign) => sum + campaign.total_transactions, 0);
                        }
                    } catch (error) {
                        console.error('Error marking notification as read:', error);
                    }
                },

                async markAllAsRead() {
                    const campaignIds = this.notifications.map(campaign => campaign.campaign_id);
                    for (const campaignId of campaignIds) {
                        await this.markCampaignAsRead(campaignId);
                    }
                }
            }
        }
    </script>

    <!-- Chart.js CDN - Load with multiple fallbacks -->
    <script>
        (function() {
            const chartJsUrls = [
                'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
                'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js',
                'https://unpkg.com/chart.js@4.4.0/dist/chart.umd.min.js'
            ];

            let currentIndex = 0;

            function loadChartJS() {
                if (typeof Chart !== 'undefined') {
                    window.chartJsLoaded = true;
                    return;
                }

                if (currentIndex >= chartJsUrls.length) {
                    console.error('Failed to load Chart.js from all CDN sources. Charts will not be displayed.');
                    window.chartJsLoaded = false;
                    return;
                }

                const script = document.createElement('script');
                script.src = chartJsUrls[currentIndex];
                script.async = false; // Load synchronously

                script.onload = function() {
                    // Give Chart.js a moment to initialize
                    setTimeout(() => {
                        if (typeof Chart !== 'undefined') {
                            console.log('Chart.js loaded successfully from:', chartJsUrls[currentIndex]);
                            window.chartJsLoaded = true;
                        } else {
                            console.warn('Chart.js script loaded but Chart object not available, trying next CDN');
                            currentIndex++;
                            loadChartJS();
                        }
                    }, 50);
                };

                script.onerror = function() {
                    console.warn('Failed to load Chart.js from:', chartJsUrls[currentIndex]);
                    currentIndex++;
                    loadChartJS();
                };

                document.head.appendChild(script);
            }

            // Start loading immediately
            loadChartJS();
        })();
    </script>

    <div class="bg-gray-50 min-h-screen pb-20 lg:pb-0" x-data="adminDashboard" x-init="init()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6 space-y-4 sm:space-y-6">
            <!-- Top Row: 4 Key KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 lg:gap-5">
                <!-- Total Platform Raised -->
                <div class="bg-gradient-to-br from-emerald-500 to-green-600 rounded-xl sm:rounded-2xl p-4 sm:p-6 shadow-lg shadow-emerald-500/30 hover:shadow-xl hover:shadow-emerald-500/40 transition-all duration-200 transform hover:scale-105">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs sm:text-sm font-medium text-white/90">Total Platform Raised</p>
                        <button class="text-white/70 hover:text-white transition-colors">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                            </svg>
                        </button>
                    </div>
                    <p class="text-2xl sm:text-3xl font-bold text-white mb-1">${{ number_format($stats['total_raised'] / 1000, 1) }}K</p>
                    <div class="flex items-center text-sm">
                        <span class="text-white/90 font-medium flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                            </svg>
                            +2.45%
                        </span>
                        <span class="text-white/70 ml-2">vs last month</span>
                    </div>
                </div>

                <!-- Active Campaigns -->
                <a href="{{ route('admin.campaigns.index', ['status' => 'active']) }}" class="bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl sm:rounded-2xl p-4 sm:p-6 shadow-lg shadow-blue-500/30 hover:shadow-xl hover:shadow-blue-500/40 transition-all duration-200 transform hover:scale-105 group">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs sm:text-sm font-medium text-white/90">Active Campaigns</p>
                        <button class="text-white/70 hover:text-white transition-colors">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                            </svg>
                        </button>
                    </div>
                    <p class="text-2xl sm:text-3xl font-bold text-white mb-1">{{ $stats['active_campaigns'] }}</p>
                    <div class="flex items-center text-sm">
                        <span class="text-white/90 font-medium flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                            </svg>
                            +1.20%
                        </span>
                        <span class="text-white/70 ml-2">Live right now</span>
                    </div>
                </a>

                <!-- Total Backers -->
                <div class="bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl sm:rounded-2xl p-4 sm:p-6 shadow-lg shadow-purple-500/30 hover:shadow-xl hover:shadow-purple-500/40 transition-all duration-200 transform hover:scale-105">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs sm:text-sm font-medium text-white/90">Total Backers</p>
                        <button class="text-white/70 hover:text-white transition-colors">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                            </svg>
                        </button>
                    </div>
                    <p class="text-2xl sm:text-3xl font-bold text-white mb-1">{{ number_format($stats['total_backers_alltime']) }}</p>
                    <div class="flex items-center text-sm">
                        <span class="text-white/90 font-medium flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                            </svg>
                            -0.50%
                        </span>
                        <span class="text-white/70 ml-2">{{ $stats['total_backers_month'] }} this month</span>
                    </div>
                </div>

                <!-- Platform Fees -->
                <a href="{{ route('admin.financial.index') }}" class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl sm:rounded-2xl p-4 sm:p-6 shadow-lg shadow-amber-500/30 hover:shadow-xl hover:shadow-amber-500/40 transition-all duration-200 transform hover:scale-105 group">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs sm:text-sm font-medium text-white/90">Platform Fees</p>
                        <button class="text-white/70 hover:text-white transition-colors">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                            </svg>
                        </button>
                    </div>
                    <p class="text-2xl sm:text-3xl font-bold text-white mb-1">${{ number_format($stats['platform_fees_month'], 0) }}</p>
                    <div class="flex items-center text-sm">
                        <span class="text-white/90 font-medium flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                            </svg>
                            +0.84%
                        </span>
                        <span class="text-white/70 ml-2">This month</span>
                    </div>
                </a>
            </div>

            <!-- Middle Row: Left (Contributions with Chart) & Right (Two Cards) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
                <!-- Left: Contributions Card with Funding Over Time Chart -->
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="mb-6">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Contributions</h3>
                        <p class="text-4xl font-bold text-gray-900">${{ number_format($stats['platform_fees_month'] / 1000, 0) }}K</p>
                    </div>

                    <!-- Funding Over Time Chart -->
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-sm font-semibold text-gray-700">Contributions Over Time</h4>
                            <span class="text-xs text-gray-500">Jan 2024 - Mar 2024</span>
                        </div>
                        <div class="h-48">
                            <canvas id="fundingChart"></canvas>
                        </div>
                    </div>

                    <!-- Progress Bars -->
                    <div class="space-y-4">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700">Contributions</span>
                                <span class="text-sm font-bold text-gray-900">${{ number_format($stats['total_raised'] / 1000, 1) }}M</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2.5">
                                <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-500" style="width: 75%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700">Total Expenses</span>
                                <span class="text-sm font-bold text-gray-900">${{ number_format($stats['pending_payouts'] / 1000, 1) }}K</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2.5">
                                <div class="bg-green-500 h-2.5 rounded-full transition-all duration-500" style="width: 45%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700">Net Profit</span>
                                <span class="text-sm font-bold text-gray-900">${{ number_format(($stats['platform_fees_month']) / 1000, 0) }}K</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2.5">
                                <div class="bg-orange-500 h-2.5 rounded-full transition-all duration-500" style="width: 60%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Two Cards -->
                <div class="space-y-6">
                    <!-- Most Day Active -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Most Day Active</h3>
                        <div class="h-48">
                            <canvas id="campaignsByStatusChart"></canvas>
                        </div>
                    </div>

                    <!-- Campaign Status Rate -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Campaign Status Rate</h3>
                        <div class="h-48 flex items-center justify-center">
                            <canvas id="topCategoriesChart"></canvas>
                        </div>
                        <div class="mt-4 text-center">
                            <p class="text-3xl font-bold text-green-600">
                                {{ $campaignsByStatus->where('status', 'active')->first() ? round(($campaignsByStatus->where('status', 'active')->first()->count / $campaignsByStatus->sum('count')) * 100) : 0 }}%
                            </p>
                            <p class="text-sm text-gray-600 mt-1">Active campaigns</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Row: Payment History Table & Recent Activity -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
                <!-- Left: Payment History Table -->
                <div class="lg:col-span-2 bg-white rounded-xl sm:rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-4 sm:px-6 py-4 border-b border-gray-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-base sm:text-lg font-bold text-gray-900">Payment History</h3>
                                <p class="text-xs sm:text-sm text-gray-600 mt-1">All payments made by all users</p>
                            </div>
                        </div>
                    </div>

                    <div x-show="loading" class="text-center py-12">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                        <p class="mt-4 text-gray-500">Loading payment history...</p>
                    </div>

                    <div x-show="!loading && (!paymentHistory || paymentHistory.length === 0)" class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="mt-4 text-gray-500 font-medium">No payment history found</p>
                        <p class="mt-2 text-sm text-gray-400">Payment transactions will appear here once users make contributions</p>
                    </div>

                    <!-- Mobile Card View -->
                    <div class="lg:hidden space-y-3 px-4 pb-4" x-show="!loading && paymentHistory && paymentHistory.length > 0" x-transition>
                        <template x-for="(payment, index) in paymentHistory.slice(0, 5)" :key="payment.id || index">
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-semibold text-gray-900 truncate" x-text="payment.campaign ? payment.campaign.title : 'N/A'"></h4>
                                        <p class="text-xs text-gray-500 mt-1" x-text="formatDate(payment.created_at)"></p>
                                    </div>
                                    <span class="px-2.5 py-1 inline-flex rounded-full text-xs font-medium ml-2 flex-shrink-0"
                                          :class="getStatusBadgeClass(payment.status || 'pending')"
                                          x-text="(payment.status || 'pending').charAt(0).toUpperCase() + (payment.status || 'pending').slice(1)"></span>
                                </div>
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-gray-600">Amount</span>
                                        <span class="text-base font-bold text-gray-900" x-text="formatCurrency((payment.amount || 0) / 100, payment.currency || 'USD')"></span>
                                    </div>
                                    <div x-show="payment.user" class="flex items-center justify-between">
                                        <span class="text-xs text-gray-600">User</span>
                                        <span class="text-xs font-medium text-gray-900" x-text="payment.user.name || 'N/A'"></span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-gray-600">Method</span>
                                        <span class="text-xs text-gray-700" x-text="payment.payment_method || 'N/A'"></span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-gray-600">Reference</span>
                                        <span class="text-xs font-mono text-gray-600 truncate max-w-[120px]" x-text="payment.reference || 'N/A'"></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Desktop Table View -->
                    <div class="hidden lg:block overflow-x-auto -mx-4 sm:mx-0" x-show="!loading && paymentHistory && paymentHistory.length > 0" x-transition>
                        <div class="inline-block min-w-full align-middle">
                            <div class="overflow-hidden shadow-sm sm:rounded-lg">
                                <table class="min-w-full divide-y divide-gray-100">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campaign</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-100">
                                        <template x-for="(payment, index) in paymentHistory.slice(0, 5)" :key="payment.id || index">
                                            <tr class="hover:bg-gray-50 transition-colors">
                                                <td class="px-4 py-3 text-sm text-gray-900" x-text="formatDate(payment.created_at)"></td>
                                                <td class="px-4 py-3 text-sm text-gray-900">
                                                    <div x-show="payment.user">
                                                        <div class="font-medium" x-text="payment.user.name || 'N/A'"></div>
                                                        <div class="text-gray-500 text-xs" x-text="payment.user.email"></div>
                                                    </div>
                                                    <span x-show="!payment.user" class="text-gray-400">N/A</span>
                                                </td>
                                                <td class="px-4 py-3 text-sm font-mono text-gray-600" x-text="payment.reference || 'N/A'"></td>
                                                <td class="px-4 py-3 text-sm text-gray-900" x-text="payment.campaign ? payment.campaign.title.substring(0, 30) + '...' : 'N/A'"></td>
                                                <td class="px-4 py-3 text-sm font-medium text-gray-900"
                                                    x-text="formatCurrency((payment.amount || 0) / 100, payment.currency || 'USD')"></td>
                                                <td class="px-4 py-3 text-sm text-gray-600" x-text="payment.payment_method || 'N/A'"></td>
                                                <td class="px-4 py-3">
                                                    <span class="px-2 py-1 inline-flex rounded-full text-xs font-medium"
                                                          :class="getStatusBadgeClass(payment.status || 'pending')"
                                                          x-text="(payment.status || 'pending').charAt(0).toUpperCase() + (payment.status || 'pending').slice(1)"></span>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                    <!-- Right: Recent Activity / AI Assistant -->
                <div class="bg-white rounded-xl sm:rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-6">
                    <h3 class="text-base sm:text-lg font-bold text-gray-900 mb-4">Recent Activity</h3>
                    <div class="space-y-4 max-h-96 overflow-y-auto">
                        @if($recentActivity->count() > 0)
                            @foreach($recentActivity->take(6) as $activity)
                                <div class="flex items-start space-x-3 pb-4 border-b border-gray-100 last:border-0">
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
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-8 text-gray-500">
                                <p>No recent activity</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>