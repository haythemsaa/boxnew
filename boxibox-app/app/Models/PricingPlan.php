<?php

namespace App\Models;

/**
 * Pricing Plans Configuration for Boxibox SaaS
 * This class defines all pricing plans and their features
 */
class PricingPlan
{
    public const PLANS = [
        'starter' => [
            'name' => 'Starter',
            'slug' => 'starter',
            'description' => 'Idéal pour démarrer votre activité de self-stockage',
            'price_monthly' => 49,
            'price_yearly' => 490,
            'currency' => 'EUR',
            'max_units' => 100,
            'max_sites' => 1,
            'max_users' => 2,
            'features' => [
                'boxes_management' => true,
                'customers_management' => true,
                'contracts_management' => true,
                'invoicing' => true,
                'basic_reports' => true,
                'email_notifications' => true,
                'payment_reminders' => 3, // per month
                'electronic_signature' => false,
                'sepa_direct_debit' => false,
                'crm_prospects' => false,
                'advanced_analytics' => false,
                'interactive_plan' => false,
                'multi_site' => false,
                'api_access' => false,
                'booking_widget' => false,
                'dynamic_pricing' => false,
                'white_label' => false,
                'priority_support' => false,
                'dedicated_support' => false,
            ],
            'support' => 'email',
            'popular' => false,
        ],
        'professional' => [
            'name' => 'Professional',
            'slug' => 'professional',
            'description' => 'Pour les centres en croissance avec besoins avancés',
            'price_monthly' => 99,
            'price_yearly' => 990,
            'currency' => 'EUR',
            'max_units' => 500,
            'max_sites' => 3,
            'max_users' => 5,
            'features' => [
                'boxes_management' => true,
                'customers_management' => true,
                'contracts_management' => true,
                'invoicing' => true,
                'basic_reports' => true,
                'email_notifications' => true,
                'payment_reminders' => -1, // unlimited
                'electronic_signature' => true,
                'sepa_direct_debit' => false,
                'crm_prospects' => true,
                'advanced_analytics' => true,
                'interactive_plan' => false,
                'multi_site' => false,
                'api_access' => 'read', // read-only
                'booking_widget' => 'addon', // available as addon
                'dynamic_pricing' => false,
                'white_label' => false,
                'priority_support' => false,
                'dedicated_support' => false,
            ],
            'support' => 'email_chat',
            'popular' => true,
        ],
        'business' => [
            'name' => 'Business',
            'slug' => 'business',
            'description' => 'Solution complète pour les opérateurs multi-sites',
            'price_monthly' => 199,
            'price_yearly' => 1990,
            'currency' => 'EUR',
            'max_units' => 2000,
            'max_sites' => 10,
            'max_users' => 15,
            'features' => [
                'boxes_management' => true,
                'customers_management' => true,
                'contracts_management' => true,
                'invoicing' => true,
                'basic_reports' => true,
                'email_notifications' => true,
                'payment_reminders' => -1,
                'electronic_signature' => true,
                'sepa_direct_debit' => true,
                'crm_prospects' => true,
                'advanced_analytics' => true,
                'interactive_plan' => true,
                'multi_site' => true,
                'api_access' => 'full',
                'booking_widget' => 'basic', // basic included
                'dynamic_pricing' => true,
                'white_label' => false,
                'priority_support' => true,
                'dedicated_support' => false,
            ],
            'support' => 'priority',
            'popular' => false,
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'slug' => 'enterprise',
            'description' => 'Pour les grands groupes avec besoins spécifiques',
            'price_monthly' => null, // custom pricing
            'price_yearly' => null,
            'currency' => 'EUR',
            'max_units' => -1, // unlimited
            'max_sites' => -1,
            'max_users' => -1,
            'features' => [
                'boxes_management' => true,
                'customers_management' => true,
                'contracts_management' => true,
                'invoicing' => true,
                'basic_reports' => true,
                'email_notifications' => true,
                'payment_reminders' => -1,
                'electronic_signature' => true,
                'sepa_direct_debit' => true,
                'crm_prospects' => true,
                'advanced_analytics' => true,
                'interactive_plan' => true,
                'multi_site' => true,
                'api_access' => 'full',
                'booking_widget' => 'pro', // pro included
                'dynamic_pricing' => true,
                'white_label' => true,
                'priority_support' => true,
                'dedicated_support' => true,
            ],
            'support' => 'dedicated',
            'popular' => false,
        ],
    ];

