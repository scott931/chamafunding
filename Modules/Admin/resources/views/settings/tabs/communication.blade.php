<div class="p-6">
    <h2 class="text-xl font-semibold mb-4">Communication & Email Settings</h2>
    <p class="text-sm text-gray-600 mb-6">Control all outgoing communication from the platform.</p>

    <form method="POST" action="{{ route('admin.settings.communication.update') }}">
        @csrf

        <!-- Notification Triggers -->
        <div class="mb-8">
            <h3 class="text-lg font-medium mb-3">Notification Triggers</h3>
            <p class="text-sm text-gray-600 mb-4">Decide which events trigger emails to admins.</p>
            <div class="space-y-3">
                <label class="flex items-center">
                    <input type="checkbox" name="notifications[new_high_value_campaign]" value="1"
                           @checked(old('notifications.new_high_value_campaign', $settings['notifications']['new_high_value_campaign'] ?? false))
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="ml-2">New high-value campaign launched</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" name="notifications[campaign_reported]" value="1"
                           @checked(old('notifications.campaign_reported', $settings['notifications']['campaign_reported'] ?? false))
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="ml-2">Campaign reported</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" name="notifications[payout_failed]" value="1"
                           @checked(old('notifications.payout_failed', $settings['notifications']['payout_failed'] ?? false))
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="ml-2">Payout failed</span>
                </label>
            </div>
        </div>

        <!-- SMTP Settings -->
        <div class="mb-8">
            <h3 class="text-lg font-medium mb-3">SMTP Settings</h3>
            <p class="text-sm text-gray-600 mb-4">Configure the platform's outgoing email server.</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">SMTP Host</label>
                    <input type="text" name="smtp[host]"
                           value="{{ old('smtp.host', $settings['smtp']['host'] ?? '') }}"
                           placeholder="smtp.example.com"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">SMTP Port</label>
                    <input type="number" name="smtp[port]"
                           value="{{ old('smtp.port', $settings['smtp']['port'] ?? 587) }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">SMTP Username</label>
                    <input type="text" name="smtp[username]"
                           value="{{ old('smtp.username', $settings['smtp']['username'] ?? '') }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">SMTP Password</label>
                    <input type="password" name="smtp[password]"
                           value="{{ old('smtp.password', $settings['smtp']['password'] ?? '') }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Encryption</label>
                    <select name="smtp[encryption]"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="tls" @selected(old('smtp.encryption', $settings['smtp']['encryption'] ?? 'tls') === 'tls')>TLS</option>
                        <option value="ssl" @selected(old('smtp.encryption', $settings['smtp']['encryption'] ?? 'tls') === 'ssl')>SSL</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">From Address</label>
                    <input type="email" name="smtp[from_address]"
                           value="{{ old('smtp.from_address', $settings['smtp']['from_address'] ?? '') }}"
                           placeholder="noreply@example.com"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">From Name</label>
                    <input type="text" name="smtp[from_name]"
                           value="{{ old('smtp.from_name', $settings['smtp']['from_name'] ?? '') }}"
                           placeholder="Platform Name"
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
