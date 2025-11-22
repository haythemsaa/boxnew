<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Customer;
use App\Models\EmailSequence;
use App\Models\EmailSequenceEnrollment;
use App\Models\Campaign;
use Illuminate\Support\Facades\Mail;

class CRMService
{
    /**
     * Create lead from form submission
     */
    public function createLead(array $data): Lead
    {
        $lead = Lead::create($data);

        // Calculate initial score
        $lead->updateScore();

        // Auto-assign if rules exist
        $this->autoAssignLead($lead);

        // Trigger email sequence for new leads
        $this->enrollInSequence($lead, 'new_lead');

        // Send instant response
        $this->sendInstantResponse($lead);

        return $lead->fresh();
    }

    /**
     * Auto-assign lead based on rules
     */
    protected function autoAssignLead(Lead $lead): void
    {
        // Simple round-robin assignment
        // In production, this could be more sophisticated
        $users = \App\Models\User::where('tenant_id', $lead->tenant_id)
            ->whereHas('roles', function ($q) {
                $q->where('name', 'sales');
            })
            ->get();

        if ($users->isEmpty()) {
            return;
        }

        // Get user with least active leads
        $assignee = $users->sortBy(function ($user) {
            return $user->assignedLeads()->where('status', '!=', 'converted')->count();
        })->first();

        $lead->update(['assigned_to' => $assignee->id]);
    }

    /**
     * Send instant auto-response email
     */
    protected function sendInstantResponse(Lead $lead): void
    {
        // In production, send actual email via queue
        // Mail::to($lead->email)->queue(new LeadAutoResponse($lead));
    }

    /**
     * Enroll in email sequence
     */
    protected function enrollInSequence(Lead $lead, string $trigger): void
    {
        $sequence = EmailSequence::active()
            ->forTrigger($trigger)
            ->where('tenant_id', $lead->tenant_id)
            ->first();

        if (!$sequence) {
            return;
        }

        $steps = $sequence->steps;
        $firstStep = $steps[0] ?? null;

        if (!$firstStep) {
            return;
        }

        EmailSequenceEnrollment::create([
            'email_sequence_id' => $sequence->id,
            'lead_id' => $lead->id,
            'status' => 'active',
            'current_step' => 0,
            'next_send_at' => now()->addMinutes($firstStep['delay_minutes'] ?? 30),
            'enrolled_at' => now(),
        ]);

        $sequence->increment('total_enrolled');
    }

    /**
     * Convert lead to customer
     */
    public function convertLeadToCustomer(Lead $lead, array $customerData): Customer
    {
        $customer = Customer::create(array_merge([
            'tenant_id' => $lead->tenant_id,
            'first_name' => $lead->first_name,
            'last_name' => $lead->last_name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'company' => $lead->company,
        ], $customerData));

        $lead->update([
            'status' => 'converted',
            'converted_at' => now(),
            'converted_to_customer_id' => $customer->id,
        ]);

        // Enroll in onboarding sequence
        $this->enrollCustomerInSequence($customer, 'onboarding');

        return $customer;
    }

    /**
     * Enroll customer in email sequence
     */
    protected function enrollCustomerInSequence(Customer $customer, string $trigger): void
    {
        $sequence = EmailSequence::active()
            ->forTrigger($trigger)
            ->where('tenant_id', $customer->tenant_id)
            ->first();

        if (!$sequence) {
            return;
        }

        $steps = $sequence->steps;
        $firstStep = $steps[0] ?? null;

        if (!$firstStep) {
            return;
        }

        EmailSequenceEnrollment::create([
            'email_sequence_id' => $sequence->id,
            'customer_id' => $customer->id,
            'status' => 'active',
            'current_step' => 0,
            'next_send_at' => now()->addMinutes($firstStep['delay_minutes'] ?? 0),
            'enrolled_at' => now(),
        ]);

        $sequence->increment('total_enrolled');
    }

    /**
     * Get lead analytics
     */
    public function getLeadAnalytics($tenantId, $startDate, $endDate): array
    {
        $leads = Lead::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $total = $leads->count();
        $converted = $leads->where('status', 'converted')->count();
        $lost = $leads->where('status', 'lost')->count();

        return [
            'total_leads' => $total,
            'converted' => $converted,
            'lost' => $lost,
            'active' => $total - $converted - $lost,
            'conversion_rate' => $total > 0 ? ($converted / $total) * 100 : 0,
            'average_score' => $leads->avg('score'),
            'by_source' => $leads->groupBy('source')->map->count(),
            'by_status' => $leads->groupBy('status')->map->count(),
            'hot_leads' => $leads->where('score', '>=', 70)->count(),
            'unassigned' => $leads->whereNull('assigned_to')->count(),
        ];
    }

