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
}
