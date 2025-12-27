<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Notification;
use App\Models\Prospect;
use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * AI-Enhanced Lead Scoring Service
 *
 * Uses machine learning patterns and predictive analytics to score leads
 * based on historical conversion data and behavioral patterns.
 */
class AILeadScoringService
{
    /**
     * Feature weights learned from historical data
     * In production, these would be trained via ML model
     */
    protected array $defaultWeights = [
        // Behavioral signals (highest impact)
        'behavior' => [
            'page_views' => 0.5,            // per unique page
            'time_on_site' => 2.0,          // per minute
            'calculator_used' => 15,
            'pricing_page_viewed' => 10,
            'box_details_viewed' => 8,
            'contact_form_opened' => 5,
            'quote_requested' => 20,
            'brochure_downloaded' => 12,
            'visit_scheduled' => 25,
            'chat_initiated' => 10,
            'return_visits' => 8,           // per return visit
            'mobile_app_downloaded' => 15,
        ],
        // Engagement signals
        'engagement' => [
            'email_opened' => 3,            // per email
            'email_clicked' => 6,           // per click
            'sms_responded' => 10,
            'call_answered' => 8,
            'social_engagement' => 4,
            'referral_link_used' => 15,
        ],
        // Profile signals
        'profile' => [
            'is_business' => 12,
            'company_email' => 8,
            'phone_provided' => 5,
            'address_provided' => 4,
            'budget_high' => 15,            // > 200 EUR/month
            'budget_medium' => 8,           // 100-200 EUR/month
            'local_area' => 6,
            'referred_by_customer' => 18,
        ],
        // Timing signals
        'timing' => [
            'urgency_immediate' => 20,      // needs within 7 days
            'urgency_soon' => 12,           // needs within 30 days
            'urgency_planning' => 5,        // needs in 1-3 months
            'created_recently' => 8,        // < 48h
            'business_hours_activity' => 3, // active during business hours
            'weekend_activity' => 2,        // researching on weekend
        ],
        // Historical pattern signals
        'historical' => [
            'similar_converted' => 10,      // similar leads converted
            'same_source_converted' => 8,   // same acquisition source
            'seasonal_peak' => 5,           // high season for rentals
        ],
    ];

    /**
     * Score thresholds with recommended actions
     */
    protected array $thresholds = [
        'very_hot' => ['min' => 85, 'action' => 'Contact immédiatement - appel téléphonique', 'color' => '#DC2626'],
        'hot' => ['min' => 70, 'action' => 'Contacter dans les 2 heures', 'color' => '#F97316'],
        'warm' => ['min' => 50, 'action' => 'Email personnalisé + suivi 24h', 'color' => '#F59E0B'],
        'lukewarm' => ['min' => 30, 'action' => 'Séquence nurturing automatique', 'color' => '#84CC16'],
        'cold' => ['min' => 0, 'action' => 'Ajouter à campagne de masse', 'color' => '#6B7280'],
    ];

    /**
     * Calculate AI-enhanced lead score
     */
    public function calculateScore($entity, string $type = 'lead'): array
    {
        $tenantId = $entity->tenant_id;
        $weights = $this->getOptimizedWeights($tenantId);

        $scoreBreakdown = [
            'behavior' => 0,
            'engagement' => 0,
            'profile' => 0,
            'timing' => 0,
            'historical' => 0,
            'ml_adjustment' => 0,
        ];

        $metadata = $entity->metadata ?? [];
        $factors = [];

        // === BEHAVIORAL SCORING ===
        $behaviorScore = $this->calculateBehaviorScore($entity, $metadata, $weights['behavior'], $factors);
        $scoreBreakdown['behavior'] = min($behaviorScore, 35);

        // === ENGAGEMENT SCORING ===
        $engagementScore = $this->calculateEngagementScore($entity, $metadata, $weights['engagement'], $factors);
        $scoreBreakdown['engagement'] = min($engagementScore, 20);

        // === PROFILE SCORING ===
        $profileScore = $this->calculateProfileScore($entity, $weights['profile'], $factors);
        $scoreBreakdown['profile'] = min($profileScore, 20);

        // === TIMING SCORING ===
        $timingScore = $this->calculateTimingScore($entity, $weights['timing'], $factors);
        $scoreBreakdown['timing'] = min($timingScore, 15);

        // === HISTORICAL PATTERN SCORING ===
        $historicalScore = $this->calculateHistoricalScore($entity, $tenantId, $weights['historical'], $factors);
        $scoreBreakdown['historical'] = min($historicalScore, 10);

        // === ML ADJUSTMENT (Predictive) ===
        $mlAdjustment = $this->calculateMLAdjustment($entity, $tenantId, $factors);
        $scoreBreakdown['ml_adjustment'] = $mlAdjustment;

        // Calculate total score
        $rawScore = array_sum($scoreBreakdown);
        $finalScore = min(max($rawScore, 0), 100);

        // Determine priority and recommended action
        $priority = $this->getPriority($finalScore);
        $recommendation = $this->getRecommendation($finalScore, $priority);

        // Calculate conversion probability
        $conversionProbability = $this->predictConversionProbability($finalScore, $factors, $tenantId);

        return [
            'score' => $finalScore,
            'breakdown' => $scoreBreakdown,
            'priority' => $priority,
            'recommendation' => $recommendation,
            'conversion_probability' => $conversionProbability,
            'factors' => array_slice($factors, 0, 10), // Top 10 factors
            'insights' => $this->generateInsights($scoreBreakdown, $factors, $priority),
        ];
    }

