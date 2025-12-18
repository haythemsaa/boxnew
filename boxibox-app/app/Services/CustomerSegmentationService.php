<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Contract;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CustomerSegmentationService
{
    /**
     * Calculate RFM scores for all customers of a tenant
     */
    public function calculateRFMScores(int $tenantId): array
    {
        $customers = Customer::where('tenant_id', $tenantId)->get();
        $results = [];

        foreach ($customers as $customer) {
            $rfm = $this->calculateCustomerRFM($customer);
            $results[$customer->id] = $rfm;

            // Update customer with RFM data
            $customer->update([
                'rfm_recency' => $rfm['recency_score'],
                'rfm_frequency' => $rfm['frequency_score'],
                'rfm_monetary' => $rfm['monetary_score'],
                'rfm_total' => $rfm['total_score'],
                'rfm_segment' => $rfm['segment'],
                'rfm_calculated_at' => now(),
            ]);
        }

        return $results;
    }

    /**
     * Calculate RFM for a single customer
     */
    public function calculateCustomerRFM(Customer $customer): array
    {
        // Recency: Days since last payment
        $lastPayment = Payment::where('customer_id', $customer->id)
            ->where('status', 'completed')
            ->latest('processed_at')
            ->first();

        $recencyDays = $lastPayment
            ? Carbon::parse($lastPayment->processed_at)->diffInDays(now())
            : 365; // Default if no payment

        // Frequency: Number of payments in last 12 months
        $frequency = Payment::where('customer_id', $customer->id)
            ->where('status', 'completed')
            ->where('processed_at', '>=', now()->subYear())
            ->count();

        // Monetary: Total amount paid in last 12 months
        $monetary = Payment::where('customer_id', $customer->id)
            ->where('status', 'completed')
            ->where('processed_at', '>=', now()->subYear())
            ->sum('amount');

        // Calculate scores (1-5)
        $recencyScore = $this->calculateRecencyScore($recencyDays);
        $frequencyScore = $this->calculateFrequencyScore($frequency);
        $monetaryScore = $this->calculateMonetaryScore($monetary);

        // Total score (weighted average)
        $totalScore = round(($recencyScore * 0.35) + ($frequencyScore * 0.25) + ($monetaryScore * 0.40), 1);

        // Determine segment
        $segment = $this->determineSegment($recencyScore, $frequencyScore, $monetaryScore);

        return [
            'customer_id' => $customer->id,
            'recency_days' => $recencyDays,
            'frequency_count' => $frequency,
            'monetary_value' => $monetary,
            'recency_score' => $recencyScore,
            'frequency_score' => $frequencyScore,
            'monetary_score' => $monetaryScore,
            'total_score' => $totalScore,
            'segment' => $segment,
            'segment_label' => $this->getSegmentLabel($segment),
            'segment_color' => $this->getSegmentColor($segment),
            'recommendations' => $this->getSegmentRecommendations($segment),
        ];
    }

    /**
     * Calculate Recency Score (1-5)
     * Lower days = higher score
     */
    protected function calculateRecencyScore(int $days): int
    {
        if ($days <= 30) return 5;    // Paid within last month
        if ($days <= 60) return 4;    // Paid within 2 months
        if ($days <= 90) return 3;    // Paid within 3 months
        if ($days <= 180) return 2;   // Paid within 6 months
        return 1;                      // Paid over 6 months ago or never
    }

    /**
     * Calculate Frequency Score (1-5)
     * More payments = higher score
     */
    protected function calculateFrequencyScore(int $count): int
    {
        if ($count >= 12) return 5;   // Monthly payer
        if ($count >= 6) return 4;    // Bi-monthly
        if ($count >= 4) return 3;    // Quarterly
        if ($count >= 2) return 2;    // Semi-annual
        return 1;                      // Annual or less
    }

    /**
     * Calculate Monetary Score (1-5)
     * Higher amount = higher score
     */
    protected function calculateMonetaryScore(float $amount): int
    {
        if ($amount >= 5000) return 5;   // High value
        if ($amount >= 2000) return 4;   // Good value
        if ($amount >= 1000) return 3;   // Medium value
        if ($amount >= 500) return 2;    // Low value
        return 1;                         // Very low value
    }

    /**
     * Determine customer segment based on RFM scores
     */
    protected function determineSegment(int $r, int $f, int $m): string
    {
        $avg = ($r + $f + $m) / 3;

        // Champions: High in all dimensions
        if ($r >= 4 && $f >= 4 && $m >= 4) {
            return 'champions';
        }

        // Loyal Customers: Good frequency and monetary
        if ($f >= 4 && $m >= 3) {
            return 'loyal';
        }

        // Potential Loyalists: Recent, good potential
        if ($r >= 4 && $f >= 2 && $m >= 2) {
            return 'potential_loyalist';
        }

        // New Customers: Very recent but low frequency
        if ($r >= 4 && $f <= 2) {
            return 'new_customer';
        }

        // Promising: Medium scores, room for growth
        if ($r >= 3 && $m >= 3) {
            return 'promising';
        }

        // Need Attention: Were good, now declining
        if ($r <= 2 && $f >= 3 && $m >= 3) {
            return 'need_attention';
        }

        // About to Sleep: Declining engagement
        if ($r <= 2 && $f <= 3) {
            return 'about_to_sleep';
        }

        // At Risk: Good customers going dormant
        if ($r <= 2 && $f >= 4) {
            return 'at_risk';
        }

        // Can't Lose Them: High value but at risk
        if ($r <= 2 && $m >= 4) {
            return 'cant_lose';
        }

        // Hibernating: Long time inactive
        if ($r <= 2 && $f <= 2 && $m <= 2) {
            return 'hibernating';
        }

        // Lost: Very low engagement
        if ($avg <= 2) {
            return 'lost';
        }

        return 'other';
    }

    /**
     * Get segment label in French
     */
    public function getSegmentLabel(string $segment): string
    {
        return match ($segment) {
            'champions' => 'Champions',
            'loyal' => 'Clients Fidèles',
            'potential_loyalist' => 'Potentiels Fidèles',
            'new_customer' => 'Nouveaux Clients',
            'promising' => 'Prometteurs',
            'need_attention' => 'À Réactiver',
            'about_to_sleep' => 'En Dormance',
            'at_risk' => 'À Risque',
            'cant_lose' => 'À Ne Pas Perdre',
            'hibernating' => 'Hibernants',
            'lost' => 'Perdus',
            default => 'Autre',
        };
    }

    /**
     * Get segment color for UI
     */
    public function getSegmentColor(string $segment): string
    {
        return match ($segment) {
            'champions' => 'emerald',
            'loyal' => 'blue',
            'potential_loyalist' => 'cyan',
            'new_customer' => 'violet',
            'promising' => 'indigo',
            'need_attention' => 'amber',
            'about_to_sleep' => 'orange',
            'at_risk' => 'red',
            'cant_lose' => 'rose',
            'hibernating' => 'gray',
            'lost' => 'slate',
            default => 'gray',
        };
    }

    /**
     * Get recommendations for a segment
     */
    public function getSegmentRecommendations(string $segment): array
    {
        return match ($segment) {
            'champions' => [
                'Récompenser leur fidélité avec des offres exclusives',
                'Les inviter à parrainer de nouveaux clients',
                'Proposer des services premium ou upgrades',
                'Solliciter des témoignages et avis',
            ],
            'loyal' => [
                'Maintenir la relation avec des communications personnalisées',
                'Proposer des programmes de fidélité',
                'Offrir des avant-premières sur les nouveaux services',
            ],
            'potential_loyalist' => [
                'Encourager des interactions plus fréquentes',
                'Proposer des offres de fidélisation',
                'Créer de l\'engagement avec du contenu personnalisé',
            ],
            'new_customer' => [
                'Mettre en place un parcours d\'onboarding',
                'Proposer une offre de bienvenue',
                'S\'assurer de la satisfaction initiale',
            ],
            'promising' => [
                'Encourager à augmenter la fréquence d\'achat',
                'Proposer des bundles ou services complémentaires',
            ],
            'need_attention' => [
                'Contacter rapidement pour comprendre la baisse d\'engagement',
                'Proposer une offre de reconquête',
                'Identifier d\'éventuels problèmes de satisfaction',
            ],
            'about_to_sleep' => [
                'Envoyer une campagne de réactivation',
                'Proposer une offre limitée dans le temps',
            ],
            'at_risk' => [
                'Action urgente requise - contacter personnellement',
                'Comprendre les raisons du désengagement',
                'Proposer une solution adaptée à leur situation',
            ],
            'cant_lose' => [
                'Intervention immédiate du manager',
                'Offre de reconquête personnalisée',
                'Appel téléphonique de suivi',
            ],
            'hibernating' => [
                'Campagne de réactivation par email',
                'Offre spéciale "Vous nous manquez"',
            ],
            'lost' => [
                'Tentative finale de réactivation',
                'Enquête de satisfaction pour comprendre le départ',
            ],
            default => ['Analyser le profil client pour définir une stratégie'],
        };
    }

    /**
     * Get segment statistics for a tenant
     */
    public function getSegmentStats(int $tenantId): array
    {
        $customers = Customer::where('tenant_id', $tenantId)
            ->whereNotNull('rfm_segment')
            ->get();

        $segments = [
            'champions' => ['count' => 0, 'value' => 0],
            'loyal' => ['count' => 0, 'value' => 0],
            'potential_loyalist' => ['count' => 0, 'value' => 0],
            'new_customer' => ['count' => 0, 'value' => 0],
            'promising' => ['count' => 0, 'value' => 0],
            'need_attention' => ['count' => 0, 'value' => 0],
            'about_to_sleep' => ['count' => 0, 'value' => 0],
            'at_risk' => ['count' => 0, 'value' => 0],
            'cant_lose' => ['count' => 0, 'value' => 0],
            'hibernating' => ['count' => 0, 'value' => 0],
            'lost' => ['count' => 0, 'value' => 0],
        ];

        foreach ($customers as $customer) {
            $segment = $customer->rfm_segment;
            if (isset($segments[$segment])) {
                $segments[$segment]['count']++;

                // Calculate customer lifetime value (annual)
                $annualValue = Payment::where('customer_id', $customer->id)
                    ->where('status', 'completed')
                    ->where('processed_at', '>=', now()->subYear())
                    ->sum('amount');

                $segments[$segment]['value'] += $annualValue;
            }
        }

        // Add labels and colors
        foreach ($segments as $key => &$data) {
            $data['label'] = $this->getSegmentLabel($key);
            $data['color'] = $this->getSegmentColor($key);
            $data['percentage'] = $customers->count() > 0
                ? round($data['count'] / $customers->count() * 100, 1)
                : 0;
        }

        return [
            'segments' => $segments,
            'total_customers' => $customers->count(),
            'last_calculated' => now()->toDateTimeString(),
        ];
    }

    /**
     * Calculate credit score for a customer (payment reliability)
     */
    public function calculateCreditScore(Customer $customer): array
    {
        $tenantId = $customer->tenant_id;

        // Payment history metrics
        $totalInvoices = Invoice::where('customer_id', $customer->id)->count();
        $paidOnTime = Invoice::where('customer_id', $customer->id)
            ->where('status', 'paid')
            ->whereColumn('paid_at', '<=', DB::raw('DATE_ADD(due_date, INTERVAL 3 DAY)'))
            ->count();
        $latePaid = Invoice::where('customer_id', $customer->id)
            ->where('status', 'paid')
            ->whereColumn('paid_at', '>', DB::raw('DATE_ADD(due_date, INTERVAL 3 DAY)'))
            ->count();
        $unpaid = Invoice::where('customer_id', $customer->id)
            ->where('status', 'overdue')
            ->count();

        // Calculate payment ratio
        $paymentRatio = $totalInvoices > 0
            ? ($paidOnTime / $totalInvoices) * 100
            : 100;

        // Contract tenure (months)
        $firstContract = Contract::where('customer_id', $customer->id)
            ->oldest('start_date')
            ->first();
        $tenureMonths = $firstContract
            ? Carbon::parse($firstContract->start_date)->diffInMonths(now())
            : 0;

        // Calculate base score (0-1000)
        $baseScore = 600; // Starting point

        // Payment history impact (+/- 200 points)
        $baseScore += ($paymentRatio - 50) * 4; // Max ±200

        // Late payments penalty (-20 per late payment, max -100)
        $baseScore -= min($latePaid * 20, 100);

        // Unpaid penalty (-50 per unpaid, max -150)
        $baseScore -= min($unpaid * 50, 150);

        // Tenure bonus (+10 per month, max +100)
        $baseScore += min($tenureMonths * 10, 100);

        // Clamp score between 300 and 850
        $score = max(300, min(850, round($baseScore)));

        // Determine rating
        $rating = $this->getCreditRating($score);

        return [
            'customer_id' => $customer->id,
            'score' => $score,
            'rating' => $rating['code'],
            'rating_label' => $rating['label'],
            'rating_color' => $rating['color'],
            'components' => [
                'payment_history' => [
                    'total_invoices' => $totalInvoices,
                    'paid_on_time' => $paidOnTime,
                    'late_payments' => $latePaid,
                    'unpaid' => $unpaid,
                    'ratio' => round($paymentRatio, 1),
                ],
                'tenure_months' => $tenureMonths,
            ],
            'recommendations' => $this->getCreditRecommendations($rating['code'], $score),
            'max_credit_recommendation' => $this->getRecommendedCreditLimit($score),
        ];
    }

    /**
     * Get credit rating based on score
     */
    protected function getCreditRating(int $score): array
    {
        if ($score >= 750) return ['code' => 'excellent', 'label' => 'Excellent', 'color' => 'emerald'];
        if ($score >= 700) return ['code' => 'good', 'label' => 'Bon', 'color' => 'blue'];
        if ($score >= 650) return ['code' => 'fair', 'label' => 'Correct', 'color' => 'amber'];
        if ($score >= 550) return ['code' => 'poor', 'label' => 'Faible', 'color' => 'orange'];
        return ['code' => 'very_poor', 'label' => 'Très Faible', 'color' => 'red'];
    }

    /**
     * Get recommendations based on credit rating
     */
    protected function getCreditRecommendations(string $rating, int $score): array
    {
        return match ($rating) {
            'excellent' => [
                'Client de confiance - paiement différé possible',
                'Éligible aux offres premium',
                'Risque minimal de défaut de paiement',
            ],
            'good' => [
                'Bon historique de paiement',
                'Délai de paiement standard acceptable',
                'Surveiller occasionnellement',
            ],
            'fair' => [
                'Historique de paiement variable',
                'Privilégier le paiement à réception',
                'Mettre en place des rappels automatiques',
            ],
            'poor' => [
                'Attention requise sur les paiements',
                'Exiger un acompte ou paiement anticipé',
                'Contacter rapidement en cas de retard',
            ],
            'very_poor' => [
                'Risque élevé de défaut de paiement',
                'Paiement anticipé obligatoire',
                'Envisager une caution supplémentaire',
                'Réviser les conditions du contrat',
            ],
            default => ['Analyser l\'historique client'],
        };
    }

    /**
     * Get recommended credit limit based on score
     */
    protected function getRecommendedCreditLimit(int $score): ?float
    {
        if ($score >= 750) return 5000;
        if ($score >= 700) return 3000;
        if ($score >= 650) return 1500;
        if ($score >= 550) return 500;
        return 0; // Prepayment only
    }

    /**
     * Calculate Customer Lifetime Value (CLV)
     */
    public function calculateCLV(Customer $customer): array
    {
        $monthlyPayments = Payment::where('customer_id', $customer->id)
            ->where('status', 'completed')
            ->where('processed_at', '>=', now()->subYear())
            ->selectRaw('YEAR(processed_at) as year, MONTH(processed_at) as month, SUM(amount) as total')
            ->groupBy('year', 'month')
            ->get();

        $avgMonthlyValue = $monthlyPayments->avg('total') ?? 0;

        // Get contract duration in months
        $contracts = Contract::where('customer_id', $customer->id)->get();
        $avgContractMonths = $contracts->avg(function ($contract) {
            return Carbon::parse($contract->start_date)
                ->diffInMonths($contract->end_date ?? now());
        }) ?? 12;

        // Churn probability (simplified)
        $churnProbability = $this->estimateChurnProbability($customer);

        // CLV = (Avg Monthly Value × Avg Contract Duration) × (1 - Churn Probability)
        $clv = ($avgMonthlyValue * $avgContractMonths) * (1 - $churnProbability);

        return [
            'customer_id' => $customer->id,
            'avg_monthly_value' => round($avgMonthlyValue, 2),
            'avg_contract_months' => round($avgContractMonths, 1),
            'churn_probability' => round($churnProbability * 100, 1),
            'estimated_clv' => round($clv, 2),
            'clv_segment' => $this->getCLVSegment($clv),
        ];
    }

    /**
     * Estimate churn probability (simplified model)
     */
    protected function estimateChurnProbability(Customer $customer): float
    {
        $factors = 0;

        // Recent payment activity
        $lastPayment = Payment::where('customer_id', $customer->id)
            ->where('status', 'completed')
            ->latest('processed_at')
            ->first();

        if (!$lastPayment) {
            $factors += 0.3;
        } else {
            $daysSincePayment = Carbon::parse($lastPayment->processed_at)->diffInDays(now());
            if ($daysSincePayment > 90) $factors += 0.2;
            elseif ($daysSincePayment > 60) $factors += 0.1;
        }

        // Overdue invoices
        $overdueCount = Invoice::where('customer_id', $customer->id)
            ->where('status', 'overdue')
            ->count();
        $factors += min($overdueCount * 0.1, 0.3);

        // Contract ending soon
        $activeContract = Contract::where('customer_id', $customer->id)
            ->where('status', 'active')
            ->where('end_date', '<=', now()->addDays(30))
            ->exists();
        if ($activeContract) $factors += 0.15;

        return min($factors, 0.9); // Cap at 90%
    }

    /**
     * Get CLV segment
     */
    protected function getCLVSegment(float $clv): array
    {
        if ($clv >= 10000) return ['code' => 'platinum', 'label' => 'Platine', 'color' => 'violet'];
        if ($clv >= 5000) return ['code' => 'gold', 'label' => 'Or', 'color' => 'amber'];
        if ($clv >= 2000) return ['code' => 'silver', 'label' => 'Argent', 'color' => 'gray'];
        return ['code' => 'bronze', 'label' => 'Bronze', 'color' => 'orange'];
    }
}
