<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Notifications\ContractExpiringNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckExpiringContracts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contracts:check-expiring {--days=30 : Number of days to look ahead}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for contracts expiring soon and send notifications';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = $this->option('days');
        $this->info("Checking for contracts expiring in the next {$days} days...");

        // Get contracts expiring within the specified days
        $expiringContracts = Contract::where('status', 'active')
            ->whereBetween('end_date', [now(), now()->addDays($days)])
            ->with(['customer', 'box', 'site'])
            ->get();

        if ($expiringContracts->isEmpty()) {
            $this->info('No contracts expiring soon.');
            return Command::SUCCESS;
        }

        $this->info("Found {$expiringContracts->count()} contract(s) expiring soon:");
        $this->newLine();

        foreach ($expiringContracts as $contract) {
            $daysUntilExpiry = now()->diffInDays($contract->end_date, false);
            $customerName = $contract->customer->type === 'company'
                ? $contract->customer->company_name
                : "{$contract->customer->first_name} {$contract->customer->last_name}";

            $this->line("  - {$contract->contract_number}");
            $this->line("    Customer: {$customerName}");
            $this->line("    Box: {$contract->box->name}");
            $this->line("    End Date: {$contract->end_date->format('Y-m-d')}");
            $this->line("    Days until expiry: {$daysUntilExpiry}");
            $this->newLine();

            // Log the expiring contract
            Log::info('Contract expiring soon', [
                'contract_id' => $contract->id,
                'contract_number' => $contract->contract_number,
                'customer_id' => $contract->customer_id,
                'customer_name' => $customerName,
                'end_date' => $contract->end_date->format('Y-m-d'),
                'days_until_expiry' => $daysUntilExpiry,
            ]);

            // Send notification to customer
            try {
                $contract->customer->notify(new ContractExpiringNotification($contract));
                $this->line("    ✓ Notification sent to customer");
            } catch (\Exception $e) {
                $this->error("    ✗ Failed to send notification: {$e->getMessage()}");
                Log::error('Failed to send contract expiring notification', [
                    'contract_id' => $contract->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Processed {$expiringContracts->count()} expiring contract(s).");

        return Command::SUCCESS;
    }
}