    /**
     * Calculate behavior score
     */
    protected function calculateBehaviorScore($entity, array $metadata, array $weights, array &$factors): float
    {
        $score = 0;

        // Page views
        $pageViews = $metadata['page_views'] ?? $metadata['visit_count'] ?? 1;
        if ($pageViews > 1) {
            $viewScore = min($pageViews * $weights['page_views'], 5);
            $score += $viewScore;
            if ($pageViews > 3) {
                $factors[] = ['name' => 'Multiple page views', 'impact' => '+' . round($viewScore), 'type' => 'positive'];
            }
        }

        // Time on site
        $timeOnSite = $metadata['time_on_site_minutes'] ?? 0;
        if ($timeOnSite > 2) {
            $timeScore = min($timeOnSite * $weights['time_on_site'], 10);
            $score += $timeScore;
            $factors[] = ['name' => 'Engaged visitor (' . $timeOnSite . ' min)', 'impact' => '+' . round($timeScore), 'type' => 'positive'];
        }

        // Calculator usage
        if ($metadata['used_calculator'] ?? $metadata['calculator_used'] ?? false) {
            $score += $weights['calculator_used'];
            $factors[] = ['name' => 'Used size calculator', 'impact' => '+' . $weights['calculator_used'], 'type' => 'positive'];
        }

        // Pricing page
        if ($metadata['visited_pricing'] ?? $metadata['pricing_page_viewed'] ?? false) {
            $score += $weights['pricing_page_viewed'];
            $factors[] = ['name' => 'Viewed pricing page', 'impact' => '+' . $weights['pricing_page_viewed'], 'type' => 'positive'];
        }

        // Quote requested
        if ($metadata['requested_quote'] ?? $metadata['quote_requested'] ?? false) {
            $score += $weights['quote_requested'];
            $factors[] = ['name' => 'Requested quote', 'impact' => '+' . $weights['quote_requested'], 'type' => 'positive'];
        }

        // Visit scheduled
        if ($metadata['scheduled_visit'] ?? $metadata['visit_scheduled'] ?? false) {
            $score += $weights['visit_scheduled'];
            $factors[] = ['name' => 'Scheduled site visit', 'impact' => '+' . $weights['visit_scheduled'], 'type' => 'positive'];
        }

        // Return visits
        $returnVisits = $metadata['return_visits'] ?? 0;
        if ($returnVisits > 0) {
            $returnScore = min($returnVisits * $weights['return_visits'], 16);
            $score += $returnScore;
            $factors[] = ['name' => "Return visitor ($returnVisits times)", 'impact' => '+' . round($returnScore), 'type' => 'positive'];
        }

        // Brochure download
        if ($metadata['downloaded_brochure'] ?? false) {
            $score += $weights['brochure_downloaded'];
            $factors[] = ['name' => 'Downloaded brochure', 'impact' => '+' . $weights['brochure_downloaded'], 'type' => 'positive'];
        }

        return $score;
    }

