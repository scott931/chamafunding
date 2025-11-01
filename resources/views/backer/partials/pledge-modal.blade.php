<!-- Pledge Details Modal -->
<div x-show="showPledgeModal"
     x-cloak
     class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
     @click.self="showPledgeModal = false">
    <div class="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto"
         x-show="selectedPledge">
        <div class="p-6">
            <!-- Header -->
            <div class="flex justify-between items-start mb-6">
                <h2 class="text-2xl font-bold" x-text="selectedPledge?.campaign?.title"></h2>
                <button @click="showPledgeModal = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Campaign Info -->
            <div class="grid grid-cols-2 gap-6 mb-6">
                <div>
                    <p class="text-sm text-gray-600">Creator</p>
                    <p class="font-medium" x-text="selectedPledge?.creator?.name"></p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Pledge Amount</p>
                    <p class="font-bold text-xl"
                       x-text="formatCurrency(selectedPledge?.contribution?.amount, selectedPledge?.contribution?.currency)"></p>
                </div>
            </div>

            <!-- Reward Tier -->
            <div class="mb-6" x-show="selectedPledge?.reward_tier">
                <h3 class="font-semibold mb-2">Reward Tier</h3>
                <div class="bg-gray-50 p-4 rounded">
                    <p class="font-medium" x-text="selectedPledge.reward_tier.name"></p>
                    <p class="text-sm text-gray-600 mt-1" x-text="selectedPledge.reward_tier.description"></p>
                    <p class="text-sm text-gray-600 mt-2" x-show="selectedPledge.reward_tier.estimated_delivery">
                        <strong>Est. Delivery:</strong> <span x-text="selectedPledge.reward_tier.estimated_delivery"></span>
                    </p>
                </div>
            </div>

            <!-- Shipping Address -->
            <div class="mb-6" x-show="selectedPledge?.reward_tier?.requires_shipping">
                <h3 class="font-semibold mb-2">Shipping Address</h3>
                <div x-show="!selectedPledge?.shipping?.address" class="bg-yellow-50 border border-yellow-200 rounded p-4">
                    <p class="text-yellow-800 text-sm mb-2">Shipping address is required for this reward.</p>
                    <button @click="$dispatch('show-shipping-form')"
                            class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">
                        Add Shipping Address
                    </button>
                </div>
                <div x-show="selectedPledge?.shipping?.address" class="bg-gray-50 p-4 rounded">
                    <p x-text="selectedPledge.shipping.full_address"></p>
                    <button @click="$dispatch('edit-shipping-address')"
                            class="text-blue-600 hover:text-blue-800 text-sm mt-2">
                        Edit Address
                    </button>
                </div>
            </div>

            <!-- Delivery Tracking -->
            <div class="mb-6" x-show="selectedPledge?.delivery?.tracking_number">
                <h3 class="font-semibold mb-2">Delivery Tracking</h3>
                <div class="bg-gray-50 p-4 rounded">
                    <p class="text-sm">
                        <strong>Tracking Number:</strong> <span x-text="selectedPledge.delivery.tracking_number"></span>
                    </p>
                    <p class="text-sm" x-show="selectedPledge.delivery.tracking_carrier">
                        <strong>Carrier:</strong> <span x-text="selectedPledge.delivery.tracking_carrier"></span>
                    </p>
                    <p class="text-sm">
                        <strong>Status:</strong>
                        <span class="px-2 py-1 rounded text-xs font-medium ml-2"
                              :class="getStatusBadgeClass(selectedPledge.delivery.status)"
                              x-text="selectedPledge.delivery.status"></span>
                    </p>
                </div>
            </div>

            <!-- Survey Status -->
            <div class="mb-6" x-show="selectedPledge?.reward_tier">
                <h3 class="font-semibold mb-2">Survey</h3>
                <div x-show="!selectedPledge?.survey?.completed" class="bg-yellow-50 border border-yellow-200 rounded p-4">
                    <p class="text-yellow-800 text-sm mb-2">Survey is required for this reward.</p>
                    <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">
                        Complete Survey
                    </button>
                </div>
                <div x-show="selectedPledge?.survey?.completed" class="bg-green-50 border border-green-200 rounded p-4">
                    <p class="text-green-800 text-sm">✓ Survey completed</p>
                </div>
            </div>
        </div>
    </div>
</div>

