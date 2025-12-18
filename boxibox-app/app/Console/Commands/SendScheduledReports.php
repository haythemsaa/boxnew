<?php

namespace App\Console\Commands;

use App\Models\ScheduledReport;
use App\Models\ReportHistory;
use App\Models\CustomReport;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\Invoice;
use App\Models\Box;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SendScheduledReports extends Command
{
    protected $signature = 'reports:send-scheduled {--force : Force send all due reports}';

    protected $description = 'Send all due scheduled reports to recipients';

    public function handle(): int
    {
        $this->info('Checking for scheduled reports...');

        $dueReports = ScheduledReport::where('is_active', true)
            ->where('next_send_at', '<=', now())
            ->get();

        if ($dueReports->isEmpty()) {
            $this->info('No due reports found.');
            return self::SUCCESS;
        }

        $this->info("Found {$dueReports->count()} report(s) to send.");

        $sent = 0;
        $failed = 0;

        foreach ($dueReports as $report) {
            try {
                $this->processReport($report);
                $sent++;
                $this->line("  [OK] {$report->name}");
            } catch (\Exception $e) {
                $failed++;
                $this->error("  [FAIL] {$report->name}: {$e->getMessage()}");
                Log::error("Failed to send scheduled report #{$report->id}: " . $e->getMessage(), [
                    'report_id' => $report->id,
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        $this->info("Done! Sent: {$sent}, Failed: {$failed}");

        return self::SUCCESS;
    }

    protected function processReport(ScheduledReport $report): void
    {
        $startTime = microtime(true);
        $tenantId = $report->tenant_id;
        $filters = $report->filters ?? [];

        // Generate report data
        $data = $this->generateReportData($report->report_type, $tenantId, $filters);

        if (empty($data)) {
            Log::info("Scheduled report #{$report->id} has no data to send.");
            $this->updateReportSchedule($report);
            return;
        }

        // Generate file content
        $content = $this->generateFileContent($data, $report->format);
        $filename = $this->generateFilename($report);

        // Send to recipients
        $recipientCount = count($report->recipients ?? []);
        foreach ($report->recipients as $recipient) {
            $this->sendReportEmail($recipient, $report, $content, $filename);
        }

        $generationTime = (microtime(true) - $startTime) * 1000;

        // Record in history
        ReportHistory::create([
            'custom_report_id' => $report->custom_report_id,
            'scheduled_report_id' => $report->id,
            'format' => $report->format,
            'row_count' => count($data),
            'generation_time_ms' => $generationTime,
            'parameters_used' => $filters,
            'status' => 'completed',
        ]);

        // Update schedule
        $this->updateReportSchedule($report);

        Log::info("Scheduled report #{$report->id} sent to {$recipientCount} recipient(s)", [
            'report_id' => $report->id,
            'recipients' => $recipientCount,
            'rows' => count($data),
            'generation_time' => $generationTime,
        ]);
    }

    protected function generateReportData(string $type, int $tenantId, array $filters): array
    {
        return match ($type) {
            'rent_roll' => $this->getRentRollData($tenantId, $filters),
            'revenue' => $this->getRevenueData($tenantId, $filters),
            'occupancy' => $this->getOccupancyData($tenantId, $filters),
            'aging' => $this->getAgingData($tenantId, $filters),
            'cash_flow' => $this->getCashFlowData($tenantId, $filters),
            default => [],
        };
    }

    protected function getRentRollData(int $tenantId, array $filters): array
    {
        return Contract::with(['customer', 'box', 'site'])
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->get()
            ->map(fn($c) => [
                'Contrat' => $c->contract_number,
                'Client' => $c->customer?->full_name ?? '-',
                'Site' => $c->site?->name ?? '-',
                'Box' => $c->box?->number ?? '-',
                'Taille (m2)' => $c->box?->size_m2 ?? 0,
                'Loyer mensuel' => number_format($c->monthly_price ?? 0, 2, ',', ' ') . ' EUR',
                'Debut' => $c->start_date?->format('d/m/Y') ?? '-',
                'Fin' => $c->end_date?->format('d/m/Y') ?? 'Indefini',
            ])
            ->toArray();
    }

    protected function getRevenueData(int $tenantId, array $filters): array
    {
        $year = $filters['year'] ?? now()->year;

        return Payment::where('tenant_id', $tenantId)
            ->whereYear('paid_at', $year)
            ->where('status', 'completed')
            ->with(['invoice.customer', 'invoice.contract.site'])
            ->get()
            ->map(fn($p) => [
                'Date' => $p->paid_at->format('d/m/Y'),
                'Client' => $p->invoice?->customer?->full_name ?? '-',
                'Site' => $p->invoice?->contract?->site?->name ?? '-',
                'Montant' => number_format($p->amount ?? 0, 2, ',', ' ') . ' EUR',
                'Methode' => $p->payment_method ?? '-',
                'Reference' => $p->reference ?? '-',
            ])
            ->toArray();
    }

    protected function getOccupancyData(int $tenantId, array $filters): array
    {
        return Box::whereHas('site', fn($q) => $q->where('tenant_id', $tenantId))
            ->with(['site', 'contracts' => fn($q) => $q->where('status', 'active')])
            ->get()
            ->map(fn($b) => [
                'Site' => $b->site?->name ?? '-',
                'Box' => $b->number ?? '-',
                'Taille (m2)' => $b->size_m2 ?? 0,
                'Statut' => $b->contracts->count() > 0 ? 'Occupe' : 'Disponible',
                'Client' => $b->contracts->first()?->customer?->full_name ?? '-',
                'Loyer' => $b->contracts->first()?->monthly_price
                    ? number_format($b->contracts->first()->monthly_price, 2, ',', ' ') . ' EUR'
                    : '-',
            ])
            ->toArray();
    }

    protected function getAgingData(int $tenantId, array $filters): array
    {
        return Invoice::where('tenant_id', $tenantId)
            ->where('status', 'pending')
            ->where('due_date', '<', now())
            ->with(['customer', 'contract.site'])
            ->get()
            ->map(fn($i) => [
                'Facture' => $i->invoice_number ?? '-',
                'Client' => $i->customer?->full_name ?? '-',
                'Site' => $i->contract?->site?->name ?? '-',
                'Montant' => number_format($i->total ?? 0, 2, ',', ' ') . ' EUR',
                'Echeance' => $i->due_date->format('d/m/Y'),
                'Jours retard' => $i->due_date->diffInDays(now()),
                'Tranche' => $this->getAgingBucket($i->due_date),
            ])
            ->toArray();
    }

    protected function getCashFlowData(int $tenantId, array $filters): array
    {
        $data = [];

        // MRR
        $mrr = Contract::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->sum('monthly_price');

        // Expected payments (next 3 months)
        for ($i = 0; $i < 3; $i++) {
            $month = now()->addMonths($i);
            $expected = Invoice::where('tenant_id', $tenantId)
                ->where('status', 'pending')
                ->whereMonth('due_date', $month->month)
                ->whereYear('due_date', $month->year)
                ->sum('total');

            $data[] = [
                'Mois' => $month->format('F Y'),
                'MRR projete' => number_format($mrr, 2, ',', ' ') . ' EUR',
                'Factures attendues' => number_format($expected, 2, ',', ' ') . ' EUR',
            ];
        }

        // Overdue
        $overdue = Invoice::where('tenant_id', $tenantId)
            ->where('status', 'pending')
            ->where('due_date', '<', now())
            ->sum('total');

        $data[] = [
            'Mois' => 'IMPAYES',
            'MRR projete' => '-',
            'Factures attendues' => number_format($overdue, 2, ',', ' ') . ' EUR',
        ];

        return $data;
    }

    protected function getAgingBucket(Carbon $dueDate): string
    {
        $days = $dueDate->diffInDays(now());

        if ($days <= 30) return '0-30 jours';
        if ($days <= 60) return '31-60 jours';
        if ($days <= 90) return '61-90 jours';
        return '90+ jours';
    }

    protected function generateFileContent(array $data, string $format): string
    {
        if (empty($data)) return '';

        switch ($format) {
            case 'csv':
                return $this->generateCsv($data);
            case 'xlsx':
                // For now, fallback to CSV
                return $this->generateCsv($data);
            case 'pdf':
                // For now, fallback to CSV
                return $this->generateCsv($data);
            default:
                return $this->generateCsv($data);
        }
    }

    protected function generateCsv(array $data): string
    {
        if (empty($data)) return '';

        $output = fopen('php://temp', 'r+');

        // BOM for Excel UTF-8 compatibility
        fwrite($output, "\xEF\xBB\xBF");

        // Header row
        fputcsv($output, array_keys($data[0]), ';');

        // Data rows
        foreach ($data as $row) {
            fputcsv($output, array_values($row), ';');
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    protected function generateFilename(ScheduledReport $report): string
    {
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $report->name);
        $date = now()->format('Y-m-d');
        $extension = $report->format === 'xlsx' ? 'xlsx' : ($report->format === 'pdf' ? 'pdf' : 'csv');

        return "{$safeName}_{$date}.{$extension}";
    }

    protected function sendReportEmail(string $recipient, ScheduledReport $report, string $content, string $filename): void
    {
        // In production, use a proper Mailable class
        try {
            Mail::raw(
                "Bonjour,\n\nVeuillez trouver ci-joint votre rapport programmé \"{$report->name}\".\n\nCordialement,\nBoxiBox",
                function ($message) use ($recipient, $report, $content, $filename) {
                    $message->to($recipient)
                        ->subject("[BoxiBox] Rapport: {$report->name}")
                        ->attachData($content, $filename, [
                            'mime' => $this->getMimeType($report->format),
                        ]);
                }
            );
        } catch (\Exception $e) {
            Log::error("Failed to send report email to {$recipient}: " . $e->getMessage());
            throw $e;
        }
    }

    protected function getMimeType(string $format): string
    {
        return match ($format) {
            'csv' => 'text/csv',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'pdf' => 'application/pdf',
            default => 'text/plain',
        };
    }

    protected function updateReportSchedule(ScheduledReport $report): void
    {
        $nextSendAt = $this->calculateNextSendTime($report);

        $report->update([
            'last_sent_at' => now(),
            'send_count' => ($report->send_count ?? 0) + 1,
            'next_send_at' => $nextSendAt,
        ]);
    }

    protected function calculateNextSendTime(ScheduledReport $report): Carbon
    {
        $timeParts = explode(':', $report->time ?? '08:00');
        $hour = (int) $timeParts[0];
        $minute = (int) ($timeParts[1] ?? 0);

        $next = now()->setTime($hour, $minute, 0);

        switch ($report->frequency) {
            case 'daily':
                $next->addDay();
                break;

            case 'weekly':
                $targetDay = $report->day_of_week ?? 1;
                $next = $next->next($targetDay);
                break;

            case 'monthly':
                $targetDay = $report->day_of_month ?? 1;
                $next->addMonth()->setDay(min($targetDay, $next->daysInMonth));
                break;
        }

        return $next;
    }
}