    /**
     * Calculate engagement score
     */
    protected function calculateEngagementScore($entity, array $metadata, array $weights, array &$factors): float
    {
        $score = 0;

        // Email engagement
        $emailsOpened = $metadata['emails_opened'] ?? 0;
        if ($emailsOpened > 0) {
            $openScore = min($emailsOpened * $weights['email_opened'], 12);
            $score += $openScore;
            $factors[] = ['name' => "Opened $emailsOpened emails", 'impact' => '+' . round($openScore), 'type' => 'positive'];
        }

        $emailsClicked = $metadata['emails_clicked'] ?? 0;
        if ($emailsClicked > 0) {
            $clickScore = min($emailsClicked * $weights['email_clicked'], 15);
            $score += $clickScore;
            $factors[] = ['name' => "Clicked $emailsClicked email links", 'impact' => '+' . round($clickScore), 'type' => 'positive'];
        }

        // Referral
        if ($metadata['referred_by'] ?? $entity->referral_source ?? false) {
            $score += $weights['referral_link_used'];
            $factors[] = ['name' => 'Referred by existing customer', 'impact' => '+' . $weights['referral_link_used'], 'type' => 'positive'];
        }

        // SMS response
        if ($metadata['sms_responded'] ?? false) {
            $score += $weights['sms_responded'];
            $factors[] = ['name' => 'Responded to SMS', 'impact' => '+' . $weights['sms_responded'], 'type' => 'positive'];
        }

        return $score;
    }

    /**
     * Calculate profile score
     */
    protected function calculateProfileScore($entity, array $weights, array &$factors): float
    {
        $score = 0;

        // Business vs Individual
        $customerType = $entity->type ?? $entity->customer_type ?? 'individual';
        if (in_array($customerType, ['company', 'business', 'professional'])) {
            $score += $weights['is_business'];
            $factors[] = ['name' => 'Business customer', 'impact' => '+' . $weights['is_business'], 'type' => 'positive'];
        }

        // Phone provided
        if (!empty($entity->phone)) {
            $score += $weights['phone_provided'];
            $factors[] = ['name' => 'Phone number provided', 'impact' => '+' . $weights['phone_provided'], 'type' => 'positive'];
        }

        // Company email domain
        $email = $entity->email ?? '';
        $freeEmailDomains = ['gmail.com', 'hotmail.com', 'yahoo.com', 'outlook.com', 'orange.fr', 'free.fr', 'wanadoo.fr', 'icloud.com', 'live.com'];
        $emailDomain = strtolower(substr(strrchr($email, "@"), 1));
        if ($email && !in_array($emailDomain, $freeEmailDomains)) {
            $score += $weights['company_email'];
            $factors[] = ['name' => 'Business email domain', 'impact' => '+' . $weights['company_email'], 'type' => 'positive'];
        }

        // Budget
        $budget = $entity->budget ?? $entity->estimated_budget ?? 0;
        if ($budget >= 200) {
            $score += $weights['budget_high'];
            $factors[] = ['name' => 'High budget (≥200€)', 'impact' => '+' . $weights['budget_high'], 'type' => 'positive'];
        } elseif ($budget >= 100) {
            $score += $weights['budget_medium'];
            $factors[] = ['name' => 'Medium budget (100-200€)', 'impact' => '+' . $weights['budget_medium'], 'type' => 'positive'];
        }

        // Address completeness
        if (!empty($entity->address) || !empty($entity->city)) {
            $score += $weights['address_provided'];
        }

        return $score;
    }

    /**
     * Calculate timing score
     */
    protected function calculateTimingScore($entity, array $weights, array &$factors): float
    {
        $score = 0;

        // Urgency based on desired move-in date
        $moveInDate = $entity->move_in_date ?? $entity->desired_start_date ?? null;
        if ($moveInDate) {
            $moveInDate = is_string($moveInDate) ? new \DateTime($moveInDate) : $moveInDate;
            $daysUntil = now()->diffInDays($moveInDate, false);

            if ($daysUntil >= 0 && $daysUntil <= 7) {
                $score += $weights['urgency_immediate'];
                $factors[] = ['name' => 'Needs storage within 1 week', 'impact' => '+' . $weights['urgency_immediate'], 'type' => 'positive'];
            } elseif ($daysUntil > 7 && $daysUntil <= 30) {
                $score += $weights['urgency_soon'];
                $factors[] = ['name' => 'Needs storage within 1 month', 'impact' => '+' . $weights['urgency_soon'], 'type' => 'positive'];
            } elseif ($daysUntil > 30 && $daysUntil <= 90) {
                $score += $weights['urgency_planning'];
            }
        }

        // Recently created
        $hoursSinceCreation = now()->diffInHours($entity->created_at);
        if ($hoursSinceCreation <= 48) {
            $score += $weights['created_recently'];
            $factors[] = ['name' => 'Recently created lead', 'impact' => '+' . $weights['created_recently'], 'type' => 'positive'];
        }

        // Activity timing
        $lastActivityHour = $entity->updated_at ? $entity->updated_at->hour : null;
        if ($lastActivityHour !== null && $lastActivityHour >= 9 && $lastActivityHour <= 18) {
            $score += $weights['business_hours_activity'];
        }

        return $score;
    }

