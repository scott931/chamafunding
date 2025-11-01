<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            My Backed Projects
        </h2>
    </x-slot>

    <div class="py-6" x-data="backerDashboard" x-init="init()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Dashboard Summary -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6" x-show="summary">
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-600">Total Pledged</p>
                    <p class="text-2xl font-bold" x-text="summary?.total_pledged"></p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-600">Campaigns Backed</p>
                    <p class="text-2xl font-bold" x-text="summary?.total_campaigns_backed"></p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-600">Active Campaigns</p>
                    <p class="text-2xl font-bold" x-text="summary?.active_campaigns"></p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-600">Pending Surveys</p>
                    <p class="text-2xl font-bold text-yellow-600" x-text="summary?.pending_surveys"></p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-600">Unread Updates</p>
                    <p class="text-2xl font-bold text-blue-600" x-text="summary?.unread_updates"></p>
                </div>
            </div>

            <!-- Tabs -->
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="border-b border-gray-200">
                    <nav class="flex -mb-px">
                        <button @click="switchTab('pledges')"
                                :class="currentTab === 'pledges' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="px-6 py-3 border-b-2 font-medium text-sm">
                            My Pledges
                        </button>
                        <button @click="switchTab('updates')"
                                :class="currentTab === 'updates' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="px-6 py-3 border-b-2 font-medium text-sm">
                            Updates Feed
                        </button>
                        <button @click="switchTab('transactions')"
                                :class="currentTab === 'transactions' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="px-6 py-3 border-b-2 font-medium text-sm">
                            Transaction History
                        </button>
                    </nav>
                </div>
            </div>

            <!-- My Pledges Tab -->
            <div x-show="currentTab === 'pledges'" class="space-y-4">
                <div x-show="loading" class="text-center py-8">
                    <p class="text-gray-500">Loading pledges...</p>
                </div>

                <div x-show="!loading && pledges.length === 0" class="text-center py-8">
                    <p class="text-gray-500">You haven't backed any campaigns yet.</p>
                    <a href="{{ route('campaigns.public.index') }}" class="text-blue-600 hover:underline mt-2 inline-block">Browse Campaigns</a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" x-show="!loading && pledges.length > 0">
                    <template x-for="pledge in pledges" :key="pledge.id">
                        <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow cursor-pointer"
                             @click="viewPledgeDetails(pledge.id)">
                            <div class="p-6">
                                <!-- Campaign Image -->
                                <div class="mb-4">
                                    <img :src="pledge.campaign.featured_image || '/placeholder-campaign.jpg'"
                                         :alt="pledge.campaign.title"
                                         class="w-full h-48 object-cover rounded-lg">
                                </div>

                                <!-- Campaign Info -->
                                <h3 class="font-bold text-lg mb-2" x-text="pledge.campaign.title"></h3>
                                <p class="text-sm text-gray-600 mb-4" x-text="pledge.creator.name"></p>

                                <!-- Pledge Amount -->
                                <div class="mb-4">
                                    <p class="text-2xl font-bold" x-text="formatCurrency(pledge.pledge.amount, pledge.pledge.currency)"></p>
                                    <p class="text-sm text-gray-600" x-text="pledge.reward_tier?.name || 'General Contribution'"></p>
                                </div>

                                <!-- Status Badges -->
                                <div class="flex gap-2 mb-4">
                                    <span class="px-2 py-1 rounded text-xs font-medium"
                                          :class="getStatusBadgeClass(pledge.campaign.status)"
                                          x-text="pledge.campaign.status"></span>
                                    <span class="px-2 py-1 rounded text-xs font-medium"
                                          :class="getStatusBadgeClass(pledge.fulfillment.delivery_status)"
                                          x-text="pledge.fulfillment.delivery_status"></span>
                                </div>

                                <!-- Progress Bar -->
                                <div class="mb-4">
                                    <div class="flex justify-between text-sm mb-1">
                                        <span>Progress</span>
                                        <span x-text="pledge.campaign.progress_percentage + '%'"></span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-blue-600 h-2 rounded-full transition-all"
                                             :style="'width: ' + pledge.campaign.progress_percentage + '%'"></div>
                                    </div>
                                </div>

                                <!-- Estimated Delivery -->
                                <p class="text-sm text-gray-600" x-show="pledge.reward_tier?.estimated_delivery">
                                    <strong>Est. Delivery:</strong> <span x-text="pledge.reward_tier.estimated_delivery"></span>
                                </p>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Pagination -->
                <div class="flex justify-center mt-6" x-show="pledgesPagination.last_page > 1">
                    <button @click="loadPledges(pledgesPagination.current_page - 1)"
                            :disabled="pledgesPagination.current_page === 1"
                            class="px-4 py-2 bg-gray-200 rounded disabled:opacity-50"
                            :class="pledgesPagination.current_page === 1 ? 'cursor-not-allowed' : 'hover:bg-gray-300'">
                        Previous
                    </button>
                    <span class="px-4 py-2" x-text="'Page ' + pledgesPagination.current_page + ' of ' + pledgesPagination.last_page"></span>
                    <button @click="loadPledges(pledgesPagination.current_page + 1)"
                            :disabled="pledgesPagination.current_page === pledgesPagination.last_page"
                            class="px-4 py-2 bg-gray-200 rounded disabled:opacity-50"
                            :class="pledgesPagination.current_page === pledgesPagination.last_page ? 'cursor-not-allowed' : 'hover:bg-gray-300'">
                        Next
                    </button>
                </div>
            </div>

            <!-- Updates Feed Tab -->
            <div x-show="currentTab === 'updates'" class="space-y-4">
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">Campaign Updates</h3>

                    <div x-show="updates.length === 0" class="text-center py-8">
                        <p class="text-gray-500">No updates yet.</p>
                    </div>

                    <div class="space-y-6" x-show="updates.length > 0">
                        <template x-for="update in updates" :key="update.id">
                            <div class="border-b border-gray-200 pb-6 last:border-0">
                                <div class="flex items-start gap-4">
                                    <img :src="update.campaign.featured_image || '/placeholder-campaign.jpg'"
                                         class="w-16 h-16 rounded-lg object-cover">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-2">
                                            <h4 class="font-semibold" x-text="update.campaign.title"></h4>
                                            <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs"
                                                  x-text="update.type"></span>
                                        </div>
                                        <h5 class="font-medium mb-2" x-text="update.title"></h5>
                                        <p class="text-gray-600 mb-2" x-text="update.content"></p>
                                        <p class="text-sm text-gray-400" x-text="update.published_at_human"></p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Transaction History Tab -->
            <div x-show="currentTab === 'transactions'" class="bg-white rounded-lg shadow">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Transaction History</h3>

                    <div x-show="transactions.length === 0" class="text-center py-8">
                        <p class="text-gray-500">No transactions yet.</p>
                    </div>

                    <div class="overflow-x-auto" x-show="transactions.length > 0">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Campaign</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="transaction in transactions" :key="transaction.id">
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm" x-text="formatDate(transaction.date)"></td>
                                        <td class="px-6 py-4 text-sm" x-text="transaction.campaign.title"></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium"
                                            x-text="formatCurrency(transaction.amount, transaction.currency)"></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm" x-text="transaction.payment_method"></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 rounded text-xs font-medium"
                                                  :class="getStatusBadgeClass(transaction.status)"
                                                  x-text="transaction.status"></span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <button @click="downloadReceipt(transaction.id)"
                                                    class="text-blue-600 hover:text-blue-800">
                                                Receipt
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

        <!-- Pledge Details Modal -->
        @include('backer.partials.pledge-modal')

        <!-- Contribution Modal -->
        @include('backer.partials.contribute-modal')
    </div>

    @push('scripts')
        <script src="{{ asset('js/payments.js') }}"></script>
        <script src="{{ asset('js/backer-dashboard.js') }}"></script>
    @endpush
</x-app-layout>

