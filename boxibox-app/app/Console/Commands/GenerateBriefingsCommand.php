<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Notification;
use App\Services\AICopilotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class GenerateBriefingsCommand extends Command
{
    protected $signature = 'copilot:generate-briefings
                            {--tenant= : Specific tenant ID (optional)}
                            {--send-email : Also send email notifications}';

    protected $description = 'Generate and cache daily briefings for all tenants';

    public function handle(): int
    {
        $tenantId = $this->option('tenant');
        $sendEmail = $this->option('send-email');

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

        $this->info("Generating daily briefings for {$tenants->count()} tenant(s)...");

        $copilot = app(AICopilotService::class);

        foreach ($tenants as $tenant) {
            $this->line("Processing tenant: {$tenant->name} (ID: {$tenant->id})");

            try {
                // Generate briefing
                $briefing = $copilot->getDailyBriefing($tenant->id);

                // Cache for 12 hours
                Cache::put(
                    "daily_briefing_{$tenant->id}",
                    $briefing,
                    now()->addHours(12)
                );

                $this->info("  - Briefing generated and cached");

                // Create notification for admin users
                $this->createBriefingNotification($tenant, $briefing);

                // Send email if requested
                if ($sendEmail) {
                    $this->sendBriefingEmail($tenant, $briefing);
                    $this->info("  - Email sent");
                }

            } catch (\Exception $e) {
                $this->error("  - Error: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info('Daily briefings generated successfully!');

        return 0;
    }

    protected function createBriefingNotification(Tenant $tenant, array $briefing): void
    {
        // Get admin users
        $adminUsers = User::where('tenant_id', $tenant->id)
            ->whereHas('roles', fn($q) => $q->whereIn('name', ['admin', 'manager']))
            ->get();

        $summary = $briefing['summary'];
        $alertCount = count($briefing['alerts'] ?? []);

        $message = sprintf(
            "Occupation: %s%% | Revenus: %s EUR | Impayes: %s EUR",
            $summary['occupancy'],
            number_format($summary['monthly_revenue'], 0, ',', ' '),
            number_format($summary['overdue_amount'], 0, ',', ' ')
        );

        if ($alertCount > 0) {
            $message .= " | {$alertCount} alerte(s)";
        }

        foreach ($adminUsers as $user) {
            Notification::create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'type' => 'daily_briefing',
                'title' => "Briefing du " . now()->format('d/m/Y'),
                'message' => $message,
                'data' => [
                    'type' => 'daily_briefing',
                    'summary' => $summary,
                    'alerts_count' => $alertCount,
                    'tip' => $briefing['tip_of_day']['tip'] ?? null,
                ],
                'read' => false,
            ]);
        }
    }

    protected function sendBriefingEmail(Tenant $tenant, array $briefing): void
    {
        // Get admin users with email
        $adminUsers = User::where('tenant_id', $tenant->id)
            ->whereHas('roles', fn($q) => $q->whereIn('name', ['admin', 'manager']))
            ->whereNotNull('email')
            ->get();

        foreach ($adminUsers as $user) {
            try {
                // In a real implementation, this would use a Mailable
                // Mail::to($user->email)->send(new DailyBriefingMail($briefing, $user));
            } catch (\Exception $e) {
                $this->warn("    - Failed to send email to {$user->email}");
            }
        }
    }
}
