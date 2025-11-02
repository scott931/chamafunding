<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900">Payment History</h2>
                <p class="text-sm text-gray-600 mt-1">
                    @php
                        $user = auth()->user();
                        $isAdmin = $user && $user->hasAnyRole([
                            'Super Admin', 'Financial Admin', 'Moderator', 'Support Agent',
                            'Treasurer', 'Secretary', 'Auditor'
                        ]);
                    @endphp
                    @if($isAdmin)
                        View and manage all transactions across the platform
                    @else
                        View and manage all your transactions
                    @endif
                </p>
            </div>
        </div>
    </x-slot>

    <script>
        (function() {
            function registerPaymentsDashboard() {
                Alpine.data('paymentsDashboard', () => ({
                    apiBase: '/api/v1',
                    token: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    loading: false,
                    summary: null,
                    paymentHistory: [],
                    campaignCount: null,
                    isAdmin: {{ auth()->user() && auth()->user()->hasAnyRole(['Super Admin', 'Financial Admin', 'Moderator', 'Support Agent', 'Treasurer', 'Secretary', 'Auditor']) ? 'true' : 'false' }},

                    init() {
                        this.loadSummary();
                        this.loadPaymentHistory();
                        this.loadCampaignCount();
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
                                credentials: 'same-origin'
                            });

                            if (!response.ok) {
                                const errorText = await response.text();
                                console.error(`API Error (${response.status}):`, errorText);
                                throw new Error(`Request failed with status ${response.status}`);
                            }

                            const data = await response.json();
                            return data;
                        } catch (error) {
                            console.error('Request error for', url, ':', error);
                            throw error;
                        }
                    },

                    async loadSummary() {
                        try {
                            const response = await this.request('/payments-summary');
                            console.log('Summary response:', response);
                            if (response.success && response.data) {
                                this.summary = response.data;
                                console.log('Summary data loaded:', this.summary);
                            } else {
                                console.error('Summary response format error:', response);
                                this.summary = null;
                            }
                        } catch (error) {
                            console.error('Failed to load summary:', error);
                            this.summary = null;
                        }
                    },

                    async loadPaymentHistory() {
                        this.loading = true;
                        try {
                            const response = await this.request('/payments-history?per_page=15');

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
                        } catch (error) {
                            console.error('Failed to load payment history:', error);
                            this.paymentHistory = [];
                        } finally {
                            this.loading = false;
                        }
                    },

                    async loadCampaignCount() {
                        try {
                            const response = await this.request('/campaigns-count');
                            console.log('Campaign count response:', response);
                            if (response.success && response.data) {
                                this.campaignCount = response.data.total_campaigns ?? 0;
                                console.log('Campaign count loaded:', this.campaignCount);
                            } else {
                                console.error('Campaign count response format error:', response);
                                this.campaignCount = 0;
                            }
                        } catch (error) {
                            console.error('Failed to load campaign count:', error);
                            this.campaignCount = 0;
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
                registerPaymentsDashboard();
            } else {
                document.addEventListener('alpine:init', registerPaymentsDashboard);
            }
        })();
    </script>

    <div class="py-8" x-data="paymentsDashboard" x-init="init()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Total Payments -->
                <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl p-6 text-white shadow-lg shadow-blue-500/30">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-100 text-sm font-medium mb-1">Total Payments</p>
                            <p class="text-3xl font-bold" x-text="(summary?.total_payments ?? (paymentHistory?.length || 0)) || 0"></p>
                        </div>
                        <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Total Campaigns -->
                <div class="bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl p-6 text-white shadow-lg shadow-purple-500/30">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-purple-100 text-sm font-medium mb-1">Total Campaigns</p>
                            <p class="text-3xl font-bold" x-text="(campaignCount ?? summary?.total_campaigns) ?? 'Loading...'"></p>
                        </div>
                        <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Total Payment Amount -->
                <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl p-6 text-white shadow-lg shadow-emerald-500/30">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-emerald-100 text-sm font-medium mb-1">Total Amount</p>
                            <p class="text-3xl font-bold" x-text="summary?.total_payment_amount ? formatCurrency(summary.total_payment_amount / 100) : '$0.00'"></p>
                        </div>
                        <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Total Expenses -->
                <div class="bg-gradient-to-br from-red-500 to-orange-600 rounded-2xl p-6 text-white shadow-lg shadow-red-500/30">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-red-100 text-sm font-medium mb-1">Total Expenses</p>
                            <p class="text-3xl font-bold" x-text="summary?.total_expenses ? formatCurrency(summary.total_expenses / 100) : '$0.00'"></p>
                        </div>
                        <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Net Balance -->
                <div class="bg-gradient-to-br from-cyan-500 to-blue-600 rounded-2xl p-6 text-white shadow-lg shadow-cyan-500/30">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-cyan-100 text-sm font-medium mb-1">Net Balance</p>
                            <p class="text-3xl font-bold" x-text="summary?.net_balance ? formatCurrency(summary.net_balance / 100) : '$0.00'"></p>
                        </div>
                        <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Contributions -->
                <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl p-6 text-white shadow-lg shadow-green-500/30">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-100 text-sm font-medium mb-1">Contributions</p>
                            <p class="text-3xl font-bold" x-text="summary?.contributions_amount ? formatCurrency((summary.contributions_amount || 0) / 100) : '$0.00'"></p>
                        </div>
                        <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment History Section -->
            <div class="bg-white rounded-2xl shadow-lg p-8">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-2xl font-bold text-gray-900">Payment History</h3>
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
                                <th x-show="isAdmin" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                                <th x-show="isAdmin" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Campaign</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template x-for="(payment, index) in paymentHistory" :key="payment.id || index">
                                <tr class="hover:bg-gray-50">
                                    <td x-show="isAdmin" class="px-6 py-4 text-sm text-gray-900">
                                        <div x-show="payment.user">
                                            <div class="font-medium" x-text="payment.user.name || 'N/A'"></div>
                                            <div class="text-gray-500 text-xs" x-text="payment.user.email"></div>
                                        </div>
                                        <span x-show="!payment.user" class="text-gray-400">N/A</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="formatDate(payment.created_at)"></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-600" x-text="payment.reference || 'N/A'"></td>
                                    <td x-show="isAdmin" class="px-6 py-4 text-sm text-gray-900" x-text="payment.campaign ? payment.campaign.title : 'N/A'"></td>
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
