/**
 * Contributor Dashboard JavaScript
 * Manages the contributor dashboard interface
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('contributorDashboard', () => ({
        apiBase: '/api/v1/backer',
        token: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        authToken: window.authToken || null,

        // State
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

        formatCurrency(amount, currency = 'USD') {
            if (!amount) return '$0.00';
            const numAmount = typeof amount === 'string' ? parseFloat(amount.replace(/,/g, '')) : parseFloat(amount);
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: currency
            }).format(numAmount);
        },

        getFundingStatusBadgeClass(status) {
            const classes = {
                'live': 'bg-green-100 text-green-800',
                'successful': 'bg-blue-100 text-blue-800',
                'unsuccessful': 'bg-red-100 text-red-800',
                'active': 'bg-green-100 text-green-800',
                'failed': 'bg-red-100 text-red-800',
            };
            return classes[status?.toLowerCase()] || 'bg-gray-100 text-gray-800';
        },

        getFundingStatusLabel(status) {
            const labels = {
                'live': 'Live & Funding',
                'successful': 'Successful',
                'unsuccessful': 'Unsuccessful',
                'active': 'Live & Funding',
                'failed': 'Unsuccessful',
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

            // Generic tracking search
            return `https://www.google.com/search?q=track+${trackingNumber}`;
        },

        showNotification(message, type = 'success') {
            // Simple notification system
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
    }));
});

