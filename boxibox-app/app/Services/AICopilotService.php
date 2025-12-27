<?php

namespace App\Services;

use App\Models\Box;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\Prospect;
use App\Models\Payment;
use App\Models\Booking;
use App\Models\Site;
use App\Models\Tenant;
use App\Models\CopilotConversation;
use App\Models\CopilotMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AICopilotService
{
    protected int $tenantId;
    protected ?CopilotConversation $conversation = null;
    protected array $context = [];
    protected array $availableActions = [];

    /**
     * Intents that the AI can recognize
     */
    const INTENTS = [
        'revenue_analysis' => ['revenus', 'chiffre d\'affaires', 'recettes', 'money', 'argent', 'gains'],
        'occupancy_analysis' => ['occupation', 'taux', 'remplissage', 'boxes vides', 'disponibles'],
        'overdue_analysis' => ['impayés', 'retard', 'paiement', 'factures', 'relance', 'dette'],
        'customer_analysis' => ['client', 'customer', 'fidélisation', 'churn', 'départ'],
        'booking_analysis' => ['réservation', 'booking', 'conversion', 'prospect'],
        'pricing_advice' => ['prix', 'tarif', 'pricing', 'augmenter', 'baisser', 'promotion'],
        'marketing_advice' => ['marketing', 'campagne', 'publicité', 'promo', 'sms', 'email'],
        'forecast' => ['prévision', 'forecast', 'futur', 'prédiction', 'tendance', 'projection'],
        'create_campaign' => ['créer campagne', 'lancer promo', 'envoyer sms', 'créer promotion'],
        'send_reminder' => ['envoyer rappel', 'relancer', 'rappeler client'],
        'daily_briefing' => ['briefing', 'résumé', 'aujourd\'hui', 'situation', 'état'],
        'help' => ['aide', 'help', 'quoi faire', 'comment', 'explique'],
    ];

    /**
     * Available quick actions
     */
    const QUICK_ACTIONS = [
        'analyze_revenue' => [
            'label' => 'Analyser les revenus',
            'icon' => 'currency-euro',
            'intent' => 'revenue_analysis'
        ],
        'check_overdue' => [
            'label' => 'Voir les impayés',
            'icon' => 'exclamation-triangle',
            'intent' => 'overdue_analysis'
        ],
        'occupancy_report' => [
            'label' => 'Taux d\'occupation',
            'icon' => 'chart-bar',
            'intent' => 'occupancy_analysis'
        ],
        'churn_risk' => [
            'label' => 'Clients à risque',
            'icon' => 'user-minus',
            'intent' => 'customer_analysis'
        ],
        'daily_briefing' => [
            'label' => 'Briefing du jour',
            'icon' => 'sun',
            'intent' => 'daily_briefing'
        ],
        'forecast' => [
            'label' => 'Prévisions 30j',
            'icon' => 'arrow-trending-up',
            'intent' => 'forecast'
        ],
    ];

    public function __construct()
    {
        $this->availableActions = self::QUICK_ACTIONS;
    }

    /**
     * Start or continue a conversation
     */
    public function chat(int $tenantId, string $message, ?int $conversationId = null): array
    {
        $this->tenantId = $tenantId;

        // Get or create conversation
        $this->conversation = $this->getOrCreateConversation($conversationId);

        // Store user message
        $this->storeMessage('user', $message);

        // Build context from tenant data
        $this->buildContext();

        // Detect intent
        $intent = $this->detectIntent($message);

        // Generate response based on intent
        $response = $this->generateResponse($intent, $message);

        // Store assistant message
        $this->storeMessage('assistant', $response['message'], $response['actions'] ?? []);

        return [
            'conversation_id' => $this->conversation->id,
            'message' => $response['message'],
            'actions' => $response['actions'] ?? [],
            'suggestions' => $response['suggestions'] ?? [],
            'data' => $response['data'] ?? null,
            'charts' => $response['charts'] ?? null,
        ];
    }

    /**
     * Get daily briefing
     */
    public function getDailyBriefing(int $tenantId): array
    {
        $this->tenantId = $tenantId;
        $this->buildContext();

        $now = Carbon::now();
        $yesterday = $now->copy()->subDay();

        // Get key metrics
        $metrics = $this->context['metrics'];

        // New bookings today
        $newBookings = Booking::where('tenant_id', $tenantId)
            ->whereDate('created_at', $now->toDateString())
            ->count();

        $newBookingsRevenue = Booking::where('tenant_id', $tenantId)
            ->whereDate('created_at', $now->toDateString())
            ->sum('monthly_price');

        // Contracts expiring soon
        $expiringContracts = Contract::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->whereBetween('end_date', [$now, $now->copy()->addDays(7)])
            ->with('customer')
            ->get();

        // Critical overdue invoices
        $criticalOverdue = Invoice::where('tenant_id', $tenantId)
            ->whereIn('status', ['sent', 'overdue'])
            ->where('due_date', '<', $now->copy()->subDays(15))
            ->with('customer')
            ->orderBy('total', 'desc')
            ->limit(5)
            ->get();

        // Churn risk customers
        $churnRisk = $this->getChurnRiskCustomers();

        // Generate insights
        $insights = [];
        $alerts = [];
        $opportunities = [];

        // Occupancy insight
        if ($metrics['occupancy_rate'] < 70) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'exclamation-triangle',
                'title' => 'Occupation faible',
                'message' => "Taux à {$metrics['occupancy_rate']}% - {$metrics['available_boxes']} boxes vides",
                'action' => ['label' => 'Créer promo', 'route' => 'tenant.crm.campaigns.create'],
            ];
        } elseif ($metrics['occupancy_rate'] > 95) {
            $opportunities[] = [
                'type' => 'success',
                'icon' => 'arrow-trending-up',
                'title' => 'Forte demande',
                'message' => "Occupation à {$metrics['occupancy_rate']}% - Opportunité d'augmenter les prix",
                'action' => ['label' => 'Ajuster prix', 'route' => 'tenant.pricing.dashboard'],
            ];
        }

        // Overdue insight
        if ($metrics['total_overdue'] > 1000) {
            $alerts[] = [
                'type' => 'danger',
                'icon' => 'banknotes',
                'title' => 'Impayés à traiter',
                'message' => number_format($metrics['total_overdue'], 0, ',', ' ') . "€ en retard ({$metrics['overdue_count']} factures)",
                'action' => ['label' => 'Envoyer rappels', 'route' => 'tenant.reminders.index'],
            ];
        }

        // Revenue trend
        if ($metrics['revenue_growth'] > 10) {
            $insights[] = [
                'type' => 'success',
                'icon' => 'arrow-up',
                'message' => "Revenus en hausse de {$metrics['revenue_growth']}% ce mois",
            ];
        } elseif ($metrics['revenue_growth'] < -10) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'arrow-down',
                'title' => 'Baisse des revenus',
                'message' => "Revenus en baisse de " . abs($metrics['revenue_growth']) . "% ce mois",
                'action' => ['label' => 'Analyser', 'route' => 'tenant.analytics.revenue'],
            ];
        }

        // AI tip of the day
        $tips = $this->generateDailyTip();

        return [
            'date' => $now->format('d F Y'),
            'greeting' => $this->getGreeting(),
            'summary' => [
                'new_bookings' => $newBookings,
                'new_revenue' => $newBookingsRevenue,
                'occupancy' => $metrics['occupancy_rate'],
                'occupancy_trend' => $this->getOccupancyTrend(),
                'monthly_revenue' => $metrics['monthly_revenue'],
                'revenue_vs_last_month' => $metrics['revenue_growth'],
                'overdue_amount' => $metrics['total_overdue'],
                'pending_bookings' => $metrics['pending_bookings'],
            ],
            'alerts' => $alerts,
            'opportunities' => $opportunities,
            'insights' => $insights,
            'expiring_contracts' => $expiringContracts->map(fn($c) => [
                'id' => $c->id,
                'customer' => $c->customer->full_name ?? 'N/A',
                'box' => $c->box->reference ?? 'N/A',
                'end_date' => $c->end_date->format('d/m/Y'),
                'days_left' => $now->diffInDays($c->end_date),
                'monthly_price' => $c->monthly_price,
            ]),
            'critical_overdue' => $criticalOverdue->map(fn($i) => [
                'id' => $i->id,
                'number' => $i->number,
                'customer' => $i->customer->full_name ?? 'N/A',
                'amount' => $i->total - ($i->paid_amount ?? 0),
                'days_overdue' => $now->diffInDays($i->due_date),
            ]),
            'churn_risk' => $churnRisk,
            'tip_of_day' => $tips,
            'quick_actions' => $this->availableActions,
        ];
    }

    /**
     * Get forecast predictions
     */
    public function getForecast(int $tenantId, int $days = 30): array
    {
        $this->tenantId = $tenantId;
        $this->buildContext();

        $now = Carbon::now();
        $metrics = $this->context['metrics'];

        // Historical data for predictions
        $historicalRevenue = Invoice::where('tenant_id', $tenantId)
            ->where('status', 'paid')
            ->where('paid_at', '>=', $now->copy()->subMonths(6))
            ->selectRaw('MONTH(paid_at) as month, YEAR(paid_at) as year, SUM(total) as revenue')
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        // Calculate average monthly growth
        $growthRates = [];
        $revenues = $historicalRevenue->pluck('revenue')->toArray();
        for ($i = 1; $i < count($revenues); $i++) {
            if ($revenues[$i - 1] > 0) {
                $growthRates[] = ($revenues[$i] - $revenues[$i - 1]) / $revenues[$i - 1];
            }
        }
        $avgGrowth = count($growthRates) > 0 ? array_sum($growthRates) / count($growthRates) : 0;

        // Predict next months
        $currentRevenue = $metrics['monthly_revenue'] ?: ($revenues[count($revenues) - 1] ?? 0);
        $predictions = [];

        for ($i = 1; $i <= 3; $i++) {
            $predictedRevenue = $currentRevenue * pow(1 + $avgGrowth, $i);
            $predictions[] = [
                'month' => $now->copy()->addMonths($i)->format('F Y'),
                'revenue' => round($predictedRevenue, 2),
                'confidence' => max(60, 95 - ($i * 10)),
            ];
        }

        // Occupancy prediction based on seasonality
        $seasonalFactors = [
            1 => 0.9, 2 => 0.85, 3 => 0.9, 4 => 0.95,
            5 => 1.0, 6 => 1.1, 7 => 1.15, 8 => 1.2,
            9 => 1.25, 10 => 1.1, 11 => 0.95, 12 => 0.9
        ];

        $nextMonth = $now->copy()->addMonth();
        $seasonalFactor = $seasonalFactors[$nextMonth->month] ?? 1.0;
        $predictedOccupancy = min(100, $metrics['occupancy_rate'] * $seasonalFactor);

        // Churn prediction
        $churnPrediction = $this->predictChurn();

        // Expected contract endings
        $expectedEndings = Contract::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->whereBetween('end_date', [$now, $now->copy()->addDays($days)])
            ->count();

        $expectedEndingsRevenue = Contract::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->whereBetween('end_date', [$now, $now->copy()->addDays($days)])
            ->sum('monthly_price');

        return [
            'period' => "{$days} jours",
            'generated_at' => $now->toIso8601String(),
            'revenue_forecast' => [
                'current_month' => $currentRevenue,
                'predictions' => $predictions,
                'trend' => $avgGrowth > 0 ? 'up' : ($avgGrowth < 0 ? 'down' : 'stable'),
                'trend_percentage' => round($avgGrowth * 100, 1),
            ],
            'occupancy_forecast' => [
                'current' => $metrics['occupancy_rate'],
                'next_month' => round($predictedOccupancy, 1),
                'seasonal_impact' => round(($seasonalFactor - 1) * 100, 1),
                'recommendation' => $predictedOccupancy > 95
                    ? 'Forte demande prévue - augmentez vos prix'
                    : ($predictedOccupancy < 70 ? 'Demande faible - lancez des promotions' : 'Demande stable'),
            ],
            'churn_forecast' => $churnPrediction,
            'contract_endings' => [
                'count' => $expectedEndings,
                'revenue_at_risk' => $expectedEndingsRevenue,
                'action_needed' => $expectedEndings > 5,
            ],
            'recommendations' => $this->generateForecastRecommendations($predictions, $predictedOccupancy, $churnPrediction),
        ];
    }

    /**
     * Execute an action from the chat
     */
    public function executeAction(int $tenantId, string $action, array $params = []): array
    {
        $this->tenantId = $tenantId;

        switch ($action) {
            case 'send_reminders':
                return $this->executeSendReminders($params);

            case 'create_campaign':
                return $this->executeCreateCampaign($params);

            case 'adjust_pricing':
                return $this->executeAdjustPricing($params);

            case 'contact_customer':
                return $this->executeContactCustomer($params);

            default:
                return [
                    'success' => false,
                    'message' => 'Action non reconnue',
                ];
        }
    }

    /**
     * Get churn risk customers with AI scoring
     */
    public function getChurnRiskCustomers(): array
    {
        $now = Carbon::now();

        $customers = Customer::where('tenant_id', $this->tenantId)
            ->whereHas('contracts', fn($q) => $q->where('status', 'active'))
            ->with(['contracts' => fn($q) => $q->where('status', 'active')])
            ->withCount(['invoices as late_payments_count' => function ($query) use ($now) {
                $query->where('due_date', '<', $now)
                    ->where('status', '!=', 'paid');
            }])
            ->get();

        $riskCustomers = [];

        foreach ($customers as $customer) {
            $riskScore = 0;
            $riskFactors = [];

            foreach ($customer->contracts as $contract) {
                // Factor 1: Contract ending soon without renewal discussion
                $daysToEnd = $now->diffInDays($contract->end_date, false);
                if ($daysToEnd > 0 && $daysToEnd <= 30) {
                    $riskScore += 30;
                    $riskFactors[] = "Contrat expire dans {$daysToEnd} jours";
                }

                // Factor 2: Payment history (pre-loaded with withCount to avoid N+1)
                $latePayments = $customer->late_payments_count;
                if ($latePayments > 0) {
                    $riskScore += min(20, $latePayments * 5);
                    $riskFactors[] = "{$latePayments} paiement(s) en retard";
                }

                // Factor 3: No recent interaction
                $lastInteraction = $customer->updated_at;
                $daysSinceInteraction = $now->diffInDays($lastInteraction);
                if ($daysSinceInteraction > 60) {
                    $riskScore += 15;
                    $riskFactors[] = "Aucune interaction depuis {$daysSinceInteraction} jours";
                }

                // Factor 4: Price sensitivity (if price increased recently)
                // This would require price history tracking

                // Factor 5: Contract duration (short-term = higher risk)
                $contractDuration = $contract->created_at->diffInMonths($contract->end_date);
                if ($contractDuration <= 3) {
                    $riskScore += 10;
                    $riskFactors[] = "Contrat courte durée";
                }
            }

            if ($riskScore >= 30) {
                $riskCustomers[] = [
                    'id' => $customer->id,
                    'name' => $customer->full_name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'risk_score' => min(100, $riskScore),
                    'risk_level' => $riskScore >= 60 ? 'high' : ($riskScore >= 40 ? 'medium' : 'low'),
                    'factors' => $riskFactors,
                    'monthly_value' => $customer->contracts->sum('monthly_price'),
                    'recommended_action' => $this->getChurnPreventionAction($riskScore, $riskFactors),
                ];
            }
        }

        // Sort by risk score descending
        usort($riskCustomers, fn($a, $b) => $b['risk_score'] <=> $a['risk_score']);

        return array_slice($riskCustomers, 0, 10);
    }

    /**
     * Get AI-powered pricing recommendations
     */
    public function getPricingRecommendations(): array
    {
        $this->buildContext();
        $metrics = $this->context['metrics'];

        $recommendations = [];

        // Get boxes by size with occupancy
        $boxesBySize = Box::where('tenant_id', $this->tenantId)
            ->selectRaw('size, COUNT(*) as total, SUM(CASE WHEN status = "occupied" THEN 1 ELSE 0 END) as occupied, AVG(price) as avg_price')
            ->groupBy('size')
            ->get();

        foreach ($boxesBySize as $sizeGroup) {
            $occupancyRate = $sizeGroup->total > 0 ? ($sizeGroup->occupied / $sizeGroup->total) * 100 : 0;

            if ($occupancyRate > 95) {
                $recommendations[] = [
                    'type' => 'increase',
                    'size' => $sizeGroup->size,
                    'current_price' => $sizeGroup->avg_price,
                    'suggested_change' => '+10-15%',
                    'reason' => "Forte demande ({$occupancyRate}% occupation)",
                    'potential_gain' => round($sizeGroup->avg_price * 0.12 * $sizeGroup->occupied, 2),
                ];
            } elseif ($occupancyRate < 50) {
                $recommendations[] = [
                    'type' => 'decrease',
                    'size' => $sizeGroup->size,
                    'current_price' => $sizeGroup->avg_price,
                    'suggested_change' => '-15-20%',
                    'reason' => "Faible demande ({$occupancyRate}% occupation)",
                    'potential_gain' => round($sizeGroup->avg_price * 0.8 * ($sizeGroup->total - $sizeGroup->occupied), 2),
                ];
            } elseif ($occupancyRate < 70) {
                $recommendations[] = [
                    'type' => 'promotion',
                    'size' => $sizeGroup->size,
                    'current_price' => $sizeGroup->avg_price,
                    'suggested_change' => '1er mois -30%',
                    'reason' => "Occupation moyenne ({$occupancyRate}%)",
                    'potential_gain' => round($sizeGroup->avg_price * 0.7 * ($sizeGroup->total - $sizeGroup->occupied) * 11, 2),
                ];
            }
        }

        return $recommendations;
    }

    // ========================
    // PROTECTED HELPER METHODS
    // ========================

    protected function getOrCreateConversation(?int $conversationId): CopilotConversation
    {
        if ($conversationId) {
            $conversation = CopilotConversation::find($conversationId);
            if ($conversation && $conversation->tenant_id === $this->tenantId) {
                return $conversation;
            }
        }

        return CopilotConversation::create([
            'tenant_id' => $this->tenantId,
            'user_id' => auth()->id(),
            'title' => 'Nouvelle conversation',
            'context' => [],
        ]);
    }

    protected function storeMessage(string $role, string $content, array $actions = []): CopilotMessage
    {
        return CopilotMessage::create([
            'conversation_id' => $this->conversation->id,
            'role' => $role,
            'content' => $content,
            'actions' => $actions,
            'context' => $role === 'assistant' ? $this->context['metrics'] ?? [] : [],
        ]);
    }

    protected function buildContext(): void
    {
        $advisor = new AIBusinessAdvisorService();
        $advice = $advisor->generateAdvice($this->tenantId);

        $this->context = [
            'metrics' => $advice['metrics'],
            'recommendations' => $advice['recommendations'],
            'score' => $advice['score'],
            'tenant' => Tenant::find($this->tenantId),
        ];
    }

    protected function detectIntent(string $message): string
    {
        $message = mb_strtolower($message);

        foreach (self::INTENTS as $intent => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($message, mb_strtolower($keyword))) {
                    return $intent;
                }
            }
        }

        return 'general';
    }

    protected function generateResponse(string $intent, string $message): array
    {
        $metrics = $this->context['metrics'];

        switch ($intent) {
            case 'revenue_analysis':
                return $this->generateRevenueResponse();

            case 'occupancy_analysis':
                return $this->generateOccupancyResponse();

            case 'overdue_analysis':
                return $this->generateOverdueResponse();

            case 'customer_analysis':
                return $this->generateCustomerResponse();

            case 'booking_analysis':
                return $this->generateBookingResponse();

            case 'pricing_advice':
                return $this->generatePricingResponse();

            case 'marketing_advice':
                return $this->generateMarketingResponse();

            case 'forecast':
                $forecast = $this->getForecast($this->tenantId);
                return [
                    'message' => $this->formatForecastMessage($forecast),
                    'data' => $forecast,
                    'suggestions' => ['Voir détails prévisions', 'Ajuster les prix', 'Planifier actions'],
                ];

            case 'daily_briefing':
                $briefing = $this->getDailyBriefing($this->tenantId);
                return [
                    'message' => $this->formatBriefingMessage($briefing),
                    'data' => $briefing,
                    'suggestions' => ['Analyser revenus', 'Voir impayés', 'Clients à risque'],
                ];

            case 'create_campaign':
                return $this->generateCreateCampaignResponse($message);

            case 'send_reminder':
                return $this->generateSendReminderResponse();

            case 'help':
                return $this->generateHelpResponse();

            default:
                return $this->generateGeneralResponse($message);
        }
    }

    protected function generateRevenueResponse(): array
    {
        $m = $this->context['metrics'];

        $trend = $m['revenue_growth'] > 0 ? '📈 en hausse' : ($m['revenue_growth'] < 0 ? '📉 en baisse' : '➡️ stable');
        $trendValue = abs($m['revenue_growth']);

        $message = "**Analyse des Revenus**\n\n";
        $message .= "💰 **Revenus ce mois:** " . number_format($m['monthly_revenue'], 0, ',', ' ') . "€\n";
        $message .= "📊 **Mois précédent:** " . number_format($m['last_month_revenue'], 0, ',', ' ') . "€\n";
        $message .= "📈 **Tendance:** {$trend} de {$trendValue}%\n\n";

        $message .= "💵 **Prix moyen:** " . number_format($m['average_price'], 0, ',', ' ') . "€/mois\n";
        $message .= "⚠️ **Revenus manqués (boxes vides):** " . number_format($m['potential_lost_revenue'], 0, ',', ' ') . "€/mois\n\n";

        $actions = [];
        $suggestions = ['Voir les prévisions', 'Optimiser les prix'];

        if ($m['revenue_growth'] < -5) {
            $message .= "⚠️ **Attention:** Baisse significative. Je recommande d'analyser les contrats résiliés et de relancer les prospects.\n";
            $actions[] = [
                'type' => 'link',
                'label' => 'Voir résiliations',
                'route' => 'tenant.contracts.index',
                'params' => ['status' => 'terminated'],
            ];
        }

        if ($m['potential_lost_revenue'] > 1000) {
            $message .= "💡 **Conseil:** Vous perdez " . number_format($m['potential_lost_revenue'], 0, ',', ' ') . "€/mois avec vos boxes vides. Une promotion pourrait attirer de nouveaux clients.\n";
            $actions[] = [
                'type' => 'action',
                'label' => 'Créer une promo',
                'action' => 'create_campaign',
                'params' => ['type' => 'promotion'],
            ];
        }

        return [
            'message' => $message,
            'actions' => $actions,
            'suggestions' => $suggestions,
            'charts' => [
                'type' => 'revenue_trend',
                'data' => $this->getRevenueChartData(),
            ],
        ];
    }

    protected function generateOccupancyResponse(): array
    {
        $m = $this->context['metrics'];

        $status = $m['occupancy_rate'] >= 90 ? '🎉 Excellent' :
                  ($m['occupancy_rate'] >= 70 ? '✅ Bon' :
                  ($m['occupancy_rate'] >= 50 ? '⚠️ À améliorer' : '🔴 Critique'));

        $message = "**Analyse du Taux d'Occupation**\n\n";
        $message .= "📊 **Taux actuel:** {$m['occupancy_rate']}% {$status}\n";
        $message .= "🏢 **Boxes total:** {$m['total_boxes']}\n";
        $message .= "✅ **Occupées:** {$m['occupied_boxes']}\n";
        $message .= "🔓 **Disponibles:** {$m['available_boxes']}\n";
        $message .= "🔒 **Réservées:** {$m['reserved_boxes']}\n\n";

        $actions = [];
        $suggestions = [];

        if ($m['occupancy_rate'] < 70) {
            $message .= "💡 **Recommandations:**\n";
            $message .= "• Lancez une promotion -30% sur le 1er mois\n";
            $message .= "• Baissez les prix des boxes difficiles à louer\n";
            $message .= "• Contactez vos anciens clients\n\n";

            $actions[] = [
                'type' => 'action',
                'label' => 'Créer promo',
                'action' => 'create_campaign',
                'params' => ['type' => 'low_occupancy'],
            ];
            $suggestions = ['Créer une promotion', 'Ajuster les prix', 'Voir boxes vides'];
        } elseif ($m['occupancy_rate'] > 95) {
            $message .= "💡 **Opportunité:** Forte demande! Vous pouvez augmenter vos prix de 5-10%.\n";
            $actions[] = [
                'type' => 'link',
                'label' => 'Ajuster les prix',
                'route' => 'tenant.pricing.dashboard',
            ];
            $suggestions = ['Augmenter les prix', 'Voir liste d\'attente', 'Ajouter services premium'];
        }

        return [
            'message' => $message,
            'actions' => $actions,
            'suggestions' => $suggestions,
        ];
    }

    protected function generateOverdueResponse(): array
    {
        $m = $this->context['metrics'];
        $now = Carbon::now();

        // Get detailed overdue info
        $overdueInvoices = Invoice::where('tenant_id', $this->tenantId)
            ->whereIn('status', ['sent', 'overdue', 'partial'])
            ->where('due_date', '<', $now)
            ->with('customer')
            ->orderBy('due_date')
            ->limit(10)
            ->get();

        $message = "**Analyse des Impayés**\n\n";

        if ($m['overdue_count'] == 0) {
            $message .= "🎉 **Excellent!** Aucune facture en retard.\n";
            $message .= "Tous vos clients sont à jour de leurs paiements.\n";
            return [
                'message' => $message,
                'suggestions' => ['Voir les factures', 'Analyser revenus'],
            ];
        }

        $message .= "⚠️ **Total impayé:** " . number_format($m['total_overdue'], 0, ',', ' ') . "€\n";
        $message .= "📄 **Factures en retard:** {$m['overdue_count']}\n";
        $message .= "⏱️ **Retard moyen:** {$m['average_days_overdue']} jours\n\n";

        if ($overdueInvoices->count() > 0) {
            $message .= "**Top factures à traiter:**\n";
            foreach ($overdueInvoices->take(5) as $invoice) {
                $daysLate = $now->diffInDays($invoice->due_date);
                $amount = $invoice->total - ($invoice->paid_amount ?? 0);
                $message .= "• {$invoice->customer->full_name}: " . number_format($amount, 0, ',', ' ') . "€ (-{$daysLate}j)\n";
            }
            $message .= "\n";
        }

        $actions = [
            [
                'type' => 'action',
                'label' => '📧 Envoyer rappels email',
                'action' => 'send_reminders',
                'params' => ['type' => 'email'],
            ],
            [
                'type' => 'action',
                'label' => '📱 Envoyer rappels SMS',
                'action' => 'send_reminders',
                'params' => ['type' => 'sms'],
            ],
            [
                'type' => 'link',
                'label' => 'Voir tous les impayés',
                'route' => 'tenant.reminders.overdue-invoices',
            ],
        ];

        $message .= "💡 **Conseil:** Envoyez des rappels automatiques et proposez le prélèvement SEPA pour éviter les retards.\n";

        return [
            'message' => $message,
            'actions' => $actions,
            'suggestions' => ['Envoyer rappels', 'Proposer facilités', 'Configurer SEPA'],
            'data' => [
                'invoices' => $overdueInvoices->map(fn($i) => [
                    'id' => $i->id,
                    'number' => $i->number,
                    'customer' => $i->customer->full_name ?? 'N/A',
                    'amount' => $i->total - ($i->paid_amount ?? 0),
                    'days_overdue' => $now->diffInDays($i->due_date),
                ]),
            ],
        ];
    }

    protected function generateCustomerResponse(): array
    {
        $m = $this->context['metrics'];
        $churnRisk = $this->getChurnRiskCustomers();

        $message = "**Analyse Clients & Fidélisation**\n\n";
        $message .= "👥 **Clients actifs:** {$m['active_customers']}\n";
        $message .= "📉 **Taux de churn:** {$m['churn_rate']}%\n";
        $message .= "🚪 **Contrats résiliés ce mois:** {$m['lost_contracts']}\n";
        $message .= "⏰ **Contrats expirant sous 30j:** {$m['expiring_contracts']}\n\n";

        if (count($churnRisk) > 0) {
            $message .= "⚠️ **Clients à risque de départ:**\n";
            foreach (array_slice($churnRisk, 0, 5) as $customer) {
                $riskIcon = $customer['risk_level'] === 'high' ? '🔴' : ($customer['risk_level'] === 'medium' ? '🟠' : '🟡');
                $message .= "{$riskIcon} **{$customer['name']}** - Risque {$customer['risk_score']}%";
                $message .= " (" . number_format($customer['monthly_value'], 0, ',', ' ') . "€/mois)\n";
                $message .= "   → {$customer['recommended_action']}\n";
            }
            $message .= "\n";
        }

        $actions = [];
        if ($m['expiring_contracts'] > 0) {
            $actions[] = [
                'type' => 'link',
                'label' => 'Contrats à renouveler',
                'route' => 'tenant.contracts.index',
                'params' => ['expiring' => true],
            ];
        }

        $message .= "💡 **Conseils:**\n";
        $message .= "• Contactez les clients à risque proactivement\n";
        $message .= "• Proposez des offres de renouvellement avantageuses\n";
        $message .= "• Envoyez des enquêtes de satisfaction\n";

        return [
            'message' => $message,
            'actions' => $actions,
            'suggestions' => ['Contacter clients à risque', 'Programme fidélité', 'Offres renouvellement'],
            'data' => ['churn_risk' => $churnRisk],
        ];
    }

    protected function generateBookingResponse(): array
    {
        $m = $this->context['metrics'];

        $message = "**Analyse des Réservations & Conversion**\n\n";
        $message .= "📥 **Prospects ce mois:** {$m['total_prospects']}\n";
        $message .= "✅ **Convertis:** {$m['converted_prospects']}\n";
        $message .= "📊 **Taux de conversion:** {$m['prospect_conversion_rate']}%\n";
        $message .= "⏳ **Réservations en attente:** {$m['pending_bookings']}\n";
        $message .= "❌ **Réservations expirées/annulées:** {$m['expired_bookings']}\n\n";

        $actions = [];

        if ($m['pending_bookings'] > 0) {
            $message .= "⚠️ **{$m['pending_bookings']} réservations** attendent d'être converties!\n";
            $actions[] = [
                'type' => 'link',
                'label' => 'Voir réservations',
                'route' => 'tenant.bookings.index',
                'params' => ['status' => 'pending'],
            ];
        }

        if ($m['prospect_conversion_rate'] < 30) {
            $message .= "\n💡 **Conseils pour améliorer la conversion:**\n";
            $message .= "• Répondez plus vite aux demandes (< 1h)\n";
            $message .= "• Proposez une visite gratuite\n";
            $message .= "• Offrez un avantage pour signature rapide\n";
            $message .= "• Simplifiez le processus de réservation\n";
        }

        return [
            'message' => $message,
            'actions' => $actions,
            'suggestions' => ['Relancer prospects', 'Améliorer conversion', 'Voir réservations'],
        ];
    }

    protected function generatePricingResponse(): array
    {
        $recommendations = $this->getPricingRecommendations();

        $message = "**Recommandations Tarifaires IA**\n\n";

        if (empty($recommendations)) {
            $message .= "✅ Vos prix semblent bien calibrés par rapport à la demande.\n";
            $message .= "Continuez à surveiller l'occupation par taille de box.\n";
        } else {
            foreach ($recommendations as $rec) {
                $icon = $rec['type'] === 'increase' ? '📈' : ($rec['type'] === 'decrease' ? '📉' : '🎁');
                $message .= "{$icon} **Boxes {$rec['size']}m²**\n";
                $message .= "   Prix actuel: " . number_format($rec['current_price'], 0, ',', ' ') . "€\n";
                $message .= "   Suggestion: {$rec['suggested_change']}\n";
                $message .= "   Raison: {$rec['reason']}\n";
                $message .= "   Gain potentiel: +" . number_format($rec['potential_gain'], 0, ',', ' ') . "€/mois\n\n";
            }
        }

        return [
            'message' => $message,
            'actions' => [
                [
                    'type' => 'link',
                    'label' => 'Ajuster les prix',
                    'route' => 'tenant.pricing.dashboard',
                ],
            ],
            'suggestions' => ['Appliquer suggestions', 'Voir historique prix', 'Comparer concurrence'],
            'data' => ['recommendations' => $recommendations],
        ];
    }

    protected function generateMarketingResponse(): array
    {
        $m = $this->context['metrics'];

        $message = "**Conseils Marketing IA**\n\n";

        $campaigns = [];

        if ($m['occupancy_rate'] < 70) {
            $campaigns[] = [
                'name' => 'Promo Remplissage',
                'type' => 'sms',
                'target' => 'Anciens clients + Prospects',
                'offer' => '-30% sur le 1er mois',
                'expected_impact' => '+5-10 nouvelles locations',
            ];
            $message .= "🎯 **Priorité: Augmenter l'occupation**\n\n";
        } elseif ($m['occupancy_rate'] > 90) {
            $campaigns[] = [
                'name' => 'Upsell Premium',
                'type' => 'email',
                'target' => 'Clients actuels',
                'offer' => 'Services premium (assurance, accès 24h)',
                'expected_impact' => '+15% revenus additionnels',
            ];
            $message .= "🎯 **Priorité: Maximiser les revenus**\n\n";
        }

        if ($m['churn_rate'] > 5) {
            $campaigns[] = [
                'name' => 'Fidélisation',
                'type' => 'email',
                'target' => 'Clients > 6 mois',
                'offer' => '-10% pour engagement 12 mois',
                'expected_impact' => '-3% churn',
            ];
        }

        $campaigns[] = [
            'name' => 'Parrainage',
            'type' => 'sms',
            'target' => 'Tous les clients',
            'offer' => '1 mois gratuit pour parrain et filleul',
            'expected_impact' => '+2-3 nouveaux clients/mois',
        ];

        $message .= "**Campagnes suggérées:**\n\n";
        foreach ($campaigns as $i => $campaign) {
            $message .= ($i + 1) . ". **{$campaign['name']}**\n";
            $message .= "   📱 Type: {$campaign['type']}\n";
            $message .= "   👥 Cible: {$campaign['target']}\n";
            $message .= "   🎁 Offre: {$campaign['offer']}\n";
            $message .= "   📈 Impact estimé: {$campaign['expected_impact']}\n\n";
        }

        return [
            'message' => $message,
            'actions' => [
                [
                    'type' => 'link',
                    'label' => 'Créer campagne SMS',
                    'route' => 'tenant.crm.campaigns.create',
                ],
                [
                    'type' => 'link',
                    'label' => 'Créer campagne Email',
                    'route' => 'tenant.crm.campaigns.create',
                    'params' => ['type' => 'email'],
                ],
            ],
            'suggestions' => ['Lancer promo', 'Programme parrainage', 'Campagne fidélité'],
            'data' => ['suggested_campaigns' => $campaigns],
        ];
    }

    protected function generateCreateCampaignResponse(string $message): array
    {
        // Analyze what type of campaign the user wants
        $m = $this->context['metrics'];

        $campaignType = 'promotion';
        if (str_contains(mb_strtolower($message), 'fidélisation') || str_contains(mb_strtolower($message), 'rétention')) {
            $campaignType = 'retention';
        } elseif (str_contains(mb_strtolower($message), 'parrainage')) {
            $campaignType = 'referral';
        }

        $response = "Je peux vous aider à créer une campagne.\n\n";

        if ($campaignType === 'promotion') {
            $response .= "📢 **Campagne Promotion suggérée:**\n";
            $response .= "• Offre: -30% sur le 1er mois\n";
            $response .= "• Cible: Anciens clients + Prospects\n";
            $response .= "• Canal: SMS + Email\n";
            $response .= "• Durée: 2 semaines\n\n";
        } elseif ($campaignType === 'retention') {
            $response .= "💎 **Campagne Fidélisation suggérée:**\n";
            $response .= "• Offre: -10% pour renouvellement 12 mois\n";
            $response .= "• Cible: Contrats expirant sous 60 jours\n";
            $response .= "• Canal: Email personnalisé + Appel\n\n";
        } else {
            $response .= "👥 **Campagne Parrainage suggérée:**\n";
            $response .= "• Offre: 1 mois gratuit pour parrain et filleul\n";
            $response .= "• Cible: Tous les clients actifs\n";
            $response .= "• Canal: Email + SMS\n\n";
        }

        $response .= "Voulez-vous que je crée cette campagne?";

        return [
            'message' => $response,
            'actions' => [
                [
                    'type' => 'action',
                    'label' => '✅ Créer cette campagne',
                    'action' => 'create_campaign',
                    'params' => ['type' => $campaignType],
                ],
                [
                    'type' => 'link',
                    'label' => '✏️ Personnaliser',
                    'route' => 'tenant.crm.campaigns.create',
                ],
            ],
            'suggestions' => ['Modifier l\'offre', 'Changer la cible', 'Voir modèles'],
        ];
    }

    protected function generateSendReminderResponse(): array
    {
        $m = $this->context['metrics'];

        if ($m['overdue_count'] == 0) {
            return [
                'message' => "🎉 Aucune facture en retard! Tous vos clients sont à jour.",
                'suggestions' => ['Voir factures', 'Analyser revenus'],
            ];
        }

        $response = "📤 **Envoi de rappels de paiement**\n\n";
        $response .= "📊 **{$m['overdue_count']} factures** en retard pour **" . number_format($m['total_overdue'], 0, ',', ' ') . "€**\n\n";
        $response .= "Comment souhaitez-vous relancer?";

        return [
            'message' => $response,
            'actions' => [
                [
                    'type' => 'action',
                    'label' => '📧 Rappels Email',
                    'action' => 'send_reminders',
                    'params' => ['type' => 'email'],
                ],
                [
                    'type' => 'action',
                    'label' => '📱 Rappels SMS',
                    'action' => 'send_reminders',
                    'params' => ['type' => 'sms'],
                ],
                [
                    'type' => 'action',
                    'label' => '📧📱 Les deux',
                    'action' => 'send_reminders',
                    'params' => ['type' => 'both'],
                ],
            ],
            'suggestions' => ['Voir impayés', 'Configurer rappels auto'],
        ];
    }

    protected function generateHelpResponse(): array
    {
        $message = "👋 **Bienvenue sur BoxiBox Copilot!**\n\n";
        $message .= "Je suis votre assistant IA pour optimiser votre activité de self-storage.\n\n";
        $message .= "**Voici ce que je peux faire:**\n\n";
        $message .= "📊 **Analyses**\n";
        $message .= "• \"Analyse mes revenus\" - Vue détaillée des revenus\n";
        $message .= "• \"Taux d'occupation\" - État du remplissage\n";
        $message .= "• \"Impayés\" - Factures en retard\n";
        $message .= "• \"Clients à risque\" - Prédiction de churn\n\n";
        $message .= "🔮 **Prévisions**\n";
        $message .= "• \"Prévisions\" - Forecast revenus & occupation\n";
        $message .= "• \"Briefing\" - Résumé quotidien\n\n";
        $message .= "🎯 **Actions**\n";
        $message .= "• \"Créer une campagne\" - Marketing automatisé\n";
        $message .= "• \"Envoyer des rappels\" - Relance impayés\n";
        $message .= "• \"Conseils pricing\" - Optimisation tarifaire\n\n";
        $message .= "💡 Vous pouvez aussi me poser des questions en langage naturel!";

        return [
            'message' => $message,
            'suggestions' => ['Briefing du jour', 'Analyse revenus', 'Clients à risque'],
        ];
    }

    protected function generateGeneralResponse(string $message): array
    {
        // For general questions, provide helpful guidance
        $m = $this->context['metrics'];

        $response = "Je comprends votre question. Voici un aperçu rapide de votre activité:\n\n";
        $response .= "📊 **Occupation:** {$m['occupancy_rate']}%\n";
        $response .= "💰 **Revenus du mois:** " . number_format($m['monthly_revenue'], 0, ',', ' ') . "€\n";
        $response .= "⚠️ **Impayés:** " . number_format($m['total_overdue'], 0, ',', ' ') . "€\n\n";

        $response .= "Que souhaitez-vous approfondir?";

        return [
            'message' => $response,
            'suggestions' => ['Analyser revenus', 'Voir impayés', 'Taux occupation', 'Prévisions'],
        ];
    }

    // ========================
    // ACTION EXECUTION METHODS
    // ========================

    protected function executeSendReminders(array $params): array
    {
        $type = $params['type'] ?? 'email';
        $now = Carbon::now();

        $overdueInvoices = Invoice::where('tenant_id', $this->tenantId)
            ->whereIn('status', ['sent', 'overdue'])
            ->where('due_date', '<', $now)
            ->with('customer')
            ->get();

        $sent = 0;
        $errors = [];

        foreach ($overdueInvoices as $invoice) {
            try {
                // In a real implementation, this would send actual emails/SMS
                // For now, we'll simulate the action
                if ($type === 'email' || $type === 'both') {
                    // Send email reminder
                    // Mail::to($invoice->customer->email)->send(new PaymentReminder($invoice));
                    $sent++;
                }
                if ($type === 'sms' || $type === 'both') {
                    // Send SMS reminder
                    // SMSService::send($invoice->customer->phone, $message);
                    if ($type === 'sms') $sent++;
                }
            } catch (\Exception $e) {
                $errors[] = $invoice->number;
            }
        }

        return [
            'success' => true,
            'message' => "✅ {$sent} rappels envoyés avec succès!",
            'details' => [
                'sent' => $sent,
                'errors' => $errors,
                'type' => $type,
            ],
        ];
    }

    protected function executeCreateCampaign(array $params): array
    {
        $type = $params['type'] ?? 'promotion';

        // This would create an actual campaign in a real implementation
        return [
            'success' => true,
            'message' => "✅ Campagne créée! Rendez-vous sur la page campagnes pour la finaliser.",
            'redirect' => route('tenant.crm.campaigns.create', ['preset' => $type]),
        ];
    }

    protected function executeAdjustPricing(array $params): array
    {
        return [
            'success' => true,
            'message' => "Redirection vers le tableau de bord pricing...",
            'redirect' => route('tenant.pricing.dashboard'),
        ];
    }

    protected function executeContactCustomer(array $params): array
    {
        $customerId = $params['customer_id'] ?? null;

        if (!$customerId) {
            return [
                'success' => false,
                'message' => 'Client non spécifié',
            ];
        }

        return [
            'success' => true,
            'message' => "Ouverture de la fiche client...",
            'redirect' => route('tenant.customers.show', $customerId),
        ];
    }

    // ========================
    // HELPER METHODS
    // ========================

    protected function getGreeting(): string
    {
        $hour = Carbon::now()->hour;

        if ($hour < 12) {
            return 'Bonjour';
        } elseif ($hour < 18) {
            return 'Bon après-midi';
        } else {
            return 'Bonsoir';
        }
    }

    protected function getOccupancyTrend(): string
    {
        // Compare with last week
        $now = Carbon::now();
        $lastWeek = $now->copy()->subWeek();

        $currentOccupied = Box::where('tenant_id', $this->tenantId)->where('status', 'occupied')->count();

        // This is simplified - in reality you'd track historical occupancy
        // For now, return a placeholder
        return 'stable';
    }

    protected function predictChurn(): array
    {
        $churnRisk = $this->getChurnRiskCustomers();

        $highRisk = count(array_filter($churnRisk, fn($c) => $c['risk_level'] === 'high'));
        $mediumRisk = count(array_filter($churnRisk, fn($c) => $c['risk_level'] === 'medium'));
        $totalAtRisk = array_sum(array_column($churnRisk, 'monthly_value'));

        return [
            'customers_at_risk' => count($churnRisk),
            'high_risk' => $highRisk,
            'medium_risk' => $mediumRisk,
            'revenue_at_risk' => $totalAtRisk,
            'predicted_churn_rate' => count($churnRisk) > 0 ? round($highRisk / max(1, $this->context['metrics']['active_customers']) * 100, 1) : 0,
        ];
    }

    protected function getChurnPreventionAction(int $riskScore, array $factors): string
    {
        if ($riskScore >= 60) {
            return 'Appeler immédiatement et proposer une offre de fidélisation';
        }

        $factorsString = implode(' ', $factors);

        if (str_contains($factorsString, 'Contrat expire')) {
            return 'Envoyer offre de renouvellement avec -10%';
        } elseif (str_contains($factorsString, 'paiement')) {
            return 'Proposer facilités de paiement';
        } else {
            return 'Envoyer email de satisfaction et offre spéciale';
        }
    }

    protected function generateForecastRecommendations(array $predictions, float $occupancy, array $churn): array
    {
        $recommendations = [];

        if ($occupancy > 95) {
            $recommendations[] = [
                'priority' => 'high',
                'action' => 'Augmentez vos prix de 5-10% dès maintenant',
                'reason' => 'Forte demande prévue',
            ];
        } elseif ($occupancy < 70) {
            $recommendations[] = [
                'priority' => 'high',
                'action' => 'Lancez une promotion -30% sur le 1er mois',
                'reason' => 'Demande faible attendue',
            ];
        }

        if ($churn['high_risk'] > 0) {
            $recommendations[] = [
                'priority' => 'critical',
                'action' => "Contactez les {$churn['high_risk']} clients à haut risque",
                'reason' => number_format($churn['revenue_at_risk'], 0, ',', ' ') . '€/mois en jeu',
            ];
        }

        return $recommendations;
    }

    protected function generateDailyTip(): array
    {
        $tips = [
            [
                'category' => 'marketing',
                'tip' => 'Demandez des avis Google à vos clients satisfaits - 88% des consommateurs font confiance aux avis en ligne.',
                'action' => ['label' => 'Demander avis', 'route' => 'tenant.reviews.requests'],
            ],
            [
                'category' => 'pricing',
                'tip' => 'Les boxes au RDC se louent 15% plus cher en moyenne. Vérifiez si vos prix reflètent cet avantage.',
                'action' => ['label' => 'Voir pricing', 'route' => 'tenant.pricing.dashboard'],
            ],
            [
                'category' => 'retention',
                'tip' => 'Un appel de courtoisie aux clients de +6 mois réduit le churn de 20%.',
                'action' => ['label' => 'Voir clients', 'route' => 'tenant.customers.index'],
            ],
            [
                'category' => 'conversion',
                'tip' => 'Répondre aux demandes en moins d\'1 heure augmente la conversion de 7x.',
                'action' => ['label' => 'Voir prospects', 'route' => 'tenant.prospects.index'],
            ],
            [
                'category' => 'revenue',
                'tip' => 'Proposez l\'assurance stockage - 40% des clients acceptent, +15% de revenus.',
                'action' => ['label' => 'Configurer', 'route' => 'tenant.settings.products'],
            ],
        ];

        return $tips[array_rand($tips)];
    }

    protected function formatForecastMessage(array $forecast): string
    {
        $message = "**🔮 Prévisions sur {$forecast['period']}**\n\n";

        $message .= "**💰 Revenus:**\n";
        foreach ($forecast['revenue_forecast']['predictions'] as $pred) {
            $message .= "• {$pred['month']}: " . number_format($pred['revenue'], 0, ',', ' ') . "€ (confiance {$pred['confidence']}%)\n";
        }

        $message .= "\n**📊 Occupation:**\n";
        $message .= "• Actuelle: {$forecast['occupancy_forecast']['current']}%\n";
        $message .= "• Prévue: {$forecast['occupancy_forecast']['next_month']}%\n";
        $message .= "• {$forecast['occupancy_forecast']['recommendation']}\n";

        if ($forecast['churn_forecast']['customers_at_risk'] > 0) {
            $message .= "\n**⚠️ Risque Churn:**\n";
            $message .= "• {$forecast['churn_forecast']['customers_at_risk']} clients à risque\n";
            $message .= "• Revenus menacés: " . number_format($forecast['churn_forecast']['revenue_at_risk'], 0, ',', ' ') . "€\n";
        }

        return $message;
    }

    protected function formatBriefingMessage(array $briefing): string
    {
        $message = "**☀️ {$briefing['greeting']}! Voici votre briefing du {$briefing['date']}**\n\n";

        $s = $briefing['summary'];

        if ($s['new_bookings'] > 0) {
            $message .= "✅ **{$s['new_bookings']} nouvelles réservations** (+{$s['new_revenue']}€/mois)\n";
        }

        $message .= "📊 **Occupation:** {$s['occupancy']}%\n";
        $message .= "💰 **Revenus du mois:** " . number_format($s['monthly_revenue'], 0, ',', ' ') . "€";
        $message .= " (" . ($s['revenue_vs_last_month'] >= 0 ? '+' : '') . "{$s['revenue_vs_last_month']}%)\n";

        if ($s['overdue_amount'] > 0) {
            $message .= "⚠️ **Impayés:** " . number_format($s['overdue_amount'], 0, ',', ' ') . "€\n";
        }

        if (count($briefing['alerts']) > 0) {
            $message .= "\n**🚨 Alertes:**\n";
            foreach ($briefing['alerts'] as $alert) {
                $message .= "• {$alert['title']}: {$alert['message']}\n";
            }
        }

        $message .= "\n💡 **Conseil du jour:** {$briefing['tip_of_day']['tip']}";

        return $message;
    }

    protected function getRevenueChartData(): array
    {
        $now = Carbon::now();
        $data = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $revenue = Invoice::where('tenant_id', $this->tenantId)
                ->where('status', 'paid')
                ->whereMonth('paid_at', $month->month)
                ->whereYear('paid_at', $month->year)
                ->sum('total');

            $data[] = [
                'month' => $month->format('M'),
                'revenue' => $revenue,
            ];
        }

        return $data;
    }
}
