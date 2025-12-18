<?php

namespace App\Console\Commands;

use App\Services\AdvancedDynamicPricingService;
use App\Models\Tenant;
use App\Models\Site;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateDynamicPrices extends Command
{
    protected $signature = 'pricing:update
                            {--tenant= : Specific tenant ID to update}
                            {--site= : Specific site ID to update}
                            {--dry-run : Preview changes without applying}
                            {--min-change=1 : Minimum percentage change to apply}';

    protected $description = 'Update box prices using ML-based dynamic pricing algorithm';

    public function handle(AdvancedDynamicPricingService $pricingService): int
    {
        $this->info('Starting dynamic pricing update...');

        $tenantId = $this->option('tenant');
        $siteId = $this->option('site');
        $dryRun = $this->option('dry-run');
        $minChange = (float) $this->option('min-change');

        if ($tenantId) {
            $tenants = Tenant::where('id', $tenantId)->where('is_active', true)->get();
        } else {
            $tenants = Tenant::where('is_active', true)->get();
        }

        $totalProcessed = 0;
        $totalAdjusted = 0;
        $totalImpact = 0;

        foreach ($tenants as $tenant) {
            $this->info("Processing tenant: {$tenant->name}");

            $results = $pricingService->batchUpdatePrices(
                $tenant->id,
                $siteId ? (int) $siteId : null,
                autoApply: !$dryRun
            );

            $totalProcessed += $results['processed'];
            $totalAdjusted += $results['adjusted'];
            $totalImpact += $results['total_impact'];

            if ($this->output->isVerbose()) {
                foreach ($results['adjustments'] as $adj) {
                    $changeStr = $adj['change'] > 0 ? "+{$adj['change']}" : $adj['change'];
                    $this->line("  Box {$adj['box_name']}: {$adj['old_price']}€ -> {$adj['new_price']}€ ({$changeStr}€)");
                }
            }

            $this->info("  Processed: {$results['processed']}, Adjusted: {$results['adjusted']}");
            $this->info("  Increases: {$results['increases']}, Decreases: {$results['decreases']}");

            // Generate demand forecasts
            if (!$dryRun) {
                $sites = Site::where('tenant_id', $tenant->id)
                    ->where('is_active', true)
                    ->get();

                foreach ($sites as $site) {
                    $pricingService->generateDemandForecast($tenant->id, $site->id);
                }
                $this->info("  Demand forecasts generated for {$sites->count()} sites");
            }
        }

        $this->newLine();
        $this->info('=== Summary ===');
        $this->info("Total boxes processed: {$totalProcessed}");
        $this->info("Total prices adjusted: {$totalAdjusted}");
        $impactSign = $totalImpact >= 0 ? '+' : '';
        $this->info("Total monthly impact: {$impactSign}" . number_format($totalImpact, 2) . '€');

        if ($dryRun) {
            $this->warn('DRY RUN - No changes were applied');
        }

        Log::info('Dynamic pricing update completed', [
            'processed' => $totalProcessed,
            'adjusted' => $totalAdjusted,
            'impact' => $totalImpact,
            'dry_run' => $dryRun,
        ]);

        return Command::SUCCESS;
    }
}
