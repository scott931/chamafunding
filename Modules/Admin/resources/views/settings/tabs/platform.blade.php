<div class="p-6">
    <h2 class="text-xl font-semibold mb-4">Platform & Business Model Settings</h2>
    <p class="text-sm text-gray-600 mb-6">These are core rules of the platform. Changing these can have massive implications.</p>

    <form method="POST" action="{{ route('admin.settings.platform.update') }}">
        @csrf

        <!-- Funding Models -->
        <div class="mb-8">
            <h3 class="text-lg font-medium mb-3">Funding Models</h3>
            <div class="space-y-3">
                <label class="flex items-center">
                    <input type="checkbox" name="funding_models[all_or_nothing_enabled]" value="1"
                           @checked(old('funding_models.all_or_nothing_enabled', $settings['funding_models']['all_or_nothing_enabled'] ?? false))
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="ml-2">All-or-Nothing (Kickstarter model)</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" name="funding_models[keep_it_all_enabled]" value="1"
                           @checked(old('funding_models.keep_it_all_enabled', $settings['funding_models']['keep_it_all_enabled'] ?? false))
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="ml-2">Keep-It-All (Indiegogo Flexible model)</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" name="funding_models[tipping_enabled]" value="1"
                           @checked(old('funding_models.tipping_enabled', $settings['funding_models']['tipping_enabled'] ?? false))
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="ml-2">Tipping (Donation-based)</span>
                </label>
            </div>
        </div>

        <!-- Fee Structure -->
        <div class="mb-8">
            <h3 class="text-lg font-medium mb-3">Fee Structure</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Platform Fee (%)</label>
                    <input type="number" step="0.01" min="0" max="100"
                           name="fee_structure[platform_fee_percentage]"
                           value="{{ old('fee_structure.platform_fee_percentage', $settings['fee_structure']['platform_fee_percentage'] ?? 5) }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <p class="mt-1 text-xs text-gray-500">Percentage fee charged on each transaction</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fixed Fee</label>
                    <input type="number" step="0.01" min="0"
                           name="fee_structure[platform_fee_fixed]"
                           value="{{ old('fee_structure.platform_fee_fixed', $settings['fee_structure']['platform_fee_fixed'] ?? 0) }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <p class="mt-1 text-xs text-gray-500">Fixed fee amount per transaction</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Payout Threshold</label>
                    <input type="number" step="0.01" min="0"
                           name="fee_structure[payout_threshold]"
                           value="{{ old('fee_structure.payout_threshold', $settings['fee_structure']['payout_threshold'] ?? 10) }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <p class="mt-1 text-xs text-gray-500">Minimum amount before creators can withdraw</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Payout Schedule (days)</label>
                    <input type="number" min="1"
                           name="fee_structure[payout_schedule_days]"
                           value="{{ old('fee_structure.payout_schedule_days', $settings['fee_structure']['payout_schedule_days'] ?? 14) }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <p class="mt-1 text-xs text-gray-500">Days after campaign end before payout</p>
                </div>
            </div>
            <div class="mt-4">
                <label class="flex items-center">
                    <input type="checkbox" name="fee_structure[payment_processor_fee_passthrough]" value="1"
                           @checked(old('fee_structure.payment_processor_fee_passthrough', $settings['fee_structure']['payment_processor_fee_passthrough'] ?? false))
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="ml-2">Pass payment processor fees to creators</span>
                </label>
            </div>
        </div>

        <!-- Currency & Regions -->
        <div class="mb-8">
            <h3 class="text-lg font-medium mb-3">Currency & Regions</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Base Currency (ISO 4217)</label>
                    <input type="text" maxlength="3"
                           name="currency[base_currency]"
                           value="{{ old('currency.base_currency', $settings['currency']['base_currency'] ?? 'USD') }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 uppercase">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Supported Currencies (comma-separated)</label>
                    <input type="text"
                           name="currency[supported_currencies]"
                           value="{{ old('currency.supported_currencies', implode(',', $settings['currency']['supported_currencies'] ?? [])) }}"
                           placeholder="USD, EUR, GBP"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>
        </div>

        <div class="flex justify-end space-x-3 pt-4 border-t">
            <a href="{{ route('admin.settings.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Save Settings</button>
        </div>
    </form>
</div>
