<div class="p-6">
    <h2 class="text-xl font-semibold mb-4">User & Security Settings</h2>
    <p class="text-sm text-gray-600 mb-6">Manage user accounts and platform security.</p>

    <form method="POST" action="{{ route('admin.settings.users.update') }}">
        @csrf

        <!-- User Registration -->
        <div class="mb-8">
            <h3 class="text-lg font-medium mb-3">User Registration</h3>
            <div class="space-y-3">
                <label class="flex items-center">
                    <input type="checkbox" name="registration[allow_public_signups]" value="1"
                           @checked(old('registration.allow_public_signups', $settings['registration']['allow_public_signups'] ?? false))
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="ml-2">Allow public sign-ups</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" name="registration[invite_only_mode]" value="1"
                           @checked(old('registration.invite_only_mode', $settings['registration']['invite_only_mode'] ?? false))
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="ml-2">Invite-only mode</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" name="registration[require_email_verification]" value="1"
                           @checked(old('registration.require_email_verification', $settings['registration']['require_email_verification'] ?? false))
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="ml-2">Require Email Verification</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" name="registration[require_phone_number]" value="1"
                           @checked(old('registration.require_phone_number', $settings['registration']['require_phone_number'] ?? false))
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="ml-2">Require Phone Number</span>
                </label>
            </div>
        </div>

        <!-- Creator Verification -->
        <div class="mb-8">
            <h3 class="text-lg font-medium mb-3">Creator Verification</h3>
            <div class="space-y-3">
                <label class="flex items-center">
                    <input type="checkbox" name="verification[require_identity_verification]" value="1"
                           @checked(old('verification.require_identity_verification', $settings['verification']['require_identity_verification'] ?? false))
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="ml-2">Identity verification required</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" name="verification[require_bank_details]" value="1"
                           @checked(old('verification.require_bank_details', $settings['verification']['require_bank_details'] ?? false))
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="ml-2">Bank account/Payout details required before first payout</span>
                </label>
            </div>
        </div>

        <!-- Security Policies -->
        <div class="mb-8">
            <h3 class="text-lg font-medium mb-3">Security Policies</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password Minimum Length</label>
                    <input type="number" min="8"
                           name="security[password_min_length]"
                           value="{{ old('security.password_min_length', $settings['security']['password_min_length'] ?? 8) }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Session Timeout (minutes)</label>
                    <input type="number" min="5"
                           name="security[session_timeout_minutes]"
                           value="{{ old('security.session_timeout_minutes', $settings['security']['session_timeout_minutes'] ?? 60) }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>
            <div class="mt-4 space-y-3">
                <label class="flex items-center">
                    <input type="checkbox" name="security[require_2fa_admins]" value="1"
                           @checked(old('security.require_2fa_admins', $settings['security']['require_2fa_admins'] ?? false))
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="ml-2">Two-Factor Authentication mandatory for admins</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" name="security[require_2fa_users]" value="1"
                           @checked(old('security.require_2fa_users', $settings['security']['require_2fa_users'] ?? false))
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="ml-2">Two-Factor Authentication optional for users</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" name="security[api_access_enabled]" value="1"
                           @checked(old('security.api_access_enabled', $settings['security']['api_access_enabled'] ?? false))
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="ml-2">Enable API Access</span>
                </label>
            </div>
        </div>

        <div class="flex justify-end space-x-3 pt-4 border-t">
            <a href="{{ route('admin.settings.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Save Settings</button>
        </div>
    </form>
</div>