    /**
     * Get funnel metrics
     */
    public function getFunnelMetrics($tenantId, $startDate, $endDate): array
    {
        $leads = Lead::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $visitors = $leads->count() * 5; // Assume 20% of visitors become leads
        $leadsCount = $leads->count();
        $qualifiedCount = $leads->whereIn('status', ['qualified', 'converted'])->count();
        $convertedCount = $leads->where('status', 'converted')->count();

        return [
            'visitors' => $visitors,
            'leads' => $leadsCount,
            'qualified' => $qualifiedCount,
            'customers' => $convertedCount,
            'visitor_to_lead' => $visitors > 0 ? ($leadsCount / $visitors) * 100 : 0,
            'lead_to_qualified' => $leadsCount > 0 ? ($qualifiedCount / $leadsCount) * 100 : 0,
            'qualified_to_customer' => $qualifiedCount > 0 ? ($convertedCount / $qualifiedCount) * 100 : 0,
            'overall_conversion' => $visitors > 0 ? ($convertedCount / $visitors) * 100 : 0,
        ];
    }

    /**
     * Get campaign performance
     */
    public function getCampaignPerformance($tenantId): array
    {
        $campaigns = Campaign::where('tenant_id', $tenantId)
            ->where('status', '!=', 'draft')
            ->get();

        return $campaigns->map(function ($campaign) {
            return [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'type' => $campaign->type,
                'status' => $campaign->status,
                'sent' => $campaign->sent_count,
                'delivered' => $campaign->delivered_count,
                'opened' => $campaign->opened_count,
                'clicked' => $campaign->clicked_count,
                'converted' => $campaign->converted_count,
                'open_rate' => $campaign->open_rate,
                'click_rate' => $campaign->click_rate,
                'conversion_rate' => $campaign->conversion_rate,
            ];
        })->toArray();
    }

    /**
     * Detect churn risk customers
     */
    public function detectChurnRisk($tenantId): array
    {
        $customers = Customer::where('tenant_id', $tenantId)
            ->with(['contracts', 'payments', 'invoices'])
            ->get();

        $atRisk = [];

        foreach ($customers as $customer) {
            $score = 0;
            $signals = [];

            // Late payments
            $latePayments = $customer->payments()
                ->where('status', 'failed')
                ->where('created_at', '>=', now()->subMonths(3))
                ->count();

            if ($latePayments >= 2) {
                $score += 30;
                $signals[] = "Multiple late payments ({$latePayments})";
            }

            // Upcoming contract expiry
            $expiringContracts = $customer->contracts()
                ->where('end_date', '>=', now())
                ->where('end_date', '<=', now()->addDays(30))
                ->count();

            if ($expiringContracts > 0) {
                $score += 40;
                $signals[] = "Contract expiring soon ({$expiringContracts})";
            }

            // Low engagement (no access logs recently)
            // This would require access log integration
            // For now, simplified check

            if ($score >= 60) {
                $atRisk[] = [
                    'customer' => $customer,
                    'risk_score' => $score,
                    'signals' => $signals,
                    'recommended_action' => $score >= 80 ? 'Immediate call' : 'Send retention email',
                ];
            }
        }

        return $atRisk;
    }

    /**
     * Get customer segmentation
     */
    public function segmentCustomers($tenantId): array
    {
        $customers = Customer::where('tenant_id', $tenantId)
            ->with(['contracts', 'payments'])
            ->get();

        return [
            'vip' => $customers->filter(function ($c) {
                return $c->contracts->sum('monthly_amount') >= 200;
            })->count(),
            'active' => $customers->filter(function ($c) {
                return $c->contracts()->where('status', 'active')->exists();
            })->count(),
            'at_risk' => count($this->detectChurnRisk($tenantId)),
            'new' => $customers->filter(function ($c) {
                return $c->created_at >= now()->subDays(30);
            })->count(),
            'inactive' => $customers->filter(function ($c) {
                return !$c->contracts()->where('status', 'active')->exists();
            })->count(),
        ];
    }
}
