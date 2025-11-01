<div class="p-6">
    <h2 class="text-xl font-semibold mb-4">Campaign Creation & Moderation Settings</h2>
    <p class="text-sm text-gray-600 mb-6">Control what creators can do when launching a campaign.</p>

    <form method="POST" action="{{ route('admin.settings.campaigns.update') }}">
        @csrf

        <!-- Campaign Requirements -->
        <div class="mb-8">
            <h3 class="text-lg font-medium mb-3">Campaign Requirements</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Min Funding Goal</label>
                    <input type="number" step="0.01" min="0"
                           name="campaign_requirements[min_funding_goal]"
                           value="{{ old('campaign_requirements.min_funding_goal', $settings['campaign_requirements']['min_funding_goal'] ?? 100) }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Max Funding Goal</label>
                    <input type="number" step="0.01" min="0"
                           name="campaign_requirements[max_funding_goal]"
                           value="{{ old('campaign_requirements.max_funding_goal', $settings['campaign_requirements']['max_funding_goal'] ?? 1000000) }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Min Duration (days)</label>
                    <input type="number" min="1"
                           name="campaign_requirements[min_duration_days]"
                           value="{{ old('campaign_requirements.min_duration_days', $settings['campaign_requirements']['min_duration_days'] ?? 1) }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Max Duration (days)</label>
                    <input type="number" min="1"
                           name="campaign_requirements[max_duration_days]"
                           value="{{ old('campaign_requirements.max_duration_days', $settings['campaign_requirements']['max_duration_days'] ?? 60) }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>
            <div class="mt-4 space-y-2">
                <label class="flex items-center">
                    <input type="checkbox" name="campaign_requirements[required_video]" value="1"
                           @checked(old('campaign_requirements.required_video', $settings['campaign_requirements']['required_video'] ?? false))
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="ml-2">Required: Video</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" name="campaign_requirements[required_image_gallery]" value="1"
                           @checked(old('campaign_requirements.required_image_gallery', $settings['campaign_requirements']['required_image_gallery'] ?? false))
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="ml-2">Required: Image Gallery</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" name="campaign_requirements[required_story_text]" value="1"
                           @checked(old('campaign_requirements.required_story_text', $settings['campaign_requirements']['required_story_text'] ?? false))
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="ml-2">Required: Story Text</span>
                </label>
            </div>
        </div>

        <!-- Approval Workflow -->
        <div class="mb-8">
            <h3 class="text-lg font-medium mb-3">Approval Workflow</h3>
            <label class="flex items-center">
                <input type="checkbox" name="approval_workflow[require_approval]" value="1"
                       @checked(old('approval_workflow.require_approval', $settings['approval_workflow']['require_approval'] ?? false))
                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <span class="ml-2">Campaigns require admin approval before going public</span>
            </label>
        </div>

        <!-- Content Restrictions -->
        <div class="mb-8">
            <h3 class="text-lg font-medium mb-3">Content Restrictions</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Manual Review Threshold ($)</label>
                    <input type="number" step="0.01" min="0"
                           name="content_restrictions[manual_review_threshold]"
                           value="{{ old('content_restrictions.manual_review_threshold', $settings['content_restrictions']['manual_review_threshold'] ?? 100000) }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <p class="mt-1 text-xs text-gray-500">Flag all campaigns with a goal above this amount</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Prohibited Categories (one per line)</label>
                    <textarea name="content_restrictions[prohibited_categories]" rows="3"
                              class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ implode("\n", old('content_restrictions.prohibited_categories', $settings['content_restrictions']['prohibited_categories'] ?? [])) }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Banned Keywords (one per line)</label>
                    <textarea name="content_restrictions[banned_keywords]" rows="3"
                              class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ implode("\n", old('content_restrictions.banned_keywords', $settings['content_restrictions']['banned_keywords'] ?? [])) }}</textarea>
                </div>
            </div>
        </div>

        <div class="flex justify-end space-x-3 pt-4 border-t">
            <a href="{{ route('admin.settings.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Save Settings</button>
        </div>
    </form>
</div>
