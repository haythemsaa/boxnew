<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckOverdueInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:check-overdue {--send-reminders : Send reminder emails}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for overdue invoices and optionally send reminder notifications';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $sendReminders = $this->option('send-reminders');

        $this->info('Checking for overdue invoices...');

        // Find invoices that are past due date and not paid
        $overdueInvoices = Invoice::whereIn('status', ['draft', 'sent'])
            ->where('due_date', '<', now())
            ->with(['customer', 'contract'])
            ->get();

        if ($overdueInvoices->isEmpty()) {
            $this->info('No overdue invoices found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$overdueInvoices->count()} overdue invoice(s):");
        $this->newLine();

        $updated = 0;
        $reminders = 0;

        foreach ($overdueInvoices as $invoice) {
            $daysOverdue = now()->diffInDays($invoice->due_date, false);
            $customerName = $invoice->customer->type === 'company'
                ? $invoice->customer->company_name
                : "{$invoice->customer->first_name} {$invoice->customer->last_name}";

            $this->line("  - {$invoice->invoice_number}");
            $this->line("    Customer: {$customerName}");
            $this->line("    Amount: €{$invoice->total}");
            $this->line("    Due Date: {$invoice->due_date->format('Y-m-d')}");
            $this->line("    Days overdue: {$daysOverdue}");

            // Update status to overdue if not already
            if ($invoice->status !== 'overdue') {
                $invoice->update(['status' => 'overdue']);
                $this->line("    Status updated to: overdue");
                $updated++;
            }

            // Send reminder if requested
            if ($sendReminders) {
                $this->sendReminder($invoice);
                $reminders++;
                $this->line("    Reminder sent: Yes");
            }

            $this->newLine();

            // Log the overdue invoice
            Log::warning('Overdue invoice detected', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'customer_id' => $invoice->customer_id,
                'customer_name' => $customerName,
                'amount' => $invoice->total,
                'due_date' => $invoice->due_date->format('Y-m-d'),
                'days_overdue' => $daysOverdue,
                'reminder_count' => $invoice->reminder_count,
            ]);
        }

        $this->info("Summary:");
        $this->info("  Total overdue: {$overdueInvoices->count()}");
        $this->info("  Status updated: {$updated}");
        if ($sendReminders) {
            $this->info("  Reminders sent: {$reminders}");
        }

        return Command::SUCCESS;
    }

    /**
     * Send a reminder for an overdue invoice.
     */
    private function sendReminder(Invoice $invoice): void
    {
        // Increment reminder count
        $invoice->increment('reminder_count');
        $invoice->update(['last_reminder_sent' => now()]);

        // TODO: Send actual email notification
        // Notification::send($invoice->customer, new InvoiceOverdueNotification($invoice));

        Log::info('Overdue invoice reminder sent', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'customer_id' => $invoice->customer_id,
            'reminder_count' => $invoice->reminder_count,
        ]);
    }
}