    /**
     * Calculate historical pattern score
     */
    protected function calculateHistoricalScore($entity, int $tenantId, array $weights, array &$factors): float
    {
        $score = 0;

        // Check if similar leads have converted
        $similarityScore = $this->findSimilarConvertedLeads($entity, $tenantId);
        if ($similarityScore > 0.7) {
            $score += $weights['similar_converted'];
            $factors[] = ['name' => 'Similar leads converted', 'impact' => '+' . $weights['similar_converted'], 'type' => 'positive'];
        }

        // Same source conversion rate
        $source = $entity->source ?? 'direct';
        $sourceConversionRate = $this->getSourceConversionRate($tenantId, $source);
        if ($sourceConversionRate > 0.15) { // > 15% conversion
            $score += $weights['same_source_converted'];
            $factors[] = ['name' => "High-converting source ({$source})", 'impact' => '+' . $weights['same_source_converted'], 'type' => 'positive'];
        }

        // Seasonal peak
        $month = now()->month;
        $peakMonths = [6, 7, 8, 9]; // Summer + September (moving season)
        if (in_array($month, $peakMonths)) {
            $score += $weights['seasonal_peak'];
            $factors[] = ['name' => 'Peak season', 'impact' => '+' . $weights['seasonal_peak'], 'type' => 'positive'];
        }

        return $score;
    }

    /**
     * Calculate ML adjustment based on pattern recognition
     */
    protected function calculateMLAdjustment($entity, int $tenantId, array $factors): float
    {
        $adjustment = 0;

        // Penalty for bounce indicators
        $metadata = $entity->metadata ?? [];
        if (($metadata['bounced'] ?? false) || ($metadata['unsubscribed'] ?? false)) {
            $adjustment -= 15;
            $factors[] = ['name' => 'Email bounced/unsubscribed', 'impact' => '-15', 'type' => 'negative'];
        }

        // Penalty for old leads without engagement
        $daysSinceCreation = now()->diffInDays($entity->created_at);
        $lastEngagement = $entity->last_activity_at ?? $entity->updated_at;
        $daysSinceEngagement = now()->diffInDays($lastEngagement);

        if ($daysSinceCreation > 30 && $daysSinceEngagement > 14) {
            $adjustment -= 10;
            $factors[] = ['name' => 'No recent engagement', 'impact' => '-10', 'type' => 'negative'];
        }

        // Bonus for repeated interactions in short time
        $recentInteractions = $metadata['interactions_last_7_days'] ?? 0;
        if ($recentInteractions >= 3) {
            $adjustment += 5;
            $factors[] = ['name' => 'High recent activity', 'impact' => '+5', 'type' => 'positive'];
        }

        // Conversion velocity bonus
        if ($daysSinceCreation <= 3 && count($factors) >= 5) {
            $adjustment += 5;
            $factors[] = ['name' => 'Fast engagement pattern', 'impact' => '+5', 'type' => 'positive'];
        }

        return $adjustment;
    }

