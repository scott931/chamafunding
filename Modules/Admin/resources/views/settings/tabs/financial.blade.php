<div class="p-6">
    <h2 class="text-xl font-semibold mb-4">Financial & Payment Settings</h2>
    <p class="text-sm text-gray-600 mb-6">Critical settings for managing money flow and compliance.</p>

    <form method="POST" action="{{ route('admin.settings.financial.update') }}">
        @csrf

        <!-- Payment Gateways -->
        <div class="mb-8">
            <h3 class="text-lg font-medium mb-3">Payment Gateways</h3>
            <div class="space-y-6">
                <!-- Stripe -->
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-3">
                        <label class="flex items-center">
                            <input type="checkbox" name="payment_gateways[stripe_enabled]" value="1"
                                   @checked(old('payment_gateways.stripe_enabled', $settings['payment_gateways']['stripe_enabled'] ?? false))
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="ml-2 font-medium">Enable Stripe</span>
                        </label>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Stripe API Key</label>
                            <input type="text" name="payment_gateways[stripe_api_key]"
                                   value="{{ old('payment_gateways.stripe_api_key', $settings['payment_gateways']['stripe_api_key'] ?? '') }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Stripe Secret Key</label>
                            <input type="password" name="payment_gateways[stripe_secret_key]"
                                   value="{{ old('payment_gateways.stripe_secret_key', $settings['payment_gateways']['stripe_secret_key'] ?? '') }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>

                <!-- PayPal -->
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-3">
                        <label class="flex items-center">
                            <input type="checkbox" name="payment_gateways[paypal_enabled]" value="1"
                                   @checked(old('payment_gateways.paypal_enabled', $settings['payment_gateways']['paypal_enabled'] ?? false))
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="ml-2 font-medium">Enable PayPal</span>
                        </label>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">PayPal Client ID</label>
                            <input type="text" name="payment_gateways[paypal_client_id]"
                                   value="{{ old('payment_gateways.paypal_client_id', $settings['payment_gateways']['paypal_client_id'] ?? '') }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">PayPal Secret</label>
                            <input type="password" name="payment_gateways[paypal_secret]"
                                   value="{{ old('payment_gateways.paypal_secret', $settings['payment_gateways']['paypal_secret'] ?? '') }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Default Gateway</label>
                    <select name="payment_gateways[default_gateway]"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="stripe" @selected(old('payment_gateways.default_gateway', $settings['payment_gateways']['default_gateway'] ?? 'stripe') === 'stripe')>Stripe</option>
                        <option value="paypal" @selected(old('payment_gateways.default_gateway', $settings['payment_gateways']['default_gateway'] ?? 'stripe') === 'paypal')>PayPal</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Payout Method -->
        <div class="mb-8">
            <h3 class="text-lg font-medium mb-3">Payout Method</h3>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Payout Method</label>
                <select name="payout_method[method]"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="bank" @selected(old('payout_method.method', $settings['payout_method']['method'] ?? 'bank') === 'bank')>Direct to Bank</option>
                    <option value="paypal" @selected(old('payout_method.method', $settings['payout_method']['method'] ?? 'bank') === 'paypal')>PayPal Mass Payouts</option>
                </select>
            </div>
        </div>

        <!-- Tax Configuration -->
        <div class="mb-8">
            <h3 class="text-lg font-medium mb-3">Tax Configuration</h3>
            <div class="space-y-4">
                <label class="flex items-center">
                    <input type="checkbox" name="tax[tax_collection_enabled]" value="1"
                           @checked(old('tax.tax_collection_enabled', $settings['tax']['tax_collection_enabled'] ?? false))
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="ml-2">Enable tax calculation on pledges</span>
                </label>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">1099-K Threshold ($)</label>
                    <input type="number" step="0.01" min="0"
                           name="tax[form_1099k_threshold]"
                           value="{{ old('tax.form_1099k_threshold', $settings['tax']['form_1099k_threshold'] ?? 600) }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <p class="mt-1 text-xs text-gray-500">US tax reporting threshold</p>
                </div>
            </div>
        </div>

        <!-- Refund Policy -->
        <div class="mb-8">
            <h3 class="text-lg font-medium mb-3">Refund Policy</h3>
            <div class="space-y-4">
                <label class="flex items-center">
                    <input type="checkbox" name="refund_policy[allow_creator_refunds]" value="1"
                           @checked(old('refund_policy.allow_creator_refunds', $settings['refund_policy']['allow_creator_refunds'] ?? false))
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="ml-2">Allow creators to issue refunds</span>
                </label>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Platform-managed Refund Window (days)</label>
                    <input type="number" min="0"
                           name="refund_policy[platform_managed_refund_days]"
                           value="{{ old('refund_policy.platform_managed_refund_days', $settings['refund_policy']['platform_managed_refund_days'] ?? 14) }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <p class="mt-1 text-xs text-gray-500">Refunds allowed within X days of campaign end</p>
                </div>
            </div>
        </div>

        <div class="flex justify-end space-x-3 pt-4 border-t">
            <a href="{{ route('admin.settings.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Save Settings</button>
        </div>
    </form>
</div>