    public const WIDGET_ADDONS = [
        'widget_basic' => [
            'name' => 'Widget Basic',
            'slug' => 'widget_basic',
            'description' => 'Réservation en ligne simple et efficace',
            'price_monthly' => 29,
            'price_yearly' => 290,
            'included_in' => ['business', 'enterprise'],
            'features' => [
                'online_booking_24_7' => true,
                'realtime_availability' => true,
                'integrated_payment' => true,
                'mobile_responsive' => true,
                'email_confirmation' => true,
                'promo_codes' => false,
                'interactive_map_selection' => false,
                'multi_language' => false,
                'conversion_analytics' => false,
                'custom_branding' => false,
                'custom_domain' => false,
                'no_boxibox_branding' => false,
                'custom_css_js' => false,
                'webhooks' => false,
            ],
        ],
        'widget_pro' => [
            'name' => 'Widget Pro',
            'slug' => 'widget_pro',
            'description' => 'Widget avancé avec personnalisation complète',
            'price_monthly' => 49,
            'price_yearly' => 490,
            'included_in' => ['enterprise'],
            'features' => [
                'online_booking_24_7' => true,
                'realtime_availability' => true,
                'integrated_payment' => true,
                'mobile_responsive' => true,
                'email_confirmation' => true,
                'promo_codes' => true,
                'interactive_map_selection' => true,
                'multi_language' => true, // FR/EN/ES/DE
                'conversion_analytics' => true,
                'custom_branding' => true,
                'custom_domain' => false,
                'no_boxibox_branding' => false,
                'custom_css_js' => false,
                'webhooks' => false,
            ],
        ],
        'widget_whitelabel' => [
            'name' => 'Widget White-Label',
            'slug' => 'widget_whitelabel',
            'description' => 'Solution entièrement personnalisable à votre marque',
            'price_monthly' => 99,
            'price_yearly' => 990,
            'included_in' => [],
            'requires_plan' => 'enterprise',
            'features' => [
                'online_booking_24_7' => true,
                'realtime_availability' => true,
                'integrated_payment' => true,
                'mobile_responsive' => true,
                'email_confirmation' => true,
                'promo_codes' => true,
                'interactive_map_selection' => true,
                'multi_language' => true,
                'conversion_analytics' => true,
                'custom_branding' => true,
                'custom_domain' => true,
                'no_boxibox_branding' => true,
                'custom_css_js' => true,
                'webhooks' => true,
            ],
        ],
    ];

    public const FEATURE_LABELS = [
        // Main features
        'boxes_management' => 'Gestion des boxes',
        'customers_management' => 'Gestion des clients',
        'contracts_management' => 'Gestion des contrats',
        'invoicing' => 'Facturation automatique',
        'basic_reports' => 'Rapports de base',
        'email_notifications' => 'Notifications email',
        'payment_reminders' => 'Rappels de paiement',
        'electronic_signature' => 'Signature électronique',
        'sepa_direct_debit' => 'Prélèvement SEPA',
        'crm_prospects' => 'CRM & Prospects',
        'advanced_analytics' => 'Analytics avancés',
        'interactive_plan' => 'Plan interactif',
        'multi_site' => 'Multi-sites',
        'api_access' => 'Accès API',
        'booking_widget' => 'Widget réservation',
        'dynamic_pricing' => 'Tarification dynamique',
        'white_label' => 'White-label',
        'priority_support' => 'Support prioritaire',
        'dedicated_support' => 'Support dédié',

        // Widget features
        'online_booking_24_7' => 'Réservation en ligne 24/7',
        'realtime_availability' => 'Disponibilités temps réel',
        'integrated_payment' => 'Paiement CB intégré',
        'mobile_responsive' => 'Responsive mobile',
        'email_confirmation' => 'Confirmation email auto',
        'promo_codes' => 'Codes promo & réductions',
        'interactive_map_selection' => 'Sélection sur plan interactif',
        'multi_language' => 'Multi-langue (FR/EN/ES/DE)',
        'conversion_analytics' => 'Analytics conversions',
        'custom_branding' => 'Personnalisation couleurs/logo',
        'custom_domain' => 'Domaine personnalisé',
        'no_boxibox_branding' => 'Sans mention Boxibox',
        'custom_css_js' => 'CSS/JS personnalisables',
        'webhooks' => 'Webhook events',
    ];

