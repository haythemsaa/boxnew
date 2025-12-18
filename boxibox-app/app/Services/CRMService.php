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
                return $c->contracts->sum('monthly_price') >= 200;
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

    // ========================================================================
    // AUTO FOLLOW-UP SYSTEM (Inspiré de Storeganise/Swivl)
    // ========================================================================

    /**
     * Processus automatique de follow-up des leads
     * À exécuter via cron: php artisan crm:auto-followup
     */
    public function processAutoFollowUp(int $tenantId): array
    {
        $results = [
            'processed' => 0,
            'emails_sent' => 0,
            'sms_sent' => 0,
            'calls_scheduled' => 0,
            'leads_updated' => 0,
            'errors' => [],
        ];

        // 1. Leads sans réponse depuis X jours
        $noResponseLeads = $this->getLeadsNeedingFollowUp($tenantId);

        foreach ($noResponseLeads as $lead) {
            try {
                $action = $this->determineFollowUpAction($lead);
                $this->executeFollowUpAction($lead, $action);
                $results[$action['type'] . '_sent']++;
                $results['processed']++;
            } catch (\Exception $e) {
                $results['errors'][] = "Lead {$lead->id}: " . $e->getMessage();
            }
        }

        // 2. Leads abandonnés (réservation non complétée)
        $abandonedLeads = $this->getAbandonedReservations($tenantId);

        foreach ($abandonedLeads as $lead) {
            try {
                $this->sendAbandonmentRecovery($lead);
                $results['emails_sent']++;
                $results['processed']++;
            } catch (\Exception $e) {
                $results['errors'][] = "Abandoned {$lead->id}: " . $e->getMessage();
            }
        }

        // 3. Mise à jour automatique des scores
        $this->recalculateLeadScores($tenantId);
        $results['leads_updated'] = Lead::where('tenant_id', $tenantId)->count();

        return $results;
    }

    /**
     * Obtenir les leads nécessitant un follow-up
     */
    protected function getLeadsNeedingFollowUp(int $tenantId): \Illuminate\Database\Eloquent\Collection
    {
        return Lead::where('tenant_id', $tenantId)
            ->whereIn('status', ['new', 'contacted', 'qualified'])
            ->where(function ($query) {
                // Pas de contact depuis 2+ jours
                $query->where('last_contact_at', '<=', now()->subDays(2))
                      ->orWhereNull('last_contact_at');
            })
            ->where('follow_up_count', '<', 5) // Max 5 follow-ups automatiques
            ->where(function ($query) {
                // Pas de follow-up planifié pour aujourd'hui
                $query->whereNull('next_follow_up_at')
                      ->orWhere('next_follow_up_at', '<=', now());
            })
            ->orderBy('score', 'desc') // Prioriser les leads chauds
            ->limit(50) // Traiter par batch
            ->get();
    }

    /**
     * Déterminer l'action de follow-up appropriée
     */
    protected function determineFollowUpAction(Lead $lead): array
    {
        $followUpCount = $lead->follow_up_count ?? 0;
        $score = $lead->score ?? 0;
        $source = $lead->source ?? 'website';

        // Matrice de décision intelligente
        if ($followUpCount === 0) {
            // Premier follow-up: email de bienvenue + info
            return [
                'type' => 'emails',
                'template' => 'lead_followup_1',
                'subject' => 'Votre demande de stockage - Des questions ?',
                'delay_hours' => 0,
                'priority' => 'high',
            ];
        } elseif ($followUpCount === 1 && $score >= 60) {
            // Lead chaud sans réponse: SMS personnalisé
            return [
                'type' => 'sms',
                'template' => 'lead_sms_followup',
                'delay_hours' => 24,
                'priority' => 'high',
            ];
        } elseif ($followUpCount === 2) {
            // 3ème tentative: email avec offre spéciale
            return [
                'type' => 'emails',
                'template' => 'lead_followup_offer',
                'subject' => 'Offre spéciale: -10% sur votre premier mois',
                'delay_hours' => 48,
                'priority' => 'medium',
            ];
        } elseif ($followUpCount === 3 && $score >= 70) {
            // Lead très chaud: programmer un appel
            return [
                'type' => 'calls',
                'action' => 'schedule_callback',
                'delay_hours' => 24,
                'priority' => 'high',
            ];
        } else {
            // Dernier follow-up: email de clôture
            return [
                'type' => 'emails',
                'template' => 'lead_final_followup',
                'subject' => 'Toujours à la recherche d\'un box de stockage ?',
                'delay_hours' => 72,
                'priority' => 'low',
            ];
        }
    }

    /**
     * Exécuter l'action de follow-up
     */
    protected function executeFollowUpAction(Lead $lead, array $action): void
    {
        switch ($action['type']) {
            case 'emails':
                $this->sendFollowUpEmail($lead, $action);
                break;
            case 'sms':
                $this->sendFollowUpSMS($lead, $action);
                break;
            case 'calls':
                $this->scheduleCallback($lead, $action);
                break;
        }

        // Mettre à jour le lead
        $lead->update([
            'follow_up_count' => ($lead->follow_up_count ?? 0) + 1,
            'last_follow_up_at' => now(),
            'next_follow_up_at' => now()->addHours($action['delay_hours'] ?? 48),
        ]);

        // Logger l'activité
        $this->logLeadActivity($lead, 'auto_followup', [
            'action_type' => $action['type'],
            'template' => $action['template'] ?? null,
            'priority' => $action['priority'] ?? 'medium',
        ]);
    }

    /**
     * Envoyer un email de follow-up
     */
    protected function sendFollowUpEmail(Lead $lead, array $action): void
    {
        $template = \App\Models\EmailTemplate::where('tenant_id', $lead->tenant_id)
            ->where('slug', $action['template'])
            ->first();

        if (!$template) {
            // Utiliser un template par défaut
            $content = $this->getDefaultFollowUpEmailContent($lead, $action);
        } else {
            $content = $template->render([
                'lead' => $lead,
                'site' => $lead->site,
            ]);
        }

        // Envoyer via le service email
        app(\App\Services\EmailService::class)->send([
            'to' => $lead->email,
            'subject' => $action['subject'] ?? 'Suivi de votre demande',
            'body' => $content,
            'tenant_id' => $lead->tenant_id,
            'lead_id' => $lead->id,
            'type' => 'lead_followup',
        ]);
    }

    /**
     * Envoyer un SMS de follow-up
     */
    protected function sendFollowUpSMS(Lead $lead, array $action): void
    {
        if (!$lead->phone) {
            throw new \Exception('Pas de numéro de téléphone');
        }

        $message = $this->getFollowUpSMSContent($lead, $action);

        // Envoyer via le service SMS
        app(\App\Services\SMSService::class)->send([
            'to' => $lead->phone,
            'message' => $message,
            'tenant_id' => $lead->tenant_id,
            'lead_id' => $lead->id,
            'type' => 'lead_followup',
        ]);
    }

    /**
     * Programmer un rappel téléphonique
     */
    protected function scheduleCallback(Lead $lead, array $action): void
    {
        // Créer une tâche pour l'équipe commerciale
        \App\Models\Task::create([
            'tenant_id' => $lead->tenant_id,
            'assigned_to' => $lead->assigned_to,
            'type' => 'callback',
            'title' => "Rappeler {$lead->full_name}",
            'description' => "Lead chaud (score: {$lead->score}) sans réponse aux follow-ups automatiques. Action manuelle requise.",
            'due_date' => now()->addHours($action['delay_hours'] ?? 24),
            'priority' => $action['priority'] ?? 'high',
            'related_type' => 'lead',
            'related_id' => $lead->id,
            'status' => 'pending',
        ]);
    }

    /**
     * Contenu email par défaut pour follow-up
     */
    protected function getDefaultFollowUpEmailContent(Lead $lead, array $action): string
    {
        $siteName = $lead->site?->name ?? 'notre centre';
        $firstName = $lead->first_name ?? 'Client';

        $templates = [
            'lead_followup_1' => "Bonjour {$firstName},\n\nMerci pour votre intérêt pour nos boxes de stockage à {$siteName}.\n\nAvez-vous des questions ? Notre équipe est disponible pour vous aider à trouver la solution idéale.\n\nCordialement,\nL'équipe BoxiBox",
            'lead_followup_offer' => "Bonjour {$firstName},\n\nNous avons remarqué que vous n'avez pas encore finalisé votre réservation.\n\nPour vous remercier de votre intérêt, nous vous offrons 10% de réduction sur votre premier mois avec le code BIENVENUE10.\n\nCette offre est valable 48h.\n\nCordialement,\nL'équipe BoxiBox",
            'lead_final_followup' => "Bonjour {$firstName},\n\nÊtes-vous toujours à la recherche d'une solution de stockage ?\n\nSi vous avez trouvé une alternative ou si vous avez des questions, n'hésitez pas à nous contacter.\n\nNous restons à votre disposition.\n\nCordialement,\nL'équipe BoxiBox",
        ];

        return $templates[$action['template']] ?? $templates['lead_followup_1'];
    }

    /**
     * Contenu SMS pour follow-up
     */
    protected function getFollowUpSMSContent(Lead $lead, array $action): string
    {
        $firstName = $lead->first_name ?? '';
        $siteName = $lead->site?->name ?? 'BoxiBox';

        return "Bonjour{$firstName}, suite à votre demande de box chez {$siteName}, avez-vous des questions ? Répondez STOP pour ne plus recevoir de SMS.";
    }

    /**
     * Logger l'activité du lead
     */
    protected function logLeadActivity(Lead $lead, string $type, array $data = []): void
    {
        \App\Models\LeadActivity::create([
            'lead_id' => $lead->id,
            'tenant_id' => $lead->tenant_id,
            'type' => $type,
            'data' => $data,
            'performed_by' => 'system',
            'created_at' => now(),
        ]);
    }

    /**
     * Récupérer les réservations abandonnées
     */
    protected function getAbandonedReservations(int $tenantId): \Illuminate\Database\Eloquent\Collection
    {
        // Prospects/leads qui ont commencé une réservation mais pas finalisé
        return \App\Models\Prospect::where('tenant_id', $tenantId)
            ->where('status', 'pending')
            ->where('created_at', '<=', now()->subHours(2))
            ->where('created_at', '>=', now()->subDays(3))
            ->whereNull('abandoned_email_sent_at')
            ->limit(30)
            ->get();
    }

    /**
     * Envoyer un email de récupération d'abandon
     */
    protected function sendAbandonmentRecovery($prospect): void
    {
        $content = "Bonjour,\n\nVous avez commencé une réservation de box sur notre site mais ne l'avez pas finalisée.\n\nVotre box vous attend ! Reprenez votre réservation dès maintenant.\n\nSi vous rencontrez des difficultés, notre équipe est là pour vous aider.\n\nCordialement,\nL'équipe BoxiBox";

        app(\App\Services\EmailService::class)->send([
            'to' => $prospect->email,
            'subject' => 'Votre réservation vous attend !',
            'body' => $content,
            'tenant_id' => $prospect->tenant_id,
            'prospect_id' => $prospect->id,
            'type' => 'abandonment_recovery',
        ]);

        $prospect->update(['abandoned_email_sent_at' => now()]);
    }

    /**
     * Recalculer les scores de tous les leads d'un tenant
     */
    public function recalculateLeadScores(int $tenantId): int
    {
        $leads = Lead::where('tenant_id', $tenantId)
            ->whereIn('status', ['new', 'contacted', 'qualified'])
            ->get();

        $updated = 0;
        foreach ($leads as $lead) {
            $oldScore = $lead->score;
            $newScore = $this->calculateAdvancedLeadScore($lead);

            if ($oldScore !== $newScore) {
                $lead->update(['score' => $newScore]);
                $updated++;

                // Notifier si le lead devient "chaud"
                if ($newScore >= 80 && $oldScore < 80) {
                    $this->notifyHotLead($lead);
                }
            }
        }

        return $updated;
    }

    /**
     * Calculer un score de lead avancé
     */
    public function calculateAdvancedLeadScore(Lead $lead): int
    {
        $score = 0;

        // 1. Source du lead (0-25 points)
        $sourceScores = [
            'google_ads' => 25,
            'google_organic' => 20,
            'facebook_ads' => 18,
            'referral' => 22,
            'direct' => 15,
            'website' => 12,
            'walk_in' => 25,
            'phone' => 23,
            'partner' => 20,
        ];
        $score += $sourceScores[$lead->source] ?? 10;

        // 2. Complétude du profil (0-15 points)
        if ($lead->email) $score += 5;
        if ($lead->phone) $score += 5;
        if ($lead->company) $score += 3;
        if ($lead->address) $score += 2;

        // 3. Engagement (0-20 points)
        if ($lead->email_opened_at) $score += 5;
        if ($lead->email_clicked_at) $score += 8;
        if ($lead->visited_pricing_at) $score += 7;

        // 4. Intention (0-20 points)
        $intentScores = [
            'immediate' => 20,
            'this_week' => 15,
            'this_month' => 10,
            'exploring' => 5,
        ];
        $score += $intentScores[$lead->move_in_timeline] ?? 5;

        // 5. Budget (0-10 points)
        if ($lead->budget && $lead->budget >= 100) $score += 10;
        elseif ($lead->budget && $lead->budget >= 50) $score += 5;

        // 6. Récence (0-10 points)
        $daysSinceCreated = $lead->created_at->diffInDays(now());
        if ($daysSinceCreated <= 1) $score += 10;
        elseif ($daysSinceCreated <= 3) $score += 7;
        elseif ($daysSinceCreated <= 7) $score += 4;
        elseif ($daysSinceCreated <= 14) $score += 2;

        // Déductions
        // Beaucoup de follow-ups sans réponse = score réduit
        $followUpCount = $lead->follow_up_count ?? 0;
        if ($followUpCount >= 3) $score -= 10;
        elseif ($followUpCount >= 2) $score -= 5;

        return max(0, min(100, $score));
    }

    /**
     * Notifier l'équipe d'un lead chaud
     */
    protected function notifyHotLead(Lead $lead): void
    {
        // Notification in-app
        if ($lead->assigned_to) {
            \App\Models\Notification::create([
                'user_id' => $lead->assigned_to,
                'type' => 'hot_lead',
                'title' => 'Lead chaud détecté !',
                'message' => "{$lead->full_name} est maintenant un lead chaud (score: {$lead->score}). Action rapide recommandée.",
                'data' => ['lead_id' => $lead->id],
                'read_at' => null,
            ]);
        }

        // Push notification si disponible
        try {
            app(\App\Services\PushNotificationService::class)->sendToUser(
                $lead->assigned_to,
                'Lead chaud détecté !',
                "{$lead->full_name} - Score: {$lead->score}",
                ['type' => 'hot_lead', 'lead_id' => $lead->id]
            );
        } catch (\Exception $e) {
            // Silent fail
        }
    }

    /**
     * Obtenir les statistiques de follow-up
     */
    public function getFollowUpStats(int $tenantId, int $days = 30): array
    {
        $startDate = now()->subDays($days);

        $leads = Lead::where('tenant_id', $tenantId)
            ->where('created_at', '>=', $startDate)
            ->get();

        $totalLeads = $leads->count();
        $contactedWithin24h = $leads->filter(function ($lead) {
            return $lead->first_contact_at &&
                   $lead->first_contact_at->diffInHours($lead->created_at) <= 24;
        })->count();

        $converted = $leads->where('status', 'converted')->count();
        $lost = $leads->where('status', 'lost')->count();

        // Taux de conversion par nombre de follow-ups
        $conversionByFollowUps = [];
        for ($i = 0; $i <= 5; $i++) {
            $group = $leads->where('follow_up_count', $i);
            $conversionByFollowUps[$i] = [
                'total' => $group->count(),
                'converted' => $group->where('status', 'converted')->count(),
                'rate' => $group->count() > 0
                    ? round(($group->where('status', 'converted')->count() / $group->count()) * 100, 1)
                    : 0,
            ];
        }

        return [
            'total_leads' => $totalLeads,
            'contacted_within_24h' => $contactedWithin24h,
            'contact_rate_24h' => $totalLeads > 0 ? round(($contactedWithin24h / $totalLeads) * 100, 1) : 0,
            'converted' => $converted,
            'lost' => $lost,
            'conversion_rate' => $totalLeads > 0 ? round(($converted / $totalLeads) * 100, 1) : 0,
            'avg_follow_ups_to_convert' => $leads->where('status', 'converted')->avg('follow_up_count') ?? 0,
            'conversion_by_follow_ups' => $conversionByFollowUps,
            'avg_time_to_first_contact_hours' => $leads->filter(fn($l) => $l->first_contact_at)
                ->avg(fn($l) => $l->first_contact_at->diffInHours($l->created_at)) ?? 0,
        ];
    }

    /**
     * Obtenir les leads prioritaires à contacter aujourd'hui
     */
    public function getTodayPriorityLeads(int $tenantId, ?int $userId = null): array
    {
        $query = Lead::where('tenant_id', $tenantId)
            ->whereIn('status', ['new', 'contacted', 'qualified'])
            ->where(function ($q) {
                $q->whereNull('next_follow_up_at')
                  ->orWhere('next_follow_up_at', '<=', now()->endOfDay());
            })
            ->orderByRaw('CASE WHEN score >= 80 THEN 1 WHEN score >= 60 THEN 2 WHEN score >= 40 THEN 3 ELSE 4 END')
            ->orderBy('score', 'desc');

        if ($userId) {
            $query->where('assigned_to', $userId);
        }

        $leads = $query->limit(20)->get();

        return $leads->map(function ($lead) {
            return [
                'id' => $lead->id,
                'name' => $lead->full_name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'score' => $lead->score,
                'score_label' => $this->getScoreLabel($lead->score),
                'status' => $lead->status,
                'source' => $lead->source,
                'days_since_created' => $lead->created_at->diffInDays(now()),
                'follow_up_count' => $lead->follow_up_count ?? 0,
                'last_contact' => $lead->last_contact_at?->diffForHumans() ?? 'Jamais',
                'recommended_action' => $this->getRecommendedAction($lead),
            ];
        })->toArray();
    }

    /**
     * Obtenir le libellé du score
     */
    protected function getScoreLabel(int $score): string
    {
        if ($score >= 80) return 'Très chaud';
        if ($score >= 60) return 'Chaud';
        if ($score >= 40) return 'Tiède';
        if ($score >= 20) return 'Froid';
        return 'Très froid';
    }

    /**
     * Obtenir l'action recommandée pour un lead
     */
    protected function getRecommendedAction(Lead $lead): array
    {
        $score = $lead->score ?? 0;
        $followUpCount = $lead->follow_up_count ?? 0;
        $daysSinceContact = $lead->last_contact_at
            ? $lead->last_contact_at->diffInDays(now())
            : $lead->created_at->diffInDays(now());

        if ($score >= 80 && $daysSinceContact >= 1) {
            return [
                'action' => 'call',
                'priority' => 'urgent',
                'message' => 'Appeler immédiatement - Lead très chaud',
            ];
        }

        if ($score >= 60 && $followUpCount < 2) {
            return [
                'action' => 'email',
                'priority' => 'high',
                'message' => 'Envoyer email personnalisé avec offre',
            ];
        }

        if ($score >= 40) {
            return [
                'action' => 'email',
                'priority' => 'medium',
                'message' => 'Email de suivi standard',
            ];
        }

        return [
            'action' => 'nurture',
            'priority' => 'low',
            'message' => 'Ajouter à séquence de nurturing',
        ];
    }
}
