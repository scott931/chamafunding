<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSettingsController extends Controller
{
    /**
     * Get PayPal client ID for frontend (public endpoint)
     */
    public function paypalClientId(): JsonResponse
    {
        // Priority 1: Get from PlatformSetting (database)
        $clientId = PlatformSetting::getString('payment_gateways.paypal_client_id', '');
        $mode = PlatformSetting::getString('payment_gateways.paypal_mode', '');
        
        // Priority 2: Fallback to config
        if (empty($clientId)) {
            $clientId = config('services.paypal.client_id', '');
        }
        if (empty($mode)) {
            $mode = config('services.paypal.mode', 'sandbox');
        }
        
        // Priority 3: Fallback to env
        if (empty($clientId)) {
            $clientId = env('PAYPAL_CLIENT_ID', '');
        }
        if (empty($mode)) {
            $mode = env('PAYPAL_MODE', 'sandbox');
        }
        
        // Priority 4: Final fallback to test credentials
        if (empty($clientId)) {
            $clientId = 'AT16jl6nE2hAKGojRWT8_NsI7iVHl79Q_A7nNkysNVC_M2X0AYHbE_YKD7_YLcXs9X1BkMm7nXo2nEwt';
        }
        if (empty($mode)) {
            $mode = 'sandbox';
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'client_id' => $clientId,
                'mode' => $mode,
            ],
        ]);
    }

    /**
     * Get all accessible settings categories
     */
    public function categories(): JsonResponse
    {
        // For now, return all categories - in production, check user permissions
        $categories = [
            'platform' => 'Platform Settings',
            'campaigns' => 'Campaign Settings',
            'users' => 'User Settings',
            'financial' => 'Financial Settings',
            'communication' => 'Communication Settings',
            'appearance' => 'Appearance Settings',
            'advanced' => 'Advanced Settings',
        ];

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Get platform settings
     */
    public function platform(): JsonResponse
    {
        $settings = [
            'funding_models' => [
                'all_or_nothing_enabled' => PlatformSetting::getBool('funding_models.all_or_nothing_enabled', true),
                'keep_it_all_enabled' => PlatformSetting::getBool('funding_models.keep_it_all_enabled', false),
                'tipping_enabled' => PlatformSetting::getBool('funding_models.tipping_enabled', false),
            ],
            'fee_structure' => [
                'platform_fee_percentage' => PlatformSetting::getFloat('fee_structure.platform_fee_percentage', 5.0),
                'platform_fee_fixed' => PlatformSetting::getFloat('fee_structure.platform_fee_fixed', 0.0),
                'payment_processor_fee_passthrough' => PlatformSetting::getBool('fee_structure.payment_processor_fee_passthrough', true),
                'payout_threshold' => PlatformSetting::getFloat('fee_structure.payout_threshold', 10.0),
                'payout_schedule_days' => PlatformSetting::getInt('fee_structure.payout_schedule_days', 14),
            ],
            'currency' => [
                'base_currency' => PlatformSetting::getString('currency.base_currency', 'USD'),
                'supported_currencies' => PlatformSetting::getJson('currency.supported_currencies', ['USD', 'EUR', 'GBP']),
                'available_countries' => PlatformSetting::getJson('currency.available_countries', []),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Update platform settings
     */
    public function updatePlatform(Request $request): JsonResponse
    {
        $data = $request->all();
        $category = 'platform';

        // Funding Models
        if (isset($data['funding_models'])) {
            PlatformSetting::setBool('funding_models.all_or_nothing_enabled', (bool) ($data['funding_models']['all_or_nothing_enabled'] ?? false), $category);
            PlatformSetting::setBool('funding_models.keep_it_all_enabled', (bool) ($data['funding_models']['keep_it_all_enabled'] ?? false), $category);
            PlatformSetting::setBool('funding_models.tipping_enabled', (bool) ($data['funding_models']['tipping_enabled'] ?? false), $category);
        }

        // Fee Structure
        if (isset($data['fee_structure'])) {
            if (isset($data['fee_structure']['platform_fee_percentage'])) {
                PlatformSetting::set('fee_structure.platform_fee_percentage', (string) $data['fee_structure']['platform_fee_percentage'], $category);
            }
            if (isset($data['fee_structure']['platform_fee_fixed'])) {
                PlatformSetting::set('fee_structure.platform_fee_fixed', (string) $data['fee_structure']['platform_fee_fixed'], $category);
            }
            PlatformSetting::setBool('fee_structure.payment_processor_fee_passthrough', (bool) ($data['fee_structure']['payment_processor_fee_passthrough'] ?? false), $category);
            if (isset($data['fee_structure']['payout_threshold'])) {
                PlatformSetting::set('fee_structure.payout_threshold', (string) $data['fee_structure']['payout_threshold'], $category);
            }
            if (isset($data['fee_structure']['payout_schedule_days'])) {
                PlatformSetting::set('fee_structure.payout_schedule_days', (string) $data['fee_structure']['payout_schedule_days'], $category);
            }
        }

        // Currency & Regions
        if (isset($data['currency'])) {
            if (isset($data['currency']['base_currency'])) {
                PlatformSetting::set('currency.base_currency', strtoupper($data['currency']['base_currency']), $category);
            }
            if (isset($data['currency']['supported_currencies'])) {
                $currencies = is_array($data['currency']['supported_currencies'])
                    ? $data['currency']['supported_currencies']
                    : array_filter(array_map('trim', explode(',', $data['currency']['supported_currencies'])));
                PlatformSetting::setJson('currency.supported_currencies', $currencies, $category);
            }
            if (isset($data['currency']['available_countries'])) {
                PlatformSetting::setJson('currency.available_countries', $data['currency']['available_countries'], $category);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Platform settings updated successfully',
        ]);
    }

    /**
     * Get campaign settings
     */
    public function campaigns(): JsonResponse
    {
        $settings = [
            'campaign_requirements' => [
                'min_funding_goal' => PlatformSetting::getFloat('campaign_requirements.min_funding_goal', 100.0),
                'max_funding_goal' => PlatformSetting::getFloat('campaign_requirements.max_funding_goal', 1000000.0),
                'min_duration_days' => PlatformSetting::getInt('campaign_requirements.min_duration_days', 1),
                'max_duration_days' => PlatformSetting::getInt('campaign_requirements.max_duration_days', 60),
                'required_video' => PlatformSetting::getBool('campaign_requirements.required_video', false),
                'required_image_gallery' => PlatformSetting::getBool('campaign_requirements.required_image_gallery', true),
                'required_story_text' => PlatformSetting::getBool('campaign_requirements.required_story_text', true),
            ],
            'approval_workflow' => [
                'require_approval' => PlatformSetting::getBool('approval_workflow.require_approval', false),
            ],
            'content_restrictions' => [
                'prohibited_categories' => PlatformSetting::getJson('content_restrictions.prohibited_categories', []),
                'banned_keywords' => PlatformSetting::getJson('content_restrictions.banned_keywords', []),
                'manual_review_threshold' => PlatformSetting::getFloat('content_restrictions.manual_review_threshold', 100000.0),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Update campaign settings
     */
    public function updateCampaigns(Request $request): JsonResponse
    {
        $data = $request->all();
        $category = 'campaigns';

        if (isset($data['campaign_requirements'])) {
            $req = $data['campaign_requirements'];
            if (isset($req['min_funding_goal'])) {
                PlatformSetting::set('campaign_requirements.min_funding_goal', (string) $req['min_funding_goal'], $category);
            }
            if (isset($req['max_funding_goal'])) {
                PlatformSetting::set('campaign_requirements.max_funding_goal', (string) $req['max_funding_goal'], $category);
            }
            if (isset($req['min_duration_days'])) {
                PlatformSetting::set('campaign_requirements.min_duration_days', (string) $req['min_duration_days'], $category);
            }
            if (isset($req['max_duration_days'])) {
                PlatformSetting::set('campaign_requirements.max_duration_days', (string) $req['max_duration_days'], $category);
            }
            PlatformSetting::setBool('campaign_requirements.required_video', (bool) ($req['required_video'] ?? false), $category);
            PlatformSetting::setBool('campaign_requirements.required_image_gallery', (bool) ($req['required_image_gallery'] ?? false), $category);
            PlatformSetting::setBool('campaign_requirements.required_story_text', (bool) ($req['required_story_text'] ?? false), $category);
        }

        if (isset($data['approval_workflow'])) {
            PlatformSetting::setBool('approval_workflow.require_approval', (bool) ($data['approval_workflow']['require_approval'] ?? false), $category);
        }

        if (isset($data['content_restrictions'])) {
            $rest = $data['content_restrictions'];
            if (isset($rest['prohibited_categories'])) {
                $categories = is_array($rest['prohibited_categories'])
                    ? $rest['prohibited_categories']
                    : array_filter(array_map('trim', explode("\n", $rest['prohibited_categories'])));
                PlatformSetting::setJson('content_restrictions.prohibited_categories', $categories, $category);
            }
            if (isset($rest['banned_keywords'])) {
                $keywords = is_array($rest['banned_keywords'])
                    ? $rest['banned_keywords']
                    : array_filter(array_map('trim', explode("\n", $rest['banned_keywords'])));
                PlatformSetting::setJson('content_restrictions.banned_keywords', $keywords, $category);
            }
            if (isset($rest['manual_review_threshold'])) {
                PlatformSetting::set('content_restrictions.manual_review_threshold', (string) $rest['manual_review_threshold'], $category);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Campaign settings updated successfully',
        ]);
    }

    /**
     * Get user settings
     */
    public function users(): JsonResponse
    {
        $settings = [
            'registration' => [
                'allow_public_signups' => PlatformSetting::getBool('registration.allow_public_signups', true),
                'invite_only_mode' => PlatformSetting::getBool('registration.invite_only_mode', false),
                'require_email_verification' => PlatformSetting::getBool('registration.require_email_verification', true),
                'require_phone_number' => PlatformSetting::getBool('registration.require_phone_number', false),
            ],
            'verification' => [
                'require_identity_verification' => PlatformSetting::getBool('verification.require_identity_verification', false),
                'require_bank_details' => PlatformSetting::getBool('verification.require_bank_details', true),
            ],
            'security' => [
                'password_min_length' => PlatformSetting::getInt('security.password_min_length', 8),
                'require_2fa_admins' => PlatformSetting::getBool('security.require_2fa_admins', true),
                'require_2fa_users' => PlatformSetting::getBool('security.require_2fa_users', false),
                'api_access_enabled' => PlatformSetting::getBool('security.api_access_enabled', false),
                'session_timeout_minutes' => PlatformSetting::getInt('security.session_timeout_minutes', 60),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Update user settings
     */
    public function updateUsers(Request $request): JsonResponse
    {
        $data = $request->all();
        $category = 'users';

        if (isset($data['registration'])) {
            $reg = $data['registration'];
            PlatformSetting::setBool('registration.allow_public_signups', (bool) ($reg['allow_public_signups'] ?? false), $category);
            PlatformSetting::setBool('registration.invite_only_mode', (bool) ($reg['invite_only_mode'] ?? false), $category);
            PlatformSetting::setBool('registration.require_email_verification', (bool) ($reg['require_email_verification'] ?? false), $category);
            PlatformSetting::setBool('registration.require_phone_number', (bool) ($reg['require_phone_number'] ?? false), $category);
        }

        if (isset($data['verification'])) {
            $ver = $data['verification'];
            PlatformSetting::setBool('verification.require_identity_verification', (bool) ($ver['require_identity_verification'] ?? false), $category);
            PlatformSetting::setBool('verification.require_bank_details', (bool) ($ver['require_bank_details'] ?? false), $category);
        }

        if (isset($data['security'])) {
            $sec = $data['security'];
            if (isset($sec['password_min_length'])) {
                PlatformSetting::set('security.password_min_length', (string) $sec['password_min_length'], $category);
            }
            PlatformSetting::setBool('security.require_2fa_admins', (bool) ($sec['require_2fa_admins'] ?? false), $category);
            PlatformSetting::setBool('security.require_2fa_users', (bool) ($sec['require_2fa_users'] ?? false), $category);
            PlatformSetting::setBool('security.api_access_enabled', (bool) ($sec['api_access_enabled'] ?? false), $category);
            if (isset($sec['session_timeout_minutes'])) {
                PlatformSetting::set('security.session_timeout_minutes', (string) $sec['session_timeout_minutes'], $category);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'User settings updated successfully',
        ]);
    }

    /**
     * Get financial settings
     */
    public function financial(): JsonResponse
    {
        // Get PayPal credentials with fallback to env/config
        $paypalClientId = PlatformSetting::getString('payment_gateways.paypal_client_id', '');
        if (empty($paypalClientId)) {
            $paypalClientId = config('services.paypal.client_id', '');
        }
        if (empty($paypalClientId)) {
            $paypalClientId = env('PAYPAL_CLIENT_ID', '');
        }

        $paypalSecret = PlatformSetting::getString('payment_gateways.paypal_secret', '');
        if (empty($paypalSecret)) {
            $paypalSecret = config('services.paypal.client_secret', '');
        }
        if (empty($paypalSecret)) {
            $paypalSecret = env('PAYPAL_CLIENT_SECRET', '');
        }

        $settings = [
            'payment_gateways' => [
                'stripe_enabled' => PlatformSetting::getBool('payment_gateways.stripe_enabled', false),
                'stripe_api_key' => PlatformSetting::getString('payment_gateways.stripe_api_key', ''),
                'stripe_secret_key' => PlatformSetting::getString('payment_gateways.stripe_secret_key', ''),
                'paypal_enabled' => PlatformSetting::getBool('payment_gateways.paypal_enabled', false),
                'paypal_client_id' => $paypalClientId,
                'paypal_secret' => $paypalSecret,
                'default_gateway' => PlatformSetting::getString('payment_gateways.default_gateway', 'stripe'),
            ],
            'payout_method' => [
                'method' => PlatformSetting::getString('payout_method.method', 'bank'),
            ],
            'tax' => [
                'tax_collection_enabled' => PlatformSetting::getBool('tax.tax_collection_enabled', false),
                'tax_rates' => PlatformSetting::getJson('tax.tax_rates', []),
                'form_1099k_threshold' => PlatformSetting::getFloat('tax.form_1099k_threshold', 600.0),
            ],
            'refund_policy' => [
                'allow_creator_refunds' => PlatformSetting::getBool('refund_policy.allow_creator_refunds', true),
                'platform_managed_refund_days' => PlatformSetting::getInt('refund_policy.platform_managed_refund_days', 14),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Update financial settings
     */
    public function updateFinancial(Request $request): JsonResponse
    {
        $data = $request->all();
        $category = 'financial';

        if (isset($data['payment_gateways'])) {
            $gw = $data['payment_gateways'];
            PlatformSetting::setBool('payment_gateways.stripe_enabled', (bool) ($gw['stripe_enabled'] ?? false), $category);
            if (isset($gw['stripe_api_key'])) {
                PlatformSetting::set('payment_gateways.stripe_api_key', $gw['stripe_api_key'], $category);
            }
            if (isset($gw['stripe_secret_key'])) {
                PlatformSetting::set('payment_gateways.stripe_secret_key', $gw['stripe_secret_key'], $category);
            }
            PlatformSetting::setBool('payment_gateways.paypal_enabled', (bool) ($gw['paypal_enabled'] ?? false), $category);
            if (isset($gw['paypal_client_id'])) {
                PlatformSetting::set('payment_gateways.paypal_client_id', $gw['paypal_client_id'], $category);
            }
            if (isset($gw['paypal_secret'])) {
                PlatformSetting::set('payment_gateways.paypal_secret', $gw['paypal_secret'], $category);
            }
            if (isset($gw['default_gateway'])) {
                PlatformSetting::set('payment_gateways.default_gateway', $gw['default_gateway'], $category);
            }
        }

        if (isset($data['payout_method'])) {
            PlatformSetting::set('payout_method.method', $data['payout_method']['method'], $category);
        }

        if (isset($data['tax'])) {
            $tax = $data['tax'];
            PlatformSetting::setBool('tax.tax_collection_enabled', (bool) ($tax['tax_collection_enabled'] ?? false), $category);
            if (isset($tax['tax_rates'])) {
                PlatformSetting::setJson('tax.tax_rates', $tax['tax_rates'], $category);
            }
            if (isset($tax['form_1099k_threshold'])) {
                PlatformSetting::set('tax.form_1099k_threshold', (string) $tax['form_1099k_threshold'], $category);
            }
        }

        if (isset($data['refund_policy'])) {
            $refund = $data['refund_policy'];
            PlatformSetting::setBool('refund_policy.allow_creator_refunds', (bool) ($refund['allow_creator_refunds'] ?? false), $category);
            if (isset($refund['platform_managed_refund_days'])) {
                PlatformSetting::set('refund_policy.platform_managed_refund_days', (string) $refund['platform_managed_refund_days'], $category);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Financial settings updated successfully',
        ]);
    }
}

