<?php

namespace Modules\Admin\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdvancedSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasRole('Super Admin') ?? false;
    }

    public function rules(): array
    {
        return [
            // Analytics
            'analytics.google_analytics_id' => ['nullable', 'string'],
            'analytics.facebook_pixel_id' => ['nullable', 'string'],
            'analytics.custom_header_scripts' => ['nullable', 'string'],
            'analytics.custom_footer_scripts' => ['nullable', 'string'],

            // Webhooks
            'webhooks' => ['nullable', 'array'],
            'webhooks.*.url' => ['required_with:webhooks', 'url'],
            'webhooks.*.events' => ['required_with:webhooks', 'array'],
        ];
    }
}
