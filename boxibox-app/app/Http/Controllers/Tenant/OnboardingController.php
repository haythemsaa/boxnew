<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OnboardingController extends Controller
{
    /**
     * Page d'onboarding / guide de démarrage
     */
    public function index()
    {
        $user = auth()->user();
        $tenant = $user->tenant;

        // Vérifier la progression actuelle
        $progress = $this->calculateProgress($tenant);

        return Inertia::render('Tenant/Onboarding/Index', [
            'progress' => $progress,
            'checklist' => $this->getChecklist($tenant),
            'tips' => $this->getTips(),
        ]);
    }

    /**
     * Met à jour la progression de l'onboarding
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:in_progress,completed,skipped',
            'step' => 'nullable|integer|min:0',
        ]);

        $user = auth()->user();
        $user->update([
            'onboarding_status' => $validated['status'],
            'onboarding_step' => $validated['step'] ?? 0,
            'onboarding_updated_at' => now(),
        ]);

        return back();
    }

    /**
     * Marque l'onboarding comme terminé
     */
    public function complete()
    {
        $user = auth()->user();
        $user->update([
            'onboarding_completed' => true,
            'onboarding_completed_at' => now(),
            'onboarding_status' => 'completed',
        ]);

        return back()->with('success', 'Félicitations ! Vous avez terminé le guide de démarrage.');
    }

    /**
     * Réinitialise l'onboarding (utile pour les démos)
     */
    public function reset()
    {
        $user = auth()->user();
        $user->update([
            'onboarding_completed' => false,
            'onboarding_completed_at' => null,
            'onboarding_status' => null,
            'onboarding_step' => 0,
        ]);

        return back()->with('success', 'Guide de démarrage réinitialisé.');
    }

    /**
     * Calcule la progression de configuration
     */
    private function calculateProgress($tenant): array
    {
        $steps = [
            'site_created' => $tenant->sites()->count() > 0,
            'boxes_created' => $tenant->sites()->withCount('boxes')->get()->sum('boxes_count') > 0,
            'customer_created' => $tenant->customers()->count() > 0,
            'contract_created' => $tenant->contracts()->count() > 0,
            'invoice_created' => $tenant->invoices()->count() > 0,
            'settings_configured' => $this->hasConfiguredSettings($tenant),
        ];

        $completed = count(array_filter($steps));
        $total = count($steps);

        return [
            'percentage' => $total > 0 ? round(($completed / $total) * 100) : 0,
            'completed' => $completed,
            'total' => $total,
            'steps' => $steps,
        ];
    }

    /**
     * Génère la checklist de démarrage
     */
    private function getChecklist($tenant): array
    {
        $sitesCount = $tenant->sites()->count();
        $boxesCount = $tenant->sites()->withCount('boxes')->get()->sum('boxes_count');
        $customersCount = $tenant->customers()->count();
        $contractsCount = $tenant->contracts()->count();
        $invoicesCount = $tenant->invoices()->count();

        return [
            [
                'id' => 'create_site',
                'title' => 'Créer votre premier site',
                'description' => 'Un site représente un emplacement physique de stockage',
                'completed' => $sitesCount > 0,
                'route' => 'tenant.sites.create',
                'icon' => 'building',
                'count' => $sitesCount,
            ],
            [
                'id' => 'add_boxes',
                'title' => 'Ajouter des boxes',
                'description' => 'Créez les unités de stockage à louer',
                'completed' => $boxesCount > 0,
                'route' => 'tenant.boxes.create',
                'icon' => 'cube',
                'count' => $boxesCount,
                'depends_on' => 'create_site',
            ],
            [
                'id' => 'configure_pricing',
                'title' => 'Configurer les tarifs',
                'description' => 'Définissez vos grilles tarifaires',
                'completed' => $tenant->pricingRules()->count() > 0,
                'route' => 'tenant.pricing.index',
                'icon' => 'currency',
            ],
            [
                'id' => 'add_customer',
                'title' => 'Ajouter un client',
                'description' => 'Créez votre premier client',
                'completed' => $customersCount > 0,
                'route' => 'tenant.customers.create',
                'icon' => 'user',
                'count' => $customersCount,
            ],
            [
                'id' => 'create_contract',
                'title' => 'Créer un contrat',
                'description' => 'Liez un client à un box avec un contrat',
                'completed' => $contractsCount > 0,
                'route' => 'tenant.contracts.create',
                'icon' => 'document',
                'count' => $contractsCount,
                'depends_on' => ['add_boxes', 'add_customer'],
            ],
            [
                'id' => 'generate_invoice',
                'title' => 'Générer une facture',
                'description' => 'Les factures sont générées automatiquement',
                'completed' => $invoicesCount > 0,
                'route' => 'tenant.invoices.index',
                'icon' => 'receipt',
                'count' => $invoicesCount,
            ],
            [
                'id' => 'configure_company',
                'title' => 'Configurer votre entreprise',
                'description' => 'Logo, coordonnées, informations légales',
                'completed' => $this->hasConfiguredSettings($tenant),
                'route' => 'tenant.settings.index',
                'icon' => 'cog',
            ],
            [
                'id' => 'explore_features',
                'title' => 'Explorer les fonctionnalités',
                'description' => 'Découvrez toutes les possibilités de BoxiBox',
                'completed' => false,
                'route' => 'tenant.dashboard',
                'icon' => 'sparkles',
                'optional' => true,
            ],
        ];
    }

    /**
     * Vérifie si les paramètres de base sont configurés
     */
    private function hasConfiguredSettings($tenant): bool
    {
        return !empty($tenant->company_name) &&
               !empty($tenant->email) &&
               !empty($tenant->address);
    }

    /**
     * Retourne des conseils utiles
     */
    private function getTips(): array
    {
        return [
            [
                'title' => 'Signature électronique',
                'content' => 'Activez la signature électronique pour que vos clients puissent signer leurs contrats en ligne, à distance.',
                'icon' => 'pencil',
            ],
            [
                'title' => 'Facturation automatique',
                'content' => 'Les factures récurrentes sont générées automatiquement selon les termes du contrat.',
                'icon' => 'clock',
            ],
            [
                'title' => 'Notifications',
                'content' => 'Configurez les notifications par email pour être alerté des impayés et échéances.',
                'icon' => 'bell',
            ],
            [
                'title' => 'Plan interactif',
                'content' => 'Créez un plan visuel de votre site pour une gestion plus intuitive.',
                'icon' => 'map',
            ],
            [
                'title' => 'Application mobile',
                'content' => 'BoxiBox est optimisé pour mobile. Gérez votre activité depuis votre smartphone.',
                'icon' => 'phone',
            ],
        ];
    }
}
