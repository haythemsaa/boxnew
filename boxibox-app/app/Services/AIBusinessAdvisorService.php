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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AIBusinessAdvisorService
{
    /**
     * Catégories de conseils
     */
    const CATEGORIES = [
        'occupancy' => ['icon' => 'chart-bar', 'color' => 'blue', 'label' => 'Taux d\'occupation'],
        'revenue' => ['icon' => 'currency-euro', 'color' => 'green', 'label' => 'Revenus'],
        'payments' => ['icon' => 'credit-card', 'color' => 'red', 'label' => 'Impayés'],
        'conversion' => ['icon' => 'arrow-trending-up', 'color' => 'purple', 'label' => 'Conversion'],
        'retention' => ['icon' => 'users', 'color' => 'orange', 'label' => 'Fidélisation'],
        'pricing' => ['icon' => 'tag', 'color' => 'indigo', 'label' => 'Tarification'],
        'marketing' => ['icon' => 'megaphone', 'color' => 'pink', 'label' => 'Marketing'],
    ];

    /**
     * Priorités des conseils
     */
    const PRIORITY_CRITICAL = 'critical';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_LOW = 'low';

    protected int $tenantId;
    protected array $metrics = [];
    protected array $recommendations = [];

    /**
     * Générer tous les conseils pour un tenant
     */
    public function generateAdvice(int $tenantId): array
    {
        $this->tenantId = $tenantId;
        $this->recommendations = [];

        // Calculer toutes les métriques
        $this->metrics = $this->calculateAllMetrics();

        // Générer les conseils par catégorie
        $this->analyzeOccupancy();
        $this->analyzeRevenue();
        $this->analyzePayments();
        $this->analyzeConversion();
        $this->analyzeRetention();
        $this->analyzePricing();
        $this->analyzeMarketing();

        // Trier par priorité
        usort($this->recommendations, function ($a, $b) {
            $priorities = [self::PRIORITY_CRITICAL => 0, self::PRIORITY_HIGH => 1, self::PRIORITY_MEDIUM => 2, self::PRIORITY_LOW => 3];
            return ($priorities[$a['priority']] ?? 3) <=> ($priorities[$b['priority']] ?? 3);
        });

        return [
            'metrics' => $this->metrics,
            'recommendations' => $this->recommendations,
            'score' => $this->calculateHealthScore(),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Calculer toutes les métriques business
     */
    protected function calculateAllMetrics(): array
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $lastMonth = $now->copy()->subMonth();

        // Boxes et occupation
        $totalBoxes = Box::where('tenant_id', $this->tenantId)->count();
        $occupiedBoxes = Box::where('tenant_id', $this->tenantId)->where('status', 'occupied')->count();
        $availableBoxes = Box::where('tenant_id', $this->tenantId)->where('status', 'available')->count();
        $reservedBoxes = Box::where('tenant_id', $this->tenantId)->where('status', 'reserved')->count();
        $occupancyRate = $totalBoxes > 0 ? ($occupiedBoxes / $totalBoxes) * 100 : 0;

        // Revenus
        $monthlyRevenue = Invoice::where('tenant_id', $this->tenantId)
            ->where('status', 'paid')
            ->whereMonth('paid_at', $now->month)
            ->whereYear('paid_at', $now->year)
            ->sum('total');

        $lastMonthRevenue = Invoice::where('tenant_id', $this->tenantId)
            ->where('status', 'paid')
            ->whereMonth('paid_at', $lastMonth->month)
            ->whereYear('paid_at', $lastMonth->year)
            ->sum('total');

        $revenueGrowth = $lastMonthRevenue > 0
            ? (($monthlyRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100
            : 0;

        // Impayés
        $overdueInvoices = Invoice::where('tenant_id', $this->tenantId)
            ->whereIn('status', ['sent', 'overdue', 'partial'])
            ->where('due_date', '<', $now)
            ->get();

        $totalOverdue = $overdueInvoices->sum(fn($i) => $i->total - ($i->paid_amount ?? 0));
        $overdueCount = $overdueInvoices->count();
        $averageDaysOverdue = $overdueInvoices->avg(fn($i) => $now->diffInDays($i->due_date));

        // Prospects et conversion
        $totalProspects = Prospect::where('tenant_id', $this->tenantId)
            ->whereMonth('created_at', $now->month)
            ->count();

        $convertedProspects = Prospect::where('tenant_id', $this->tenantId)
            ->whereMonth('created_at', $now->month)
            ->where('status', 'converted')
            ->count();

        $prospectConversionRate = $totalProspects > 0
            ? ($convertedProspects / $totalProspects) * 100
            : 0;

        // Réservations non converties
        $pendingBookings = 0;
        $expiredBookings = 0;
        if (class_exists(Booking::class)) {
            $pendingBookings = Booking::where('tenant_id', $this->tenantId)
                ->where('status', 'pending')
                ->count();

            $expiredBookings = Booking::where('tenant_id', $this->tenantId)
                ->whereIn('status', ['expired', 'cancelled'])
                ->whereMonth('created_at', $now->month)
                ->count();
        }

        // Contrats expirant
        $expiringContracts = Contract::where('tenant_id', $this->tenantId)
            ->where('status', 'active')
            ->whereBetween('end_date', [$now, $now->copy()->addDays(30)])
            ->count();

        // Clients actifs
        $activeCustomers = Customer::where('tenant_id', $this->tenantId)
            ->whereHas('contracts', fn($q) => $q->where('status', 'active'))
            ->count();

        // Prix moyen
        $averagePrice = Contract::where('tenant_id', $this->tenantId)
            ->where('status', 'active')
            ->avg('monthly_price') ?? 0;

        // Taux de churn (clients perdus)
        $lostContracts = Contract::where('tenant_id', $this->tenantId)
            ->where('status', 'terminated')
            ->whereMonth('updated_at', $now->month)
            ->count();

        $churnRate = $activeCustomers > 0
            ? ($lostContracts / ($activeCustomers + $lostContracts)) * 100
            : 0;

        // Revenu potentiel manqué (boxes vides)
        $potentialLostRevenue = $availableBoxes * $averagePrice;

        return [
            // Occupation
            'total_boxes' => $totalBoxes,
            'occupied_boxes' => $occupiedBoxes,
            'available_boxes' => $availableBoxes,
            'reserved_boxes' => $reservedBoxes,
            'occupancy_rate' => round($occupancyRate, 1),

            // Revenus
            'monthly_revenue' => round($monthlyRevenue, 2),
            'last_month_revenue' => round($lastMonthRevenue, 2),
            'revenue_growth' => round($revenueGrowth, 1),
            'average_price' => round($averagePrice, 2),
            'potential_lost_revenue' => round($potentialLostRevenue, 2),

            // Impayés
            'total_overdue' => round($totalOverdue, 2),
            'overdue_count' => $overdueCount,
            'average_days_overdue' => round($averageDaysOverdue ?? 0, 0),

            // Conversion
            'total_prospects' => $totalProspects,
            'converted_prospects' => $convertedProspects,
            'prospect_conversion_rate' => round($prospectConversionRate, 1),
            'pending_bookings' => $pendingBookings,
            'expired_bookings' => $expiredBookings,

            // Fidélisation
            'active_customers' => $activeCustomers,
            'expiring_contracts' => $expiringContracts,
            'churn_rate' => round($churnRate, 1),
            'lost_contracts' => $lostContracts,
        ];
    }

    /**
     * Analyser le taux d'occupation
     */
    protected function analyzeOccupancy(): void
    {
        $rate = $this->metrics['occupancy_rate'];
        $available = $this->metrics['available_boxes'];
        $potential = $this->metrics['potential_lost_revenue'];

        if ($rate < 50) {
            $this->addRecommendation([
                'category' => 'occupancy',
                'priority' => self::PRIORITY_CRITICAL,
                'title' => 'Taux d\'occupation critique',
                'problem' => "Votre taux d'occupation est de seulement {$rate}%. Vous avez {$available} boxes vides.",
                'impact' => "Vous perdez potentiellement " . number_format($potential, 0, ',', ' ') . "€/mois de revenus.",
                'recommendations' => [
                    'Lancez une campagne promotionnelle avec -30% sur le premier mois',
                    'Proposez des offres de parrainage (1 mois gratuit pour le parrain)',
                    'Baissez temporairement les prix des boxes difficiles à louer',
                    'Contactez les anciens clients avec une offre de retour',
                    'Investissez dans la publicité locale (Google Ads, Facebook)',
                ],
                'quick_wins' => [
                    ['action' => 'Créer une campagne SMS', 'route' => 'tenant.crm.campaigns.create'],
                    ['action' => 'Ajuster les prix', 'route' => 'tenant.pricing.dashboard'],
                ],
            ]);
        } elseif ($rate < 70) {
            $this->addRecommendation([
                'category' => 'occupancy',
                'priority' => self::PRIORITY_HIGH,
                'title' => 'Taux d\'occupation faible',
                'problem' => "Votre taux d'occupation de {$rate}% est en dessous de l'objectif (80-90%).",
                'impact' => "Revenu potentiel manqué: " . number_format($potential, 0, ',', ' ') . "€/mois.",
                'recommendations' => [
                    'Proposez des promotions ciblées (étudiants, entreprises)',
                    'Offrez le premier mois à -20% pour les nouveaux clients',
                    'Améliorez votre visibilité en ligne (SEO, avis Google)',
                    'Partenariats avec déménageurs et agences immobilières',
                ],
                'quick_wins' => [
                    ['action' => 'Voir les prospects', 'route' => 'tenant.prospects.index'],
                    ['action' => 'Envoyer des offres', 'route' => 'tenant.crm.campaigns.create'],
                ],
            ]);
        } elseif ($rate < 80) {
            $this->addRecommendation([
                'category' => 'occupancy',
                'priority' => self::PRIORITY_MEDIUM,
                'title' => 'Optimiser le remplissage',
                'problem' => "Taux d'occupation de {$rate}% - {$available} boxes disponibles.",
                'impact' => "Marge d'amélioration de " . number_format($potential * 0.5, 0, ',', ' ') . "€/mois possible.",
                'recommendations' => [
                    'Analysez les types de boxes les moins demandés',
                    'Proposez des options de stockage flexible (courte durée)',
                    'Mettez en avant les boxes climatisées ou sécurisées',
                ],
                'quick_wins' => [
                    ['action' => 'Analyser par taille', 'route' => 'tenant.analytics.occupancy'],
                ],
            ]);
        } elseif ($rate > 95) {
            $this->addRecommendation([
                'category' => 'occupancy',
                'priority' => self::PRIORITY_LOW,
                'title' => 'Excellente occupation - Augmentez les prix!',
                'problem' => "Votre taux de {$rate}% indique une forte demande.",
                'impact' => "Opportunité d'augmenter les revenus de 5-15%.",
                'recommendations' => [
                    'Augmentez progressivement les prix (5% par trimestre)',
                    'Créez une liste d\'attente pour les boxes populaires',
                    'Proposez des services premium (assurance, accès 24h)',
                    'Envisagez l\'extension de capacité',
                ],
                'quick_wins' => [
                    ['action' => 'Ajuster les prix', 'route' => 'tenant.pricing.dashboard'],
                ],
            ]);
        }
    }

    /**
     * Analyser les revenus
     */
    protected function analyzeRevenue(): void
    {
        $growth = $this->metrics['revenue_growth'];
        $current = $this->metrics['monthly_revenue'];
        $avgPrice = $this->metrics['average_price'];

        if ($growth < -10) {
            $this->addRecommendation([
                'category' => 'revenue',
                'priority' => self::PRIORITY_HIGH,
                'title' => 'Baisse significative des revenus',
                'problem' => "Vos revenus ont baissé de " . abs(round($growth)) . "% ce mois-ci.",
                'impact' => "Perte de " . number_format(abs($current - $this->metrics['last_month_revenue']), 0, ',', ' ') . "€ par rapport au mois dernier.",
                'recommendations' => [
                    'Identifiez les contrats résiliés et les raisons',
                    'Contactez les clients partis pour comprendre',
                    'Vérifiez la compétitivité de vos prix',
                    'Relancez les prospects en attente',
                ],
                'quick_wins' => [
                    ['action' => 'Voir les résiliations', 'route' => 'tenant.contracts.index'],
                    ['action' => 'Analyser les revenus', 'route' => 'tenant.analytics.revenue'],
                ],
            ]);
        }

        if ($avgPrice < 50) {
            $this->addRecommendation([
                'category' => 'revenue',
                'priority' => self::PRIORITY_MEDIUM,
                'title' => 'Prix moyen trop bas',
                'problem' => "Votre prix moyen de {$avgPrice}€/mois semble sous-évalué.",
                'impact' => "Une augmentation de 10% générerait +" . number_format($current * 0.1, 0, ',', ' ') . "€/mois.",
                'recommendations' => [
                    'Comparez vos prix avec la concurrence locale',
                    'Ajoutez des services pour justifier des prix plus élevés',
                    'Proposez des options premium (climatisation, sécurité renforcée)',
                ],
                'quick_wins' => [
                    ['action' => 'Voir le pricing', 'route' => 'tenant.pricing.dashboard'],
                ],
            ]);
        }
    }

    /**
     * Analyser les impayés
     */
    protected function analyzePayments(): void
    {
        $total = $this->metrics['total_overdue'];
        $count = $this->metrics['overdue_count'];
        $avgDays = $this->metrics['average_days_overdue'];

        if ($total > 5000 || $count > 10) {
            $this->addRecommendation([
                'category' => 'payments',
                'priority' => self::PRIORITY_CRITICAL,
                'title' => 'Impayés critiques à traiter',
                'problem' => "{$count} factures impayées pour un total de " . number_format($total, 0, ',', ' ') . "€.",
                'impact' => "Retard moyen: {$avgDays} jours. Risque de perte définitive.",
                'recommendations' => [
                    'Envoyez des rappels automatiques par email et SMS',
                    'Proposez des facilités de paiement (3x sans frais)',
                    'Contactez personnellement les gros débiteurs',
                    'Mettez en place le prélèvement automatique SEPA',
                    'Envisagez une procédure de recouvrement pour les cas > 60 jours',
                ],
                'quick_wins' => [
                    ['action' => 'Voir les impayés', 'route' => 'tenant.reminders.overdue-invoices'],
                    ['action' => 'Envoyer des rappels', 'route' => 'tenant.reminders.index'],
                ],
            ]);
        } elseif ($total > 2000 || $count > 5) {
            $this->addRecommendation([
                'category' => 'payments',
                'priority' => self::PRIORITY_HIGH,
                'title' => 'Impayés à surveiller',
                'problem' => "{$count} factures en retard totalisant " . number_format($total, 0, ',', ' ') . "€.",
                'impact' => "Impact sur la trésorerie. Agissez avant que ça s'aggrave.",
                'recommendations' => [
                    'Automatisez les rappels de paiement',
                    'Proposez le prélèvement automatique',
                    'Envoyez un SMS de rappel personnalisé',
                ],
                'quick_wins' => [
                    ['action' => 'Envoyer des rappels', 'route' => 'tenant.reminders.index'],
                ],
            ]);
        } elseif ($avgDays > 30) {
            $this->addRecommendation([
                'category' => 'payments',
                'priority' => self::PRIORITY_MEDIUM,
                'title' => 'Délais de paiement trop longs',
                'problem' => "Retard moyen de {$avgDays} jours sur les factures.",
                'impact' => "Trésorerie tendue et risque d'impayés définitifs.",
                'recommendations' => [
                    'Réduisez les délais de paiement (15 jours au lieu de 30)',
                    'Offrez 2% de remise pour paiement anticipé',
                    'Envoyez les rappels plus tôt (J+7 au lieu de J+15)',
                ],
                'quick_wins' => [
                    ['action' => 'Configurer les rappels', 'route' => 'tenant.settings.index'],
                ],
            ]);
        }
    }

    /**
     * Analyser la conversion
     */
    protected function analyzeConversion(): void
    {
        $rate = $this->metrics['prospect_conversion_rate'];
        $prospects = $this->metrics['total_prospects'];
        $pending = $this->metrics['pending_bookings'];
        $expired = $this->metrics['expired_bookings'];

        if ($rate < 20 && $prospects > 5) {
            $this->addRecommendation([
                'category' => 'conversion',
                'priority' => self::PRIORITY_HIGH,
                'title' => 'Taux de conversion trop faible',
                'problem' => "Seulement {$rate}% de vos prospects deviennent clients.",
                'impact' => "Vous perdez " . ($prospects - $this->metrics['converted_prospects']) . " opportunités ce mois.",
                'recommendations' => [
                    'Répondez plus vite aux demandes (< 1 heure)',
                    'Proposez une visite gratuite du site',
                    'Offrez un avantage pour signature immédiate',
                    'Suivez les prospects avec des relances personnalisées',
                    'Simplifiez le processus de réservation en ligne',
                ],
                'quick_wins' => [
                    ['action' => 'Relancer les prospects', 'route' => 'tenant.prospects.index'],
                    ['action' => 'Créer une campagne', 'route' => 'tenant.crm.campaigns.create'],
                ],
            ]);
        }

        if ($pending > 5) {
            $this->addRecommendation([
                'category' => 'conversion',
                'priority' => self::PRIORITY_HIGH,
                'title' => 'Réservations en attente',
                'problem' => "{$pending} réservations n'ont pas été converties en contrat.",
                'impact' => "Revenus potentiels bloqués. Risque d'annulation.",
                'recommendations' => [
                    'Contactez immédiatement ces clients',
                    'Proposez une aide pour finaliser le contrat',
                    'Vérifiez s\'il y a un blocage (paiement, documents)',
                    'Offrez une incitation à finaliser rapidement',
                ],
                'quick_wins' => [
                    ['action' => 'Voir les réservations', 'route' => 'tenant.bookings.index'],
                ],
            ]);
        }

        if ($expired > 3) {
            $this->addRecommendation([
                'category' => 'conversion',
                'priority' => self::PRIORITY_MEDIUM,
                'title' => 'Trop de réservations expirées',
                'problem' => "{$expired} réservations ont expiré ou été annulées ce mois.",
                'impact' => "Perte d'opportunités et boxes bloquées inutilement.",
                'recommendations' => [
                    'Réduisez le délai de confirmation (48h max)',
                    'Envoyez des rappels automatiques avant expiration',
                    'Demandez un acompte pour confirmer',
                    'Analysez les raisons des annulations',
                ],
                'quick_wins' => [
                    ['action' => 'Paramètres réservation', 'route' => 'tenant.bookings.settings'],
                ],
            ]);
        }
    }

    /**
     * Analyser la fidélisation
     */
    protected function analyzeRetention(): void
    {
        $churn = $this->metrics['churn_rate'];
        $expiring = $this->metrics['expiring_contracts'];
        $lost = $this->metrics['lost_contracts'];

        if ($churn > 10) {
            $this->addRecommendation([
                'category' => 'retention',
                'priority' => self::PRIORITY_HIGH,
                'title' => 'Taux de churn élevé',
                'problem' => "{$churn}% de vos clients partent chaque mois ({$lost} ce mois).",
                'impact' => "Coût d'acquisition d'un nouveau client = 5x fidélisation.",
                'recommendations' => [
                    'Contactez les clients avant leur départ pour comprendre',
                    'Proposez des réductions pour renouvellement long terme',
                    'Mettez en place un programme de fidélité',
                    'Améliorez la qualité de service (propreté, sécurité)',
                    'Envoyez des enquêtes de satisfaction',
                ],
                'quick_wins' => [
                    ['action' => 'Programme fidélité', 'route' => 'tenant.loyalty.index'],
                ],
            ]);
        }

        if ($expiring > 5) {
            $this->addRecommendation([
                'category' => 'retention',
                'priority' => self::PRIORITY_MEDIUM,
                'title' => 'Contrats à renouveler',
                'problem' => "{$expiring} contrats expirent dans les 30 prochains jours.",
                'impact' => "Risque de perte de revenus si non renouvelés.",
                'recommendations' => [
                    'Contactez ces clients proactivement',
                    'Proposez une offre de renouvellement avantageuse',
                    'Offrez un mois gratuit pour engagement 12 mois',
                    'Organisez une visite pour montrer les améliorations',
                ],
                'quick_wins' => [
                    ['action' => 'Contrats expirant', 'route' => 'tenant.contracts.index'],
                ],
            ]);
        }
    }

    /**
     * Analyser la tarification
     */
    protected function analyzePricing(): void
    {
        $avgPrice = $this->metrics['average_price'];
        $occupancy = $this->metrics['occupancy_rate'];
        $available = $this->metrics['available_boxes'];

        // Prix dynamique selon l'occupation
        if ($occupancy > 90 && $avgPrice < 100) {
            $this->addRecommendation([
                'category' => 'pricing',
                'priority' => self::PRIORITY_MEDIUM,
                'title' => 'Opportunité d\'augmentation des prix',
                'problem' => "Forte demande ({$occupancy}% occupation) mais prix moyens bas.",
                'impact' => "Vous pouvez augmenter les prix de 10-15% sans perdre de clients.",
                'recommendations' => [
                    'Augmentez les prix des nouveaux contrats',
                    'Créez des offres premium avec services additionnels',
                    'Utilisez la tarification dynamique selon la saison',
                ],
                'quick_wins' => [
                    ['action' => 'Pricing dynamique', 'route' => 'tenant.pricing.dashboard'],
                ],
            ]);
        }

        if ($occupancy < 60 && $available > 10) {
            $this->addRecommendation([
                'category' => 'pricing',
                'priority' => self::PRIORITY_HIGH,
                'title' => 'Baissez les prix temporairement',
                'problem' => "Faible occupation ({$occupancy}%) et {$available} boxes vides.",
                'impact' => "Mieux vaut louer moins cher que laisser vide.",
                'recommendations' => [
                    'Offrez -30% sur le 1er mois pour les nouvelles locations',
                    'Créez des tarifs dégressifs (6 mois = -15%)',
                    'Proposez des durées flexibles (mois par mois)',
                    'Baissez les prix des boxes les moins demandées',
                ],
                'quick_wins' => [
                    ['action' => 'Ajuster les prix', 'route' => 'tenant.pricing.dashboard'],
                ],
            ]);
        }
    }

    /**
     * Analyser le marketing
     */
    protected function analyzeMarketing(): void
    {
        $prospects = $this->metrics['total_prospects'];
        $occupancy = $this->metrics['occupancy_rate'];

        if ($prospects < 5 && $occupancy < 80) {
            $this->addRecommendation([
                'category' => 'marketing',
                'priority' => self::PRIORITY_HIGH,
                'title' => 'Manque de visibilité',
                'problem' => "Seulement {$prospects} prospects ce mois malgré des boxes disponibles.",
                'impact' => "Pas assez de demandes entrantes pour remplir vos boxes.",
                'recommendations' => [
                    'Investissez dans Google Ads (mots-clés locaux)',
                    'Optimisez votre fiche Google Business',
                    'Demandez des avis clients (objectif: 50+ avis 5 étoiles)',
                    'Créez du contenu SEO (blog, guides de stockage)',
                    'Partenariats avec entreprises locales',
                ],
                'quick_wins' => [
                    ['action' => 'Demander des avis', 'route' => 'tenant.reviews.requests'],
                    ['action' => 'Campagne SMS', 'route' => 'tenant.crm.campaigns.create'],
                ],
            ]);
        }

        if ($occupancy < 70) {
            $this->addRecommendation([
                'category' => 'marketing',
                'priority' => self::PRIORITY_MEDIUM,
                'title' => 'Actions marketing recommandées',
                'problem' => "Occupation insuffisante, besoin de plus de clients.",
                'impact' => "Le marketing peut améliorer le remplissage de 20-30%.",
                'recommendations' => [
                    'Lancez une promotion \"1 mois offert\"',
                    'Parrainage: 50€ de réduction pour le parrain et le filleul',
                    'Publicité Facebook ciblée (déménagements, rénovations)',
                    'Flyers dans les agences immobilières',
                    'Email aux anciens clients partis',
                ],
                'quick_wins' => [
                    ['action' => 'Créer une promotion', 'route' => 'tenant.crm.leads.create'],
                ],
            ]);
        }
    }

    /**
     * Calculer le score de santé global
     */
    protected function calculateHealthScore(): array
    {
        $score = 100;
        $details = [];

        // Occupation (30 points)
        $occScore = min(30, ($this->metrics['occupancy_rate'] / 90) * 30);
        $score -= (30 - $occScore);
        $details['occupation'] = ['score' => round($occScore), 'max' => 30];

        // Impayés (25 points)
        $overdueRatio = $this->metrics['monthly_revenue'] > 0
            ? ($this->metrics['total_overdue'] / $this->metrics['monthly_revenue'])
            : 0;
        $paymentScore = max(0, 25 - ($overdueRatio * 100));
        $score -= (25 - $paymentScore);
        $details['paiements'] = ['score' => round($paymentScore), 'max' => 25];

        // Conversion (20 points)
        $convScore = min(20, ($this->metrics['prospect_conversion_rate'] / 50) * 20);
        $score -= (20 - $convScore);
        $details['conversion'] = ['score' => round($convScore), 'max' => 20];

        // Fidélisation (15 points)
        $retScore = max(0, 15 - ($this->metrics['churn_rate'] * 1.5));
        $score -= (15 - $retScore);
        $details['fidélisation'] = ['score' => round($retScore), 'max' => 15];

        // Croissance (10 points)
        $growthScore = min(10, max(0, 5 + ($this->metrics['revenue_growth'] / 10) * 5));
        $score -= (10 - $growthScore);
        $details['croissance'] = ['score' => round($growthScore), 'max' => 10];

        return [
            'total' => max(0, min(100, round($score))),
            'grade' => $this->getGrade($score),
            'details' => $details,
        ];
    }

    /**
     * Obtenir la note lettrée
     */
    protected function getGrade(float $score): string
    {
        if ($score >= 90) return 'A';
        if ($score >= 80) return 'B';
        if ($score >= 70) return 'C';
        if ($score >= 60) return 'D';
        return 'F';
    }

    /**
     * Ajouter une recommandation
     */
    protected function addRecommendation(array $data): void
    {
        $category = self::CATEGORIES[$data['category']] ?? self::CATEGORIES['occupancy'];

        $this->recommendations[] = array_merge($data, [
            'icon' => $category['icon'],
            'color' => $category['color'],
            'category_label' => $category['label'],
        ]);
    }
}
