<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900">Reports & Analytics</h2>
                <p class="text-sm text-gray-600 mt-1">Comprehensive insights into your platform performance</p>
            </div>
        </div>
    </x-slot>

    <script>
        (function() {
            function registerReportsDashboard() {
                Alpine.data('reportsDashboard', () => ({
                    apiBase: '/api/v1/reports',
                    token: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    authToken: window.authToken || null,
                    loading: false,
                    paymentHistory: [],
                    campaignCount: null,
                    reportsAvailable: null,
                    analytics: null,
                    isAdmin: false,

                    init() {
                        this.checkAdminStatus();
                        this.loadPaymentHistory();
                        this.loadCampaignCount();
                        this.loadReportsAvailable();
                        this.loadAnalytics();
                    },

                    checkAdminStatus() {
                        // Check if user has admin roles (this is a simple check, you might want to pass this from backend)
                        // For now, we'll infer from payment history having user objects
                        this.isAdmin = false;
                    },

                    async request(url, options = {}) {
                        const headers = {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.token,
                            'X-Requested-With': 'XMLHttpRequest'
                        };

                        try {
                            const response = await fetch(`${this.apiBase}${url}`, {
                                ...options,
                                headers: { ...headers, ...options.headers },
                                credentials: 'same-origin' // Include cookies for session auth
                            });

                            const data = await response.json();
                            if (!response.ok) throw new Error(data.message || 'Request failed');
                            return data;
                        } catch (error) {
                            console.error('Request error:', error);
                            throw error;
                        }
                    },

                    async loadPaymentHistory() {
                        this.loading = true;
                        try {
                            const response = await this.request('/payment-history?per_page=50');
                            console.log('Reports payment history response:', response);

                            // Handle paginated response structure: { success: true, data: { data: [...], ... } }
                            if (response.success && response.data) {
                                if (response.data.data && Array.isArray(response.data.data)) {
                                    this.paymentHistory = response.data.data;
                                    // Check if admin based on user data presence
                                    if (this.paymentHistory.length > 0 && this.paymentHistory[0].user) {
                                        this.isAdmin = true;
                                    }
                                } else if (Array.isArray(response.data)) {
                                    this.paymentHistory = response.data;
                                } else {
                                    this.paymentHistory = [];
                                }
                            } else if (Array.isArray(response.data)) {
                                this.paymentHistory = response.data;
                            } else {
                                this.paymentHistory = [];
                            }

                            console.log('Reports payment history items:', this.paymentHistory);
                            console.log('Payment history count:', this.paymentHistory.length);
                        } catch (error) {
                            console.error('Failed to load payment history:', error);
                            console.error('Error details:', error.message);
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
                            // Use admin API endpoint for reports available
                            const adminApiBase = '/api/v1/admin';
                            const headers = {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.token,
                                'X-Requested-With': 'XMLHttpRequest'
                            };

                            const response = await fetch(`${adminApiBase}/reports-available`, {
                                method: 'GET',
                                headers: headers,
                                credentials: 'same-origin'
                            });

                            if (!response.ok) throw new Error('Failed to fetch reports available');

                            const data = await response.json();
                            if (data.success && data.data) {
                                // Count the number of available reports
                                this.reportsAvailable = Object.keys(data.data).length;
                            } else {
                                this.reportsAvailable = 0;
                            }
                        } catch (error) {
                            console.error('Failed to load reports available:', error);
                            this.reportsAvailable = 0;
                        }
                    },

                    async loadAnalytics() {
                        try {
                            const data = await this.request('/analytics');
                            if (data.success && data.data) {
                                this.analytics = data.data;
                                // Infer admin status from seeing user data in payment history
                                if (this.paymentHistory && this.paymentHistory.length > 0 && this.paymentHistory[0].user) {
                                    this.isAdmin = true;
                                }
                            }
                        } catch (error) {
                            console.error('Failed to load analytics:', error);
                            this.analytics = null;
                        }
                    },

                    formatDate(dateString) {
                        if (!dateString) return 'N/A';
                        const date = new Date(dateString);
                        return date.toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric'
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
                    }
                }));
            }

            if (typeof Alpine !== 'undefined') {
                registerReportsDashboard();
            } else {
                document.addEventListener('alpine:init', registerReportsDashboard);
            }
        })();
    </script>

    <div class="py-8" x-data="reportsDashboard" x-init="init()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Total Campaigns -->
                <div class="bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-purple-100 text-sm font-medium mb-1">Total Campaigns</p>
                            <p class="text-3xl font-bold" x-text="campaignCount ?? 'Loading...'"></p>
                        </div>
                        <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Payment History -->
                <div class="bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-100 text-sm font-medium mb-1">Payment History</p>
                            <p class="text-3xl font-bold" x-text="paymentHistory.length"></p>
                            <p class="text-blue-100 text-xs mt-1">payments recorded</p>
                        </div>
                        <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Reports & Analytics -->
                <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-amber-100 text-sm font-medium mb-1">Reports & Analytics</p>
                            <p class="text-3xl font-bold" x-text="reportsAvailable ?? '0'"></p>
                            <p class="text-amber-100 text-xs mt-1" x-show="reportsAvailable !== null">report types</p>
                            <p class="text-amber-100 text-xs mt-1" x-show="reportsAvailable === null">Loading...</p>
                        </div>
                        <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Reports Available -->
                <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-100 text-sm font-medium mb-1">Reports Available</p>
                            <p class="text-3xl font-bold">4</p>
                            <p class="text-green-100 text-xs mt-1">report types</p>
                        </div>
                        <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment History Section -->
            <div class="bg-white rounded-2xl shadow-lg p-8">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-2xl font-bold text-gray-900">Payment History</h3>
                    <span x-show="isAdmin" class="text-sm text-gray-600 bg-gray-100 px-3 py-1 rounded-full">Admin View - All Users</span>
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
                    <p class="mt-2 text-sm text-gray-400">Your payment transactions will appear here once you make contributions</p>
                    </div>

                <div class="overflow-x-auto" x-show="!loading && paymentHistory && paymentHistory.length > 0" x-transition>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th x-show="isAdmin || (paymentHistory && paymentHistory.length > 0 && paymentHistory[0].user)" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Campaign</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template x-for="(payment, index) in paymentHistory" :key="payment.id || index">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="formatDate(payment.created_at)"></td>
                                    <td x-show="isAdmin || payment.user" class="px-6 py-4 text-sm text-gray-900">
                                        <template x-if="payment.user">
                                            <div>
                                                <div class="font-medium" x-text="payment.user.name || 'N/A'"></div>
                                                <div class="text-gray-500 text-xs" x-text="payment.user.email"></div>
                                            </div>
                                        </template>
                                        <span x-show="!payment.user" class="text-gray-400">N/A</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-600" x-text="payment.reference || 'N/A'"></td>
                                    <td class="px-6 py-4 text-sm text-gray-900" x-text="payment.campaign ? payment.campaign.title : 'N/A'"></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"
                                        x-text="formatCurrency((payment.amount || 0) / 100, payment.currency || 'USD')"></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="payment.payment_method || 'N/A'"></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
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
</x-app-layout>
