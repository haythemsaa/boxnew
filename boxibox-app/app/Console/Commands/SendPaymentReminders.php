<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\PaymentReminder;
use App\Notifications\PaymentReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendPaymentReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:send {--dry-run : Simulate without sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send payment reminders for upcoming and overdue invoices';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('🔍 Dry run mode - No emails will be sent');
        }

        $this->info('🔄 Processing payment reminders...');

        // 1. Create reminders for upcoming invoices (before due date)
        $this->createPreDueReminders();

        // 2. Create reminders for invoices due today
        $this->createDueTodayReminders();

        // 3. Create reminders for overdue invoices
        $this->createOverdueReminders();

        // 4. Send scheduled reminders
        $sent = $this->sendScheduledReminders($dryRun);

        $this->info("✅ Completed! {$sent} reminders " . ($dryRun ? 'would be' : 'were') . " sent.");

        return Command::SUCCESS;
    }

    /**
     * Create reminders for invoices 3 days before due date
     */
    protected function createPreDueReminders(): void
    {
        $targetDate = now()->addDays(3)->startOfDay();

        $invoices = Invoice::whereDate('due_date', $targetDate)
            ->whereIn('status', ['sent', 'partial'])
            ->whereDoesntHave('reminders', function ($query) {
                $query->where('type', 'before_due')
                      ->where('status', '!=', 'failed');
            })
            ->with('customer')
            ->get();

        foreach ($invoices as $invoice) {
            PaymentReminder::create([
                'tenant_id' => $invoice->tenant_id,
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'type' => 'before_due',
                'days_before_due' => 3,
                'status' => 'pending',
                'method' => 'email',
                'scheduled_at' => now(),
                'message' => $this->generateMessage($invoice, 'before_due'),
            ]);
        }

        $this->line("Created {$invoices->count()} pre-due reminders");
    }

    /**
     * Create reminders for invoices due today
     */
    protected function createDueTodayReminders(): void
    {
        $invoices = Invoice::whereDate('due_date', now())
            ->whereIn('status', ['sent', 'partial'])
            ->whereDoesntHave('reminders', function ($query) {
                $query->where('type', 'on_due')
                      ->where('status', '!=', 'failed');
            })
            ->with('customer')
            ->get();

        foreach ($invoices as $invoice) {
            PaymentReminder::create([
                'tenant_id' => $invoice->tenant_id,
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'type' => 'on_due',
                'status' => 'pending',
                'method' => 'email',
                'scheduled_at' => now(),
                'message' => $this->generateMessage($invoice, 'on_due'),
            ]);
        }

        $this->line("Created {$invoices->count()} due-today reminders");
    }

    /**
     * Create reminders for overdue invoices
     */
    protected function createOverdueReminders(): void
    {
        // Send reminders at 7, 14, and 30 days overdue
        $reminderDays = [7, 14, 30];

        foreach ($reminderDays as $days) {
            $targetDate = now()->subDays($days)->startOfDay();

            $invoices = Invoice::whereDate('due_date', $targetDate)
                ->where('status', 'overdue')
                ->whereDoesntHave('reminders', function ($query) use ($days) {
                    $query->where('type', 'after_due')
                          ->where('days_after_due', $days)
                          ->where('status', '!=', 'failed');
                })
                ->with('customer')
                ->get();

            foreach ($invoices as $invoice) {
                PaymentReminder::create([
                    'tenant_id' => $invoice->tenant_id,
                    'invoice_id' => $invoice->id,
                    'customer_id' => $invoice->customer_id,
                    'type' => 'after_due',
                    'days_after_due' => $days,
                    'status' => 'pending',
                    'method' => 'email',
                    'scheduled_at' => now(),
                    'message' => $this->generateMessage($invoice, 'after_due', $days),
                ]);
            }

            $this->line("Created {$invoices->count()} overdue reminders ({$days} days)");
        }
    }

    /**
     * Send all scheduled reminders
     */
    protected function sendScheduledReminders(bool $dryRun): int
    {
        $reminders = PaymentReminder::scheduled()
            ->with(['invoice', 'customer'])
            ->get();

        $sent = 0;

        foreach ($reminders as $reminder) {
            try {
                if (!$dryRun) {
                    // Send notification
                    $reminder->customer->notify(new PaymentReminderNotification($reminder));
                    $reminder->markAsSent();
                    $sent++;
                } else {
                    $this->line("Would send: {$reminder->type} to {$reminder->customer->email}");
                    $sent++;
                }
            } catch (\Exception $e) {
                if (!$dryRun) {
                    $reminder->markAsFailed($e->getMessage());
                }
                $this->error("Failed to send reminder {$reminder->id}: {$e->getMessage()}");
            }
        }

        return $sent;
    }

    /**
     * Generate reminder message based on type
     */
    protected function generateMessage(Invoice $invoice, string $type, int $daysOverdue = 0): string
    {
        $customerName = $invoice->customer->type === 'company'
            ? $invoice->customer->company_name
            : $invoice->customer->first_name . ' ' . $invoice->customer->last_name;

        $amount = number_format($invoice->total, 2, ',', ' ') . ' €';

        switch ($type) {
            case 'before_due':
                return "Bonjour {$customerName},\n\n" .
                       "Nous vous rappelons que votre facture n°{$invoice->invoice_number} " .
                       "d'un montant de {$amount} arrivera à échéance dans 3 jours.\n\n" .
                       "Date d'échéance : " . $invoice->due_date->format('d/m/Y') . "\n\n" .
                       "Merci de procéder au règlement dans les délais.\n\n" .
                       "Cordialement,";

            case 'on_due':
                return "Bonjour {$customerName},\n\n" .
                       "Votre facture n°{$invoice->invoice_number} d'un montant de {$amount} " .
                       "arrive à échéance aujourd'hui.\n\n" .
                       "Merci de procéder au règlement dès que possible.\n\n" .
                       "Cordialement,";

            case 'after_due':
                return "Bonjour {$customerName},\n\n" .
                       "Nous constatons que votre facture n°{$invoice->invoice_number} " .
                       "d'un montant de {$amount} est en retard de {$daysOverdue} jours.\n\n" .
                       "Date d'échéance : " . $invoice->due_date->format('d/m/Y') . "\n\n" .
                       "Nous vous remercions de bien vouloir régulariser cette situation au plus vite.\n\n" .
                       "Cordialement,";

            default:
                return '';
        }
    }
}
