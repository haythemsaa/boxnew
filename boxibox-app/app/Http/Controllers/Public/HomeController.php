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

        $pricing = [
            [
                'name' => 'Starter',
                'price' => 49,
                'period' => 'mois',
                'description' => 'Parfait pour démarrer',
                'features' => [
                    '1 site',
                    'Jusqu\'à 50 boxes',
                    'Gestion clients & contrats',
                    'Facturation basique',
                    'Support email',
                ],
                'cta' => 'Commencer gratuitement',
                'popular' => false,
            ],
            [
                'name' => 'Growth',
                'price' => 149,
                'period' => 'mois',
                'description' => 'Pour les entreprises en croissance',
                'features' => [
                    'Jusqu\'à 3 sites',
                    'Jusqu\'à 200 boxes',
                    'CRM & Marketing',
                    'Analytics avancés',
                    'Paiements automatiques',
                    'API accès',
                    'Support prioritaire',
                ],
                'cta' => 'Essai gratuit 14 jours',
                'popular' => true,
            ],
            [
                'name' => 'Pro',
                'price' => 299,
                'period' => 'mois',
                'description' => 'Pour les opérateurs multi-sites',
                'features' => [
                    'Jusqu\'à 10 sites',
                    'Jusqu\'à 1000 boxes',
                    'Tout Growth +',
                    'Dynamic pricing',
                    'Smart locks integration',
                    'White-label portal',
                    'Support dédié',
                ],
                'cta' => 'Contacter les ventes',
                'popular' => false,
            ],
        ];

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
        return Inertia::render('Public/Pricing');
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
