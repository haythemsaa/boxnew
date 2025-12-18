<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Site;
use App\Models\Box;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    /**
     * Landing page - Marketing home
     */
    public function index()
    {
        // Get some stats for social proof
        $stats = [
            'total_sites' => Site::count(),
            'total_boxes' => Box::count(),
            'total_customers' => \App\Models\Customer::count(),
            'average_rating' => 4.8, // Could be calculated from reviews
        ];

        $features = [
            [
                'icon' => 'cube',
                'title' => 'Gestion des boxes',
                'description' => 'Gérez facilement vos unités de stockage avec notre interface intuitive et nos plans interactifs.',
            ],
            [
                'icon' => 'users',
                'title' => 'CRM intégré',
                'description' => 'Suivez vos prospects, gérez vos clients et automatisez votre marketing.',
            ],
            [
                'icon' => 'credit-card',
                'title' => 'Paiements automatiques',
                'description' => 'Facturation récurrente, prélèvements SEPA et rappels automatiques.',
            ],
            [
                'icon' => 'chart-bar',
                'title' => 'Analytics avancés',
                'description' => 'Tableaux de bord en temps réel, prévisions d\'occupation et optimisation des prix.',
            ],
            [
                'icon' => 'lock-closed',
                'title' => 'Contrôle d\'accès',
                'description' => 'Intégration smart locks, codes d\'accès et surveillance 24/7.',
            ],
            [
                'icon' => 'device-mobile',
                'title' => 'App mobile',
                'description' => 'Portail client mobile pour gérer les réservations et paiements.',
            ],
        ];

        $testimonials = [
            [
                'name' => 'Marie Dupont',
                'company' => 'StoragePlus Paris',
                'quote' => 'Boxibox a révolutionné notre gestion. Nous avons augmenté notre taux d\'occupation de 23% en 6 mois.',
                'rating' => 5,
                'avatar' => null,
            ],
            [
                'name' => 'Jean Martin',
                'company' => 'Self-Stock Lyon',
                'quote' => 'L\'automatisation des paiements nous fait gagner 15 heures par semaine. Interface très intuitive.',
                'rating' => 5,
                'avatar' => null,
            ],
            [
                'name' => 'Sophie Bernard',
                'company' => 'BoxStore Bordeaux',
                'quote' => 'Le meilleur rapport qualité-prix du marché. Support client exceptionnel.',
                'rating' => 5,
                'avatar' => null,
            ],
        ];

        // Load pricing from database - show first 3 plans for home page
        $pricing = \App\Models\SubscriptionPlan::where('is_active', true)
            ->orderBy('sort_order')
            ->limit(3)
            ->get()
            ->map(function ($plan) {
                $features = [];
                $features[] = ($plan->max_sites ? $plan->max_sites : 'Illimité') . ' site(s)';
                $features[] = 'Jusqu\'à ' . ($plan->max_boxes ? number_format($plan->max_boxes, 0, '', ' ') : 'illimité') . ' boxes';
                $features[] = 'Gestion clients & contrats';
                $features[] = ($plan->emails_per_month ? number_format($plan->emails_per_month, 0, '', ' ') : 'Illimité') . ' emails/mois';
                if ($plan->api_access) $features[] = 'Accès API';
                if ($plan->whitelabel) $features[] = 'Marque blanche';

                return [
                    'name' => $plan->name,
                    'price' => (int) $plan->monthly_price,
                    'period' => 'mois',
                    'description' => $plan->description ?? 'Plan ' . $plan->name,
                    'features' => $features,
                    'cta' => $plan->is_popular ? 'Essai gratuit 14 jours' : 'Commencer',
                    'popular' => (bool) $plan->is_popular,
                ];
            })
            ->toArray();

        return Inertia::render('Public/Home', [
            'stats' => $stats,
            'features' => $features,
            'testimonials' => $testimonials,
            'pricing' => $pricing,
        ]);
    }

    /**
     * Features page
     */
    public function features()
    {
        $featureCategories = [
            [
                'category' => 'Gestion des opérations',
                'icon' => 'cog',
                'features' => [
                    [
                        'title' => 'Plans interactifs',
                        'description' => 'Visualisez votre facility avec des plans SVG interactifs. Drag & drop pour organiser vos boxes.',
                        'image' => null,
                    ],
                    [
                        'title' => 'Gestion multi-sites',
                        'description' => 'Gérez plusieurs locations depuis un seul tableau de bord centralisé.',
                        'image' => null,
                    ],
                    [
                        'title' => 'Maintenance & Inspections',
                        'description' => 'Planifiez les maintenances, créez des tickets et suivez les inspections.',
                        'image' => null,
                    ],
                ],
            ],
            [
                'category' => 'Ventes & Marketing',
                'icon' => 'megaphone',
                'features' => [
                    [
                        'title' => 'Booking en ligne',
                        'description' => 'Widget de réservation intégrable sur votre site. Checkout optimisé pour la conversion.',
                        'image' => null,
                    ],
                    [
                        'title' => 'CRM complet',
                        'description' => 'Gestion des leads, scoring automatique, et suivi du pipeline commercial.',
                        'image' => null,
                    ],
                    [
                        'title' => 'Campagnes SMS/Email',
                        'description' => 'Automatisez vos communications marketing et relances.',
                        'image' => null,
                    ],
                ],
            ],
            [
                'category' => 'Finance & Paiements',
                'icon' => 'banknotes',
                'features' => [
                    [
                        'title' => 'Facturation automatique',
                        'description' => 'Génération automatique des factures, rappels et prélèvements SEPA.',
                        'image' => null,
                    ],
                    [
                        'title' => 'Dynamic Pricing',
                        'description' => 'Optimisez vos prix en fonction de l\'occupation et de la demande.',
                        'image' => null,
                    ],
                    [
                        'title' => 'Stripe & SEPA',
                        'description' => 'Acceptez les paiements CB, prélèvements SEPA et mandats récurrents.',
                        'image' => null,
                    ],
                ],
            ],
            [
                'category' => 'Sécurité & Accès',
                'icon' => 'shield-check',
                'features' => [
                    [
                        'title' => 'Smart Locks',
                        'description' => 'Intégration avec Nuki, Yale, et autres serrures connectées.',
                        'image' => null,
                    ],
                    [
                        'title' => 'Codes d\'accès',
                        'description' => 'Génération automatique de codes, gestion des accès 24/7.',
                        'image' => null,
                    ],
                    [
                        'title' => 'Audit trail',
                        'description' => 'Historique complet des accès et actions pour la sécurité.',
                        'image' => null,
                    ],
                ],
            ],
        ];

        return Inertia::render('Public/Features', [
            'featureCategories' => $featureCategories,
        ]);
    }

    /**
     * About page
     */
    public function about()
    {
        return Inertia::render('Public/About');
    }

    /**
     * Contact page
     */
    public function contact()
    {
        return Inertia::render('Public/Contact');
    }

    /**
     * Submit contact form
     */
    public function submitContact(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'company' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'message' => 'required|string|max:2000',
            'type' => 'required|in:demo,support,partnership,other',
        ]);

        // TODO: Send email notification, store in DB, etc.

        return back()->with('success', 'Merci pour votre message ! Nous vous répondrons sous 24h.');
    }

    /**
     * Demo request page
     */
    public function demo()
    {
        return Inertia::render('Public/Demo');
    }

    /**
     * Submit demo request
     */
    public function submitDemo(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'company' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'sites_count' => 'required|integer|min:1',
            'boxes_count' => 'required|integer|min:1',
            'current_software' => 'nullable|string|max:255',
            'message' => 'nullable|string|max:2000',
        ]);

        // TODO: Create lead, send notification, etc.

        return back()->with('success', 'Demande de démo reçue ! Notre équipe vous contactera dans les 24h.');
    }

    /**
     * Pricing page
     */
    public function pricing()
    {
        $plans = \App\Models\SubscriptionPlan::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(function ($plan) {
                // Get module names for display
                $moduleNames = $plan->included_modules
                    ? \App\Models\Module::whereIn('id', $plan->included_modules)
                        ->pluck('name')
                        ->toArray()
                    : [];

                return [
                    'id' => $plan->id,
                    'code' => $plan->code,
                    'name' => $plan->name,
                    'description' => $plan->description,
                    'badge_color' => $plan->badge_color,
                    'monthly_price' => (float) $plan->monthly_price,
                    'yearly_price' => (float) $plan->yearly_price,
                    'yearly_discount' => (float) $plan->yearly_discount,
                    'max_sites' => $plan->max_sites,
                    'max_boxes' => $plan->max_boxes,
                    'max_users' => $plan->max_users,
                    'max_customers' => $plan->max_customers,
                    'emails_per_month' => $plan->emails_per_month,
                    'sms_per_month' => $plan->sms_per_month,
                    'api_access' => (bool) $plan->api_access,
                    'whitelabel' => (bool) $plan->whitelabel,
                    'includes_support' => (bool) $plan->includes_support,
                    'support_level' => $plan->support_level,
                    'is_popular' => (bool) $plan->is_popular,
                    'modules' => $moduleNames,
                    'modules_count' => count($moduleNames),
                ];
            });

        $faqs = [
            [
                'question' => 'Puis-je changer de plan à tout moment ?',
                'answer' => 'Oui, vous pouvez upgrader votre plan à tout moment. Le changement prend effet immédiatement et vous ne payez que la différence au prorata.',
            ],
            [
                'question' => 'Y a-t-il des frais cachés ?',
                'answer' => 'Non, tous nos prix sont transparents. Le prix affiché inclut toutes les fonctionnalités du plan. Les seuls coûts supplémentaires sont les crédits email/SMS au-delà de votre quota inclus.',
            ],
            [
                'question' => 'Comment fonctionne l\'essai gratuit ?',
                'answer' => 'Vous bénéficiez de 14 jours d\'essai gratuit avec accès complet à toutes les fonctionnalités. Aucune carte bancaire requise pour commencer.',
            ],
            [
                'question' => 'Mes données sont-elles sécurisées ?',
                'answer' => 'Absolument. Nous utilisons le chiffrement SSL/TLS, stockons vos données sur des serveurs européens certifiés ISO 27001, et sommes conformes au RGPD.',
            ],
            [
                'question' => 'Quel support est inclus ?',
                'answer' => 'Tous les plans incluent un support par email. Les plans Professional et supérieurs bénéficient d\'un support prioritaire, et Enterprise a un account manager dédié.',
            ],
            [
                'question' => 'Proposez-vous une migration depuis un autre logiciel ?',
                'answer' => 'Oui, notre équipe peut vous accompagner gratuitement pour migrer vos données depuis la plupart des solutions du marché.',
            ],
        ];

        return Inertia::render('Public/Pricing', [
            'plans' => $plans,
            'faqs' => $faqs,
        ]);
    }

    /**
     * ROI Calculator page
     */
    public function calculator()
    {
        return Inertia::render('Public/Calculator');
    }

    /**
     * Size Calculator page for customers
     */
    public function sizeCalculator()
    {
        return Inertia::render('Public/SizeCalculator');
    }
}
