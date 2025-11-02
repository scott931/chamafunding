/**
 * Backer Dashboard JavaScript
 * Manages the backer dashboard interface and interactions
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('backerDashboard', () => ({
        apiBase: '/api/v1/backer',
        token: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        authToken: window.authToken || null,

        // State
        loading: false,
        summary: null,
        pledges: [],
        updates: [],
        transactions: [],
        paymentHistory: [],
        campaignCount: null,
        currentTab: 'pledges',
        selectedPledge: null,
        showPledgeModal: false,
        showContributeModal: false,
        selectedCampaign: null,

        // Pagination
        pledgesPagination: { current_page: 1, last_page: 1, per_page: 15 },
        updatesPagination: { current_page: 1, last_page: 1, per_page: 20 },

        init() {
            this.loadDashboardSummary();
            this.loadPledges();
            this.loadUpdates();
            this.loadCampaignCountData();
        },

        async loadCampaignCountData() {
            try {
                const data = await this.request('/campaigns-count');
                this.campaignCount = data.data.total_campaigns;
            } catch (error) {
                console.error('Failed to load campaign count:', error);
            }
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
                    headers: { ...headers, ...options.headers }
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

        async loadDashboardSummary() {
            try {
                const data = await this.request('/dashboard/summary');
                this.summary = data.data;
            } catch (error) {
                console.error('Failed to load dashboard summary:', error);
            }
        },

        async loadPledges(page = 1) {
            this.loading = true;
            try {
                const data = await this.request(`/pledges?per_page=15&page=${page}`);
                this.pledges = data.data.pledges;
                this.pledgesPagination = data.data.pagination;
            } catch (error) {
                console.error('Failed to load pledges:', error);
            } finally {
                this.loading = false;
            }
        },

        async loadUpdates(page = 1) {
            try {
                const data = await this.request(`/updates?per_page=20&page=${page}`);
                this.updates = data.data.updates;
                this.updatesPagination = data.data.pagination;
            } catch (error) {
                console.error('Failed to load updates:', error);
            }
        },

        async loadTransactions(page = 1) {
            this.loading = true;
            try {
                const data = await this.request(`/transactions?per_page=20&page=${page}`);
                this.transactions = data.data.transactions;
            } catch (error) {
                console.error('Failed to load transactions:', error);
            } finally {
                this.loading = false;
            }
        },

        async loadPaymentHistory(page = 1) {
            this.loading = true;
            try {
                const data = await this.request(`/payment-history?per_page=15&page=${page}`);
                return data.data;
            } catch (error) {
                console.error('Failed to load payment history:', error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async loadCampaignCount() {
            try {
                const data = await this.request('/campaigns-count');
                return data.data.total_campaigns;
            } catch (error) {
                console.error('Failed to load campaign count:', error);
                throw error;
            }
        },

        async loadCampaignTotalPayment(campaignId) {
            try {
                const data = await this.request(`/campaigns/${campaignId}/total-payment`);
                return data.data;
            } catch (error) {
                console.error('Failed to load campaign total payment:', error);
                throw error;
            }
        },


        async viewPledgeDetails(pledgeId) {
            try {
                const data = await this.request(`/pledges/${pledgeId}`);
                this.selectedPledge = data.data;
                this.showPledgeModal = true;
            } catch (error) {
                console.error('Failed to load pledge details:', error);
            }
        },

        async updateShippingAddress(contributionId, shippingData) {
            try {
                const data = await this.request(`/pledges/${contributionId}/shipping`, {
                    method: 'PUT',
                    body: JSON.stringify(shippingData)
                });

                this.showNotification('Shipping address updated successfully', 'success');
                this.loadPledges(this.pledgesPagination.current_page);

                if (this.selectedPledge && this.selectedPledge.contribution.id === contributionId) {
                    this.selectedPledge.shipping = data.data.shipping;
                }

                return data;
            } catch (error) {
                console.error('Failed to update shipping address:', error);
                throw error;
            }
        },

        async saveCampaign(campaignId) {
            try {
                await this.request('/save-campaign', {
                    method: 'POST',
                    body: JSON.stringify({ campaign_id: campaignId })
                });

                this.showNotification('Campaign saved to watchlist', 'success');
            } catch (error) {
                console.error('Failed to save campaign:', error);
            }
        },

        async unsaveCampaign(campaignId) {
            try {
                await this.request(`/unsave-campaign/${campaignId}`, {
                    method: 'DELETE'
                });

                this.showNotification('Campaign removed from watchlist', 'success');
            } catch (error) {
                console.error('Failed to unsave campaign:', error);
            }
        },

        async downloadReceipt(contributionId) {
            try {
                const data = await this.request(`/transactions/${contributionId}/receipt`);
                this.showReceiptModal(data.data);
            } catch (error) {
                console.error('Failed to download receipt:', error);
            }
        },

        showReceiptModal(receipt) {
            // Create and show receipt modal
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            modal.innerHTML = `
                <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4">
                    <h2 class="text-2xl font-bold mb-4">Receipt</h2>
                    <div class="space-y-2 mb-6">
                        <p><strong>Receipt Number:</strong> ${receipt.receipt_number}</p>
                        <p><strong>Date:</strong> ${receipt.date}</p>
                        <p><strong>Transaction ID:</strong> ${receipt.transaction_id}</p>
                        <p><strong>Amount:</strong> ${receipt.contribution.amount} ${receipt.contribution.currency}</p>
                    </div>
                    <button onclick="this.closest('.fixed').remove(); window.print();"
                            class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Print Receipt
                    </button>
                    <button onclick="this.closest('.fixed').remove();"
                            class="ml-2 bg-gray-300 px-4 py-2 rounded hover:bg-gray-400">
                        Close
                    </button>
                </div>
            `;
            document.body.appendChild(modal);
        },

        showNotification(message, type = 'success') {
            // Simple notification system - can be enhanced with a proper toast library
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 p-4 rounded shadow-lg z-50 ${
                type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
            }`;
            notification.textContent = message;
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.remove();
            }, 3000);
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
                'active': 'bg-green-100 text-green-800',
                'successful': 'bg-blue-100 text-blue-800',
                'failed': 'bg-red-100 text-red-800',
                'pending': 'bg-yellow-100 text-yellow-800',
                'succeeded': 'bg-green-100 text-green-800',
            };
            return classes[status.toLowerCase()] || 'bg-gray-100 text-gray-800';
        },

        switchTab(tab) {
            this.currentTab = tab;

            if (tab === 'transactions' && this.transactions.length === 0) {
                this.loadTransactions();
            }

            if (tab === 'payment-history' && this.paymentHistory.length === 0) {
                this.loadPaymentHistoryForTab();
            }
        },

        async loadPaymentHistoryForTab(page = 1) {
            this.loading = true;
            try {
                const response = await this.request(`/payment-history?per_page=15&page=${page}`);
                console.log('Payment history API response:', JSON.stringify(response, null, 2)); // Debug log

                // Laravel paginator structure: { success: true, data: { data: [...items...], current_page: 1, ... } }
                if (response && response.success && response.data) {
                    // Check if it's a paginator object (has data property with array)
                    if (response.data.data && Array.isArray(response.data.data)) {
                        this.paymentHistory = response.data.data;
                        console.log('Loaded payment history items:', this.paymentHistory.length);
                    }
                    // Check if data is directly an array
                    else if (Array.isArray(response.data)) {
                        this.paymentHistory = response.data;
                        console.log('Loaded payment history items (direct array):', this.paymentHistory.length);
                    }
                    // Otherwise it might be an object with items
                    else if (response.data.items && Array.isArray(response.data.items)) {
                        this.paymentHistory = response.data.items;
                        console.log('Loaded payment history items (items property):', this.paymentHistory.length);
                    }
                    else {
                        console.warn('Unexpected response structure:', response.data);
                        this.paymentHistory = [];
                    }
                } else {
                    console.warn('Invalid response structure:', response);
                    this.paymentHistory = [];
                }
            } catch (error) {
                console.error('Failed to load payment history:', error);
                if (error.message) {
                    console.error('Error message:', error.message);
                }
                if (error.stack) {
                    console.error('Error stack:', error.stack);
                }
                this.paymentHistory = [];
            } finally {
                this.loading = false;
            }
        },

        openContributeModal(campaign) {
            this.selectedCampaign = campaign;
            this.showContributeModal = true;
        }
    }));
});

