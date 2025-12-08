<?php

namespace App\Services;

use App\Models\Box;
use App\Models\Contract;
use App\Models\Site;
use App\Models\PricingAiStrategy;
use App\Models\PricingAiRecommendation;
use App\Models\RevenueMetric;
use App\Models\PricingAlert;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RevenueManagementService
{
    /**
     * Calculer le RevPAU (Revenue per Available Unit) pour un site
     */
    public function calculateRevPAU(Site $site, ?Carbon $date = null): array
    {
        $date = $date ?? Carbon::today();
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        $totalUnits = $site->boxes()->count();
        $occupiedUnits = $site->boxes()->where('status', 'occupied')->count();
        $reservedUnits = $site->boxes()->where('status', 'reserved')->count();

        // Revenus du mois
        $grossRevenue = Contract::where('site_id', $site->id)
            ->where('status', 'active')
            ->sum('monthly_price');

        // RevPAU = Revenu total / Nombre total d'unités
        $revpau = $totalUnits > 0 ? $grossRevenue / $totalUnits : 0;

        // RevPOU = Revenu total / Nombre d'unités occupées
        $revpou = $occupiedUnits > 0 ? $grossRevenue / $occupiedUnits : 0;

        // Taux d'occupation
        $physicalOccupancy = $totalUnits > 0 ? ($occupiedUnits / $totalUnits) * 100 : 0;
        $economicOccupancy = $totalUnits > 0 ? (($occupiedUnits + $reservedUnits) / $totalUnits) * 100 : 0;

        return [
            'date' => $date->toDateString(),
            'total_units' => $totalUnits,
            'occupied_units' => $occupiedUnits,
            'reserved_units' => $reservedUnits,
            'physical_occupancy_rate' => round($physicalOccupancy, 2),
            'economic_occupancy_rate' => round($economicOccupancy, 2),
            'gross_revenue' => round($grossRevenue, 2),
            'revpau' => round($revpau, 2),
            'revpou' => round($revpou, 2),
            'average_rent' => $occupiedUnits > 0 ? round($grossRevenue / $occupiedUnits, 2) : 0,
        ];
    }

    /**
     * Générer des recommandations de prix basées sur l'IA
     */
    public function generatePricingRecommendations(Site $site): Collection
    {
        $strategy = PricingAiStrategy::where('site_id', $site->id)
            ->where('is_active', true)
            ->first() ?? $this->getDefaultStrategy($site);

        $recommendations = collect();

        // Grouper les boxes par type/taille
        $boxGroups = $site->boxes()
            ->select('box_type', DB::raw('COUNT(*) as total'), DB::raw('SUM(CASE WHEN status = "occupied" THEN 1 ELSE 0 END) as occupied'))
            ->groupBy('box_type')
            ->get();

        foreach ($boxGroups as $group) {
            $occupancyRate = $group->total > 0 ? ($group->occupied / $group->total) * 100 : 0;

            // Calculer le prix moyen actuel pour ce type
            $avgCurrentPrice = Box::where('site_id', $site->id)
                ->where('box_type', $group->box_type)
                ->avg('monthly_price') ?? 0;

            // Appliquer l'algorithme de pricing
            $recommendation = $this->calculateOptimalPrice(
                $avgCurrentPrice,
                $occupancyRate,
                $strategy
            );

            if ($recommendation['price_change_percent'] != 0) {
                $recommendations->push([
                    'site_id' => $site->id,
                    'box_type' => $group->box_type,
                    'current_price' => $avgCurrentPrice,
                    'recommended_price' => $recommendation['price'],
                    'price_change_percent' => $recommendation['price_change_percent'],
                    'confidence_score' => $recommendation['confidence'],
                    'occupancy_rate' => $occupancyRate,
                    'reason' => $recommendation['reason'],
                ]);
            }
        }

        return $recommendations;
    }

    /**
     * Calculer le prix optimal basé sur les paramètres de stratégie
     */
    protected function calculateOptimalPrice(float $currentPrice, float $occupancyRate, PricingAiStrategy $strategy): array
    {
        $priceChange = 0;
        $reason = '';
        $confidence = 0.85;

        // Logique de pricing basée sur l'occupation
        if ($occupancyRate >= $strategy->occupancy_threshold_high) {
            // Haute occupation = augmenter les prix
            $priceChange = $strategy->price_increase_step;
            $reason = "Forte demande (occupation {$occupancyRate}%)";
            $confidence = min(0.95, 0.70 + ($occupancyRate / 100) * 0.25);
        } elseif ($occupancyRate <= $strategy->occupancy_threshold_low) {
            // Basse occupation = baisser les prix
            $priceChange = -$strategy->price_decrease_step;
            $reason = "Faible demande (occupation {$occupancyRate}%)";
            $confidence = min(0.90, 0.75 + ((100 - $occupancyRate) / 100) * 0.15);
        }

        // Appliquer les facteurs saisonniers
        $seasonalFactor = $this->getSeasonalFactor($strategy);
        $priceChange += ($seasonalFactor - 1);

        // Limiter les changements de prix
        $priceFactor = 1 + $priceChange;
        $priceFactor = max($strategy->min_price_factor, min($strategy->max_price_factor, $priceFactor));

        $newPrice = $currentPrice * $priceFactor;
        $actualChange = $currentPrice > 0 ? (($newPrice - $currentPrice) / $currentPrice) * 100 : 0;

        return [
            'price' => round($newPrice, 2),
            'price_change_percent' => round($actualChange, 2),
            'confidence' => round($confidence, 2),
            'reason' => $reason ?: 'Prix optimal maintenu',
        ];
    }

    /**
     * Obtenir le facteur saisonnier actuel
     */
    protected function getSeasonalFactor(PricingAiStrategy $strategy): float
    {
        $month = strtolower(Carbon::now()->format('M'));
        $factors = $strategy->seasonal_factors ?? [];

        return $factors[$month] ?? 1.0;
    }

    /**
     * Créer des alertes de pricing
     */
    public function checkAndCreateAlerts(Site $site): void
    {
        $metrics = $this->calculateRevPAU($site);

        // Alerte haute vacance
        if ($metrics['physical_occupancy_rate'] < 70) {
            $this->createAlert($site, [
                'alert_type' => 'high_vacancy',
                'severity' => $metrics['physical_occupancy_rate'] < 50 ? 'critical' : 'warning',
                'title' => 'Taux de vacance élevé',
                'message' => "Le taux d'occupation est de {$metrics['physical_occupancy_rate']}%",
                'recommended_action' => 'Envisagez de réduire les prix ou d\'augmenter le marketing',
                'potential_revenue_impact' => $metrics['revpau'] * (70 - $metrics['physical_occupancy_rate']) / 100 * $metrics['total_units'],
            ]);
        }

        // Alerte opportunité saisonnière
        $seasonalFactor = $this->getSeasonalFactor($this->getDefaultStrategy($site));
        if ($seasonalFactor > 1.1) {
            $this->createAlert($site, [
                'alert_type' => 'seasonal_opportunity',
                'severity' => 'info',
                'title' => 'Opportunité saisonnière',
                'message' => 'Période de forte demande détectée',
                'recommended_action' => 'Augmentez vos prix de ' . round(($seasonalFactor - 1) * 100) . '%',
            ]);
        }
    }

    /**
     * Créer une alerte
     */
    protected function createAlert(Site $site, array $data): void
    {
        // Vérifier qu'une alerte similaire n'existe pas déjà
        $exists = PricingAlert::where('site_id', $site->id)
            ->where('alert_type', $data['alert_type'])
            ->where('is_actioned', false)
            ->where('created_at', '>', Carbon::now()->subDay())
            ->exists();

        if (!$exists) {
            PricingAlert::create(array_merge($data, [
                'tenant_id' => $site->tenant_id,
                'site_id' => $site->id,
            ]));
        }
    }

    /**
     * Obtenir ou créer une stratégie par défaut
     */
    protected function getDefaultStrategy(Site $site): PricingAiStrategy
    {
        return PricingAiStrategy::firstOrCreate(
            ['tenant_id' => $site->tenant_id, 'site_id' => $site->id],
            [
                'name' => 'Stratégie par défaut',
                'strategy_type' => 'occupancy_based',
                'min_price_factor' => 0.80,
                'max_price_factor' => 1.50,
                'occupancy_threshold_low' => 60,
                'occupancy_threshold_high' => 85,
                'price_increase_step' => 0.05,
                'price_decrease_step' => 0.03,
                'seasonal_factors' => [
                    'jan' => 0.95, 'feb' => 0.95, 'mar' => 1.0,
                    'apr' => 1.0, 'may' => 1.05, 'jun' => 1.15,
                    'jul' => 1.20, 'aug' => 1.20, 'sep' => 1.10,
                    'oct' => 1.0, 'nov' => 0.95, 'dec' => 0.90,
                ],
            ]
        );
    }

    /**
     * Enregistrer les métriques quotidiennes
     */
    public function recordDailyMetrics(Site $site): RevenueMetric
    {
        $metrics = $this->calculateRevPAU($site);

        // Calculer les variations
        $yesterday = RevenueMetric::where('site_id', $site->id)
            ->where('metric_date', Carbon::yesterday())
            ->first();

        $lastYear = RevenueMetric::where('site_id', $site->id)
            ->where('metric_date', Carbon::now()->subYear()->toDateString())
            ->first();

        return RevenueMetric::updateOrCreate(
            ['site_id' => $site->id, 'metric_date' => Carbon::today()],
            array_merge($metrics, [
                'tenant_id' => $site->tenant_id,
                'net_revenue' => $metrics['gross_revenue'] * 0.95, // Estimation
                'revpau_change_vs_previous' => $yesterday ? (($metrics['revpau'] - $yesterday->revpau) / max($yesterday->revpau, 1)) * 100 : null,
                'revpau_change_vs_year' => $lastYear ? (($metrics['revpau'] - $lastYear->revpau) / max($lastYear->revpau, 1)) * 100 : null,
            ])
        );
    }

    /**
     * Obtenir les données pour le dashboard Revenue Management
     */
    public function getDashboardData(int $tenantId, ?int $siteId = null): array
    {
        $query = RevenueMetric::where('tenant_id', $tenantId)
            ->where('metric_date', '>=', Carbon::now()->subMonths(6));

        if ($siteId) {
            $query->where('site_id', $siteId);
        }

        $metrics = $query->orderBy('metric_date')->get();

        // Dernières métriques
        $latest = $metrics->last();

        // Tendances
        $lastMonth = $metrics->where('metric_date', '>=', Carbon::now()->subMonth());
        $previousMonth = $metrics->where('metric_date', '>=', Carbon::now()->subMonths(2))
            ->where('metric_date', '<', Carbon::now()->subMonth());

        return [
            'current' => $latest ? [
                'revpau' => $latest->revpau,
                'occupancy' => $latest->physical_occupancy_rate,
                'revenue' => $latest->gross_revenue,
            ] : null,
            'trends' => [
                'revpau' => $this->calculateTrend($lastMonth->avg('revpau'), $previousMonth->avg('revpau')),
                'occupancy' => $this->calculateTrend($lastMonth->avg('physical_occupancy_rate'), $previousMonth->avg('physical_occupancy_rate')),
                'revenue' => $this->calculateTrend($lastMonth->sum('gross_revenue'), $previousMonth->sum('gross_revenue')),
            ],
            'chart_data' => $metrics->map(fn($m) => [
                'date' => $m->metric_date,
                'revpau' => $m->revpau,
                'occupancy' => $m->physical_occupancy_rate,
                'revenue' => $m->gross_revenue,
            ]),
            'alerts' => PricingAlert::where('tenant_id', $tenantId)
                ->where('is_read', false)
                ->orderBy('severity')
                ->limit(5)
                ->get(),
        ];
    }

    protected function calculateTrend($current, $previous): float
    {
        if (!$previous || $previous == 0) return 0;
        return round((($current - $previous) / $previous) * 100, 2);
    }
}
