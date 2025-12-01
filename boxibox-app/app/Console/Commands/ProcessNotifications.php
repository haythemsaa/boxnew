<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Contract;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class ProcessNotifications extends Command
{
    protected $signature = 'notifications:process';
    protected $description = 'Process and send notifications for invoices, contracts, and payments';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Starting notification processing...');

        try {
            $notificationService = new NotificationService();

            // Send payment reminders for overdue invoices
            $this->info('Sending payment reminders...');
            $overdueInvoices = Invoice::overdue()->get();
            $remindersSent = 0;

            foreach ($overdueInvoices as $invoice) {
                if ($notificationService->sendPaymentReminder($invoice)) {
                    $remindersSent++;
                }
            }

            $this->info("Sent {$remindersSent} payment reminders");

            // Send contract expiration warnings
            $this->info('Sending contract expiration warnings...');
            $expiringContracts = Contract::where('status', 'active')
                ->whereDate('end_date', '>=', now())
                ->whereDate('end_date', '<=', now()->addDays(30))
                ->get();

            $warningsSent = 0;
            foreach ($expiringContracts as $contract) {
                $daysUntilExpiry = now()->diffInDays($contract->end_date);
                if ($notificationService->sendContractExpirationWarning($contract, $daysUntilExpiry)) {
                    $warningsSent++;
                }
            }

            $this->info("Sent {$warningsSent} contract expiration warnings");

            // Send overdue invoices digest
            $this->info('Sending overdue invoices digest...');
            $tenants = \App\Models\Tenant::all();

            foreach ($tenants as $tenant) {
                $digestsSent = $notificationService->sendOverdueInvoicesDigest($tenant->id);
                if ($digestsSent > 0) {
                    $this->info("Sent {$digestsSent} digest(s) for tenant {$tenant->id}");
                }
            }

            $this->info('Notification processing completed successfully!');

            return 0;
        } catch (\Exception $e) {
            $this->error('Error processing notifications: ' . $e->getMessage());
            return 1;
        }
    }
}
