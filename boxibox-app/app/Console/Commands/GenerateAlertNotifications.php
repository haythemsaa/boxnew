<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\AlertNotificationService;
use Illuminate\Console\Command;

class GenerateAlertNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:generate-alerts
                            {--tenant= : Generate alerts for a specific tenant ID}
                            {--all : Generate alerts for all active tenants}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate automatic alert notifications for overdue invoices, expiring contracts, and occupancy issues';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $alertService = new AlertNotificationService();

        if ($tenantId = $this->option('tenant')) {
            $this->info("Generating alerts for tenant ID: {$tenantId}");
            $alerts = $alertService->generateAlertsForTenant((int) $tenantId);
            $count = count($alerts);
            $this->info("Generated {$count} alert(s).");
            return Command::SUCCESS;
        }

        if ($this->option('all')) {
            $tenants = Tenant::where('status', 'active')->get();
            $totalAlerts = 0;

            $this->info("Generating alerts for {$tenants->count()} active tenant(s)...");

            $bar = $this->output->createProgressBar($tenants->count());
            $bar->start();

            foreach ($tenants as $tenant) {
                $alerts = $alertService->generateAlertsForTenant($tenant->id);
                $totalAlerts += count($alerts);
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->info("Generated {$totalAlerts} alert(s) across all tenants.");
            return Command::SUCCESS;
        }

        $this->error('Please specify --tenant=ID or --all option.');
        return Command::FAILURE;
    }
}