    /**
     * Find similarity score with converted leads
     */
    protected function findSimilarConvertedLeads($entity, int $tenantId): float
    {
        $cacheKey = "lead_similarity_{$tenantId}_{$entity->id}";

        return Cache::remember($cacheKey, 3600, function () use ($entity, $tenantId) {
            // Get converted customers from last 6 months with avg contract price (avoid N+1)
            $convertedCustomers = Customer::where('tenant_id', $tenantId)
                ->where('created_at', '>=', now()->subMonths(6))
                ->whereHas('contracts')
                ->withAvg('contracts', 'monthly_price')
                ->get();

            if ($convertedCustomers->isEmpty()) {
                return 0;
            }

            $matches = 0;
            $total = 0;

            foreach ($convertedCustomers as $customer) {
                $total++;
                $similarity = 0;

                // Compare type
                $entityType = $entity->type ?? $entity->customer_type ?? 'individual';
                $customerType = $customer->type ?? 'individual';
                if ($entityType === $customerType) {
                    $similarity += 0.3;
                }

                // Compare source
                if (($entity->source ?? null) === ($customer->source ?? null)) {
                    $similarity += 0.2;
                }

                // Compare budget range (using pre-loaded avg from withAvg to avoid N+1)
                $entityBudget = $entity->budget ?? 0;
                $customerBudget = $customer->contracts_avg_monthly_price ?? 0;
                if (abs($entityBudget - $customerBudget) < 50) {
                    $similarity += 0.3;
                }

                // Compare email domain type
                $entityEmail = $entity->email ?? '';
                $customerEmail = $customer->email ?? '';
                $entityIsBusiness = !$this->isPersonalEmail($entityEmail);
                $customerIsBusiness = !$this->isPersonalEmail($customerEmail);
                if ($entityIsBusiness === $customerIsBusiness) {
                    $similarity += 0.2;
                }

                if ($similarity >= 0.6) {
                    $matches++;
                }
            }

            return $total > 0 ? $matches / $total : 0;
        });
    }

    /**
     * Get source conversion rate
     */
    protected function getSourceConversionRate(int $tenantId, string $source): float
    {
        $cacheKey = "source_conversion_{$tenantId}_{$source}";

        return Cache::remember($cacheKey, 3600, function () use ($tenantId, $source) {
            $total = Lead::where('tenant_id', $tenantId)
                ->where('source', $source)
                ->count();

            if ($total === 0) {
                return 0;
            }

            $converted = Lead::where('tenant_id', $tenantId)
                ->where('source', $source)
                ->whereNotNull('converted_at')
                ->count();

            return $converted / $total;
        });
    }

    /**
     * Check if email is personal domain
     */
    protected function isPersonalEmail(string $email): bool
    {
        $personalDomains = ['gmail.com', 'hotmail.com', 'yahoo.com', 'outlook.com', 'orange.fr', 'free.fr', 'wanadoo.fr', 'icloud.com', 'live.com', 'msn.com'];
        $domain = strtolower(substr(strrchr($email, "@"), 1));
        return in_array($domain, $personalDomains);
    }

    /**
     * Get optimized weights for tenant (could be ML-trained)
     */
    protected function getOptimizedWeights(int $tenantId): array
    {
        // In production, this would load ML-trained weights per tenant
        // For now, return default weights
        $cacheKey = "scoring_weights_{$tenantId}";

        return Cache::remember($cacheKey, 86400, function () use ($tenantId) {
            // Check if tenant has custom weights
            $tenant = Tenant::find($tenantId);
            $customWeights = $tenant?->settings['lead_scoring_weights'] ?? null;

            if ($customWeights) {
                return array_merge_recursive($this->defaultWeights, $customWeights);
            }

            return $this->defaultWeights;
        });
    }

    /**
     * Get priority based on score
     */
    protected function getPriority(int $score): string
    {
        foreach ($this->thresholds as $priority => $config) {
            if ($score >= $config['min']) {
                return $priority;
            }
        }
        return 'cold';
    }

    /**
     * Get recommendation for lead
     */
    protected function getRecommendation(int $score, string $priority): array
    {
        $config = $this->thresholds[$priority];

        return [
            'action' => $config['action'],
            'color' => $config['color'],
            'urgency' => $priority === 'very_hot' ? 'immediate' : ($priority === 'hot' ? 'high' : ($priority === 'warm' ? 'medium' : 'low')),
        ];
    }

    /**
     * Predict conversion probability
     */
    protected function predictConversionProbability(int $score, array $factors, int $tenantId): float
    {
        // Base probability from score
        $baseProbability = $score / 100;

        // Adjust based on tenant's historical conversion rate
        $tenantConversionRate = $this->getTenantConversionRate($tenantId);

        // Weight: 70% base probability, 30% historical
        $probability = ($baseProbability * 0.7) + ($tenantConversionRate * 0.3);

        // Boost for high-impact factors
        $positiveFactors = count(array_filter($factors, fn($f) => $f['type'] === 'positive'));
        if ($positiveFactors >= 5) {
            $probability *= 1.1;
        }

        return min(round($probability * 100, 1), 95); // Cap at 95%
    }

