<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\CustomerReview;
use App\Models\ReviewRequest;
use App\Models\Customer;
use App\Models\Contract;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $query = CustomerReview::where('tenant_id', $tenantId)
            ->with(['customer', 'contract.site']);

        if ($request->filled('rating')) {
            $query->where('rating', $request->rating);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('site_id')) {
            $query->whereHas('contract', fn($q) => $q->where('site_id', $request->site_id));
        }

        $reviews = $query->latest()->paginate(20)->withQueryString();

        // Stats - Optimized with single query
        $reviewStats = CustomerReview::where('tenant_id', $tenantId)
            ->selectRaw("
                COUNT(*) as total_reviews,
                AVG(rating) as average_rating,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star_count,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_moderation
            ")
            ->first();

        $stats = [
            'total_reviews' => (int) ($reviewStats->total_reviews ?? 0),
            'average_rating' => round((float) ($reviewStats->average_rating ?? 0), 1),
            'five_star_count' => (int) ($reviewStats->five_star_count ?? 0),
            'pending_moderation' => (int) ($reviewStats->pending_moderation ?? 0),
            'nps_score' => $this->calculateNPS($tenantId),
        ];

        // Distribution des notes - Optimized with single GROUP BY query
        $ratingCounts = CustomerReview::where('tenant_id', $tenantId)
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        $ratingDistribution = [];
        for ($i = 5; $i >= 1; $i--) {
            $ratingDistribution[$i] = $ratingCounts[$i] ?? 0;
        }

        // Recent review requests
        $reviewRequests = ReviewRequest::where('tenant_id', $tenantId)
            ->with('customer')
            ->latest()
            ->limit(5)
            ->get();

        // NPS breakdown for stats
        $npsData = $this->calculateNPSBreakdown($tenantId);
        $stats['nps_responses'] = $npsData['total'];
        $stats['promoters'] = $npsData['promoters_percent'];
        $stats['passives'] = $npsData['passives_percent'];
        $stats['detractors'] = $npsData['detractors_percent'];
        $stats['pending_reviews'] = $stats['pending_moderation'];

        return Inertia::render('Tenant/Reviews/Index', [
            'reviews' => $reviews,
            'stats' => $stats,
            'ratingDistribution' => $ratingDistribution,
            'reviewRequests' => $reviewRequests,
            'sites' => Site::where('tenant_id', $tenantId)->get(['id', 'name']),
            'filters' => $request->only(['rating', 'status', 'site_id']),
        ]);
    }

    public function show(CustomerReview $review)
    {
        $this->authorize('view', $review);

        $review->load(['customer', 'contract.site', 'contract.box']);

        return Inertia::render('Tenant/Reviews/Show', [
            'review' => $review,
        ]);
    }

    public function moderate(Request $request, CustomerReview $review)
    {
        $this->authorize('update', $review);

        $validated = $request->validate([
            'action' => 'required|in:approve,reject,flag',
            'moderation_notes' => 'nullable|string',
        ]);

        $status = match ($validated['action']) {
            'approve' => 'published',
            'reject' => 'rejected',
            'flag' => 'flagged',
        };

        $review->update([
            'status' => $status,
            'moderated_at' => now(),
            'moderated_by' => Auth::id(),
            'moderation_notes' => $validated['moderation_notes'] ?? null,
        ]);

        return back()->with('success', 'Avis modéré.');
    }

    public function respond(Request $request, CustomerReview $review)
    {
        $this->authorize('update', $review);

        $validated = $request->validate([
            'response' => 'required|string|max:1000',
        ]);

        $review->update([
            'response' => $validated['response'],
            'responded_at' => now(),
            'responded_by' => Auth::id(),
        ]);

        return back()->with('success', 'Réponse publiée.');
    }

    // Demandes d'avis
    public function requests(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $query = ReviewRequest::where('tenant_id', $tenantId)
            ->with(['customer', 'contract', 'review']);

        if ($request->filled('status')) {
            if ($request->status === 'pending') {
                $query->pending();
            } elseif ($request->status === 'completed') {
                $query->completed();
            }
        }

        $requests = $query->latest()->paginate(20)->withQueryString();

        $stats = [
            'total_sent' => ReviewRequest::where('tenant_id', $tenantId)->whereNotNull('sent_at')->count(),
            'total_completed' => ReviewRequest::where('tenant_id', $tenantId)->completed()->count(),
            'response_rate' => $this->calculateResponseRate($tenantId),
        ];

        // Get customers for individual request form
        $customers = Customer::where('tenant_id', $tenantId)
            ->whereHas('contracts', fn($q) => $q->where('status', 'active'))
            ->get(['id', 'first_name', 'last_name']);

        return Inertia::render('Tenant/Reviews/Requests', [
            'requests' => $requests,
            'stats' => $stats,
            'customers' => $customers,
            'filters' => $request->only(['status']),
        ]);
    }

    public function sendRequest(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'contract_id' => 'nullable|exists:contracts,id',
            'type' => 'required|in:satisfaction,nps,google',
            'channel' => 'required|in:email,sms',
        ]);

        $tenantId = Auth::user()->tenant_id;

        $reviewRequest = ReviewRequest::create([
            'tenant_id' => $tenantId,
            'customer_id' => $validated['customer_id'],
            'contract_id' => $validated['contract_id'] ?? null,
            'type' => $validated['type'],
            'channel' => $validated['channel'],
        ]);

        // Envoyer la demande
        $this->sendReviewRequest($reviewRequest);

        return back()->with('success', 'Demande d\'avis envoyée.');
    }

    public function bulkSendRequests(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:satisfaction,nps,google',
            'channel' => 'required|in:email,sms',
            'filter' => 'required|in:all_active,recent_contracts,never_reviewed',
        ]);

        $tenantId = Auth::user()->tenant_id;

        // Sélectionner les clients selon le filtre
        $query = Customer::where('tenant_id', $tenantId);

        switch ($validated['filter']) {
            case 'all_active':
                $query->whereHas('contracts', fn($q) => $q->where('status', 'active'));
                break;
            case 'recent_contracts':
                $query->whereHas('contracts', fn($q) => $q->where('status', 'active')
                    ->where('start_date', '>=', now()->subMonths(3)));
                break;
            case 'never_reviewed':
                $query->whereDoesntHave('reviews');
                break;
        }

        $customers = $query->get();
        $count = 0;

        foreach ($customers as $customer) {
            // Vérifier si une demande récente existe déjà
            $recentRequest = ReviewRequest::where('customer_id', $customer->id)
                ->where('created_at', '>=', now()->subDays(30))
                ->exists();

            if (!$recentRequest) {
                $reviewRequest = ReviewRequest::create([
                    'tenant_id' => $tenantId,
                    'customer_id' => $customer->id,
                    'type' => $validated['type'],
                    'channel' => $validated['channel'],
                ]);

                $this->sendReviewRequest($reviewRequest);
                $count++;
            }
        }

        return back()->with('success', "{$count} demandes d'avis envoyées.");
    }

    public function resendRequest(ReviewRequest $reviewRequest)
    {
        if ($reviewRequest->completed_at) {
            return back()->with('error', 'Cette demande a déjà reçu une réponse.');
        }

        $reviewRequest->increment('reminder_count');
        $reviewRequest->update(['last_reminder_at' => now()]);

        $this->sendReviewRequest($reviewRequest);

        return back()->with('success', 'Relance envoyée.');
    }

    // Statistiques NPS
    public function npsReport(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $period = $request->input('period', 'year');

        $startDate = match ($period) {
            'month' => now()->subMonth(),
            'quarter' => now()->subQuarter(),
            'year' => now()->subYear(),
            default => now()->subYear(),
        };

        $reviews = CustomerReview::where('tenant_id', $tenantId)
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('rating')
            ->get();

        $promoters = $reviews->where('rating', 5)->count();
        $passives = $reviews->where('rating', 4)->count();
        $detractors = $reviews->where('rating', '<=', 3)->count();
        $total = $reviews->count();

        $npsScore = $total > 0
            ? round((($promoters - $detractors) / $total) * 100)
            : 0;

        // Évolution mensuelle
        $monthlyNPS = [];
        for ($i = 11; $i >= 0; $i--) {
            $monthStart = now()->subMonths($i)->startOfMonth();
            $monthEnd = now()->subMonths($i)->endOfMonth();

            $monthReviews = CustomerReview::where('tenant_id', $tenantId)
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->whereNotNull('rating')
                ->get();

            $monthTotal = $monthReviews->count();
            if ($monthTotal > 0) {
                $monthPromoters = $monthReviews->where('rating', 5)->count();
                $monthDetractors = $monthReviews->where('rating', '<=', 3)->count();
                $monthNPS = round((($monthPromoters - $monthDetractors) / $monthTotal) * 100);
            } else {
                $monthNPS = null;
            }

            $monthlyNPS[] = [
                'month' => $monthStart->format('M Y'),
                'nps' => $monthNPS,
                'responses' => $monthTotal,
            ];
        }

        return Inertia::render('Tenant/Reviews/NPSReport', [
            'npsScore' => $npsScore,
            'breakdown' => [
                'promoters' => $promoters,
                'passives' => $passives,
                'detractors' => $detractors,
                'total' => $total,
            ],
            'monthlyNPS' => $monthlyNPS,
            'period' => $period,
        ]);
    }

    protected function sendReviewRequest(ReviewRequest $reviewRequest): void
    {
        // TODO: Implémenter l'envoi email/SMS
        $reviewRequest->update(['sent_at' => now()]);
    }

    protected function calculateNPS(int $tenantId): int
    {
        // Use rating as NPS proxy (convert 1-5 scale to NPS-like calculation)
        // 5 = promoters (9-10), 4 = passive (7-8), 1-3 = detractors (0-6)
        $reviews = CustomerReview::where('tenant_id', $tenantId)
            ->whereNotNull('rating')
            ->get();

        $total = $reviews->count();
        if ($total === 0) return 0;

        $promoters = $reviews->where('rating', 5)->count();
        $detractors = $reviews->where('rating', '<=', 3)->count();

        return (int) round((($promoters - $detractors) / $total) * 100);
    }

    protected function calculateResponseRate(int $tenantId): float
    {
        $sent = ReviewRequest::where('tenant_id', $tenantId)->whereNotNull('sent_at')->count();
        if ($sent === 0) return 0;

        $completed = ReviewRequest::where('tenant_id', $tenantId)->completed()->count();
        return round(($completed / $sent) * 100, 1);
    }

    protected function calculateNPSBreakdown(int $tenantId): array
    {
        $reviews = CustomerReview::where('tenant_id', $tenantId)
            ->whereNotNull('rating')
            ->get();

        $total = $reviews->count();
        if ($total === 0) {
            return [
                'promoters' => 0,
                'passives' => 0,
                'detractors' => 0,
                'total' => 0,
                'promoters_percent' => 0,
                'passives_percent' => 0,
                'detractors_percent' => 0,
            ];
        }

        $promoters = $reviews->where('rating', 5)->count();
        $passives = $reviews->where('rating', 4)->count();
        $detractors = $reviews->where('rating', '<=', 3)->count();

        return [
            'promoters' => $promoters,
            'passives' => $passives,
            'detractors' => $detractors,
            'total' => $total,
            'promoters_percent' => round(($promoters / $total) * 100),
            'passives_percent' => round(($passives / $total) * 100),
            'detractors_percent' => round(($detractors / $total) * 100),
        ];
    }
}
