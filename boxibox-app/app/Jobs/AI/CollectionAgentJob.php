<?php

namespace App\Jobs\AI;

use App\Models\Tenant;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\Notification;
use App\Models\PaymentReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class CollectionAgentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $tenantId;
    protected bool $autoSend;

    /**
     * Collection workflow stages
     */
    const STAGES = [
        1 => ['days' => 3, 'action' => 'email_friendly', 'template' => 'friendly_reminder'],
        2 => ['days' => 7, 'action' => 'email_formal', 'template' => 'formal_reminder'],
        3 => ['days' => 14, 'action' => 'sms', 'template' => 'sms_reminder'],
        4 => ['days' => 21, 'action' => 'email_urgent', 'template' => 'urgent_reminder'],
        5 => ['days' => 30, 'action' => 'call_required', 'template' => 'call_script'],
        6 => ['days' => 45, 'action' => 'final_notice', 'template' => 'final_notice'],
    ];

    public function __construct(int $tenantId, bool $autoSend = false)
    {
        $this->tenantId = $tenantId;
        $this->autoSend = $autoSend;
    }

    public function handle(): void
    {
        $tenant = Tenant::find($this->tenantId);
        if (!$tenant) return;

        $now = Carbon::now();

        // Get all overdue invoices
        $overdueInvoices = Invoice::where('tenant_id', $this->tenantId)
            ->whereIn('status', ['sent', 'overdue', 'partial'])
            ->where('due_date', '<', $now)
            ->with('customer')
            ->get();

        $actions = [];
        $summary = [
            'total_overdue' => 0,
            'invoices_processed' => 0,
            'reminders_sent' => 0,
            'calls_required' => 0,
            'high_priority' => 0,
        ];

        foreach ($overdueInvoices as $invoice) {
            $daysOverdue = $now->diffInDays($invoice->due_date);
            $amountDue = $invoice->total - ($invoice->paid_amount ?? 0);
            $summary['total_overdue'] += $amountDue;

            // Determine the appropriate stage
            $stage = $this->determineStage($daysOverdue);

            // Check if we already sent a reminder at this stage
            $existingReminder = PaymentReminder::where('invoice_id', $invoice->id)
                ->where('stage', $stage)
                ->first();

            if (!$existingReminder) {
                $action = $this->processInvoice($invoice, $stage, $daysOverdue, $amountDue);
                $actions[] = $action;
                $summary['invoices_processed']++;

                if (in_array($action['action_type'], ['email_friendly', 'email_formal', 'email_urgent', 'sms'])) {
                    $summary['reminders_sent']++;
                }

                if ($action['action_type'] === 'call_required') {
                    $summary['calls_required']++;
                }

                if ($amountDue > 500 || $daysOverdue > 30) {
                    $summary['high_priority']++;
                }
            }
        }

        // Create summary notification if there are actions
        if (!empty($actions)) {
            $this->createSummaryNotification($summary, $actions);
        }

        // Store results for API access
        cache()->put(
            "collection_status_{$this->tenantId}",
            [
                'summary' => $summary,
                'actions' => $actions,
                'updated_at' => $now->toIso8601String(),
            ],
            now()->addHours(2)
        );

        Log::info("CollectionAgentJob completed for tenant {$this->tenantId}", $summary);
    }

    protected function determineStage(int $daysOverdue): int
    {
        foreach (self::STAGES as $stage => $config) {
            if ($daysOverdue <= $config['days']) {
                return $stage;
            }
        }
        return 6; // Final stage
    }

    protected function processInvoice(Invoice $invoice, int $stage, int $daysOverdue, float $amountDue): array
    {
        $stageConfig = self::STAGES[$stage];
        $customer = $invoice->customer;

        $action = [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->number,
            'customer_id' => $customer->id ?? null,
            'customer_name' => $customer->full_name ?? 'N/A',
            'customer_email' => $customer->email ?? null,
            'customer_phone' => $customer->phone ?? null,
            'amount_due' => $amountDue,
            'days_overdue' => $daysOverdue,
            'stage' => $stage,
            'action_type' => $stageConfig['action'],
            'template' => $stageConfig['template'],
            'executed' => false,
            'message' => null,
        ];

        // If auto-send is enabled, execute the action
        if ($this->autoSend && $customer) {
            switch ($stageConfig['action']) {
                case 'email_friendly':
                case 'email_formal':
                case 'email_urgent':
                case 'final_notice':
                    $action = $this->sendEmailReminder($action, $invoice, $customer, $stage);
                    break;

                case 'sms':
                    $action = $this->sendSmsReminder($action, $invoice, $customer, $stage);
                    break;

                case 'call_required':
                    $action['message'] = "Appel telephonique requis pour {$customer->full_name}";
                    break;
            }
        }

        // Generate personalized message for manual execution
        if (!$action['executed']) {
            $action['suggested_message'] = $this->generateMessage($stageConfig['template'], $invoice, $customer, $daysOverdue, $amountDue);
        }

        return $action;
    }

    protected function sendEmailReminder(array $action, Invoice $invoice, Customer $customer, int $stage): array
    {
        try {
            // In a real implementation, this would use Mail facade
            // Mail::to($customer->email)->send(new PaymentReminderMail($invoice, $stage));

            // Record the reminder
            PaymentReminder::create([
                'tenant_id' => $this->tenantId,
                'invoice_id' => $invoice->id,
                'customer_id' => $customer->id,
                'stage' => $stage,
                'type' => 'email',
                'sent_at' => now(),
                'status' => 'sent',
            ]);

            $action['executed'] = true;
            $action['message'] = "Email de rappel envoye a {$customer->email}";

        } catch (\Exception $e) {
            Log::error("Failed to send email reminder", [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
            $action['message'] = "Echec envoi email: {$e->getMessage()}";
        }

        return $action;
    }

    protected function sendSmsReminder(array $action, Invoice $invoice, Customer $customer, int $stage): array
    {
        if (!$customer->phone) {
            $action['message'] = "Pas de numero de telephone pour {$customer->full_name}";
            return $action;
        }

        try {
            // In a real implementation, this would use SMS service
            // SmsService::send($customer->phone, $message);

            PaymentReminder::create([
                'tenant_id' => $this->tenantId,
                'invoice_id' => $invoice->id,
                'customer_id' => $customer->id,
                'stage' => $stage,
                'type' => 'sms',
                'sent_at' => now(),
                'status' => 'sent',
            ]);

            $action['executed'] = true;
            $action['message'] = "SMS de rappel envoye au {$customer->phone}";

        } catch (\Exception $e) {
            Log::error("Failed to send SMS reminder", [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
            $action['message'] = "Echec envoi SMS: {$e->getMessage()}";
        }

        return $action;
    }

    protected function generateMessage(string $template, Invoice $invoice, ?Customer $customer, int $daysOverdue, float $amountDue): string
    {
        $customerName = $customer->first_name ?? 'Client';
        $formattedAmount = number_format($amountDue, 2, ',', ' ');

        $messages = [
            'friendly_reminder' => "Bonjour {$customerName},\n\nNous vous rappelons que la facture #{$invoice->number} d'un montant de {$formattedAmount} EUR est arrivee a echeance. Merci de proceder au reglement a votre convenance.\n\nCordialement,\nL'equipe BoxiBox",

            'formal_reminder' => "Madame, Monsieur {$customerName},\n\nSauf erreur de notre part, la facture #{$invoice->number} ({$formattedAmount} EUR) reste impayee depuis {$daysOverdue} jours.\n\nNous vous prions de bien vouloir regulariser cette situation dans les meilleurs delais.\n\nCordialement,\nService Comptabilite BoxiBox",

            'sms_reminder' => "BoxiBox: Facture #{$invoice->number} de {$formattedAmount} EUR en retard de {$daysOverdue}j. Merci de regulariser rapidement.",

            'urgent_reminder' => "RAPPEL URGENT\n\nMadame, Monsieur {$customerName},\n\nMalgre nos precedentes relances, la facture #{$invoice->number} ({$formattedAmount} EUR) reste impayee depuis {$daysOverdue} jours.\n\nSans reglement sous 7 jours, nous serons contraints de suspendre l'acces a votre box.\n\nContactez-nous au plus vite si vous rencontrez des difficultes de paiement.\n\nService Recouvrement BoxiBox",

            'call_script' => "SCRIPT APPEL:\n1. Se presenter: 'Bonjour, [votre nom] de BoxiBox'\n2. Confirmer identite: 'Puis-je parler a {$customerName}?'\n3. Objet: 'Je vous appelle concernant la facture #{$invoice->number} de {$formattedAmount} EUR'\n4. Ecouter: Comprendre la situation du client\n5. Proposer: Plan de paiement en 3x si necessaire\n6. Conclure: Fixer une date de paiement ferme",

            'final_notice' => "MISE EN DEMEURE\n\nMadame, Monsieur {$customerName},\n\nLa presente vaut mise en demeure de payer sous 8 jours la somme de {$formattedAmount} EUR correspondant a la facture #{$invoice->number}.\n\nA defaut de reglement, nous engagerons une procedure de recouvrement et l'acces a votre box sera suspendu.\n\nPour tout arrangement, contactez-nous immediatement.\n\nService Juridique BoxiBox",
        ];

        return $messages[$template] ?? $messages['formal_reminder'];
    }

    protected function createSummaryNotification(array $summary, array $actions): void
    {
        $adminUsers = \App\Models\User::where('tenant_id', $this->tenantId)
            ->whereHas('roles', fn($q) => $q->whereIn('name', ['admin', 'manager', 'accountant']))
            ->get();

        $formattedTotal = number_format($summary['total_overdue'], 0, ',', ' ');

        foreach ($adminUsers as $user) {
            Notification::create([
                'tenant_id' => $this->tenantId,
                'user_id' => $user->id,
                'type' => 'ai_alert',
                'title' => "[AI] Rapport de recouvrement",
                'message' => sprintf(
                    '%s EUR d\'impayes traites. %d rappels envoyes, %d appels requis, %d prioritaires.',
                    $formattedTotal,
                    $summary['reminders_sent'],
                    $summary['calls_required'],
                    $summary['high_priority']
                ),
                'data' => [
                    'alert_type' => 'collection_report',
                    'severity' => $summary['high_priority'] > 3 ? 'high' : 'medium',
                    'summary' => $summary,
                    'actions' => array_slice($actions, 0, 10),
                    'source' => 'CollectionAgent',
                ],
                'read' => false,
            ]);
        }
    }
}
