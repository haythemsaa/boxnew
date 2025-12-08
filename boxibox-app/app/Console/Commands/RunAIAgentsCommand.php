<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Jobs\AI\RevenueGuardianJob;
use App\Jobs\AI\ChurnPredictorJob;
use App\Jobs\AI\CollectionAgentJob;
use Illuminate\Console\Command;

class RunAIAgentsCommand extends Command
{
    protected $signature = 'copilot:run-agents
                            {--agent= : Specific agent to run (revenue, churn, collection)}
                            {--tenant= : Specific tenant ID (optional)}
                            {--all : Run all agents}';

    protected $description = 'Run AI Copilot agents for business intelligence';

    public function handle(): int
    {
        $specificAgent = $this->option('agent');
        $tenantId = $this->option('tenant');
        $runAll = $this->option('all');

        // Get tenants
        if ($tenantId) {
            $tenants = Tenant::where('id', $tenantId)->get();
        } else {
            $tenants = Tenant::where('status', 'active')->get();
        }

        if ($tenants->isEmpty()) {
            $this->warn('No active tenants found.');
            return 0;
        }

        $this->info("Running AI agents for {$tenants->count()} tenant(s)...");

        $agentsToRun = [];

        if ($runAll || !$specificAgent) {
            $agentsToRun = ['revenue', 'churn', 'collection'];
        } else {
            $agentsToRun = [$specificAgent];
        }

        foreach ($tenants as $tenant) {
            $this->line("Processing tenant: {$tenant->name} (ID: {$tenant->id})");

            foreach ($agentsToRun as $agent) {
                try {
                    switch ($agent) {
                        case 'revenue':
                            RevenueGuardianJob::dispatch($tenant->id);
                            $this->info("  - Revenue Guardian dispatched");
                            break;

                        case 'churn':
                            ChurnPredictorJob::dispatch($tenant->id);
                            $this->info("  - Churn Predictor dispatched");
                            break;

                        case 'collection':
                            CollectionAgentJob::dispatch($tenant->id, false);
                            $this->info("  - Collection Agent dispatched");
                            break;

                        default:
                            $this->warn("  - Unknown agent: {$agent}");
                    }
                } catch (\Exception $e) {
                    $this->error("  - Error dispatching {$agent}: {$e->getMessage()}");
                }
            }
        }

        $this->newLine();
        $this->info('AI agents dispatched successfully!');

        return 0;
    }
}
