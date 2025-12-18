<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Module;
use App\Models\SubscriptionPlan;

class SuperAdminSeeder extends Seeder
{
    /**
     * Seed modules et plans d'abonnement pour le système BoxiBox SaaS
     */
    public function run(): void
    {
        $this->seedModules();
        $this->seedSubscriptionPlans();
    }

    /**
     * Créer les modules disponibles
     */
    private function seedModules(): void
    {
        $modules = [
            // MODULES CORE (Toujours inclus)
            [
                'code' => 'core_boxes',
                'name' => 'Gestion des Boxes',
                'description' => 'Gestion complète des boxes, sites et étages',
                'icon' => 'cube',
                'color' => 'blue',
                'category' => 'core',
                'monthly_price' => 0,
                'yearly_price' => 0,
                'is_core' => true,
                'is_active' => true,
                'sort_order' => 1,
                'features' => ['boxes', 'sites', 'floors', 'visual_layout'],
            ],
            [
                'code' => 'core_customers',
                'name' => 'Gestion Clients',
                'description' => 'Gestion des clients et contrats',
                'icon' => 'users',
                'color' => 'green',
                'category' => 'core',
                'monthly_price' => 0,
                'yearly_price' => 0,
                'is_core' => true,
                'is_active' => true,
                'sort_order' => 2,
                'features' => ['customers', 'contracts', 'customer_portal'],
            ],
            [
                'code' => 'core_invoicing',
                'name' => 'Facturation',
                'description' => 'Facturation et suivi des paiements',
                'icon' => 'file-invoice',
                'color' => 'yellow',
                'category' => 'core',
                'monthly_price' => 0,
                'yearly_price' => 0,
                'is_core' => true,
                'is_active' => true,
                'sort_order' => 3,
                'features' => ['invoices', 'payments', 'reminders'],
            ],

            // MODULES MARKETING & CRM
            [
                'code' => 'crm',
                'name' => 'CRM Avancé',
                'description' => 'Gestion des prospects, leads et campagnes marketing',
                'icon' => 'chart-line',
                'color' => 'purple',
                'category' => 'marketing',
                'monthly_price' => 29,
                'yearly_price' => 290,
                'is_core' => false,
                'is_active' => true,
                'sort_order' => 10,
                'features' => ['leads', 'prospects', 'email_campaigns', 'sms_campaigns', 'email_sequences'],
                'routes' => ['tenant.crm.*', 'tenant.leads.*', 'tenant.prospects.*'],
            ],
            [
                'code' => 'booking',
                'name' => 'Système de Réservation',
                'description' => 'Réservation en ligne avec widget personnalisable',
                'icon' => 'calendar-check',
                'color' => 'teal',
                'category' => 'marketing',
                'monthly_price' => 49,
                'yearly_price' => 490,
                'is_core' => false,
                'is_active' => true,
                'sort_order' => 11,
                'features' => ['online_booking', 'booking_widget', 'promo_codes', 'payment_integration'],
                'routes' => ['tenant.bookings.*'],
            ],
            [
                'code' => 'loyalty',
                'name' => 'Programme de Fidélité',
                'description' => 'Gestion des points de fidélité et récompenses',
                'icon' => 'gift',
                'color' => 'pink',
                'category' => 'marketing',
                'monthly_price' => 19,
                'yearly_price' => 190,
                'is_core' => false,
                'is_active' => true,
                'sort_order' => 12,
                'features' => ['loyalty_points', 'rewards', 'redemption'],
                'routes' => ['tenant.loyalty.*'],
            ],
            [
                'code' => 'reviews',
                'name' => 'Gestion des Avis',
                'description' => 'Collecte et gestion des avis clients',
                'icon' => 'star',
                'color' => 'orange',
                'category' => 'marketing',
                'monthly_price' => 15,
                'yearly_price' => 150,
                'is_core' => false,
                'is_active' => true,
                'sort_order' => 13,
                'features' => ['review_collection', 'review_management', 'auto_requests'],
                'routes' => ['tenant.reviews.*'],
            ],

            // MODULES OPERATIONS
            [
                'code' => 'maintenance',
                'name' => 'Gestion Maintenance',
                'description' => 'Tickets de maintenance et suivi des interventions',
                'icon' => 'wrench',
                'color' => 'red',
                'category' => 'operations',
                'monthly_price' => 25,
                'yearly_price' => 250,
                'is_core' => false,
                'is_active' => true,
                'sort_order' => 20,
                'features' => ['maintenance_tickets', 'vendor_management', 'maintenance_history'],
                'routes' => ['tenant.maintenance.*'],
            ],
            [
                'code' => 'inspections',
                'name' => 'Inspections & Rondes',
                'description' => 'Inspections de boxes et rondes de sécurité',
                'icon' => 'clipboard-check',
                'color' => 'indigo',
                'category' => 'operations',
                'monthly_price' => 20,
                'yearly_price' => 200,
                'is_core' => false,
                'is_active' => true,
                'sort_order' => 21,
                'features' => ['inspections', 'patrols', 'condition_reports'],
                'routes' => ['tenant.inspections.*'],
            ],
            [
                'code' => 'overdue',
                'name' => 'Gestion Impayés',
                'description' => 'Workflows automatisés pour la gestion des impayés',
                'icon' => 'exclamation-triangle',
                'color' => 'red',
                'category' => 'operations',
                'monthly_price' => 30,
                'yearly_price' => 300,
                'is_core' => false,
                'is_active' => true,
                'sort_order' => 22,
                'features' => ['overdue_workflows', 'auto_reminders', 'lock_management', 'auction'],
                'routes' => ['tenant.overdue.*'],
            ],
            [
                'code' => 'staff',
                'name' => 'Gestion du Personnel',
                'description' => 'Gestion des équipes, horaires et performances',
                'icon' => 'user-tie',
                'color' => 'gray',
                'category' => 'operations',
                'monthly_price' => 35,
                'yearly_price' => 350,
                'is_core' => false,
                'is_active' => true,
                'sort_order' => 23,
                'features' => ['staff_management', 'shifts', 'tasks', 'performance'],
                'routes' => ['tenant.staff.*'],
            ],
            [
                'code' => 'valet',
                'name' => 'Valet Storage',
                'description' => 'Service de stockage avec enlèvement et livraison',
                'icon' => 'truck',
                'color' => 'blue',
                'category' => 'operations',
                'monthly_price' => 40,
                'yearly_price' => 400,
                'is_core' => false,
                'is_active' => true,
                'sort_order' => 24,
                'features' => ['pickup_delivery', 'route_optimization', 'driver_management'],
                'routes' => ['tenant.valet.*'],
            ],

            // MODULES INTEGRATIONS
            [
                'code' => 'iot',
                'name' => 'IoT & Smart Locks',
                'description' => 'Intégration serrures connectées et capteurs IoT',
                'icon' => 'lock',
                'color' => 'cyan',
                'category' => 'integrations',
                'monthly_price' => 45,
                'yearly_price' => 450,
                'is_core' => false,
                'is_active' => true,
                'sort_order' => 30,
                'features' => ['smart_locks', 'sensors', 'alerts', 'automation'],
                'routes' => ['tenant.iot.*'],
                'dependencies' => [],
            ],
            [
                'code' => 'accounting',
                'name' => 'Intégration Comptable',
                'description' => 'Synchronisation avec logiciels comptables (FEC, Sage, etc.)',
                'icon' => 'calculator',
                'color' => 'green',
                'category' => 'integrations',
                'monthly_price' => 35,
                'yearly_price' => 350,
                'is_core' => false,
                'is_active' => true,
                'sort_order' => 31,
                'features' => ['fec_export', 'accounting_sync', 'tax_reports'],
                'routes' => ['tenant.accounting.*'],
            ],
            [
                'code' => 'webhooks',
                'name' => 'API & Webhooks',
                'description' => 'Webhooks et API pour intégrations personnalisées',
                'icon' => 'code',
                'color' => 'purple',
                'category' => 'integrations',
                'monthly_price' => 25,
                'yearly_price' => 250,
                'is_core' => false,
                'is_active' => true,
                'sort_order' => 32,
                'features' => ['webhooks', 'api_access', 'custom_integrations'],
                'routes' => ['tenant.integrations.*'],
            ],
            [
                'code' => 'video_calls',
                'name' => 'Visites Virtuelles',
                'description' => 'Visioconférence pour visites à distance',
                'icon' => 'video',
                'color' => 'blue',
                'category' => 'integrations',
                'monthly_price' => 20,
                'yearly_price' => 200,
                'is_core' => false,
                'is_active' => true,
                'sort_order' => 33,
                'features' => ['video_calls', 'screen_sharing', 'recording'],
                'routes' => ['tenant.video-calls.*'],
            ],

            // MODULES ANALYTICS
            [
                'code' => 'analytics',
                'name' => 'Analytics Avancés',
                'description' => 'Tableaux de bord et rapports personnalisés',
                'icon' => 'chart-bar',
                'color' => 'indigo',
                'category' => 'analytics',
                'monthly_price' => 30,
                'yearly_price' => 300,
                'is_core' => false,
                'is_active' => true,
                'sort_order' => 40,
                'features' => ['advanced_analytics', 'custom_reports', 'kpi_tracking'],
                'routes' => ['tenant.analytics.*', 'tenant.reports.*'],
            ],
            [
                'code' => 'ai_advisor',
                'name' => 'Conseiller IA',
                'description' => 'Intelligence artificielle pour optimisation et prédictions',
                'icon' => 'brain',
                'color' => 'pink',
                'category' => 'analytics',
                'monthly_price' => 50,
                'yearly_price' => 500,
                'is_core' => false,
                'is_active' => true,
                'sort_order' => 41,
                'features' => ['ai_predictions', 'churn_analysis', 'pricing_optimization', 'upsell'],
                'routes' => ['tenant.ai.*'],
                'dependencies' => ['analytics'],
            ],

            // MODULES PREMIUM
            [
                'code' => 'dynamic_pricing',
                'name' => 'Tarification Dynamique',
                'description' => 'Prix automatiques basés sur la demande',
                'icon' => 'tags',
                'color' => 'yellow',
                'category' => 'premium',
                'monthly_price' => 40,
                'yearly_price' => 400,
                'is_core' => false,
                'is_active' => true,
                'sort_order' => 50,
                'features' => ['dynamic_pricing', 'seasonal_rates', 'demand_based'],
                'routes' => ['tenant.pricing.*'],
            ],
            [
                'code' => 'sustainability',
                'name' => 'Durabilité',
                'description' => 'Suivi empreinte carbone et initiatives écologiques',
                'icon' => 'leaf',
                'color' => 'green',
                'category' => 'premium',
                'monthly_price' => 25,
                'yearly_price' => 250,
                'is_core' => false,
                'is_active' => true,
                'sort_order' => 51,
                'features' => ['carbon_tracking', 'eco_initiatives', 'certifications'],
                'routes' => ['tenant.sustainability.*'],
            ],
            [
                'code' => 'gdpr',
                'name' => 'Conformité RGPD',
                'description' => 'Outils de conformité RGPD et gestion des consentements',
                'icon' => 'shield-alt',
                'color' => 'blue',
                'category' => 'premium',
                'monthly_price' => 30,
                'yearly_price' => 300,
                'is_core' => false,
                'is_active' => true,
                'sort_order' => 52,
                'features' => ['gdpr_compliance', 'consent_management', 'data_export'],
                'routes' => ['tenant.gdpr.*'],
            ],
        ];

        foreach ($modules as $module) {
            Module::updateOrCreate(
                ['code' => $module['code']],
                $module
            );
        }

        $this->command->info('✓ Modules créés avec succès');
    }