    /**
     * Get tenant's historical conversion rate
     */
    protected function getTenantConversionRate(int $tenantId): float
    {
        return Cache::remember("tenant_conversion_{$tenantId}", 86400, function () use ($tenantId) {
            $totalLeads = Lead::where('tenant_id', $tenantId)
                ->where('created_at', '>=', now()->subMonths(6))
                ->count();

            if ($totalLeads === 0) {
                return 0.1; // Default 10%
            }

            $converted = Lead::where('tenant_id', $tenantId)
                ->where('created_at', '>=', now()->subMonths(6))
                ->whereNotNull('converted_at')
                ->count();

            return $converted / $totalLeads;
        });
    }

    /**
     * Generate human-readable insights
     */
    protected function generateInsights(array $breakdown, array $factors, string $priority): array
    {
        $insights = [];

        // Main insight based on priority
        switch ($priority) {
            case 'very_hot':
                $insights[] = '🔥 Lead très chaud ! Ce prospect montre des signaux d\'achat imminents.';
                break;
            case 'hot':
                $insights[] = '🔥 Lead chaud avec forte intention d\'achat. Contactez-le rapidement.';
                break;
            case 'warm':
                $insights[] = '🌡️ Lead en phase de recherche active. Un suivi personnalisé augmentera les chances de conversion.';
                break;
            case 'lukewarm':
                $insights[] = '💡 Lead au début du parcours. Nourrissez-le avec du contenu pertinent.';
                break;
            default:
                $insights[] = '❄️ Lead froid. Incluez-le dans une campagne de nurturing automatisée.';
        }

        // Specific insights based on breakdown
        if ($breakdown['behavior'] >= 25) {
            $insights[] = '✓ Comportement très engagé sur le site';
        } elseif ($breakdown['behavior'] < 10) {
            $insights[] = '⚠️ Peu d\'interactions avec le site - besoin de plus d\'engagement';
        }

        if ($breakdown['profile'] >= 15) {
            $insights[] = '✓ Profil qualifié (entreprise ou budget élevé)';
        }

        if ($breakdown['timing'] >= 12) {
            $insights[] = '⏰ Besoin urgent - timing idéal pour convertir';
        }

        // Factor-based insights
        $positiveFactors = array_filter($factors, fn($f) => $f['type'] === 'positive');
        $negativeFactors = array_filter($factors, fn($f) => $f['type'] === 'negative');

        if (count($negativeFactors) > 0) {
            $insights[] = '⚠️ Points d\'attention : ' . implode(', ', array_column($negativeFactors, 'name'));
        }

        // Top strengths
        if (count($positiveFactors) > 3) {
            $topFactors = array_slice($positiveFactors, 0, 3);
            $insights[] = '💪 Points forts : ' . implode(', ', array_column($topFactors, 'name'));
        }

        return $insights;
    }

    /**
     * Batch calculate scores for all leads/prospects
     */
    public function batchCalculateScores(int $tenantId, bool $notifyHotLeads = true): array
    {
        $results = [
            'processed' => 0,
            'very_hot' => 0,
            'hot' => 0,
            'warm' => 0,
            'errors' => 0,
        ];

        // Process Leads
        $leads = Lead::where('tenant_id', $tenantId)
            ->whereNull('converted_at')
            ->get();

        foreach ($leads as $lead) {
            try {
                $scoreResult = $this->calculateScore($lead, 'lead');

                $previousPriority = $lead->priority;
                $lead->update([
                    'score' => $scoreResult['score'],
                    'priority' => $scoreResult['priority'],
                    'conversion_probability' => $scoreResult['conversion_probability'],
                    'score_breakdown' => $scoreResult['breakdown'],
                    'score_factors' => $scoreResult['factors'],
                    'score_calculated_at' => now(),
                ]);

                $results['processed']++;
                $results[$scoreResult['priority']] = ($results[$scoreResult['priority']] ?? 0) + 1;

                // Notify for new hot leads
                if ($notifyHotLeads && in_array($scoreResult['priority'], ['very_hot', 'hot']) && $previousPriority !== $scoreResult['priority']) {
                    $this->notifyHotLead($lead, $scoreResult);
                }
            } catch (\Exception $e) {
                $results['errors']++;
                Log::error('Lead scoring error', ['lead_id' => $lead->id, 'error' => $e->getMessage()]);
            }
        }

        // Process Prospects similarly
        $prospects = Prospect::where('tenant_id', $tenantId)
            ->whereNotIn('status', ['converted', 'lost'])
            ->get();

        foreach ($prospects as $prospect) {
            try {
                $scoreResult = $this->calculateScore($prospect, 'prospect');

                $previousPriority = $prospect->priority ?? 'cold';
                $prospect->update([
                    'score' => $scoreResult['score'],
                    'priority' => $scoreResult['priority'],
                    'conversion_probability' => $scoreResult['conversion_probability'],
                    'score_breakdown' => $scoreResult['breakdown'],
                    'score_factors' => $scoreResult['factors'],
                    'score_calculated_at' => now(),
                ]);

                $results['processed']++;
                $results[$scoreResult['priority']] = ($results[$scoreResult['priority']] ?? 0) + 1;

                if ($notifyHotLeads && in_array($scoreResult['priority'], ['very_hot', 'hot']) && $previousPriority !== $scoreResult['priority']) {
                    $this->notifyHotProspect($prospect, $scoreResult);
                }
            } catch (\Exception $e) {
                $results['errors']++;
                Log::error('Prospect scoring error', ['prospect_id' => $prospect->id, 'error' => $e->getMessage()]);
            }
        }

        return $results;
    }

