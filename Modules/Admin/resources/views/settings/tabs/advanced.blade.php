<div class="p-6">
    <h2 class="text-xl font-semibold mb-4">Advanced & Technical Settings</h2>
    <p class="text-sm text-gray-600 mb-6">For developers or technical admins.</p>

    <form method="POST" action="{{ route('admin.settings.advanced.update') }}">
        @csrf

        <!-- Analytics & Tracking -->
        <div class="mb-8">
            <h3 class="text-lg font-medium mb-3">Analytics & Tracking</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Google Analytics ID</label>
                    <input type="text" name="analytics[google_analytics_id]"
                           value="{{ old('analytics.google_analytics_id', $settings['analytics']['google_analytics_id'] ?? '') }}"
                           placeholder="G-XXXXXXXXXX"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Facebook Pixel ID</label>
                    <input type="text" name="analytics[facebook_pixel_id]"
                           value="{{ old('analytics.facebook_pixel_id', $settings['analytics']['facebook_pixel_id'] ?? '') }}"
                           placeholder="123456789012345"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Custom Header Scripts</label>
                    <textarea name="analytics[custom_header_scripts]" rows="4"
                              class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm">{{ old('analytics.custom_header_scripts', $settings['analytics']['custom_header_scripts'] ?? '') }}</textarea>
                    <p class="mt-1 text-xs text-gray-500">Scripts to inject in &lt;head&gt; tag</p>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Custom Footer Scripts</label>
                    <textarea name="analytics[custom_footer_scripts]" rows="4"
                              class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm">{{ old('analytics.custom_footer_scripts', $settings['analytics']['custom_footer_scripts'] ?? '') }}</textarea>
                    <p class="mt-1 text-xs text-gray-500">Scripts to inject before &lt;/body&gt; tag</p>
                </div>
            </div>
        </div>

        <!-- Webhooks -->
        <div class="mb-8">
            <h3 class="text-lg font-medium mb-3">Webhooks</h3>
            <p class="text-sm text-gray-600 mb-4">Configure URLs to receive notifications for events like campaign.ended or pledge.created.</p>
            <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                <p class="text-sm text-gray-600">Webhook configuration coming soon. This will allow you to configure event notifications.</p>
            </div>
        </div>

        <!-- Audit Log Preview -->
        @if(isset($auditLog) && $auditLog->count() > 0)
        <div class="mb-8">
            <h3 class="text-lg font-medium mb-3">Recent Settings Changes (Audit Log)</h3>
            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Setting</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Old Value</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">New Value</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Changed By</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($auditLog as $log)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $log->setting_key }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ Str::limit($log->old_value, 30) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ Str::limit($log->new_value, 30) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $log->changedBy->name ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $log->created_at->format('M j, Y H:i') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mt-4">
                <a href="{{ route('admin.settings.audit-log') }}" class="text-indigo-600 hover:text-indigo-700 text-sm font-medium">View Full Audit Log →</a>
            </div>
        </div>
        @endif

        <div class="flex justify-end space-x-3 pt-4 border-t">
            <a href="{{ route('admin.settings.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Save Settings</button>
        </div>
    </form>
</div>