    public const SUPPORT_LABELS = [
        'email' => 'Support email',
        'email_chat' => 'Email + Chat en direct',
        'priority' => 'Support prioritaire',
        'dedicated' => 'Support dédié + Téléphone',
    ];

    /**
     * Get all plans
     */
    public static function all(): array
    {
        return self::PLANS;
    }

    /**
     * Get a specific plan by slug
     */
    public static function get(string $slug): ?array
    {
        return self::PLANS[$slug] ?? null;
    }

    /**
     * Get all widget addons
     */
    public static function widgets(): array
    {
        return self::WIDGET_ADDONS;
    }

    /**
     * Get a specific widget addon by slug
     */
    public static function getWidget(string $slug): ?array
    {
        return self::WIDGET_ADDONS[$slug] ?? null;
    }

    /**
     * Check if a plan has a specific feature
     */
    public static function hasFeature(string $planSlug, string $feature): bool
    {
        $plan = self::get($planSlug);
        if (!$plan) {
            return false;
        }

        $value = $plan['features'][$feature] ?? false;

        // Handle special cases
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value)) {
            return $value !== 0;
        }
        if (is_string($value)) {
            return !empty($value) && $value !== 'false';
        }

        return (bool) $value;
    }

    /**
     * Get the monthly price for a plan
     */
    public static function getMonthlyPrice(string $planSlug): ?float
    {
        $plan = self::get($planSlug);
        return $plan['price_monthly'] ?? null;
    }

    /**
     * Get the yearly price for a plan
     */
    public static function getYearlyPrice(string $planSlug): ?float
    {
        $plan = self::get($planSlug);
        return $plan['price_yearly'] ?? null;
    }

    /**
     * Calculate yearly savings
     */
    public static function getYearlySavings(string $planSlug): ?float
    {
        $plan = self::get($planSlug);
        if (!$plan || !$plan['price_monthly'] || !$plan['price_yearly']) {
            return null;
        }

        return ($plan['price_monthly'] * 12) - $plan['price_yearly'];
    }

    /**
     * Get plans available for upgrade from a given plan
     */
    public static function getUpgradeOptions(string $currentPlan): array
    {
        $planOrder = ['starter', 'professional', 'business', 'enterprise'];
        $currentIndex = array_search($currentPlan, $planOrder);

        if ($currentIndex === false) {
            return [];
        }

        $upgrades = [];
        for ($i = $currentIndex + 1; $i < count($planOrder); $i++) {
            $upgrades[] = self::get($planOrder[$i]);
        }

        return $upgrades;
    }

    /**
     * Calculate total monthly cost with addons
     */
    public static function calculateMonthlyTotal(string $planSlug, array $addons = []): float
    {
        $plan = self::get($planSlug);
        $total = $plan['price_monthly'] ?? 0;

        foreach ($addons as $addonSlug) {
            $addon = self::getWidget($addonSlug);
            if ($addon && !in_array($planSlug, $addon['included_in'] ?? [])) {
                $total += $addon['price_monthly'];
            }
        }

        return $total;
    }
}
