<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\EmailSequence;
use App\Models\EmailSequenceEnrollment;
use App\Models\EmailTemplate;
use App\Models\EmailTracking;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EmailAutomationService
{
    protected EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Get automation dashboard data
     */
    public function getDashboardData(int $tenantId): array
    {
        $sequences = EmailSequence::where('tenant_id', $tenantId)->get();

        // Active automations
        $activeAutomations = $sequences->where('is_active', true)->count();

        // Total enrolled
        $totalEnrolled = EmailSequenceEnrollment::whereIn('email_sequence_id', $sequences->pluck('id'))
            ->where('status', 'active')
            ->count();

        // Emails sent last 30 days
        $emailsSent = EmailTracking::where('tenant_id', $tenantId)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        // Open rate
        $opened = EmailTracking::where('tenant_id', $tenantId)
            ->where('created_at', '>=', now()->subDays(30))
            ->whereNotNull('opened_at')
            ->count();
        $openRate = $emailsSent > 0 ? round($opened / $emailsSent * 100, 1) : 0;

        // Click rate
        $clicked = EmailTracking::where('tenant_id', $tenantId)
            ->where('created_at', '>=', now()->subDays(30))
            ->whereNotNull('clicked_at')
            ->count();
        $clickRate = $emailsSent > 0 ? round($clicked / $emailsSent * 100, 1) : 0;

        // Conversion rate
        $conversions = EmailSequenceEnrollment::whereIn('email_sequence_id', $sequences->pluck('id'))
            ->where('status', 'converted')
            ->where('completed_at', '>=', now()->subDays(30))
            ->count();
        $conversionRate = $totalEnrolled > 0 ? round($conversions / $totalEnrolled * 100, 1) : 0;

        return [
            'stats' => [
                'active_automations' => $activeAutomations,
                'total_enrolled' => $totalEnrolled,
                'emails_sent_30d' => $emailsSent,
                'open_rate' => $openRate,
                'click_rate' => $clickRate,
                'conversion_rate' => $conversionRate,
            ],
            'sequences' => $sequences->map(fn($s) => $this->formatSequence($s)),
            'recent_activity' => $this->getRecentActivity($tenantId),
            'performance_chart' => $this->getPerformanceChart($tenantId),
        ];
    }

    /**
     * Format sequence for frontend
     */
    protected function formatSequence(EmailSequence $sequence): array
    {
        $enrollments = $sequence->enrollments();
        $active = $enrollments->clone()->where('status', 'active')->count();
        $completed = $enrollments->clone()->where('status', 'completed')->count();
        $converted = $enrollments->clone()->where('status', 'converted')->count();

        return [
            'id' => $sequence->id,
            'name' => $sequence->name,
            'description' => $sequence->description,
            'trigger' => $sequence->trigger,
            'trigger_label' => $this->getTriggerLabel($sequence->trigger),
            'is_active' => $sequence->is_active,
            'steps_count' => count($sequence->steps ?? []),
            'steps' => $sequence->steps,
            'enrolled' => $active,
            'completed' => $completed,
            'converted' => $converted,
            'completion_rate' => ($active + $completed + $converted) > 0
                ? round($completed / ($active + $completed + $converted) * 100)
                : 0,
            'conversion_rate' => ($completed + $converted) > 0
                ? round($converted / ($completed + $converted) * 100)
                : 0,
            'created_at' => $sequence->created_at,
        ];
    }

    /**
     * Get trigger label
     */
    protected function getTriggerLabel(string $trigger): string
    {
        return match($trigger) {
            'new_lead' => 'Nouveau lead',
            'new_customer' => 'Nouveau client',
            'contract_signed' => 'Contrat signé',
            'contract_expiring' => 'Contrat expire bientôt',
            'invoice_overdue' => 'Facture impayée',
            'inactive_customer' => 'Client inactif',
            'birthday' => 'Anniversaire',
            'referral' => 'Parrainage',
            'abandoned_cart' => 'Panier abandonné',
            'manual' => 'Manuel',
            default => ucfirst(str_replace('_', ' ', $trigger)),
        };
    }

    /**
     * Get recent activity
     */
    protected function getRecentActivity(int $tenantId): array
    {
        return EmailTracking::where('tenant_id', $tenantId)
            ->with(['customer', 'lead'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'recipient' => $t->customer?->full_name ?? $t->lead?->full_name ?? $t->email,
                'subject' => $t->subject,
                'status' => $t->opened_at ? 'opened' : ($t->delivered_at ? 'delivered' : 'sent'),
                'opened_at' => $t->opened_at,
                'clicked_at' => $t->clicked_at,
                'created_at' => $t->created_at,
            ])
            ->toArray();
    }

    /**
     * Get performance chart data
     */
    protected function getPerformanceChart(int $tenantId): array
    {
        $data = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $sent = EmailTracking::where('tenant_id', $tenantId)
                ->whereDate('created_at', $date)
                ->count();
            $opened = EmailTracking::where('tenant_id', $tenantId)
                ->whereDate('created_at', $date)
                ->whereNotNull('opened_at')
                ->count();

            $data[] = [
                'date' => now()->subDays($i)->format('d/m'),
                'sent' => $sent,
                'opened' => $opened,
            ];
        }

        return $data;
    }

    /**
     * Create a new automation sequence
     */
    public function createSequence(int $tenantId, array $data): EmailSequence
    {
        return EmailSequence::create([
            'tenant_id' => $tenantId,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'trigger' => $data['trigger'],
            'is_active' => $data['is_active'] ?? false,
            'steps' => $data['steps'] ?? [],
        ]);
    }

    /**
     * Update sequence
     */
    public function updateSequence(EmailSequence $sequence, array $data): EmailSequence
    {
        $sequence->update([
            'name' => $data['name'] ?? $sequence->name,
            'description' => $data['description'] ?? $sequence->description,
            'trigger' => $data['trigger'] ?? $sequence->trigger,
            'is_active' => $data['is_active'] ?? $sequence->is_active,
            'steps' => $data['steps'] ?? $sequence->steps,
        ]);

        return $sequence->fresh();
    }

    /**
     * Enroll a customer/lead in a sequence
     */
    public function enroll(EmailSequence $sequence, ?Customer $customer = null, ?Lead $lead = null): ?EmailSequenceEnrollment
    {
        if (!$sequence->is_active) {
            return null;
        }

        // Check if already enrolled
        $existing = EmailSequenceEnrollment::where('email_sequence_id', $sequence->id)
            ->where(function ($q) use ($customer, $lead) {
                if ($customer) $q->where('customer_id', $customer->id);
                if ($lead) $q->orWhere('lead_id', $lead->id);
            })
            ->whereIn('status', ['active', 'paused'])
            ->exists();

        if ($existing) {
            return null;
        }

        $steps = $sequence->steps ?? [];
        $firstStep = $steps[0] ?? null;
        $nextSendAt = $firstStep ? now()->addMinutes($firstStep['delay_minutes'] ?? 0) : null;

        return EmailSequenceEnrollment::create([
            'email_sequence_id' => $sequence->id,
            'customer_id' => $customer?->id,
            'lead_id' => $lead?->id,
            'status' => 'active',
            'current_step' => 0,
            'next_send_at' => $nextSendAt,
            'enrolled_at' => now(),
        ]);
    }

    /**
     * Process due emails for all sequences
     */
    public function processDueEmails(): array
    {
        $processed = 0;
        $errors = 0;

        $dueEnrollments = EmailSequenceEnrollment::with(['emailSequence', 'customer', 'lead'])
            ->where('status', 'active')
            ->where('next_send_at', '<=', now())
            ->limit(100)
            ->get();

        foreach ($dueEnrollments as $enrollment) {
            try {
                $this->processEnrollmentStep($enrollment);
                $processed++;
            } catch (\Exception $e) {
                $errors++;
                \Log::error('Email automation error: ' . $e->getMessage());
            }
        }

        return ['processed' => $processed, 'errors' => $errors];
    }

    /**
     * Process a single enrollment step
     */
    protected function processEnrollmentStep(EmailSequenceEnrollment $enrollment): void
    {
        $sequence = $enrollment->emailSequence;
        $steps = $sequence->steps ?? [];
        $currentStep = $enrollment->current_step;

        if (!isset($steps[$currentStep])) {
            // Sequence completed
            $enrollment->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
            return;
        }

        $step = $steps[$currentStep];
        $recipient = $enrollment->customer ?? $enrollment->lead;

        if (!$recipient || !$recipient->email) {
            $enrollment->update(['status' => 'failed']);
            return;
        }

        // Send email
        $this->sendStepEmail($enrollment, $step, $recipient);

        // Update enrollment
        $nextStep = $currentStep + 1;
        if (isset($steps[$nextStep])) {
            $delayMinutes = $steps[$nextStep]['delay_minutes'] ?? 1440; // Default 1 day
            $enrollment->update([
                'current_step' => $nextStep,
                'next_send_at' => now()->addMinutes($delayMinutes),
            ]);
        } else {
            $enrollment->update([
                'current_step' => $nextStep,
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }
    }

    /**
     * Send step email
     */
    protected function sendStepEmail(EmailSequenceEnrollment $enrollment, array $step, $recipient): void
    {
        $sequence = $enrollment->emailSequence;

        // Get template or use step content
        $subject = $step['subject'] ?? 'Message de ' . config('app.name');
        $content = $step['content'] ?? '';

        // Replace placeholders
        $replacements = [
            '{{first_name}}' => $recipient->first_name ?? '',
            '{{last_name}}' => $recipient->last_name ?? '',
            '{{full_name}}' => $recipient->full_name ?? '',
            '{{email}}' => $recipient->email ?? '',
            '{{company}}' => $recipient->company ?? '',
        ];

        $subject = str_replace(array_keys($replacements), array_values($replacements), $subject);
        $content = str_replace(array_keys($replacements), array_values($replacements), $content);

        // Track email
        $tracking = EmailTracking::create([
            'tenant_id' => $sequence->tenant_id,
            'customer_id' => $enrollment->customer_id,
            'lead_id' => $enrollment->lead_id,
            'email' => $recipient->email,
            'subject' => $subject,
            'tracking_id' => Str::uuid(),
        ]);

        // Send via email service
        $this->emailService->send(
            $sequence->tenant_id,
            $recipient->email,
            $subject,
            $content,
            [
                'tracking_id' => $tracking->tracking_id,
                'sequence_id' => $sequence->id,
                'enrollment_id' => $enrollment->id,
            ]
        );

        $tracking->update(['sent_at' => now()]);
    }

    /**
     * Get available triggers
     */
    public function getAvailableTriggers(): array
    {
        return [
            ['value' => 'new_lead', 'label' => 'Nouveau lead', 'description' => 'Déclenché quand un nouveau lead est créé'],
            ['value' => 'new_customer', 'label' => 'Nouveau client', 'description' => 'Déclenché quand un nouveau client s\'inscrit'],
            ['value' => 'contract_signed', 'label' => 'Contrat signé', 'description' => 'Déclenché après signature d\'un contrat'],
            ['value' => 'contract_expiring', 'label' => 'Contrat expire bientôt', 'description' => 'Déclenché X jours avant expiration'],
            ['value' => 'invoice_overdue', 'label' => 'Facture impayée', 'description' => 'Déclenché quand une facture est en retard'],
            ['value' => 'inactive_customer', 'label' => 'Client inactif', 'description' => 'Déclenché après X jours sans activité'],
            ['value' => 'birthday', 'label' => 'Anniversaire', 'description' => 'Déclenché le jour de l\'anniversaire'],
            ['value' => 'abandoned_cart', 'label' => 'Panier abandonné', 'description' => 'Déclenché après abandon de réservation'],
            ['value' => 'referral', 'label' => 'Parrainage', 'description' => 'Déclenché lors d\'un parrainage réussi'],
            ['value' => 'manual', 'label' => 'Manuel', 'description' => 'Inscription manuelle uniquement'],
        ];
    }

    /**
     * Get sequence analytics
     */
    public function getSequenceAnalytics(EmailSequence $sequence): array
    {
        $enrollments = $sequence->enrollments;
        $stepAnalytics = [];

        foreach ($sequence->steps ?? [] as $index => $step) {
            $atStep = $enrollments->where('current_step', $index)->where('status', 'active')->count();
            $passedStep = $enrollments->where('current_step', '>', $index)->count();
            $completedAtStep = $enrollments->where('status', 'completed')->where('current_step', $index + 1)->count();

            $stepAnalytics[] = [
                'step' => $index + 1,
                'name' => $step['name'] ?? 'Étape ' . ($index + 1),
                'subject' => $step['subject'] ?? '',
                'active' => $atStep,
                'passed' => $passedStep,
                'completed' => $completedAtStep,
                'drop_off_rate' => ($atStep + $passedStep) > 0
                    ? round(($atStep / ($atStep + $passedStep)) * 100)
                    : 0,
            ];
        }

        return [
            'total_enrolled' => $enrollments->count(),
            'active' => $enrollments->where('status', 'active')->count(),
            'completed' => $enrollments->where('status', 'completed')->count(),
            'converted' => $enrollments->where('status', 'converted')->count(),
            'failed' => $enrollments->where('status', 'failed')->count(),
            'paused' => $enrollments->where('status', 'paused')->count(),
            'steps' => $stepAnalytics,
            'funnel' => $this->buildFunnel($sequence, $enrollments),
        ];
    }

    /**
     * Build funnel visualization data
     */
    protected function buildFunnel(EmailSequence $sequence, $enrollments): array
    {
        $total = $enrollments->count();
        $funnel = [
            ['stage' => 'Inscrits', 'count' => $total, 'percentage' => 100],
        ];

        $steps = $sequence->steps ?? [];
        $remaining = $total;

        foreach ($steps as $index => $step) {
            $passedThisStep = $enrollments->filter(function ($e) use ($index) {
                return $e->current_step > $index || in_array($e->status, ['completed', 'converted']);
            })->count();

            $funnel[] = [
                'stage' => $step['name'] ?? 'Étape ' . ($index + 1),
                'count' => $passedThisStep,
                'percentage' => $total > 0 ? round($passedThisStep / $total * 100) : 0,
            ];

            $remaining = $passedThisStep;
        }

        $converted = $enrollments->where('status', 'converted')->count();
        $funnel[] = [
            'stage' => 'Convertis',
            'count' => $converted,
            'percentage' => $total > 0 ? round($converted / $total * 100) : 0,
        ];

        return $funnel;
    }
}
