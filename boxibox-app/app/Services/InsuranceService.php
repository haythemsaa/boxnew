<?php

namespace App\Services;

use App\Models\InsuranceProvider;
use App\Models\InsurancePlan;
use App\Models\InsurancePolicy;
use App\Models\InsuranceClaim;
use App\Models\InsuranceCertificate;
use App\Models\Contract;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class InsuranceService
{
    /**
     * Obtenir les plans d'assurance disponibles pour un tenant
     */
    public function getAvailablePlans(int $tenantId): \Illuminate\Database\Eloquent\Collection
    {
        return InsurancePlan::whereHas('provider', fn($q) => $q->where('is_active', true))
            ->where('is_active', true)
            ->orderBy('order')
            ->orderBy('coverage_amount')
            ->get();
    }

    /**
     * Calculer la prime pour un client
     */
    public function calculatePremium(InsurancePlan $plan, float $declaredValue, float $boxSize): array
    {
        $monthlyPremium = match ($plan->pricing_type) {
            'fixed' => $plan->price_monthly,
            'percentage' => ($declaredValue * $plan->percentage_of_value) / 100 / 12,
            'per_sqm' => $plan->price_per_sqm * $boxSize,
            default => $plan->price_monthly,
        };

        $yearlyPremium = $monthlyPremium * 12;
        $yearlyDiscount = $plan->price_yearly ? ($monthlyPremium * 12) - $plan->price_yearly : 0;

        return [
            'monthly' => round($monthlyPremium, 2),
            'yearly' => round($plan->price_yearly ?? $yearlyPremium, 2),
            'yearly_savings' => round($yearlyDiscount, 2),
            'coverage_amount' => $plan->coverage_amount,
            'deductible' => $plan->deductible,
            'covered_risks' => $plan->covered_risks,
        ];
    }

    /**
     * Souscrire une assurance
     */
    public function subscribe(
        Contract $contract,
        InsurancePlan $plan,
        float $declaredValue,
        ?string $itemsDescription = null,
        string $paymentFrequency = 'monthly'
    ): InsurancePolicy {
        $premium = $this->calculatePremium($plan, $declaredValue, $contract->box->size ?? 5);

        $policy = InsurancePolicy::create([
            'tenant_id' => $contract->tenant_id,
            'customer_id' => $contract->customer_id,
            'contract_id' => $contract->id,
            'plan_id' => $plan->id,
            'policy_number' => $this->generatePolicyNumber(),
            'coverage_amount' => $plan->coverage_amount,
            'premium_monthly' => $premium['monthly'],
            'premium_yearly' => $premium['yearly'],
            'deductible' => $plan->deductible,
            'declared_value' => $declaredValue,
            'items_description' => $itemsDescription,
            'start_date' => Carbon::today(),
            'payment_frequency' => $paymentFrequency,
            'status' => 'active',
            'next_payment_date' => $paymentFrequency === 'yearly'
                ? Carbon::today()->addYear()
                : Carbon::today()->addMonth(),
        ]);

        // Générer le certificat initial
        $this->generateCertificate($policy);

        return $policy;
    }

    /**
     * Générer un numéro de police unique
     */
    protected function generatePolicyNumber(): string
    {
        do {
            $number = 'POL-' . strtoupper(Str::random(8));
        } while (InsurancePolicy::where('policy_number', $number)->exists());

        return $number;
    }

    /**
     * Résilier une police
     */
    public function cancel(InsurancePolicy $policy, string $reason): bool
    {
        $policy->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        return true;
    }

    /**
     * Déclarer un sinistre
     */
    public function createClaim(
        InsurancePolicy $policy,
        Carbon $incidentDate,
        string $incidentType,
        string $description,
        float $claimedAmount,
        array $documents = []
    ): InsuranceClaim {
        return InsuranceClaim::create([
            'policy_id' => $policy->id,
            'tenant_id' => $policy->tenant_id,
            'claim_number' => $this->generateClaimNumber(),
            'incident_date' => $incidentDate,
            'incident_type' => $incidentType,
            'incident_description' => $description,
            'claimed_amount' => min($claimedAmount, $policy->coverage_amount),
            'status' => 'draft',
            'documents' => $documents,
        ]);
    }

    /**
     * Générer un numéro de sinistre unique
     */
    protected function generateClaimNumber(): string
    {
        return 'CLM-' . Carbon::now()->format('Ymd') . '-' . strtoupper(Str::random(4));
    }

    /**
     * Soumettre un sinistre
     */
    public function submitClaim(InsuranceClaim $claim, int $userId): InsuranceClaim
    {
        $claim->update([
            'status' => 'submitted',
            'submitted_by' => $userId,
            'submitted_at' => now(),
        ]);

        // TODO: Notifier l'assureur via API si disponible

        return $claim;
    }

    /**
     * Traiter une décision sur un sinistre
     */
    public function processClaim(
        InsuranceClaim $claim,
        string $decision,
        ?float $approvedAmount = null,
        ?string $notes = null,
        int $reviewerId = null
    ): InsuranceClaim {
        $data = [
            'status' => $decision,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'status_notes' => $notes,
        ];

        if ($decision === 'approved' || $decision === 'partially_approved') {
            $data['approved_amount'] = $approvedAmount ?? $claim->claimed_amount;
        } elseif ($decision === 'rejected') {
            $data['rejection_reason'] = $notes;
        }

        $claim->update($data);

        return $claim;
    }

    /**
     * Marquer un sinistre comme payé
     */
    public function markClaimAsPaid(InsuranceClaim $claim, float $paidAmount): InsuranceClaim
    {
        $claim->update([
            'status' => 'paid',
            'paid_amount' => $paidAmount,
            'paid_at' => now(),
        ]);

        return $claim;
    }

    /**
     * Générer un certificat d'assurance PDF
     */
    public function generateCertificate(InsurancePolicy $policy): InsuranceCertificate
    {
        $pdf = Pdf::loadView('pdf.insurance-certificate', [
            'policy' => $policy,
            'plan' => $policy->plan,
            'customer' => $policy->customer,
            'contract' => $policy->contract,
        ]);

        $fileName = "certificate-{$policy->policy_number}.pdf";
        $path = "certificates/{$policy->tenant_id}/{$fileName}";

        // Sauvegarder le PDF
        \Storage::disk('public')->put($path, $pdf->output());

        // Invalider les anciens certificats
        InsuranceCertificate::where('policy_id', $policy->id)
            ->update(['is_current' => false]);

        return InsuranceCertificate::create([
            'policy_id' => $policy->id,
            'certificate_number' => 'CERT-' . strtoupper(Str::random(8)),
            'file_path' => $path,
            'valid_from' => Carbon::today(),
            'valid_until' => Carbon::today()->addYear(),
            'is_current' => true,
        ]);
    }

    /**
     * Renouveler les polices arrivant à échéance
     */
    public function renewExpiring(): int
    {
        $renewedCount = 0;

        $expiring = InsurancePolicy::where('status', 'active')
            ->where('auto_renew', true)
            ->where('end_date', '<=', Carbon::today()->addDays(7))
            ->get();

        foreach ($expiring as $policy) {
            $policy->update([
                'end_date' => $policy->end_date->addYear(),
            ]);
            $this->generateCertificate($policy);
            $renewedCount++;
        }

        return $renewedCount;
    }

    /**
     * Obtenir les statistiques d'assurance pour un tenant
     */
    public function getStats(int $tenantId): array
    {
        $policies = InsurancePolicy::where('tenant_id', $tenantId);
        $claims = InsuranceClaim::where('tenant_id', $tenantId);

        return [
            'active_policies' => (clone $policies)->where('status', 'active')->count(),
            'total_coverage' => (clone $policies)->where('status', 'active')->sum('coverage_amount'),
            'monthly_premiums' => (clone $policies)->where('status', 'active')->sum('premium_monthly'),
            'total_claims' => $claims->count(),
            'pending_claims' => (clone $claims)->whereIn('status', ['submitted', 'under_review'])->count(),
            'paid_claims' => (clone $claims)->where('status', 'paid')->sum('paid_amount'),
            'claim_rate' => $policies->count() > 0
                ? round(($claims->count() / $policies->count()) * 100, 2)
                : 0,
        ];
    }

    /**
     * Vérifier si un contrat a une assurance active
     */
    public function hasActiveInsurance(Contract $contract): bool
    {
        return InsurancePolicy::where('contract_id', $contract->id)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Obtenir la police active d'un contrat
     */
    public function getActivePolicy(Contract $contract): ?InsurancePolicy
    {
        return InsurancePolicy::where('contract_id', $contract->id)
            ->where('status', 'active')
            ->first();
    }

    // ========================================================================
    // INSURANCE MARKETPLACE - Comparaison multi-assureurs
    // ========================================================================

    /**
     * Comparer les offres de plusieurs assureurs
     */
    public function compareOffers(
        int $tenantId,
        float $declaredValue,
        float $boxSize,
        array $coverageTypes = ['theft', 'fire', 'water', 'vandalism']
    ): array {
        $offers = [];

        // Récupérer tous les plans actifs des providers configurés
        $plans = InsurancePlan::whereHas('provider', function ($q) use ($tenantId) {
            $q->where('is_active', true);
        })
            ->where('is_active', true)
            ->with('provider')
            ->get();

        foreach ($plans as $plan) {
            $premium = $this->calculatePremium($plan, $declaredValue, $boxSize);

            // Calculer le score de valeur (couverture par euro de prime)
            $valueScore = $premium['monthly'] > 0
                ? round($premium['coverage_amount'] / $premium['monthly'], 0)
                : 0;

            // Vérifier la couverture des risques demandés
            $coveredRisks = $plan->covered_risks ?? [];
            $matchingCoverage = count(array_intersect($coverageTypes, $coveredRisks));
            $coverageMatch = count($coverageTypes) > 0
                ? round(($matchingCoverage / count($coverageTypes)) * 100)
                : 100;

            $offers[] = [
                'plan_id' => $plan->id,
                'plan_name' => $plan->name,
                'plan_code' => $plan->code,
                'provider' => [
                    'id' => $plan->provider->id,
                    'name' => $plan->provider->name,
                    'logo' => $plan->provider->logo_url,
                    'rating' => $plan->provider->rating ?? 4.0,
                    'claims_ratio' => $plan->provider->claims_ratio ?? 0,
                ],
                'premium' => $premium,
                'features' => $plan->features ?? [],
                'covered_risks' => $coveredRisks,
                'coverage_match' => $coverageMatch,
                'exclusions' => $plan->exclusions ?? [],
                'value_score' => $valueScore,
                'is_recommended' => $plan->is_recommended ?? false,
                'is_default' => $plan->is_default ?? false,
                'processing_time' => $plan->provider->avg_claim_processing_days ?? 14,
            ];
        }

        // Trier par value_score décroissant
        usort($offers, fn($a, $b) => $b['value_score'] - $a['value_score']);

        // Marquer le meilleur rapport qualité/prix
        if (count($offers) > 0) {
            $offers[0]['best_value'] = true;
        }

        // Trouver la couverture la plus complète
        $bestCoverage = collect($offers)->sortByDesc('coverage_match')->first();
        if ($bestCoverage) {
            $idx = array_search($bestCoverage, $offers);
            if ($idx !== false) {
                $offers[$idx]['best_coverage'] = true;
            }
        }

        return [
            'offers' => $offers,
            'requested_coverage' => $coverageTypes,
            'declared_value' => $declaredValue,
            'box_size' => $boxSize,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Souscrire via la marketplace avec comparaison
     */
    public function subscribeFromMarketplace(
        Contract $contract,
        int $planId,
        float $declaredValue,
        array $selectedCoverage = [],
        string $paymentFrequency = 'monthly'
    ): InsurancePolicy {
        $plan = InsurancePlan::findOrFail($planId);

        // Vérifier que le plan couvre les risques sélectionnés
        $coveredRisks = $plan->covered_risks ?? [];
        $missingCoverage = array_diff($selectedCoverage, $coveredRisks);

        if (!empty($missingCoverage)) {
            throw new \Exception('Le plan sélectionné ne couvre pas : ' . implode(', ', $missingCoverage));
        }

        return $this->subscribe($contract, $plan, $declaredValue, null, $paymentFrequency);
    }

    /**
     * Obtenir les providers d'assurance configurés
     */
    public function getProviders(int $tenantId): \Illuminate\Database\Eloquent\Collection
    {
        return InsuranceProvider::where('is_active', true)
            ->withCount(['plans' => fn($q) => $q->where('is_active', true)])
            ->withAvg('policies', 'premium_monthly')
            ->orderBy('order')
            ->get();
    }

    /**
     * Calculer la recommandation personnalisée
     */
    public function getPersonalizedRecommendation(
        Contract $contract,
        ?float $declaredValue = null
    ): array {
        $customer = $contract->customer;
        $box = $contract->box;

        // Estimation de la valeur si non fournie
        if (!$declaredValue) {
            $declaredValue = $this->estimateValue($box->size_m2 ?? $box->area ?? 5);
        }

        // Analyser le profil du client
        $customerProfile = $this->analyzeCustomerProfile($customer);

        // Obtenir les offres
        $comparison = $this->compareOffers(
            $contract->tenant_id,
            $declaredValue,
            $box->size_m2 ?? $box->area ?? 5,
            $customerProfile['recommended_coverage']
        );

        // Sélectionner la meilleure recommandation
        $recommended = collect($comparison['offers'])
            ->filter(fn($o) => $o['coverage_match'] >= 80)
            ->sortByDesc('value_score')
            ->first();

        return [
            'customer_profile' => $customerProfile,
            'estimated_value' => $declaredValue,
            'recommended_plan' => $recommended,
            'alternative_plans' => array_slice($comparison['offers'], 0, 3),
            'reasons' => $this->getRecommendationReasons($recommended, $customerProfile),
        ];
    }

    /**
     * Analyser le profil client pour recommandation
     */
    protected function analyzeCustomerProfile(Customer $customer): array
    {
        $profile = [
            'risk_level' => 'medium',
            'recommended_coverage' => ['theft', 'fire', 'water'],
            'factors' => [],
        ];

        // Entreprise = valeur plus élevée probable
        if ($customer->company || $customer->is_professional) {
            $profile['risk_level'] = 'high';
            $profile['recommended_coverage'][] = 'business_interruption';
            $profile['factors'][] = 'professional_use';
        }

        // Historique de sinistres
        $previousClaims = InsuranceClaim::whereHas('policy', fn($q) => $q->where('customer_id', $customer->id))
            ->count();

        if ($previousClaims > 0) {
            $profile['risk_level'] = 'high';
            $profile['factors'][] = 'previous_claims';
        }

        // Durée de location
        $activeContracts = Contract::where('customer_id', $customer->id)
            ->where('status', 'active')
            ->get();

        $avgDuration = $activeContracts->avg(fn($c) => $c->start_date->diffInMonths(now()));
        if ($avgDuration > 12) {
            $profile['factors'][] = 'long_term_customer';
        }

        return $profile;
    }

    /**
     * Estimer la valeur des biens stockés
     */
    protected function estimateValue(float $boxSizeM2): float
    {
        // Estimation basée sur la taille du box
        // Moyenne France : ~500€/m² de valeur stockée
        $baseValuePerM2 = 500;

        // Ajustement selon la taille (boxes plus grands = densité plus faible)
        if ($boxSizeM2 <= 3) {
            $multiplier = 1.5; // Affaires personnelles de valeur
        } elseif ($boxSizeM2 <= 10) {
            $multiplier = 1.0; // Mobilier standard
        } else {
            $multiplier = 0.7; // Stockage volumineux, moins dense
        }

        return round($boxSizeM2 * $baseValuePerM2 * $multiplier, -2); // Arrondi à la centaine
    }

    /**
     * Générer les raisons de la recommandation
     */
    protected function getRecommendationReasons(array $plan, array $profile): array
    {
        $reasons = [];

        if ($plan['best_value'] ?? false) {
            $reasons[] = 'Meilleur rapport qualité/prix du marché';
        }

        if ($plan['coverage_match'] >= 100) {
            $reasons[] = 'Couvre tous les risques recommandés pour votre profil';
        }

        if (in_array('long_term_customer', $profile['factors'])) {
            $reasons[] = 'Adapté aux clients fidèles avec couverture étendue';
        }

        if (in_array('professional_use', $profile['factors'])) {
            $reasons[] = 'Inclut la protection des biens professionnels';
        }

        if (($plan['provider']['rating'] ?? 0) >= 4.5) {
            $reasons[] = 'Assureur très bien noté par nos clients';
        }

        if (($plan['processing_time'] ?? 30) <= 7) {
            $reasons[] = 'Traitement rapide des sinistres (moins de 7 jours)';
        }

        return $reasons;
    }

    /**
     * Synchroniser avec l'API d'un assureur externe
     */
    public function syncWithExternalProvider(InsuranceProvider $provider): array
    {
        $result = [
            'plans_updated' => 0,
            'plans_created' => 0,
            'errors' => [],
        ];

        if (!$provider->api_endpoint || !$provider->api_key) {
            return $result;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $provider->api_key,
                'Accept' => 'application/json',
            ])
                ->timeout(30)
                ->get($provider->api_endpoint . '/plans');

            if (!$response->successful()) {
                $result['errors'][] = 'API returned ' . $response->status();
                return $result;
            }

            $externalPlans = $response->json('plans') ?? [];

            foreach ($externalPlans as $externalPlan) {
                $plan = InsurancePlan::updateOrCreate(
                    [
                        'provider_id' => $provider->id,
                        'external_id' => $externalPlan['id'],
                    ],
                    [
                        'name' => $externalPlan['name'],
                        'code' => $externalPlan['code'] ?? Str::slug($externalPlan['name']),
                        'description' => $externalPlan['description'] ?? '',
                        'price_monthly' => $externalPlan['monthly_premium'] ?? 0,
                        'price_yearly' => $externalPlan['annual_premium'] ?? null,
                        'coverage_amount' => $externalPlan['coverage_limit'] ?? 0,
                        'deductible' => $externalPlan['deductible'] ?? 0,
                        'covered_risks' => $externalPlan['covered_perils'] ?? [],
                        'exclusions' => $externalPlan['exclusions'] ?? [],
                        'features' => $externalPlan['features'] ?? [],
                        'is_active' => $externalPlan['is_available'] ?? true,
                    ]
                );

                if ($plan->wasRecentlyCreated) {
                    $result['plans_created']++;
                } else {
                    $result['plans_updated']++;
                }
            }

            $provider->update(['last_sync_at' => now()]);

        } catch (\Exception $e) {
            $result['errors'][] = $e->getMessage();
            Log::error('Insurance provider sync failed', [
                'provider_id' => $provider->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $result;
    }

    /**
     * Soumettre un sinistre directement à l'assureur via API
     */
    public function submitClaimToProvider(InsuranceClaim $claim): array
    {
        $policy = $claim->policy;
        $provider = $policy->plan->provider;

        if (!$provider->api_endpoint || !$provider->api_key) {
            return ['success' => false, 'message' => 'Provider API not configured'];
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $provider->api_key,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
                ->timeout(30)
                ->post($provider->api_endpoint . '/claims', [
                    'policy_number' => $policy->policy_number,
                    'claim_number' => $claim->claim_number,
                    'incident_date' => $claim->incident_date->format('Y-m-d'),
                    'incident_type' => $claim->incident_type,
                    'description' => $claim->incident_description,
                    'claimed_amount' => $claim->claimed_amount,
                    'customer' => [
                        'name' => $policy->customer->full_name,
                        'email' => $policy->customer->email,
                        'phone' => $policy->customer->phone,
                    ],
                    'documents' => $claim->documents ?? [],
                ]);

            if ($response->successful()) {
                $claim->update([
                    'external_claim_id' => $response->json('claim_id'),
                    'status' => 'submitted',
                    'submitted_at' => now(),
                ]);

                return [
                    'success' => true,
                    'external_claim_id' => $response->json('claim_id'),
                    'message' => 'Claim submitted to provider',
                ];
            }

            return [
                'success' => false,
                'message' => $response->json('message') ?? 'Submission failed',
            ];

        } catch (\Exception $e) {
            Log::error('Claim submission to provider failed', [
                'claim_id' => $claim->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Obtenir les statistiques de la marketplace
     */
    public function getMarketplaceStats(int $tenantId): array
    {
        $policies = InsurancePolicy::where('tenant_id', $tenantId);

        return [
            'total_policies' => (clone $policies)->count(),
            'active_policies' => (clone $policies)->where('status', 'active')->count(),
            'total_coverage' => (clone $policies)->where('status', 'active')->sum('coverage_amount'),
            'avg_premium' => round((clone $policies)->where('status', 'active')->avg('premium_monthly') ?? 0, 2),
            'providers_used' => InsuranceProvider::whereHas('plans.policies', fn($q) => $q->where('tenant_id', $tenantId))->count(),
            'conversion_rate' => $this->calculateInsuranceConversionRate($tenantId),
            'by_provider' => $this->getStatsByProvider($tenantId),
        ];
    }

    /**
     * Calculer le taux de conversion assurance
     */
    protected function calculateInsuranceConversionRate(int $tenantId): float
    {
        $totalContracts = Contract::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->count();

        $insuredContracts = InsurancePolicy::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->distinct('contract_id')
            ->count('contract_id');

        return $totalContracts > 0
            ? round(($insuredContracts / $totalContracts) * 100, 1)
            : 0;
    }

    /**
     * Statistiques par provider
     */
    protected function getStatsByProvider(int $tenantId): array
    {
        return InsurancePolicy::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->with('plan.provider')
            ->get()
            ->groupBy('plan.provider.name')
            ->map(function ($policies, $providerName) {
                return [
                    'provider' => $providerName,
                    'policies_count' => $policies->count(),
                    'total_coverage' => $policies->sum('coverage_amount'),
                    'avg_premium' => round($policies->avg('premium_monthly'), 2),
                ];
            })
            ->values()
            ->toArray();
    }
}
