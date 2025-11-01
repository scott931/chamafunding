<?php

namespace Modules\Admin\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasAnyRole(['Super Admin', 'Moderator']) ?? false;
    }

    public function rules(): array
    {
        return [
            // User Registration
            'registration.allow_public_signups' => ['nullable', 'boolean'],
            'registration.invite_only_mode' => ['nullable', 'boolean'],
            'registration.require_email_verification' => ['nullable', 'boolean'],
            'registration.require_phone_number' => ['nullable', 'boolean'],

            // Creator Verification
            'verification.require_identity_verification' => ['nullable', 'boolean'],
            'verification.require_bank_details' => ['nullable', 'boolean'],

            // Security Policies
            'security.password_min_length' => ['nullable', 'integer', 'min:8'],
            'security.require_2fa_admins' => ['nullable', 'boolean'],
            'security.require_2fa_users' => ['nullable', 'boolean'],
            'security.api_access_enabled' => ['nullable', 'boolean'],
            'security.session_timeout_minutes' => ['nullable', 'integer', 'min:5'],
        ];
    }
}
