<?php

namespace Modules\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFinancialSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasAnyRole(['Super Admin', 'Financial Admin']) ?? false;
    }

    public function rules(): array
    {
        return [
            // Payment Gateways
            'payment_gateways.stripe_enabled' => ['nullable', 'boolean'],
            'payment_gateways.stripe_api_key' => ['nullable', 'string'],
            'payment_gateways.stripe_secret_key' => ['nullable', 'string'],
            'payment_gateways.paypal_enabled' => ['nullable', 'boolean'],
            'payment_gateways.paypal_client_id' => ['nullable', 'string'],
            'payment_gateways.paypal_secret' => ['nullable', 'string'],
            'payment_gateways.default_gateway' => ['nullable', 'string', 'in:stripe,paypal'],

            // Payout Method
            'payout_method.method' => ['nullable', 'string', 'in:bank,paypal'],

            // Tax Configuration
            'tax.tax_collection_enabled' => ['nullable', 'boolean'],
            'tax.tax_rates' => ['nullable', 'array'],
            'tax.form_1099k_threshold' => ['nullable', 'numeric', 'min:0'],

            // Refund Policy
            'refund_policy.allow_creator_refunds' => ['nullable', 'boolean'],
            'refund_policy.platform_managed_refund_days' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
