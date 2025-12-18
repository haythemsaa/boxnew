<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\CRMService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessAutoFollowUp extends Command
{
    protected $signature = 'crm:auto-followup
                            {--tenant= : Process specific tenant ID}
                            {--dry-run : Simulate without sending}';

    protected $description = 'Process automatic lead follow-ups for all tenants';

    public function handle(CRMService $crmService): int
    {
        $this->info('Starting auto follow-up process...');

        $tenantId = $this->option('tenant');
        $dryRun = $this->option('dry-run');

        if ($tenantId) {
            $tenants = Tenant::where('id', $tenantId)->get();
        } else {
            $tenants = Tenant::where('is_active', true)->get();
        }

        $totalResults = [
            'tenants_processed' => 0,
            'leads_processed' => 0,
            'emails_sent' => 0,
            'sms_sent' => 0,
            'calls_scheduled' => 0,
            'errors' => [],
        ];

        foreach ($tenants as $tenant) {
            $this->line("Processing tenant: {$tenant->name} (ID: {$tenant->id})");

            try {
                if ($dryRun) {
                    $this->warn('  [DRY RUN] Skipping actual sends');
                    continue;
                }

                $results = $crmService->processAutoFollowUp($tenant->id);

                $totalResults['tenants_processed']++;
                $totalResults['leads_processed'] += $results['processed'];
                $totalResults['emails_sent'] += $results['emails_sent'];
                $totalResults['sms_sent'] += $results['sms_sent'];
                $totalResults['calls_scheduled'] += $results['calls_scheduled'];

                if (!empty($results['errors'])) {
                    $totalResults['errors'] = array_merge($totalResults['errors'], $results['errors']);
                }

                $this->info("  Processed: {$results['processed']} leads");
                $this->info("  Emails: {$results['emails_sent']}, SMS: {$results['sms_sent']}, Calls: {$results['calls_scheduled']}");

            } catch (\Exception $e) {
                $this->error("  Error: {$e->getMessage()}");
                $totalResults['errors'][] = "Tenant {$tenant->id}: {$e->getMessage()}";

                Log::error('CRM Auto Follow-up Error', [
                    'tenant_id' => $tenant->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info('=== Summary ===');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Tenants Processed', $totalResults['tenants_processed']],
                ['Leads Processed', $totalResults['leads_processed']],
                ['Emails Sent', $totalResults['emails_sent']],
                ['SMS Sent', $totalResults['sms_sent']],
                ['Calls Scheduled', $totalResults['calls_scheduled']],
                ['Errors', count($totalResults['errors'])],
            ]
        );

        if (!empty($totalResults['errors'])) {
            $this->newLine();
            $this->warn('Errors encountered:');
            foreach ($totalResults['errors'] as $error) {
                $this->error("  - {$error}");
            }
        }

        Log::info('CRM Auto Follow-up Completed', $totalResults);

        return Command::SUCCESS;
    }
}
