<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Models\Prospect;
use App\Models\Tenant;
use App\Services\AILeadScoringService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CalculateLeadScores extends Command
{
    protected $signature = 'leads:calculate-scores
                            {--tenant= : Calculate scores for specific tenant only}
                            {--dry-run : Calculate without saving}
                            {--no-notifications : Disable hot lead notifications}';

    protected $description = 'Calculate and update lead scores using AI-enhanced scoring algorithm';

    public function __construct(
        protected AILeadScoringService $scoringService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $tenantId = $this->option('tenant');
        $noNotifications = $this->option('no-notifications');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - Scores will not be saved');
        }

        $this->info('');
        $this->info('<fg=cyan>╔══════════════════════════════════════════════════════╗</>');
        $this->info('<fg=cyan>║</><fg=white>     AI Lead Scoring - Calculating Scores...         </><fg=cyan>║</>');
        $this->info('<fg=cyan>╚══════════════════════════════════════════════════════╝</>');
        $this->info('');

        $tenants = $tenantId
            ? Tenant::where('id', $tenantId)->get()
            : Tenant::where('is_active', true)->get();

        $totalProcessed = 0;
        $totalVeryHot = 0;
        $totalHot = 0;
        $totalWarm = 0;
        $totalErrors = 0;

        foreach ($tenants as $tenant) {
            $this->info("<fg=yellow>Processing tenant:</> {$tenant->name} (ID: {$tenant->id})");

            if ($dryRun) {
                $results = $this->dryRunForTenant($tenant->id);
            } else {
                $results = $this->scoringService->batchCalculateScores($tenant->id, !$noNotifications);
            }

            $totalProcessed += $results['processed'];
            $totalVeryHot += $results['very_hot'] ?? 0;
            $totalHot += $results['hot'] ?? 0;
            $totalWarm += $results['warm'] ?? 0;
            $totalErrors += $results['errors'] ?? 0;

            $this->displayTenantResults($results);
        }

        $this->newLine();
        $this->info('<fg=cyan>╔══════════════════════════════════════════════════════╗</>');
        $this->info('<fg=cyan>║</>                  <fg=white;options=bold>SUMMARY</>                           <fg=cyan>║</>');
        $this->info('<fg=cyan>╠══════════════════════════════════════════════════════╣</>');
        $this->info("<fg=cyan>║</>  Total processed:   <fg=white;options=bold>" . str_pad($totalProcessed, 6) . "</>                     <fg=cyan>║</>");
        $this->info("<fg=cyan>║</>  Very Hot leads:    <fg=red;options=bold>" . str_pad($totalVeryHot, 6) . "</>                     <fg=cyan>║</>");
        $this->info("<fg=cyan>║</>  Hot leads:         <fg=yellow;options=bold>" . str_pad($totalHot, 6) . "</>                     <fg=cyan>║</>");
        $this->info("<fg=cyan>║</>  Warm leads:        <fg=green;options=bold>" . str_pad($totalWarm, 6) . "</>                     <fg=cyan>║</>");
        if ($totalErrors > 0) {
            $this->info("<fg=cyan>║</>  Errors:            <fg=red>" . str_pad($totalErrors, 6) . "</>                     <fg=cyan>║</>");
        }
        $this->info('<fg=cyan>╚══════════════════════════════════════════════════════╝</>');
        $this->newLine();

        if ($totalErrors > 0) {
            $this->warn("There were {$totalErrors} errors during processing. Check logs for details.");
        }

        return $totalErrors > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Dry run for a specific tenant - calculate but don't save
     */
    private function dryRunForTenant(int $tenantId): array
    {
        $results = [
            'processed' => 0,
            'very_hot' => 0,
            'hot' => 0,
            'warm' => 0,
            'lukewarm' => 0,
            'cold' => 0,
            'errors' => 0,
        ];

        // Process Leads
        $leads = Lead::where('tenant_id', $tenantId)
            ->whereNull('converted_at')
            ->get();

        foreach ($leads as $lead) {
            try {
                $scoreResult = $this->scoringService->calculateScore($lead, 'lead');
                $results['processed']++;
                $results[$scoreResult['priority']] = ($results[$scoreResult['priority']] ?? 0) + 1;

                $this->displayLeadScore($lead, $scoreResult);
            } catch (\Exception $e) {
                $results['errors']++;
                Log::error('Lead scoring error (dry-run)', ['lead_id' => $lead->id, 'error' => $e->getMessage()]);
            }
        }

        // Process Prospects
        $prospects = Prospect::where('tenant_id', $tenantId)
            ->whereNotIn('status', ['converted', 'lost'])
            ->get();

        foreach ($prospects as $prospect) {
            try {
                $scoreResult = $this->scoringService->calculateScore($prospect, 'prospect');
                $results['processed']++;
                $results[$scoreResult['priority']] = ($results[$scoreResult['priority']] ?? 0) + 1;

                $this->displayLeadScore($prospect, $scoreResult);
            } catch (\Exception $e) {
                $results['errors']++;
                Log::error('Prospect scoring error (dry-run)', ['prospect_id' => $prospect->id, 'error' => $e->getMessage()]);
            }
        }

        return $results;
    }

    /**
     * Display individual lead score in dry-run mode
     */
    private function displayLeadScore($entity, array $scoreResult): void
    {
        $name = $entity->company_name ?? (($entity->first_name ?? '') . ' ' . ($entity->last_name ?? ''));
        $name = trim($name) ?: $entity->email;

        $icon = match($scoreResult['priority']) {
            'very_hot' => '<fg=red;options=bold>🔥🔥</> ',
            'hot' => '<fg=yellow;options=bold>🔥</> ',
            'warm' => '<fg=green>🌡️</> ',
            'lukewarm' => '<fg=blue>💧</> ',
            default => '<fg=gray>❄️</> ',
        };

        $scoreColor = match(true) {
            $scoreResult['score'] >= 85 => 'red',
            $scoreResult['score'] >= 70 => 'yellow',
            $scoreResult['score'] >= 50 => 'green',
            $scoreResult['score'] >= 30 => 'blue',
            default => 'gray',
        };

        $this->line("  {$icon}<fg=white>{$name}</>: <fg={$scoreColor};options=bold>{$scoreResult['score']}</>/100 (<fg={$scoreColor}>{$scoreResult['priority']}</>) - Conversion: <fg=cyan>{$scoreResult['conversion_probability']}%</>");
    }

    /**
     * Display tenant results
     */
    private function displayTenantResults(array $results): void
    {
        $veryHot = $results['very_hot'] ?? 0;
        $hot = $results['hot'] ?? 0;
        $warm = $results['warm'] ?? 0;

        $this->line("  <fg=gray>└──</> Processed: <fg=white>{$results['processed']}</> | <fg=red>Very Hot: {$veryHot}</> | <fg=yellow>Hot: {$hot}</> | <fg=green>Warm: {$warm}</>");
    }
}
