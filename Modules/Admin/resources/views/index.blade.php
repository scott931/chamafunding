<x-app-layout>
    <x-slot name="header">
        <div class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <!-- Top Row: Welcome & Actions -->
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Welcome Back, {{ Auth::user()->name }}</h2>
                    </div>

                    <!-- Right side: Search, Notifications, Profile -->
                    <div class="flex items-center space-x-4">
                        <!-- Search Bar -->
                        <div class="relative hidden md:block">
                            <input type="text" placeholder="Search anything..." class="w-64 pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                            <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>

                        <!-- Notifications -->
                        <button class="relative p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                        </button>

                        <!-- Messages -->
                        <button class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                        </button>

                        <!-- Settings -->
                        <button class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </button>

                        <!-- User Profile -->
                        <div class="flex items-center space-x-2">
                            <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </div>
                            <span class="text-sm font-medium text-gray-700 hidden md:block">{{ Auth::user()->name }}</span>
                            <svg class="w-4 h-4 text-gray-400 hidden md:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Date Range -->
                <div class="text-sm text-gray-600">
                    {{ now()->subDays(30)->format('M j, Y') }} - {{ now()->format('M j, Y') }} | Last 30 days
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

    <div class="bg-gray-50 min-h-screen" x-data="adminDashboard" x-init="init()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
            <!-- Top Row: 4 Key KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Platform Raised -->
                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm font-medium text-gray-600">Total Platform Raised</p>
                        <button class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                            </svg>
                        </button>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 mb-1">${{ number_format($stats['total_raised'] / 1000, 1) }}K</p>
                    <div class="flex items-center text-sm">
                        <span class="text-green-600 font-medium flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                            </svg>
                            +2.45%
                        </span>
                        <span class="text-gray-500 ml-2">vs last month</span>
                    </div>
                </div>

                <!-- Active Campaigns -->
                <a href="{{ route('admin.campaigns.index', ['status' => 'active']) }}" class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm font-medium text-gray-600">Active Campaigns</p>
                        <button class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                            </svg>
                        </button>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 mb-1">{{ $stats['active_campaigns'] }}</p>
                    <div class="flex items-center text-sm">
                        <span class="text-green-600 font-medium flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                            </svg>
                            +1.20%
                        </span>
                        <span class="text-gray-500 ml-2">Live right now</span>
                    </div>
                </a>

                <!-- Total Backers -->
                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm font-medium text-gray-600">Total Backers</p>
                        <button class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                            </svg>
                        </button>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 mb-1">{{ number_format($stats['total_backers_alltime']) }}</p>
                    <div class="flex items-center text-sm">
                        <span class="text-red-600 font-medium flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                            </svg>
                            -0.50%
                        </span>
                        <span class="text-gray-500 ml-2">{{ $stats['total_backers_month'] }} this month</span>
                    </div>
                </div>

                <!-- Platform Fees -->
                <a href="{{ route('admin.financial.index') }}" class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm font-medium text-gray-600">Platform Fees</p>
                        <button class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                            </svg>
                        </button>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 mb-1">${{ number_format($stats['platform_fees_month'], 0) }}</p>
                    <div class="flex items-center text-sm">
                        <span class="text-green-600 font-medium flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                            </svg>
                            +0.84%
                        </span>
                        <span class="text-gray-500 ml-2">This month</span>
                    </div>
                </a>

            </div>

            <!-- Middle Row: Left (Total Profit with Chart) & Right (Two Cards) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left: Total Profit Card with Funding Over Time Chart -->
                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="mb-6">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Total Profit</h3>
                        <p class="text-4xl font-bold text-gray-900">${{ number_format($stats['platform_fees_month'] / 1000, 0) }}K</p>
                    </div>

                    <!-- Funding Over Time Chart -->
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-sm font-semibold text-gray-700">Funding Over Time</h4>
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
                                <span class="text-sm font-medium text-gray-700">Total Profit</span>
                                <span class="text-sm font-bold text-gray-900">${{ number_format($stats['total_raised'] / 1000, 1) }}M</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="bg-blue-600 h-2.5 rounded-full" style="width: 75%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700">Total Expenses</span>
                                <span class="text-sm font-bold text-gray-900">${{ number_format($stats['pending_payouts'] / 1000, 1) }}K</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="bg-green-500 h-2.5 rounded-full" style="width: 45%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700">Net Profit</span>
                                <span class="text-sm font-bold text-gray-900">${{ number_format(($stats['platform_fees_month']) / 1000, 0) }}K</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="bg-orange-500 h-2.5 rounded-full" style="width: 60%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Two Cards -->
                <div class="space-y-6">
                    <!-- Most Day Active -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Most Day Active</h3>
                        <div class="h-48">
                            <canvas id="campaignsByStatusChart"></canvas>
                        </div>
                    </div>

                    <!-- Campaign Status Rate -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
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
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left: Payment History Table -->
                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">Payment History</h3>
                                <p class="text-sm text-gray-600 mt-1">All payments made by all users</p>
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

                    <div class="overflow-x-auto" x-show="!loading && paymentHistory && paymentHistory.length > 0" x-transition>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Campaign</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="(payment, index) in paymentHistory.slice(0, 5)" :key="payment.id || index">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900" x-text="formatDate(payment.created_at)"></td>
                                        <td class="px-4 py-3 text-sm text-gray-900">
                                            <div x-show="payment.user">
                                                <div class="font-medium" x-text="payment.user.name || 'N/A'"></div>
                                                <div class="text-gray-500 text-xs" x-text="payment.user.email"></div>
                                            </div>
                                            <span x-show="!payment.user" class="text-gray-400">N/A</span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-mono text-gray-600" x-text="payment.reference || 'N/A'"></td>
                                        <td class="px-4 py-3 text-sm text-gray-900" x-text="payment.campaign ? payment.campaign.title.substring(0, 20) + '...' : 'N/A'"></td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900"
                                            x-text="formatCurrency((payment.amount || 0) / 100, payment.currency || 'USD')"></td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600" x-text="payment.payment_method || 'N/A'"></td>
                                        <td class="px-4 py-3 whitespace-nowrap">
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

                <!-- Right: Recent Activity / AI Assistant -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Recent Activity</h3>
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