    /**
     * Créer les plans d'abonnement
     */
    private function seedSubscriptionPlans(): void
    {
        // Récupérer les IDs des modules
        $coreModules = Module::where('is_core', true)->pluck('id')->toArray();
        $crmModule = Module::where('code', 'crm')->first()?->id;
        $bookingModule = Module::where('code', 'booking')->first()?->id;
        $maintenanceModule = Module::where('code', 'maintenance')->first()?->id;
        $analyticsModule = Module::where('code', 'analytics')->first()?->id;
        $iotModule = Module::where('code', 'iot')->first()?->id;
        $aiModule = Module::where('code', 'ai_advisor')->first()?->id;
        $dynamicPricingModule = Module::where('code', 'dynamic_pricing')->first()?->id;

        $plans = [
            [
                'code' => 'starter',
                'name' => 'Starter',
                'description' => 'Parfait pour démarrer votre activité de self-storage',
                'badge_color' => 'blue',
                'monthly_price' => 49,
                'yearly_price' => 490,
                'yearly_discount' => 16.67,
                'max_sites' => 1,
                'max_boxes' => 100,
                'max_users' => 3,
                'max_customers' => null,
                'includes_support' => true,
                'support_level' => 'email',
                'included_modules' => $coreModules,
                'features' => [
                    '1 site',
                    'Jusqu\'à 100 boxes',
                    '3 utilisateurs',
                    'Gestion complète des boxes',
                    'Gestion clients et contrats',
                    'Facturation',
                    'Support email',
                ],
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'professional',
                'name' => 'Professional',
                'description' => 'Pour les entreprises en croissance avec plusieurs sites',
                'badge_color' => 'purple',
                'monthly_price' => 99,
                'yearly_price' => 990,
                'yearly_discount' => 16.67,
                'max_sites' => 3,
                'max_boxes' => 500,
                'max_users' => 10,
                'max_customers' => null,
                'includes_support' => true,
                'support_level' => 'priority',
                'included_modules' => array_merge($coreModules, array_filter([$crmModule, $bookingModule, $maintenanceModule])),
                'features' => [
                    '3 sites',
                    'Jusqu\'à 500 boxes',
                    '10 utilisateurs',
                    'Tous les modules Starter',
                    'CRM & Marketing',
                    'Système de réservation en ligne',
                    'Gestion de la maintenance',
                    'Support prioritaire',
                ],
                'is_popular' => true,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'business',
                'name' => 'Business',
                'description' => 'Solution complète pour les grandes entreprises',
                'badge_color' => 'yellow',
                'monthly_price' => 199,
                'yearly_price' => 1990,
                'yearly_discount' => 16.67,
                'max_sites' => 10,
                'max_boxes' => 2000,
                'max_users' => 50,
                'max_customers' => null,
                'includes_support' => true,
                'support_level' => 'priority',
                'included_modules' => array_merge(
                    $coreModules,
                    array_filter([
                        $crmModule,
                        $bookingModule,
                        $maintenanceModule,
                        $analyticsModule,
                        $iotModule,
                        $dynamicPricingModule,
                    ])
                ),
                'features' => [
                    '10 sites',
                    'Jusqu\'à 2000 boxes',
                    '50 utilisateurs',
                    'Tous les modules Professional',
                    'Analytics avancés',
                    'IoT & Smart Locks',
                    'Tarification dynamique',
                    'API & Webhooks',
                    'Support prioritaire',
                ],
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'code' => 'enterprise',
                'name' => 'Enterprise',
                'description' => 'Solution sur mesure pour les grands groupes',
                'badge_color' => 'red',
                'monthly_price' => 399,
                'yearly_price' => 3990,
                'yearly_discount' => 16.67,
                'max_sites' => null,
                'max_boxes' => null,
                'max_users' => null,
                'max_customers' => null,
                'includes_support' => true,
                'support_level' => 'dedicated',
                'included_modules' => Module::pluck('id')->toArray(), // Tous les modules
                'features' => [
                    'Sites illimités',
                    'Boxes illimitées',
                    'Utilisateurs illimités',
                    'TOUS les modules inclus',
                    'Conseiller IA',
                    'Formation personnalisée',
                    'Account manager dédié',
                    'Support 24/7',
                    'SLA garanti',
                    'Développements sur mesure',
                ],
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(
                ['code' => $plan['code']],
                $plan
            );
        }

        $this->command->info('✓ Plans d\'abonnement créés avec succès');
    }
}
