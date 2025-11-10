<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Models\SettingsAuditLog;
use Modules\Admin\Http\Requests\UpdateAdvancedSettingsRequest;
use Modules\Admin\Http\Requests\UpdateAppearanceSettingsRequest;
use Modules\Admin\Http\Requests\UpdateCampaignSettingsRequest;
use Modules\Admin\Http\Requests\UpdateCommunicationSettingsRequest;
use Modules\Admin\Http\Requests\UpdateFinancialSettingsRequest;
use Modules\Admin\Http\Requests\UpdatePlatformSettingsRequest;
use Modules\Admin\Http\Requests\UpdateUserSettingsRequest;
use Modules\Admin\Services\SettingsRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * Show the settings index page with tabs.
     */
    public function index(): View
    {
        $accessibleCategories = SettingsRegistry::getAccessibleCategories();
        $categoryNames = SettingsRegistry::getCategoryNames();
        $activeTab = request()->get('tab', $accessibleCategories[0] ?? 'platform');

        // Redirect to first accessible category if requested tab is not accessible
        if (!in_array($activeTab, $accessibleCategories)) {
            $activeTab = $accessibleCategories[0] ?? 'platform';
        }

        // Load settings for the active tab
        $settings = [];
        switch ($activeTab) {
            case 'platform':
                if (SettingsRegistry::canAccess('platform')) {
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
                }
                break;
            case 'campaigns':
                if (SettingsRegistry::canAccess('campaigns')) {
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
                }
                break;
            case 'users':
                if (SettingsRegistry::canAccess('users')) {
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
                }
                break;
            case 'financial':
                if (SettingsRegistry::canAccess('financial')) {
                    $settings = [
                        'payment_gateways' => [
                            'stripe_enabled' => PlatformSetting::getBool('payment_gateways.stripe_enabled', false),
                            'stripe_api_key' => PlatformSetting::getString('payment_gateways.stripe_api_key', ''),
                            'stripe_secret_key' => PlatformSetting::getString('payment_gateways.stripe_secret_key', ''),
                            'paypal_enabled' => PlatformSetting::getBool('payment_gateways.paypal_enabled', false),
                            'paypal_client_id' => PlatformSetting::getString('payment_gateways.paypal_client_id', ''),
                            'paypal_secret' => PlatformSetting::getString('payment_gateways.paypal_secret', ''),
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
                }
                break;
            case 'communication':
                if (SettingsRegistry::canAccess('communication')) {
                    $settings = [
                        'notifications' => [
                            'new_high_value_campaign' => PlatformSetting::getBool('notifications.new_high_value_campaign', true),
                            'campaign_reported' => PlatformSetting::getBool('notifications.campaign_reported', true),
                            'payout_failed' => PlatformSetting::getBool('notifications.payout_failed', true),
                        ],
                        'smtp' => [
                            'host' => PlatformSetting::getString('smtp.host', ''),
                            'port' => PlatformSetting::getInt('smtp.port', 587),
                            'username' => PlatformSetting::getString('smtp.username', ''),
                            'password' => PlatformSetting::getString('smtp.password', ''),
                            'encryption' => PlatformSetting::getString('smtp.encryption', 'tls'),
                            'from_address' => PlatformSetting::getString('smtp.from_address', ''),
                            'from_name' => PlatformSetting::getString('smtp.from_name', ''),
                        ],
                    ];
                }
                break;
            case 'appearance':
                if (SettingsRegistry::canAccess('appearance')) {
                    $settings = [
                        'general' => [
                            'site_name' => PlatformSetting::getString('general.site_name', config('app.name')),
                            'site_logo' => PlatformSetting::getString('general.site_logo', ''),
                            'favicon' => PlatformSetting::getString('general.favicon', ''),
                            'meta_description' => PlatformSetting::getString('general.meta_description', ''),
                        ],
                        'landing_page' => [
                            'featured_campaigns' => PlatformSetting::getJson('landing_page.featured_campaigns', []),
                            'hero_banner_text' => PlatformSetting::getString('landing_page.hero_banner_text', ''),
                            'hero_banner_image' => PlatformSetting::getString('landing_page.hero_banner_image', ''),
                        ],
                        'categories' => PlatformSetting::getJson('categories.list', []),
                    ];
                }
                break;
            case 'advanced':
                if (SettingsRegistry::canAccess('advanced')) {
                    $settings = [
                        'analytics' => [
                            'google_analytics_id' => PlatformSetting::getString('analytics.google_analytics_id', ''),
                            'facebook_pixel_id' => PlatformSetting::getString('analytics.facebook_pixel_id', ''),
                            'custom_header_scripts' => PlatformSetting::getString('analytics.custom_header_scripts', ''),
                            'custom_footer_scripts' => PlatformSetting::getString('analytics.custom_footer_scripts', ''),
                        ],
                        'webhooks' => PlatformSetting::getJson('webhooks.list', []),
                    ];

                    // Load audit log for advanced tab
                    $auditLog = SettingsAuditLog::with('changedBy')
                        ->orderBy('created_at', 'desc')
                        ->limit(50)
                        ->get();
                    $settings['auditLog'] = $auditLog;
                }
                break;
        }

        return view('admin::settings.index', [
            'accessibleCategories' => $accessibleCategories,
            'categoryNames' => $categoryNames,
            'activeTab' => $activeTab,
            'settings' => $settings,
        ]);
    }

    /**
     * Show platform & business model settings.
     */
    public function platform(): View
    {
        if (!SettingsRegistry::canAccess('platform')) {
            abort(403, 'You do not have permission to access platform settings.');
        }

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

        return view('admin::settings.platform', compact('settings'));
    }

    /**
     * Update platform settings.
     */
    public function updatePlatform(UpdatePlatformSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();
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
                // Handle comma-separated string or array
                $currencies = is_array($data['currency']['supported_currencies'])
                    ? $data['currency']['supported_currencies']
                    : array_filter(array_map('trim', explode(',', $data['currency']['supported_currencies'])));
                PlatformSetting::setJson('currency.supported_currencies', $currencies, $category);
            }
            if (isset($data['currency']['available_countries'])) {
                PlatformSetting::setJson('currency.available_countries', $data['currency']['available_countries'], $category);
            }
        }

        return redirect()->route('admin.settings.index', ['tab' => 'platform'])->with('status', 'Platform settings updated successfully.');
    }

    /**
     * Show campaign settings.
     */
    public function campaigns(): View
    {
        if (!SettingsRegistry::canAccess('campaigns')) {
            abort(403, 'You do not have permission to access campaign settings.');
        }

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

        return view('admin::settings.campaigns', compact('settings'));
    }

    /**
     * Update campaign settings.
     */
    public function updateCampaigns(UpdateCampaignSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();
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
                // Handle newline-separated string or array
                $categories = is_array($rest['prohibited_categories'])
                    ? $rest['prohibited_categories']
                    : array_filter(array_map('trim', explode("\n", $rest['prohibited_categories'])));
                PlatformSetting::setJson('content_restrictions.prohibited_categories', $categories, $category);
            }
            if (isset($rest['banned_keywords'])) {
                // Handle newline-separated string or array
                $keywords = is_array($rest['banned_keywords'])
                    ? $rest['banned_keywords']
                    : array_filter(array_map('trim', explode("\n", $rest['banned_keywords'])));
                PlatformSetting::setJson('content_restrictions.banned_keywords', $keywords, $category);
            }
            if (isset($rest['manual_review_threshold'])) {
                PlatformSetting::set('content_restrictions.manual_review_threshold', (string) $rest['manual_review_threshold'], $category);
            }
        }

        return redirect()->route('admin.settings.index', ['tab' => 'campaigns'])->with('status', 'Campaign settings updated successfully.');
    }

    /**
     * Show user & security settings.
     */
    public function users(): View
    {
        if (!SettingsRegistry::canAccess('users')) {
            abort(403, 'You do not have permission to access user settings.');
        }

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

        return view('admin::settings.users', compact('settings'));
    }

    /**
     * Update user settings.
     */
    public function updateUsers(UpdateUserSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();
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

        return redirect()->route('admin.settings.index', ['tab' => 'users'])->with('status', 'User settings updated successfully.');
    }

    /**
     * Show financial & payment settings.
     */
    public function financial(): View
    {
        if (!SettingsRegistry::canAccess('financial')) {
            abort(403, 'You do not have permission to access financial settings.');
        }

        $settings = [
            'payment_gateways' => [
                'stripe_enabled' => PlatformSetting::getBool('payment_gateways.stripe_enabled', false),
                'stripe_api_key' => PlatformSetting::getString('payment_gateways.stripe_api_key', ''),
                'stripe_secret_key' => PlatformSetting::getString('payment_gateways.stripe_secret_key', ''),
                'paypal_enabled' => PlatformSetting::getBool('payment_gateways.paypal_enabled', false),
                'paypal_client_id' => PlatformSetting::getString('payment_gateways.paypal_client_id', ''),
                'paypal_secret' => PlatformSetting::getString('payment_gateways.paypal_secret', ''),
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

        return view('admin::settings.financial', compact('settings'));
    }

    /**
     * Update financial settings.
     */
    public function updateFinancial(UpdateFinancialSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();
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

        return redirect()->route('admin.settings.index', ['tab' => 'financial'])->with('status', 'Financial settings updated successfully.');
    }

    /**
     * Show communication & email settings.
     */
    public function communication(): View
    {
        if (!SettingsRegistry::canAccess('communication')) {
            abort(403, 'You do not have permission to access communication settings.');
        }

        $settings = [
            'notifications' => [
                'new_high_value_campaign' => PlatformSetting::getBool('notifications.new_high_value_campaign', true),
                'campaign_reported' => PlatformSetting::getBool('notifications.campaign_reported', true),
                'payout_failed' => PlatformSetting::getBool('notifications.payout_failed', true),
            ],
            'smtp' => [
                'host' => PlatformSetting::getString('smtp.host', ''),
                'port' => PlatformSetting::getInt('smtp.port', 587),
                'username' => PlatformSetting::getString('smtp.username', ''),
                'password' => PlatformSetting::getString('smtp.password', ''),
                'encryption' => PlatformSetting::getString('smtp.encryption', 'tls'),
                'from_address' => PlatformSetting::getString('smtp.from_address', ''),
                'from_name' => PlatformSetting::getString('smtp.from_name', ''),
            ],
        ];

        return view('admin::settings.communication', compact('settings'));
    }

    /**
     * Update communication settings.
     */
    public function updateCommunication(UpdateCommunicationSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $category = 'communication';

        if (isset($data['notifications'])) {
            $notif = $data['notifications'];
            PlatformSetting::setBool('notifications.new_high_value_campaign', (bool) ($notif['new_high_value_campaign'] ?? false), $category);
            PlatformSetting::setBool('notifications.campaign_reported', (bool) ($notif['campaign_reported'] ?? false), $category);
            PlatformSetting::setBool('notifications.payout_failed', (bool) ($notif['payout_failed'] ?? false), $category);
        }

        if (isset($data['smtp'])) {
            $smtp = $data['smtp'];
            if (isset($smtp['host'])) {
                PlatformSetting::set('smtp.host', $smtp['host'], $category);
            }
            if (isset($smtp['port'])) {
                PlatformSetting::set('smtp.port', (string) $smtp['port'], $category);
            }
            if (isset($smtp['username'])) {
                PlatformSetting::set('smtp.username', $smtp['username'], $category);
            }
            if (isset($smtp['password'])) {
                PlatformSetting::set('smtp.password', $smtp['password'], $category);
            }
            if (isset($smtp['encryption'])) {
                PlatformSetting::set('smtp.encryption', $smtp['encryption'], $category);
            }
            if (isset($smtp['from_address'])) {
                PlatformSetting::set('smtp.from_address', $smtp['from_address'], $category);
            }
            if (isset($smtp['from_name'])) {
                PlatformSetting::set('smtp.from_name', $smtp['from_name'], $category);
            }
        }

        return redirect()->route('admin.settings.index', ['tab' => 'communication'])->with('status', 'Communication settings updated successfully.');
    }

    /**
     * Show appearance settings.
     */
    public function appearance(): View
    {
        if (!SettingsRegistry::canAccess('appearance')) {
            abort(403, 'You do not have permission to access appearance settings.');
        }

        $settings = [
            'general' => [
                'site_name' => PlatformSetting::getString('general.site_name', config('app.name')),
                'site_logo' => PlatformSetting::getString('general.site_logo', ''),
                'favicon' => PlatformSetting::getString('general.favicon', ''),
                'meta_description' => PlatformSetting::getString('general.meta_description', ''),
            ],
            'landing_page' => [
                'featured_campaigns' => PlatformSetting::getJson('landing_page.featured_campaigns', []),
                'hero_banner_text' => PlatformSetting::getString('landing_page.hero_banner_text', ''),
                'hero_banner_image' => PlatformSetting::getString('landing_page.hero_banner_image', ''),
            ],
            'categories' => PlatformSetting::getJson('categories.list', []),
        ];

        return view('admin::settings.appearance', compact('settings'));
    }

    /**
     * Update appearance settings.
     */
    public function updateAppearance(UpdateAppearanceSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $category = 'appearance';

        if (isset($data['general'])) {
            $gen = $data['general'];
            if (isset($gen['site_name'])) {
                PlatformSetting::set('general.site_name', $gen['site_name'], $category);
            }
            if (isset($gen['site_logo'])) {
                PlatformSetting::set('general.site_logo', $gen['site_logo'], $category);
            }
            if (isset($gen['favicon'])) {
                PlatformSetting::set('general.favicon', $gen['favicon'], $category);
            }
            if (isset($gen['meta_description'])) {
                PlatformSetting::set('general.meta_description', $gen['meta_description'], $category);
            }
        }

        if (isset($data['landing_page'])) {
            $lp = $data['landing_page'];
            if (isset($lp['featured_campaigns'])) {
                // Handle comma-separated string or array
                $campaigns = is_array($lp['featured_campaigns'])
                    ? $lp['featured_campaigns']
                    : array_filter(array_map('trim', explode(',', $lp['featured_campaigns'])));
                PlatformSetting::setJson('landing_page.featured_campaigns', $campaigns, $category);
            }
            if (isset($lp['hero_banner_text'])) {
                PlatformSetting::set('landing_page.hero_banner_text', $lp['hero_banner_text'], $category);
            }
            if (isset($lp['hero_banner_image'])) {
                PlatformSetting::set('landing_page.hero_banner_image', $lp['hero_banner_image'], $category);
            }
        }

        if (isset($data['categories'])) {
            PlatformSetting::setJson('categories.list', $data['categories'], $category);
        }

        return redirect()->route('admin.settings.index', ['tab' => 'appearance'])->with('status', 'Appearance settings updated successfully.');
    }

    /**
     * Show advanced & technical settings.
     */
    public function advanced(): View
    {
        if (!SettingsRegistry::canAccess('advanced')) {
            abort(403, 'You do not have permission to access advanced settings.');
        }

        $settings = [
            'analytics' => [
                'google_analytics_id' => PlatformSetting::getString('analytics.google_analytics_id', ''),
                'facebook_pixel_id' => PlatformSetting::getString('analytics.facebook_pixel_id', ''),
                'custom_header_scripts' => PlatformSetting::getString('analytics.custom_header_scripts', ''),
                'custom_footer_scripts' => PlatformSetting::getString('analytics.custom_footer_scripts', ''),
            ],
            'webhooks' => PlatformSetting::getJson('webhooks.list', []),
        ];

        $auditLog = SettingsAuditLog::with('changedBy')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return view('admin::settings.advanced', compact('settings', 'auditLog'));
    }

    /**
     * Update advanced settings.
     */
    public function updateAdvanced(UpdateAdvancedSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $category = 'advanced';

        if (isset($data['analytics'])) {
            $analytics = $data['analytics'];
            if (isset($analytics['google_analytics_id'])) {
                PlatformSetting::set('analytics.google_analytics_id', $analytics['google_analytics_id'], $category);
            }
            if (isset($analytics['facebook_pixel_id'])) {
                PlatformSetting::set('analytics.facebook_pixel_id', $analytics['facebook_pixel_id'], $category);
            }
            if (isset($analytics['custom_header_scripts'])) {
                PlatformSetting::set('analytics.custom_header_scripts', $analytics['custom_header_scripts'], $category);
            }
            if (isset($analytics['custom_footer_scripts'])) {
                PlatformSetting::set('analytics.custom_footer_scripts', $analytics['custom_footer_scripts'], $category);
            }
        }

        if (isset($data['webhooks'])) {
            PlatformSetting::setJson('webhooks.list', $data['webhooks'], $category);
        }

        return redirect()->route('admin.settings.index', ['tab' => 'advanced'])->with('status', 'Advanced settings updated successfully.');
    }

    /**
     * Show audit log for settings changes.
     */
    public function auditLog(): View
    {
        if (!auth()->user()?->hasRole('Super Admin')) {
            abort(403, 'Only Super Admins can view the audit log.');
        }

        $logs = SettingsAuditLog::with('changedBy')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('admin::settings.audit-log', compact('logs'));
    }
}