    /**
     * Notify about hot lead
     */
    protected function notifyHotLead(Lead $lead, array $scoreResult): void
    {
        $name = $lead->company_name ?? ($lead->first_name . ' ' . $lead->last_name);

        Notification::create([
            'tenant_id' => $lead->tenant_id,
            'type' => 'ai_hot_lead_alert',
            'title' => "🔥 Lead IA: {$name} ({$scoreResult['score']}/100)",
            'message' => $scoreResult['recommendation']['action'] . "\n\nProbabilité de conversion: {$scoreResult['conversion_probability']}%",
            'data' => [
                'lead_id' => $lead->id,
                'score' => $scoreResult['score'],
                'priority' => $scoreResult['priority'],
                'conversion_probability' => $scoreResult['conversion_probability'],
                'factors' => $scoreResult['factors'],
                'insights' => $scoreResult['insights'],
            ],
            'priority' => 'high',
            'action_url' => "/tenant/crm/leads/{$lead->id}",
        ]);
    }

    /**
     * Notify about hot prospect
     */
    protected function notifyHotProspect(Prospect $prospect, array $scoreResult): void
    {
        $name = $prospect->company_name ?? ($prospect->first_name . ' ' . $prospect->last_name);

        Notification::create([
            'tenant_id' => $prospect->tenant_id,
            'type' => 'ai_hot_prospect_alert',
            'title' => "🔥 Prospect IA: {$name} ({$scoreResult['score']}/100)",
            'message' => $scoreResult['recommendation']['action'] . "\n\nProbabilité de conversion: {$scoreResult['conversion_probability']}%",
            'data' => [
                'prospect_id' => $prospect->id,
                'score' => $scoreResult['score'],
                'priority' => $scoreResult['priority'],
                'conversion_probability' => $scoreResult['conversion_probability'],
                'factors' => $scoreResult['factors'],
                'insights' => $scoreResult['insights'],
            ],
            'priority' => 'high',
            'action_url' => "/tenant/crm/prospects/{$prospect->id}",
        ]);
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats(int $tenantId): array
    {
        $leads = Lead::where('tenant_id', $tenantId)->whereNull('converted_at');
        $prospects = Prospect::where('tenant_id', $tenantId)->whereNotIn('status', ['converted', 'lost']);

        return [
            'total_leads' => (clone $leads)->count() + (clone $prospects)->count(),
            'very_hot' => (clone $leads)->where('priority', 'very_hot')->count() + (clone $prospects)->where('priority', 'very_hot')->count(),
            'hot' => (clone $leads)->where('priority', 'hot')->count() + (clone $prospects)->where('priority', 'hot')->count(),
            'warm' => (clone $leads)->where('priority', 'warm')->count() + (clone $prospects)->where('priority', 'warm')->count(),
            'cold' => (clone $leads)->where('priority', 'cold')->count() + (clone $prospects)->where('priority', 'cold')->count(),
            'avg_score' => round((clone $leads)->avg('score') ?? 0, 1),
            'avg_conversion_probability' => round((clone $leads)->avg('conversion_probability') ?? 0, 1),
            'high_probability_leads' => (clone $leads)->where('conversion_probability', '>=', 50)->count(),
        ];
    }
}
