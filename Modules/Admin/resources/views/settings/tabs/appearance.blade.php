<div class="p-6">
    <h2 class="text-xl font-semibold mb-4">Site & Appearance Settings</h2>
    <p class="text-sm text-gray-600 mb-6">Manage the public-facing website.</p>

    <form method="POST" action="{{ route('admin.settings.appearance.update') }}">
        @csrf

        <!-- General -->
        <div class="mb-8">
            <h3 class="text-lg font-medium mb-3">General</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Site Name</label>
                    <input type="text" name="general[site_name]"
                           value="{{ old('general.site_name', $settings['general']['site_name'] ?? '') }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Site Logo (URL)</label>
                    <input type="text" name="general[site_logo]"
                           value="{{ old('general.site_logo', $settings['general']['site_logo'] ?? '') }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Favicon (URL)</label>
                    <input type="text" name="general[favicon]"
                           value="{{ old('general.favicon', $settings['general']['favicon'] ?? '') }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Meta Description</label>
                    <textarea name="general[meta_description]" rows="2"
                              class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('general.meta_description', $settings['general']['meta_description'] ?? '') }}</textarea>
                </div>
            </div>
        </div>

        <!-- Landing Page -->
        <div class="mb-8">
            <h3 class="text-lg font-medium mb-3">Landing Page</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hero Banner Text</label>
                    <textarea name="landing_page[hero_banner_text]" rows="3"
                              class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('landing_page.hero_banner_text', $settings['landing_page']['hero_banner_text'] ?? '') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hero Banner Image (URL)</label>
                    <input type="text" name="landing_page[hero_banner_image]"
                           value="{{ old('landing_page.hero_banner_image', $settings['landing_page']['hero_banner_image'] ?? '') }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Featured Campaign IDs (comma-separated)</label>
                    <input type="text" name="landing_page[featured_campaigns]"
                           value="{{ old('landing_page.featured_campaigns', implode(',', $settings['landing_page']['featured_campaigns'] ?? [])) }}"
                           placeholder="1, 2, 3"
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
