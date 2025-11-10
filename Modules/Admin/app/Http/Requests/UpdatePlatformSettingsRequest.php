<?php

namespace Modules\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlatformSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasRole('Super Admin') ?? false;
    }

    public function rules(): array
    {
        return [
            // Funding Models
            'funding_models.all_or_nothing_enabled' => ['nullable', 'boolean'],
            'funding_models.keep_it_all_enabled' => ['nullable', 'boolean'],
            'funding_models.tipping_enabled' => ['nullable', 'boolean'],

            // Fee Structure
            'fee_structure.platform_fee_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'fee_structure.platform_fee_fixed' => ['nullable', 'numeric', 'min:0'],
            'fee_structure.payment_processor_fee_passthrough' => ['nullable', 'boolean'],
            'fee_structure.payout_threshold' => ['nullable', 'numeric', 'min:0'],
            'fee_structure.payout_schedule_days' => ['nullable', 'integer', 'min:1'],

            // Currency & Regions
            'currency.base_currency' => ['nullable', 'string', 'size:3'],
            'currency.supported_currencies' => ['nullable', 'array'],
            'currency.available_countries' => ['nullable', 'array'],
        ];
    }
}
