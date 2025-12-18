<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\CustomerSegmentationService;
use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerSegmentationController extends Controller
{
    protected CustomerSegmentationService $segmentationService;

    public function __construct(CustomerSegmentationService $segmentationService)
    {
        $this->segmentationService = $segmentationService;
    }

    /**
     * Display segmentation dashboard
     */
    public function index(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        // Get segment statistics
        $segmentStats = $this->segmentationService->getSegmentStats($tenantId);

        // Get customers by segment for the table
        $segment = $request->input('segment');
        $query = Customer::where('tenant_id', $tenantId)
            ->whereNotNull('rfm_segment')
            ->with(['contracts' => function ($q) {
                $q->where('status', 'active');
            }]);

        if ($segment) {
            $query->where('rfm_segment', $segment);
        }

        $customers = $query->orderByDesc('rfm_total')
            ->paginate(20)
            ->through(function ($customer) {
                return [
                    'id' => $customer->id,
                    'name' => $customer->type === 'company'
                        ? $customer->company_name
                        : "{$customer->first_name} {$customer->last_name}",
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'type' => $customer->type,
                    'rfm_total' => $customer->rfm_total,
                    'rfm_recency' => $customer->rfm_recency,
                    'rfm_frequency' => $customer->rfm_frequency,
                    'rfm_monetary' => $customer->rfm_monetary,
                    'segment' => $customer->rfm_segment,
                    'segment_label' => $this->segmentationService->getSegmentLabel($customer->rfm_segment),
                    'segment_color' => $this->segmentationService->getSegmentColor($customer->rfm_segment),
                    'active_contracts' => $customer->contracts->count(),
                    'created_at' => $customer->created_at?->format('d/m/Y'),
                ];
            });

        return Inertia::render('Tenant/Customers/Segmentation', [
            'segmentStats' => $segmentStats,
            'customers' => $customers,
            'currentSegment' => $segment,
        ]);
    }

    /**
     * Recalculate RFM scores for all customers
     */
    public function recalculate(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $this->segmentationService->calculateRFMScores($tenantId);

        return back()->with('success', 'Scores RFM recalculés avec succès');
    }

    /**
     * Show detailed customer analysis
     */
    public function show(Request $request, Customer $customer)
    {
        $this->authorize('view', $customer);

        // RFM Data
        $rfmData = $this->segmentationService->calculateCustomerRFM($customer);

        // Credit Score
        $creditScore = $this->segmentationService->calculateCreditScore($customer);

        // CLV
        $clvData = $this->segmentationService->calculateCLV($customer);

        return Inertia::render('Tenant/Customers/Analysis', [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->type === 'company'
                    ? $customer->company_name
                    : "{$customer->first_name} {$customer->last_name}",
                'email' => $customer->email,
                'phone' => $customer->phone,
                'type' => $customer->type,
                'created_at' => $customer->created_at?->format('d/m/Y'),
            ],
            'rfm' => $rfmData,
            'credit' => $creditScore,
            'clv' => $clvData,
        ]);
    }

    /**
     * Get customers for a specific segment (API)
     */
    public function bySegment(Request $request, string $segment)
    {
        $tenantId = $request->user()->tenant_id;

        $customers = Customer::where('tenant_id', $tenantId)
            ->where('rfm_segment', $segment)
            ->select('id', 'first_name', 'last_name', 'company_name', 'email', 'type', 'rfm_total')
            ->orderByDesc('rfm_total')
            ->limit(50)
            ->get()
            ->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'name' => $customer->type === 'company'
                        ? $customer->company_name
                        : "{$customer->first_name} {$customer->last_name}",
                    'email' => $customer->email,
                    'score' => $customer->rfm_total,
                ];
            });

        return response()->json([
            'segment' => $segment,
            'label' => $this->segmentationService->getSegmentLabel($segment),
            'recommendations' => $this->segmentationService->getSegmentRecommendations($segment),
            'customers' => $customers,
        ]);
    }

    /**
     * Credit scoring dashboard
     */
    public function creditScores(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $customers = Customer::where('tenant_id', $tenantId)
            ->get()
            ->map(function ($customer) {
                $credit = $this->segmentationService->calculateCreditScore($customer);
                return [
                    'id' => $customer->id,
                    'name' => $customer->type === 'company'
                        ? $customer->company_name
                        : "{$customer->first_name} {$customer->last_name}",
                    'email' => $customer->email,
                    'score' => $credit['score'],
                    'rating' => $credit['rating'],
                    'rating_label' => $credit['rating_label'],
                    'rating_color' => $credit['rating_color'],
                    'payment_ratio' => $credit['components']['payment_history']['ratio'],
                    'late_payments' => $credit['components']['payment_history']['late_payments'],
                    'unpaid' => $credit['components']['payment_history']['unpaid'],
                    'credit_limit' => $credit['max_credit_recommendation'],
                ];
            })
            ->sortByDesc('score')
            ->values();

        // Group by rating
        $byRating = $customers->groupBy('rating')->map(function ($group, $rating) {
            return [
                'count' => $group->count(),
                'avg_score' => round($group->avg('score')),
            ];
        });

        return Inertia::render('Tenant/Customers/CreditScores', [
            'customers' => $customers,
            'byRating' => $byRating,
            'total' => $customers->count(),
        ]);
    }
}
