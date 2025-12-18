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

    /**
     * Algorithme avancé de pricing dynamique avec ML-like scoring
     */
    public function calculateAdvancedOptimalPrice(Box $box, array $options = []): array
    {
        $site = $box->site;
        $basePrice = $box->base_price ?? $box->current_price ?? 0;

        // Facteurs avec pondération
        $factors = [
            'occupancy' => $this->calculateOccupancyScore($site),
            'demand' => $this->calculateDemandScore($site, $box),
            'seasonality' => $this->calculateSeasonalityScore(),
            'competitor' => $this->calculateCompetitorScore($site, $box),
            'velocity' => $this->calculateVelocityScore($site),
            'duration' => $this->calculateDurationScore($box),
        ];

        // Pondération des facteurs (somme = 1.0)
        $weights = [
            'occupancy' => 0.30,
            'demand' => 0.20,
            'seasonality' => 0.15,
            'competitor' => 0.15,
            'velocity' => 0.10,
            'duration' => 0.10,
        ];

        // Score combiné pondéré
        $combinedScore = 0;
        foreach ($factors as $key => $score) {
            $combinedScore += $score * $weights[$key];
        }

        // Convertir le score en multiplicateur de prix (0.8 à 1.2)
        $priceMultiplier = 0.8 + ($combinedScore * 0.4);

        // Appliquer les limites min/max
        $strategy = $this->getDefaultStrategy($site);
        $priceMultiplier = max($strategy->min_price_factor, min($strategy->max_price_factor, $priceMultiplier));

        $optimalPrice = round($basePrice * $priceMultiplier, 2);
        $change = $optimalPrice - $box->current_price;
        $changePercent = $box->current_price > 0
            ? round((($optimalPrice - $box->current_price) / $box->current_price) * 100, 1)
            : 0;

        // Calculer le niveau de confiance
        $confidence = $this->calculatePriceConfidence($factors, $site);

        return [
            'box_id' => $box->id,
            'box_code' => $box->number ?? $box->name,
            'box_size' => $box->size_m2 ?? $box->area,
            'site_id' => $site->id,
            'site_name' => $site->name,
            'current_price' => $box->current_price,
            'base_price' => $basePrice,
            'optimal_price' => $optimalPrice,
            'adjustment_percent' => $changePercent,
            'change' => $change,
            'estimated_revenue_impact' => $change * 12, // Impact annuel
            'factors' => $factors,
            'adjustments' => $this->getActiveAdjustments($factors),
            'confidence' => $confidence,
            'recommendation' => $this->getRecommendationText($changePercent, $confidence),
        ];
    }

    /**
     * Score basé sur le taux d'occupation (0-1)
     */
    protected function calculateOccupancyScore(Site $site): float
    {
        $total = $site->boxes()->count();
        $occupied = $site->boxes()->where('status', 'occupied')->count();

        if ($total === 0) return 0.5;

        $rate = $occupied / $total;

        // Occupation haute = score élevé = prix plus hauts
        // < 60% = 0.2, 60-80% = 0.5, 80-90% = 0.7, > 90% = 1.0
        if ($rate < 0.60) return 0.2 + ($rate * 0.3);
        if ($rate < 0.80) return 0.5 + (($rate - 0.60) * 1.0);
        if ($rate < 0.90) return 0.7 + (($rate - 0.80) * 2.0);
        return 0.9 + (($rate - 0.90) * 1.0);
    }

    /**
     * Score basé sur la demande récente (0-1)
     */
    protected function calculateDemandScore(Site $site, Box $box): float
    {
        // Réservations/contrats des 30 derniers jours vs moyenne 6 mois
        $recent = \App\Models\Contract::where('site_id', $site->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $avgMonthly = \App\Models\Contract::where('site_id', $site->id)
            ->where('created_at', '>=', now()->subMonths(6))
            ->count() / 6;

        if ($avgMonthly === 0) return 0.5;

        $ratio = $recent / max($avgMonthly, 1);

        // ratio > 1.5 = forte demande, < 0.7 = faible demande
        return min(1.0, max(0.0, ($ratio - 0.5) / 1.5));
    }

    /**
     * Score saisonnier (0-1)
     */
    protected function calculateSeasonalityScore(): float
    {
        $month = (int) now()->format('n');

        // Haute saison: juin-septembre (déménagements)
        $seasonScores = [
            1 => 0.3, 2 => 0.35, 3 => 0.45, 4 => 0.5,
            5 => 0.6, 6 => 0.85, 7 => 1.0, 8 => 0.95,
            9 => 0.75, 10 => 0.5, 11 => 0.4, 12 => 0.25,
        ];

        return $seasonScores[$month] ?? 0.5;
    }

    /**
     * Score concurrentiel (0-1) basé sur données de marché
     */
    protected function calculateCompetitorScore(Site $site, Box $box): float
    {
        // Vérifier si données concurrentielles disponibles en cache
        $competitorKey = "competitor_avg_price_{$site->id}_{$box->size_category}";
        $avgCompetitorPrice = \Illuminate\Support\Facades\Cache::get($competitorKey);

        if (!$avgCompetitorPrice) {
            return 0.5; // Neutre si pas de données
        }

        $priceRatio = $box->current_price / $avgCompetitorPrice;

        // Si moins cher que concurrence = score élevé (marge augmentation)
        // Si plus cher = score bas (besoin de réduire)
        if ($priceRatio < 0.85) return 0.9;
        if ($priceRatio < 0.95) return 0.7;
        if ($priceRatio < 1.05) return 0.5;
        if ($priceRatio < 1.15) return 0.3;
        return 0.1;
    }

    /**
     * Score de vélocité de location (0-1)
     */
    protected function calculateVelocityScore(Site $site): float
    {
        // Temps moyen pour louer un box (jours depuis disponible jusqu'à occupé)
        $avgDays = \App\Models\Box::where('site_id', $site->id)
            ->where('status', 'occupied')
            ->whereNotNull('last_status_change')
            ->avg(\DB::raw('DATEDIFF(last_status_change, created_at)'));

        if (!$avgDays || $avgDays <= 0) return 0.5;

        // < 7 jours = très rapide = score élevé
        // > 60 jours = lent = score bas
        if ($avgDays <= 7) return 1.0;
        if ($avgDays <= 14) return 0.8;
        if ($avgDays <= 30) return 0.6;
        if ($avgDays <= 60) return 0.4;
        return 0.2;
    }

    /**
     * Score basé sur la durée moyenne des contrats (0-1)
     */
    protected function calculateDurationScore(Box $box): float
    {
        $avgDuration = \App\Models\Contract::where('box_id', $box->id)
            ->whereNotNull('end_date')
            ->avg(\DB::raw('DATEDIFF(end_date, start_date)'));

        if (!$avgDuration) return 0.5;

        // Contrats longs = clients fidèles = possibilité d'augmentation
        $months = $avgDuration / 30;

        if ($months >= 12) return 0.9;
        if ($months >= 6) return 0.7;
        if ($months >= 3) return 0.5;
        return 0.3;
    }

    /**
     * Calculer le niveau de confiance de la recommandation
     */
    protected function calculatePriceConfidence(array $factors, Site $site): float
    {
        $confidence = 0.5;

        // Plus de données historiques = plus de confiance
        $contractCount = \App\Models\Contract::where('site_id', $site->id)->count();
        if ($contractCount > 100) $confidence += 0.2;
        elseif ($contractCount > 50) $confidence += 0.15;
        elseif ($contractCount > 20) $confidence += 0.1;

        // Données concurrentielles disponibles
        if ($factors['competitor'] !== 0.5) $confidence += 0.1;

        // Stabilité des facteurs (écart-type bas)
        $variance = $this->calculateVariance(array_values($factors));
        if ($variance < 0.1) $confidence += 0.1;

        return min(0.95, $confidence);
    }

    /**
     * Calculer la variance d'un tableau
     */
    protected function calculateVariance(array $values): float
    {
        $count = count($values);
        if ($count === 0) return 0;

        $mean = array_sum($values) / $count;
        $squaredDiffs = array_map(fn($v) => pow($v - $mean, 2), $values);

        return array_sum($squaredDiffs) / $count;
    }

    /**
     * Obtenir les ajustements actifs pour affichage
     */
    protected function getActiveAdjustments(array $factors): array
    {
        $adjustments = [];

        if ($factors['occupancy'] > 0.7) $adjustments['high_occupation'] = 1;
        if ($factors['occupancy'] < 0.4) $adjustments['low_occupation'] = -1;
        if ($factors['demand'] > 0.7) $adjustments['high_demand'] = 1;
        if ($factors['seasonality'] > 0.7) $adjustments['high_season'] = 1;
        if ($factors['seasonality'] < 0.4) $adjustments['low_season'] = -1;
        if ($factors['competitor'] > 0.7) $adjustments['competitor_advantage'] = 1;

        return $adjustments;
    }

    /**
     * Générer le texte de recommandation
     */
    protected function getRecommendationText(float $changePercent, float $confidence): string
    {
        $confidenceText = $confidence > 0.8 ? 'haute' : ($confidence > 0.6 ? 'moyenne' : 'faible');

        if ($changePercent > 10) {
            return "Forte augmentation recommandée (confiance {$confidenceText})";
        } elseif ($changePercent > 5) {
            return "Augmentation modérée recommandée (confiance {$confidenceText})";
        } elseif ($changePercent > 0) {
            return "Légère augmentation possible (confiance {$confidenceText})";
        } elseif ($changePercent > -5) {
            return "Prix optimal - aucun changement nécessaire";
        } elseif ($changePercent > -10) {
            return "Réduction modérée recommandée pour stimuler les ventes";
        } else {
            return "Forte réduction recommandée - prix trop élevé vs marché";
        }
    }

    /**
     * Appliquer automatiquement les prix optimaux pour un site
     */
    public function autoApplyOptimalPrices(Site $site, array $options = []): array
    {
        $dryRun = $options['dry_run'] ?? true;
        $minConfidence = $options['min_confidence'] ?? 0.7;
        $minChangePercent = $options['min_change_percent'] ?? 3;
        $maxChangePercent = $options['max_change_percent'] ?? 15;

        $results = [
            'site_id' => $site->id,
            'site_name' => $site->name,
            'dry_run' => $dryRun,
            'applied' => [],
            'skipped' => [],
            'errors' => [],
            'summary' => [
                'total_analyzed' => 0,
                'total_applied' => 0,
                'total_revenue_impact' => 0,
            ],
        ];

        $boxes = $site->boxes()->where('status', 'available')->get();
        $results['summary']['total_analyzed'] = $boxes->count();

        \DB::beginTransaction();

        try {
            foreach ($boxes as $box) {
                $pricing = $this->calculateAdvancedOptimalPrice($box);

                // Vérifications de sécurité
                if ($pricing['confidence'] < $minConfidence) {
                    $results['skipped'][] = [
                        'box_id' => $box->id,
                        'reason' => "Confiance trop basse ({$pricing['confidence']})",
                    ];
                    continue;
                }

                if (abs($pricing['adjustment_percent']) < $minChangePercent) {
                    $results['skipped'][] = [
                        'box_id' => $box->id,
                        'reason' => "Changement trop faible ({$pricing['adjustment_percent']}%)",
                    ];
                    continue;
                }

                if (abs($pricing['adjustment_percent']) > $maxChangePercent) {
                    $results['skipped'][] = [
                        'box_id' => $box->id,
                        'reason' => "Changement trop important ({$pricing['adjustment_percent']}%) - validation manuelle requise",
                    ];
                    continue;
                }

                if (!$dryRun) {
                    // Enregistrer l'historique
                    \App\Models\PriceHistory::create([
                        'box_id' => $box->id,
                        'old_price' => $box->current_price,
                        'new_price' => $pricing['optimal_price'],
                        'change_reason' => 'auto_optimization',
                        'factors' => json_encode($pricing['factors']),
                        'confidence' => $pricing['confidence'],
                        'applied_by' => 'system',
                        'applied_at' => now(),
                    ]);

                    // Appliquer le nouveau prix
                    $box->update(['current_price' => $pricing['optimal_price']]);
                }

                $results['applied'][] = $pricing;
                $results['summary']['total_applied']++;
                $results['summary']['total_revenue_impact'] += $pricing['estimated_revenue_impact'];
            }

            if (!$dryRun) {
                \DB::commit();

                Log::info('Revenue Management: Auto-applied optimal prices', [
                    'site_id' => $site->id,
                    'boxes_updated' => $results['summary']['total_applied'],
                    'revenue_impact' => $results['summary']['total_revenue_impact'],
                ]);
            }

        } catch (\Exception $e) {
            \DB::rollBack();

            Log::error('Revenue Management: Auto-apply failed', [
                'site_id' => $site->id,
                'error' => $e->getMessage(),
            ]);

            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Stocker les données de benchmark concurrentiel
     */
    public function storeCompetitorBenchmark(Site $site, array $data): void
    {
        foreach ($data as $sizeCategory => $prices) {
            $key = "competitor_avg_price_{$site->id}_{$sizeCategory}";
            $avgPrice = is_array($prices) ? array_sum($prices) / count($prices) : $prices;

            \Illuminate\Support\Facades\Cache::put($key, $avgPrice, now()->addDays(30));
        }

        Log::info('Revenue Management: Competitor benchmark updated', [
            'site_id' => $site->id,
            'categories' => array_keys($data),
        ]);
    }

    /**
     * Obtenir toutes les optimisations pour un tenant
     */
    public function getAllOptimizations(int $tenantId): array
    {
        $sites = Site::where('tenant_id', $tenantId)->with('boxes')->get();
        $allOptimizations = [];

        foreach ($sites as $site) {
            $availableBoxes = $site->boxes->where('status', 'available');

            foreach ($availableBoxes as $box) {
                $optimization = $this->calculateAdvancedOptimalPrice($box);

                // Ne garder que les optimisations significatives
                if (abs($optimization['adjustment_percent']) >= 2) {
                    $allOptimizations[] = $optimization;
                }
            }
        }

        // Trier par impact potentiel décroissant
        usort($allOptimizations, fn($a, $b) => abs($b['estimated_revenue_impact']) - abs($a['estimated_revenue_impact']));

        return $allOptimizations;
    }

    /**
     * Calculer le résumé des optimisations
     */
    public function getOptimizationSummary(array $optimizations): array
    {
        $count = count($optimizations);

        if ($count === 0) {
            return [
                'boxes_analyzed' => 0,
                'boxes_to_optimize' => 0,
                'total_potential_revenue' => 0,
                'average_adjustment' => 0,
                'average_confidence' => 0,
            ];
        }

        $totalPotentialRevenue = array_sum(array_column($optimizations, 'estimated_revenue_impact'));
        $avgAdjustment = array_sum(array_column($optimizations, 'adjustment_percent')) / $count;
        $avgConfidence = array_sum(array_column($optimizations, 'confidence')) / $count;

        return [
            'boxes_analyzed' => $count,
            'boxes_to_optimize' => count(array_filter($optimizations, fn($o) => abs($o['adjustment_percent']) >= 3)),
            'total_potential_revenue' => round($totalPotentialRevenue, 2),
            'average_adjustment' => round($avgAdjustment, 1),
            'average_confidence' => round($avgConfidence * 100, 0),
        ];
    }
}
