<?php

namespace Modules\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppearanceSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasRole('Super Admin') ?? false;
    }

    public function rules(): array
    {
        return [
            // General
            'general.site_name' => ['nullable', 'string', 'max:255'],
            'general.site_logo' => ['nullable', 'string'],
            'general.favicon' => ['nullable', 'string'],
            'general.meta_description' => ['nullable', 'string', 'max:500'],

            // Landing Page
            'landing_page.featured_campaigns' => ['nullable', 'array'],
            'landing_page.hero_banner_text' => ['nullable', 'string'],
            'landing_page.hero_banner_image' => ['nullable', 'string'],

            // Categories
            'categories' => ['nullable', 'array'],
        ];
    }
}
