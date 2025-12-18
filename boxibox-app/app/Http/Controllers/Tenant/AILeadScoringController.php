<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Prospect;
use App\Models\Site;
use App\Services\AILeadScoringService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AILeadScoringController extends Controller
{
    public function __construct(
        protected AILeadScoringService $scoringService
    ) {}

    /**
     * AI Lead Scoring Dashboard
     */
    public function dashboard(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $stats = $this->scoringService->getDashboardStats($tenantId);

        // Get hot leads
        $hotLeads = Lead::where('tenant_id', $tenantId)
            ->whereNull('converted_at')
            ->whereIn('priority', ['very_hot', 'hot'])
            ->with(['site', 'assignedTo'])
            ->orderByDesc('score')
            ->limit(10)
            ->get();

        // Get hot prospects
        $hotProspects = Prospect::where('tenant_id', $tenantId)
            ->whereNotIn('status', ['converted', 'lost'])
            ->whereIn('priority', ['very_hot', 'hot'])
            ->orderByDesc('score')
            ->limit(10)
            ->get();

        // Score distribution
        $scoreDistribution = $this->getScoreDistribution($tenantId);

        // Conversion funnel by priority
        $conversionFunnel = $this->getConversionFunnel($tenantId);

        // Best performing sources
        $topSources = $this->getTopSources($tenantId);

        return Inertia::render('Tenant/CRM/AIScoring/Dashboard', [
            'stats' => $stats,
            'hotLeads' => $hotLeads,
            'hotProspects' => $hotProspects,
            'scoreDistribution' => $scoreDistribution,
            'conversionFunnel' => $conversionFunnel,
            'topSources' => $topSources,
        ]);
    }

    /**
     * Scored leads list with AI insights
     */
    public function leads(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $priority = $request->input('priority');
        $minScore = $request->input('min_score');

        $query = Lead::where('tenant_id', $tenantId)
            ->whereNull('converted_at')
            ->with(['site', 'assignedTo']);

        if ($priority) {
            $query->where('priority', $priority);
        }

        if ($minScore) {
            $query->where('score', '>=', $minScore);
        }

        $leads = $query->orderByDesc('score')->paginate(20);

        return Inertia::render('Tenant/CRM/AIScoring/Leads', [
            'leads' => $leads,
            'stats' => $this->scoringService->getDashboardStats($tenantId),
            'filters' => [
                'priority' => $priority,
                'min_score' => $minScore,
            ],
        ]);
    }

    /**
     * Show detailed AI analysis for a lead
     */
    public function showLead(Request $request, Lead $lead)
    {
        $this->authorize('view', $lead);

        // Calculate fresh score with full details
        $scoreResult = $this->scoringService->calculateScore($lead, 'lead');

        $lead->load(['site', 'assignedTo']);

        // Get similar converted leads for comparison
        $similarConverted = $this->getSimilarConvertedLeads($lead);

        return Inertia::render('Tenant/CRM/AIScoring/LeadDetail', [
            'lead' => $lead,
            'scoreResult' => $scoreResult,
            'similarConverted' => $similarConverted,
        ]);
    }

    /**
     * Show detailed AI analysis for a prospect
     */
    public function showProspect(Request $request, Prospect $prospect)
    {
        $this->authorize('view', $prospect);

        // Calculate fresh score with full details
        $scoreResult = $this->scoringService->calculateScore($prospect, 'prospect');

        return Inertia::render('Tenant/CRM/AIScoring/ProspectDetail', [
            'prospect' => $prospect,
            'scoreResult' => $scoreResult,
        ]);
    }

    /**
     * Recalculate score for a single lead
     */
    public function recalculateLead(Request $request, Lead $lead)
    {
        $this->authorize('update', $lead);

        $scoreResult = $this->scoringService->calculateScore($lead, 'lead');

        $lead->update([
            'score' => $scoreResult['score'],
            'priority' => $scoreResult['priority'],
            'conversion_probability' => $scoreResult['conversion_probability'],
            'score_breakdown' => $scoreResult['breakdown'],
            'score_factors' => $scoreResult['factors'],
            'score_calculated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'score' => $scoreResult['score'],
            'priority' => $scoreResult['priority'],
            'recommendation' => $scoreResult['recommendation'],
            'insights' => $scoreResult['insights'],
        ]);
    }

    /**
     * Recalculate score for a single prospect
     */
    public function recalculateProspect(Request $request, Prospect $prospect)
    {
        $this->authorize('update', $prospect);

        $scoreResult = $this->scoringService->calculateScore($prospect, 'prospect');

        $prospect->update([
            'score' => $scoreResult['score'],
            'priority' => $scoreResult['priority'],
            'conversion_probability' => $scoreResult['conversion_probability'],
            'score_breakdown' => $scoreResult['breakdown'],
            'score_factors' => $scoreResult['factors'],
            'score_calculated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'score' => $scoreResult['score'],
            'priority' => $scoreResult['priority'],
            'recommendation' => $scoreResult['recommendation'],
            'insights' => $scoreResult['insights'],
        ]);
    }

    /**
     * Batch recalculate all scores
     */
    public function batchRecalculate(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $results = $this->scoringService->batchCalculateScores($tenantId, true);

        return response()->json([
            'success' => true,
            'results' => $results,
            'message' => "Scored {$results['processed']} leads/prospects. {$results['very_hot']} very hot, {$results['hot']} hot.",
        ]);
    }

    /**
     * Get AI recommendations for next actions
     */
    public function recommendations(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        // Get leads needing immediate attention
        $urgentLeads = Lead::where('tenant_id', $tenantId)
            ->whereNull('converted_at')
            ->where('priority', 'very_hot')
            ->where(function ($q) {
                $q->whereNull('last_contacted_at')
                    ->orWhere('last_contacted_at', '<', now()->subHours(24));
            })
            ->orderByDesc('score')
            ->limit(5)
            ->get();

        // Get leads with declining engagement
        $decliningLeads = Lead::where('tenant_id', $tenantId)
            ->whereNull('converted_at')
            ->whereIn('priority', ['warm', 'hot'])
            ->where('updated_at', '<', now()->subDays(7))
            ->orderByDesc('score')
            ->limit(5)
            ->get();

        // Get high probability leads not yet contacted
        $highProbabilityNew = Lead::where('tenant_id', $tenantId)
            ->whereNull('converted_at')
            ->where('conversion_probability', '>=', 60)
            ->whereNull('last_contacted_at')
            ->orderByDesc('conversion_probability')
            ->limit(5)
            ->get();

        // Get leads with urgent timing
        $urgentTiming = Lead::where('tenant_id', $tenantId)
            ->whereNull('converted_at')
            ->whereNotNull('move_in_date')
            ->where('move_in_date', '<=', now()->addDays(7))
            ->where('move_in_date', '>=', now())
            ->orderBy('move_in_date')
            ->limit(5)
            ->get();

        return Inertia::render('Tenant/CRM/AIScoring/Recommendations', [
            'urgentLeads' => $urgentLeads,
            'decliningLeads' => $decliningLeads,
            'highProbabilityNew' => $highProbabilityNew,
            'urgentTiming' => $urgentTiming,
            'stats' => $this->scoringService->getDashboardStats($tenantId),
        ]);
    }

    /**
     * Analytics and insights page
     */
    public function analytics(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $period = $request->input('period', 30);

        $startDate = now()->subDays($period);

        // Conversion rates by priority
        $conversionByPriority = Lead::where('tenant_id', $tenantId)
            ->where('created_at', '>=', $startDate)
            ->selectRaw("
                priority,
                COUNT(*) as total,
                SUM(CASE WHEN converted_at IS NOT NULL THEN 1 ELSE 0 END) as converted
            ")
            ->groupBy('priority')
            ->get()
            ->mapWithKeys(function ($item) {
                $rate = $item->total > 0 ? round(($item->converted / $item->total) * 100, 1) : 0;
                return [$item->priority => [
                    'total' => $item->total,
                    'converted' => $item->converted,
                    'rate' => $rate,
                ]];
            });

        // Score accuracy analysis
        $scoreAccuracy = $this->analyzeScoreAccuracy($tenantId, $startDate);

        // Lead velocity
        $leadVelocity = Lead::where('tenant_id', $tenantId)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, AVG(score) as avg_score')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top converting factors
        $topFactors = $this->analyzeTopConvertingFactors($tenantId, $startDate);

        return Inertia::render('Tenant/CRM/AIScoring/Analytics', [
            'period' => $period,
            'conversionByPriority' => $conversionByPriority,
            'scoreAccuracy' => $scoreAccuracy,
            'leadVelocity' => $leadVelocity,
            'topFactors' => $topFactors,
            'stats' => $this->scoringService->getDashboardStats($tenantId),
        ]);
    }

    /**
     * Get score distribution for charts
     */
    protected function getScoreDistribution(int $tenantId): array
    {
        $ranges = [
            '0-20' => [0, 20],
            '21-40' => [21, 40],
            '41-60' => [41, 60],
            '61-80' => [61, 80],
            '81-100' => [81, 100],
        ];

        $distribution = [];
        foreach ($ranges as $label => [$min, $max]) {
            $distribution[$label] = Lead::where('tenant_id', $tenantId)
                ->whereNull('converted_at')
                ->whereBetween('score', [$min, $max])
                ->count();
        }

        return $distribution;
    }

    /**
     * Get conversion funnel data
     */
    protected function getConversionFunnel(int $tenantId): array
    {
        $priorities = ['very_hot', 'hot', 'warm', 'lukewarm', 'cold'];
        $funnel = [];

        foreach ($priorities as $priority) {
            $total = Lead::where('tenant_id', $tenantId)
                ->where('priority', $priority)
                ->count();
            $converted = Lead::where('tenant_id', $tenantId)
                ->where('priority', $priority)
                ->whereNotNull('converted_at')
                ->count();

            $funnel[$priority] = [
                'total' => $total,
                'converted' => $converted,
                'rate' => $total > 0 ? round(($converted / $total) * 100, 1) : 0,
            ];
        }

        return $funnel;
    }

    /**
     * Get top performing lead sources
     */
    protected function getTopSources(int $tenantId): array
    {
        return Lead::where('tenant_id', $tenantId)
            ->whereNotNull('source')
            ->selectRaw("
                source,
                COUNT(*) as total,
                SUM(CASE WHEN converted_at IS NOT NULL THEN 1 ELSE 0 END) as converted,
                AVG(score) as avg_score
            ")
            ->groupBy('source')
            ->having('total', '>=', 3)
            ->orderByDesc('converted')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'source' => $item->source,
                    'total' => $item->total,
                    'converted' => $item->converted,
                    'rate' => $item->total > 0 ? round(($item->converted / $item->total) * 100, 1) : 0,
                    'avg_score' => round($item->avg_score ?? 0, 1),
                ];
            })
            ->toArray();
    }

    /**
     * Get similar converted leads for comparison
     */
    protected function getSimilarConvertedLeads(Lead $lead): array
    {
        $query = Lead::where('tenant_id', $lead->tenant_id)
            ->whereNotNull('converted_at');

        // Same source
        if ($lead->source) {
            $query->where('source', $lead->source);
        }

        // Same type
        if ($lead->type) {
            $query->where('type', $lead->type);
        }

        return $query->select('id', 'first_name', 'last_name', 'company', 'source', 'score', 'created_at', 'converted_at')
            ->orderByDesc('converted_at')
            ->limit(5)
            ->get()
            ->map(function ($l) {
                return [
                    'id' => $l->id,
                    'name' => $l->company ?? ($l->first_name . ' ' . $l->last_name),
                    'source' => $l->source,
                    'score' => $l->score,
                    'days_to_convert' => $l->created_at->diffInDays($l->converted_at),
                ];
            })
            ->toArray();
    }

    /**
     * Analyze score accuracy
     */
    protected function analyzeScoreAccuracy(int $tenantId, $startDate): array
    {
        $converted = Lead::where('tenant_id', $tenantId)
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('converted_at')
            ->avg('score');

        $notConverted = Lead::where('tenant_id', $tenantId)
            ->where('created_at', '>=', $startDate)
            ->whereNull('converted_at')
            ->where('status', 'lost')
            ->avg('score');

        return [
            'avg_score_converted' => round($converted ?? 0, 1),
            'avg_score_not_converted' => round($notConverted ?? 0, 1),
            'score_difference' => round(($converted ?? 0) - ($notConverted ?? 0), 1),
            'accuracy_indicator' => ($converted ?? 0) > ($notConverted ?? 0) ? 'good' : 'needs_improvement',
        ];
    }

    /**
     * Analyze top converting factors
     */
    protected function analyzeTopConvertingFactors(int $tenantId, $startDate): array
    {
        $factors = [
            'quote_requested' => 0,
            'visit_scheduled' => 0,
            'calculator_used' => 0,
            'referral' => 0,
            'business_customer' => 0,
            'high_budget' => 0,
        ];

        $convertedLeads = Lead::where('tenant_id', $tenantId)
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('converted_at')
            ->get();

        foreach ($convertedLeads as $lead) {
            $metadata = $lead->metadata ?? [];
            $scoreFactors = $lead->score_factors ?? [];

            if ($metadata['requested_quote'] ?? false) {
                $factors['quote_requested']++;
            }
            if ($metadata['scheduled_visit'] ?? false) {
                $factors['visit_scheduled']++;
            }
            if ($metadata['used_calculator'] ?? false) {
                $factors['calculator_used']++;
            }
            if ($metadata['referred_by'] ?? false) {
                $factors['referral']++;
            }
            if (in_array($lead->type, ['company', 'business'])) {
                $factors['business_customer']++;
            }
            if (($lead->budget ?? 0) >= 200) {
                $factors['high_budget']++;
            }
        }

        $total = $convertedLeads->count() ?: 1;

        return collect($factors)
            ->map(function ($count, $factor) use ($total) {
                return [
                    'factor' => $factor,
                    'count' => $count,
                    'percentage' => round(($count / $total) * 100, 1),
                ];
            })
            ->sortByDesc('count')
            ->values()
            ->toArray();
    }
}
