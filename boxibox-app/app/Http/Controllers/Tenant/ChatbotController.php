<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\AIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ChatbotController extends Controller
{
    protected AIService $aiService;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Send a message to the chatbot
     */
    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:2000',
            'mode' => 'nullable|string|in:assistant,marketing,analyst,financial,operations,predictive',
        ]);

        $message = $validated['message'];
        $mode = $validated['mode'] ?? 'assistant';
        $tenantId = $request->user()->tenant_id;

        // Get business context
        $context = $this->getBusinessContext($tenantId);

        // Add mode-specific instructions to the message
        $enhancedMessage = $this->enhanceMessageWithMode($message, $mode, $context);

        // Call AI service
        $response = $this->aiService->chat($enhancedMessage, $context, [
            'temperature' => 0.7,
            'max_tokens' => 2048,
        ]);

        // Generate contextual actions and suggestions
        $actions = $this->getContextualActions($message);
        $suggestions = $this->getFollowUpSuggestions($message, $mode);

        return response()->json([
            'response' => $response['message'],
            'actions' => $actions,
            'suggestions' => $suggestions,
            'provider' => $response['provider'] ?? 'unknown',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get AI provider info
     */
    public function getProviderInfo()
    {
        return response()->json($this->aiService->getProviderInfo());
    }

    /**
     * Enhance message with mode-specific instructions
     */
    private function enhanceMessageWithMode(string $message, string $mode, array $context): string
    {
        $modeInstructions = match ($mode) {
            'marketing' => "Tu es un EXPERT MARKETING DIGITAL spécialisé dans le self-storage avec 15 ans d'expérience.
Tu dois fournir des conseils CONCRETS et ACTIONNABLES sur:

📧 **EMAIL MARKETING**: Templates d'emails, objets accrocheurs, séquences d'onboarding, emails de relance
📱 **SMS MARKETING**: Messages courts et percutants, timing optimal, campagnes promotionnelles
📢 **PUBLICITÉS**: Annonces Google Ads, Facebook Ads, Instagram - avec accroches, textes et CTA optimisés
🌐 **SITE WEB**: Améliorations UX/UI, pages de landing, optimisation conversion, SEO local
🚀 **GROWTH HACKING**: Techniques innovantes, parrainage, fidélisation, upselling
📅 **CALENDRIER**: Planification des campagnes selon saisonnalité (déménagements été, rentrée, etc.)

Fournis TOUJOURS:
- Des exemples de textes prêts à l'emploi
- Des métriques à suivre (taux d'ouverture, CTR, conversion)
- Un budget estimatif si pertinent
- Le timing recommandé

",
            'analyst' => "En tant qu'analyste business, analyse cette question en te concentrant sur les KPIs, tendances et métriques. Fournis des insights data-driven. ",
            'financial' => "En tant qu'expert financier, concentre-toi sur les aspects financiers: factures, paiements, revenus, trésorerie. ",
            'operations' => "En tant que gestionnaire opérationnel, concentre-toi sur la gestion quotidienne: boxes, contrats, clients, réservations. ",
            'predictive' => "En tant que système prédictif, fournis des prédictions, tendances futures et recommandations stratégiques basées sur les données. ",
            default => "",
        };

        // Add current date
        $dateInfo = "Nous sommes le " . Carbon::now()->locale('fr')->isoFormat('dddd D MMMM YYYY') . ". ";

        return $dateInfo . $modeInstructions . $message;
    }

    /**
     * Get business context for AI
     */
    private function getBusinessContext(int $tenantId): array
    {
        try {
            // Box statistics
            $totalBoxes = \App\Models\Box::where('tenant_id', $tenantId)->count();
            $occupiedBoxes = \App\Models\Box::where('tenant_id', $tenantId)->where('status', 'occupied')->count();
            $availableBoxes = \App\Models\Box::where('tenant_id', $tenantId)->where('status', 'available')->count();
            $reservedBoxes = \App\Models\Box::where('tenant_id', $tenantId)->where('status', 'reserved')->count();

            // Financial data
            $unpaidInvoices = \App\Models\Invoice::where('tenant_id', $tenantId)
                ->whereIn('status', ['pending', 'overdue'])
                ->count();

            $unpaidAmount = \App\Models\Invoice::where('tenant_id', $tenantId)
                ->whereIn('status', ['pending', 'overdue'])
                ->sum('total_amount');

            // Revenue
            $revenue = \App\Models\Payment::where('tenant_id', $tenantId)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount');

            $lastMonthRevenue = \App\Models\Payment::where('tenant_id', $tenantId)
                ->whereMonth('created_at', now()->subMonth()->month)
                ->whereYear('created_at', now()->subMonth()->year)
                ->sum('amount');

            // Customers and contracts
            $activeCustomers = \App\Models\Customer::where('tenant_id', $tenantId)
                ->whereHas('contracts', fn($q) => $q->where('status', 'active'))
                ->count();

            $newContractsThisMonth = \App\Models\Contract::where('tenant_id', $tenantId)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();

            $expiringContracts = \App\Models\Contract::where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->whereBetween('end_date', [now(), now()->addDays(30)])
                ->count();

            // Sites
            $sites = \App\Models\Site::where('tenant_id', $tenantId)
                ->withCount(['boxes', 'boxes as occupied_count' => fn($q) => $q->where('status', 'occupied')])
                ->get()
                ->map(fn($s) => [
                    'name' => $s->name,
                    'total' => $s->boxes_count,
                    'occupied' => $s->occupied_count,
                    'rate' => $s->boxes_count > 0 ? round(($s->occupied_count / $s->boxes_count) * 100) : 0,
                ])
                ->toArray();

            // Calculate occupation rate
            $occupationRate = $totalBoxes > 0 ? round(($occupiedBoxes / $totalBoxes) * 100, 1) : 0;
            $revenueTrend = $lastMonthRevenue > 0 ? round(($revenue - $lastMonthRevenue) / $lastMonthRevenue * 100, 1) : 0;

            return [
                'total_boxes' => $totalBoxes,
                'occupied_boxes' => $occupiedBoxes,
                'available_boxes' => $availableBoxes,
                'reserved_boxes' => $reservedBoxes,
                'occupation_rate' => $occupationRate,
                'unpaid_invoices' => $unpaidInvoices,
                'unpaid_amount' => $unpaidAmount,
                'revenue' => $revenue,
                'revenue_trend' => $revenueTrend,
                'active_customers' => $activeCustomers,
                'new_contracts' => $newContractsThisMonth,
                'expiring_contracts' => $expiringContracts,
                'sites' => $sites,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get business context', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get contextual action buttons
     */
    private function getContextualActions(string $message): array
    {
        $lower = strtolower($message);

        // Marketing actions
        if (str_contains($lower, 'email') || str_contains($lower, 'mail') || str_contains($lower, 'newsletter')) {
            return [
                ['label' => 'Créer campagne email', 'action' => 'navigate', 'route' => 'tenant.marketing.email', 'primary' => true],
                ['label' => 'Templates email', 'action' => 'navigate', 'route' => 'tenant.email-templates.index'],
            ];
        }

        if (str_contains($lower, 'sms') || str_contains($lower, 'texto') || str_contains($lower, 'message')) {
            return [
                ['label' => 'Campagne SMS', 'action' => 'navigate', 'route' => 'tenant.sms-campaigns.index', 'primary' => true],
                ['label' => 'Envoyer SMS', 'action' => 'sms'],
            ];
        }

        if (str_contains($lower, 'pub') || str_contains($lower, 'ads') || str_contains($lower, 'annonce') || str_contains($lower, 'marketing')) {
            return [
                ['label' => '📋 Copier les textes', 'action' => 'copy', 'primary' => true],
                ['label' => 'Dashboard', 'action' => 'navigate', 'route' => 'tenant.dashboard'],
            ];
        }

        if (str_contains($lower, 'facture') || str_contains($lower, 'impayé') || str_contains($lower, 'paiement')) {
            return [
                ['label' => 'Voir les factures', 'action' => 'navigate', 'route' => 'tenant.invoices.index', 'primary' => true],
                ['label' => 'Lancer relances', 'action' => 'relance'],
            ];
        }

        if (str_contains($lower, 'contrat') || str_contains($lower, 'expir')) {
            return [
                ['label' => 'Voir contrats', 'action' => 'navigate', 'route' => 'tenant.contracts.index', 'primary' => true],
                ['label' => 'Envoyer rappels', 'action' => 'reminder'],
            ];
        }

        if (str_contains($lower, 'box') || str_contains($lower, 'occupation') || str_contains($lower, 'disponible')) {
            return [
                ['label' => 'Voir les boxes', 'action' => 'navigate', 'route' => 'tenant.boxes.index', 'primary' => true],
            ];
        }

        if (str_contains($lower, 'client') || str_contains($lower, 'customer')) {
            return [
                ['label' => 'Voir clients', 'action' => 'navigate', 'route' => 'tenant.customers.index', 'primary' => true],
            ];
        }

        if (str_contains($lower, 'stat') || str_contains($lower, 'kpi') || str_contains($lower, 'performance') || str_contains($lower, 'dashboard')) {
            return [
                ['label' => 'Dashboard', 'action' => 'navigate', 'route' => 'tenant.dashboard', 'primary' => true],
            ];
        }

        return [];
    }

    /**
     * Get follow-up suggestions based on conversation
     */
    private function getFollowUpSuggestions(string $message, string $mode): array
    {
        $lower = strtolower($message);

        // Mode-specific suggestions
        $modeSuggestions = match ($mode) {
            'marketing' => ['Créer campagne email', 'Idées pubs Facebook', 'SMS promotionnel', 'Améliorer le site'],
            'analyst' => ['Évolution sur 6 mois', 'Comparer les sites', 'Tendances du marché'],
            'financial' => ['Prévision trésorerie', 'Analyse des impayés', 'Rentabilité par box'],
            'operations' => ['Planning du jour', 'Maintenances en attente', 'Nouvelles réservations'],
            'predictive' => ['Prédiction du churn', 'Prévision occupation', 'Optimisation tarifs'],
            default => [],
        };

        // Marketing-specific suggestions
        if (str_contains($lower, 'email') || str_contains($lower, 'mail') || str_contains($lower, 'newsletter')) {
            return ['Objet email accrocheur', 'Séquence bienvenue', 'Email de relance', 'A/B test sujets'];
        }

        if (str_contains($lower, 'pub') || str_contains($lower, 'ads') || str_contains($lower, 'annonce')) {
            return ['Google Ads texte', 'Facebook carousel', 'Instagram stories', 'Retargeting'];
        }

        if (str_contains($lower, 'sms') || str_contains($lower, 'texto')) {
            return ['SMS promotion', 'Rappel réservation', 'SMS fidélisation', 'Timing optimal'];
        }

        if (str_contains($lower, 'site') || str_contains($lower, 'landing') || str_contains($lower, 'page')) {
            return ['Page de landing', 'Call-to-action', 'Formulaire optimal', 'SEO local'];
        }

        // Context-specific suggestions
        if (str_contains($lower, 'facture') || str_contains($lower, 'impayé')) {
            return array_merge(['Détail par client', 'Historique relances'], $modeSuggestions);
        }

        if (str_contains($lower, 'occupation') || str_contains($lower, 'box')) {
            return array_merge(['Par taille de box', 'Évolution mensuelle'], $modeSuggestions);
        }

        if (str_contains($lower, 'revenu') || str_contains($lower, 'chiffre')) {
            return array_merge(['Par site', 'Comparaison N-1'], $modeSuggestions);
        }

        // Default suggestions based on mode
        if ($mode === 'marketing') {
            return ['Stratégie complète', 'Calendrier campagnes', 'Budget marketing', 'Concurrence'];
        }

        return array_merge([
            'Briefing du jour',
            'État des impayés',
            'Clients à risque',
        ], array_slice($modeSuggestions, 0, 2));
    }

    /**
     * Get chat history (for future implementation)
     */
    public function history(Request $request)
    {
        return response()->json([
            'messages' => [],
        ]);
    }
}
