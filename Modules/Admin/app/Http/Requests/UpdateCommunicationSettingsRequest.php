<?php

namespace Modules\Admin\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCommunicationSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasAnyRole(['Super Admin', 'Moderator']) ?? false;
    }

    public function rules(): array
    {
        return [
            // Notification Triggers
            'notifications.new_high_value_campaign' => ['nullable', 'boolean'],
            'notifications.campaign_reported' => ['nullable', 'boolean'],
            'notifications.payout_failed' => ['nullable', 'boolean'],

            // SMTP Settings
            'smtp.host' => ['nullable', 'string'],
            'smtp.port' => ['nullable', 'integer'],
            'smtp.username' => ['nullable', 'string'],
            'smtp.password' => ['nullable', 'string'],
            'smtp.encryption' => ['nullable', 'string', 'in:tls,ssl'],
            'smtp.from_address' => ['nullable', 'email'],
            'smtp.from_name' => ['nullable', 'string'],
        ];
    }
}
