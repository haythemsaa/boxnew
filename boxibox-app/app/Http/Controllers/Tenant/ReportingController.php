<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\CustomReport;
use App\Models\ScheduledReport;
use App\Models\ReportHistory;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Box;
use App\Models\Customer;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class ReportingController extends Controller
{
    public function index()
    {
        $tenantId = Auth::user()->tenant_id;

        $reports = CustomReport::where('tenant_id', $tenantId)
            ->orWhere(function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)->where('is_public', true);
            })
            ->with('creator')
            ->latest()
            ->get();

        $scheduledReports = ScheduledReport::where('tenant_id', $tenantId)
            ->with('customReport')
            ->get();

        return Inertia::render('Tenant/Reports/Index', [
            'reports' => $reports,
            'scheduledReports' => $scheduledReports,
        ]);
    }

    public function create()
    {
        return Inertia::render('Tenant/Reports/Create', [
            'reportTypes' => $this->getReportTypes(),
            'availableColumns' => $this->getAvailableColumns(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string',
            'columns' => 'required|array|min:1',
            'filters' => 'nullable|array',
            'grouping' => 'nullable|array',
            'sorting' => 'nullable|array',
            'is_public' => 'boolean',
        ]);

        $report = CustomReport::create([
            'tenant_id' => Auth::user()->tenant_id,
            'created_by' => Auth::id(),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'columns' => $validated['columns'],
            'filters' => $validated['filters'] ?? [],
            'grouping' => $validated['grouping'] ?? [],
            'sorting' => $validated['sorting'] ?? [],
            'is_public' => $validated['is_public'] ?? false,
        ]);

        return redirect()->route('tenant.reports.show', $report)
            ->with('success', 'Rapport créé avec succès.');
    }

    public function show(CustomReport $report, Request $request)
    {
        $this->authorize('view', $report);

        $data = $this->generateReportData($report, $request->all());

        return Inertia::render('Tenant/Reports/Show', [
            'report' => $report,
            'data' => $data,
            'filters' => $request->only(['date_from', 'date_to', 'site_id']),
        ]);
    }

    public function edit(CustomReport $report)
    {
        $this->authorize('update', $report);

        return Inertia::render('Tenant/Reports/Edit', [
            'report' => $report,
            'reportTypes' => $this->getReportTypes(),
            'availableColumns' => $this->getAvailableColumns(),
        ]);
    }

    public function update(Request $request, CustomReport $report)
    {
        $this->authorize('update', $report);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'columns' => 'required|array|min:1',
            'filters' => 'nullable|array',
            'grouping' => 'nullable|array',
            'sorting' => 'nullable|array',
            'is_public' => 'boolean',
            'is_favorite' => 'boolean',
        ]);

        $report->update($validated);

        return redirect()->route('tenant.reports.show', $report)
            ->with('success', 'Rapport mis à jour.');
    }

    public function destroy(CustomReport $report)
    {
        $this->authorize('delete', $report);
        $report->delete();

        return redirect()->route('tenant.reports.index')
            ->with('success', 'Rapport supprimé.');
    }

    public function export(CustomReport $report, Request $request)
    {
        $this->authorize('view', $report);

        $format = $request->input('format', 'csv');
        $startTime = microtime(true);

        try {
            $data = $this->generateReportData($report, $request->all());

            if (empty($data)) {
                return redirect()->back()
                    ->with('warning', 'Aucune donnee a exporter pour ce rapport.');
            }

            // Générer le fichier selon le format
            $content = match ($format) {
                'csv' => $this->generateCsv($data, $report->columns),
                'xlsx' => $this->generateExcel($data, $report->columns),
                'pdf' => $this->generatePdf($data, $report),
                default => $this->generateCsv($data, $report->columns),
            };

            $generationTime = (microtime(true) - $startTime) * 1000;

            // Enregistrer dans l'historique
            ReportHistory::create([
                'custom_report_id' => $report->id,
                'generated_by' => Auth::id(),
                'format' => $format,
                'row_count' => count($data),
                'generation_time_ms' => $generationTime,
                'parameters_used' => $request->all(),
                'status' => 'completed',
            ]);

            $filename = $report->name . '_' . now()->format('Y-m-d') . '.' . $format;

            return response($content)
                ->header('Content-Type', $this->getContentType($format))
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');

        } catch (\Exception $e) {
            $generationTime = (microtime(true) - $startTime) * 1000;

            // Enregistrer l'echec dans l'historique
            ReportHistory::create([
                'custom_report_id' => $report->id,
                'generated_by' => Auth::id(),
                'format' => $format,
                'row_count' => 0,
                'generation_time_ms' => $generationTime,
                'parameters_used' => $request->all(),
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            \Log::error("Failed to export report #{$report->id}: " . $e->getMessage(), [
                'report_id' => $report->id,
                'format' => $format,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la generation du rapport. Veuillez reessayer.');
        }
    }

    // Rapports prédéfinis
    public function rentRoll(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $siteId = $request->input('site_id');
        $status = $request->input('status', 'active');

        // Get sites
        $sites = Site::where('tenant_id', $tenantId)->get(['id', 'name']);

        // Get contracts with full relations
        $query = Contract::with(['customer', 'box.site', 'site'])
            ->where('tenant_id', $tenantId);

        if ($status === 'active') {
            $query->where('status', 'active');
        } elseif ($status === 'ending_soon') {
            $query->where('status', 'active')
                ->whereNotNull('end_date')
                ->whereBetween('end_date', [now(), now()->addDays(30)]);
        }

        if ($siteId) {
            $query->where('site_id', $siteId);
        }

        $contracts = $query->get()->map(function ($contract) {
            return [
                'id' => $contract->id,
                'contract_number' => $contract->contract_number,
                'customer_id' => $contract->customer_id,
                'customer' => $contract->customer ? [
                    'id' => $contract->customer->id,
                    'full_name' => $contract->customer->full_name,
                ] : null,
                'site_id' => $contract->site_id,
                'site' => $contract->site ? [
                    'id' => $contract->site->id,
                    'name' => $contract->site->name,
                ] : null,
                'box_id' => $contract->box_id,
                'box' => $contract->box ? [
                    'id' => $contract->box->id,
                    'code' => $contract->box->code,
                    'size_m2' => $contract->box->size_m2,
                ] : null,
                'monthly_rent' => $contract->monthly_price ?? 0,
                'start_date' => $contract->start_date?->format('Y-m-d'),
                'end_date' => $contract->end_date?->format('Y-m-d'),
                'status' => $contract->status,
            ];
        });

        // Calculate summary
        $totalArea = $contracts->sum(fn($c) => $c['box']['size_m2'] ?? 0);
        $monthlyRent = $contracts->sum('monthly_rent');
        $totalContracts = $contracts->count();

        $summary = [
            'total_contracts' => $totalContracts,
            'total_area' => round($totalArea, 2),
            'monthly_rent' => $monthlyRent,
            'annual_rent' => $monthlyRent * 12,
            'avg_price_per_m2' => $totalArea > 0 ? round($monthlyRent / $totalArea, 2) : 0,
        ];

        // Site breakdown
        $siteBreakdown = $contracts->groupBy('site_id')->map(function ($group, $siteId) use ($sites) {
            $site = $sites->firstWhere('id', $siteId);
            return [
                'id' => $siteId,
                'name' => $site?->name ?? 'Non assigné',
                'count' => $group->count(),
                'total_rent' => $group->sum('monthly_rent'),
            ];
        })->values();

        // Size breakdown
        $sizeRanges = [
            '< 3 m²' => [0, 3],
            '3-6 m²' => [3, 6],
            '6-12 m²' => [6, 12],
            '12-20 m²' => [12, 20],
            '> 20 m²' => [20, 9999],
        ];

        $sizeBreakdown = collect($sizeRanges)->map(function ($range, $label) use ($contracts) {
            $count = $contracts->filter(function ($c) use ($range) {
                $size = $c['box']['size_m2'] ?? 0;
                return $size >= $range[0] && $size < $range[1];
            })->count();

            return [
                'range' => $label,
                'count' => $count,
            ];
        })->values();

        return Inertia::render('Tenant/Reports/RentRoll', [
            'contracts' => $contracts,
            'sites' => $sites,
            'summary' => $summary,
            'siteBreakdown' => $siteBreakdown,
            'sizeBreakdown' => $sizeBreakdown,
            'filters' => [
                'site_id' => $siteId,
                'status' => $status,
            ],
        ]);
    }

    public function occupancy(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $siteId = $request->input('site_id');

        $query = Site::where('tenant_id', $tenantId);
        if ($siteId) {
            $query->where('id', $siteId);
        }

        $sites = $query->with(['boxes' => function ($q) {
            $q->withCount(['contracts as active_contracts_count' => function ($q) {
                $q->where('status', 'active');
            }]);
        }])->get();

        $occupancyData = $sites->map(function ($site) {
            $totalBoxes = $site->boxes->count();
            $occupiedBoxes = $site->boxes->filter(fn($box) => $box->active_contracts_count > 0)->count();
            $totalSurface = $site->boxes->sum('size_m2');
            $occupiedSurface = $site->boxes->filter(fn($box) => $box->active_contracts_count > 0)->sum('size_m2');

            return [
                'site_id' => $site->id,
                'site_name' => $site->name,
                'total_boxes' => $totalBoxes,
                'occupied_boxes' => $occupiedBoxes,
                'available_boxes' => $totalBoxes - $occupiedBoxes,
                'occupancy_rate' => $totalBoxes > 0 ? round(($occupiedBoxes / $totalBoxes) * 100, 1) : 0,
                'total_surface_m2' => $totalSurface,
                'occupied_surface_m2' => $occupiedSurface,
                'surface_occupancy_rate' => $totalSurface > 0 ? round(($occupiedSurface / $totalSurface) * 100, 1) : 0,
            ];
        });

        // Historique d'occupation (12 derniers mois)
        $occupancyHistory = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthEnd = $date->copy()->endOfMonth();

            $activeContracts = Contract::where('tenant_id', $tenantId)
                ->where('start_date', '<=', $monthEnd)
                ->where(function ($q) use ($monthEnd) {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', $monthEnd);
                })
                ->count();

            $totalBoxes = Box::whereHas('site', fn($q) => $q->where('tenant_id', $tenantId))->count();

            $occupancyHistory[] = [
                'month' => $date->format('M Y'),
                'rate' => $totalBoxes > 0 ? round(($activeContracts / $totalBoxes) * 100, 1) : 0,
            ];
        }

        return Inertia::render('Tenant/Reports/Occupancy', [
            'occupancyData' => $occupancyData,
            'occupancyHistory' => $occupancyHistory,
            'sites' => Site::where('tenant_id', $tenantId)->get(['id', 'name']),
            'filters' => $request->only(['site_id']),
        ]);
    }

    public function revenue(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $year = $request->input('year', now()->year);

        // Revenus mensuels
        $monthlyRevenue = Payment::where('tenant_id', $tenantId)
            ->whereYear('payment_date', $year)
            ->where('status', 'completed')
            ->selectRaw('MONTH(payment_date) as month, SUM(amount) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('total', 'month')
            ->toArray();

        $revenueData = [];
        for ($m = 1; $m <= 12; $m++) {
            $revenueData[] = [
                'month' => Carbon::create($year, $m, 1)->format('M'),
                'revenue' => $monthlyRevenue[$m] ?? 0,
            ];
        }

        // Revenus par site
        $revenueBySite = Payment::where('tenant_id', $tenantId)
            ->whereYear('payment_date', $year)
            ->where('status', 'completed')
            ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
            ->join('contracts', 'invoices.contract_id', '=', 'contracts.id')
            ->join('sites', 'contracts.site_id', '=', 'sites.id')
            ->selectRaw('sites.name as site_name, SUM(payments.amount) as total')
            ->groupBy('sites.id', 'sites.name')
            ->get();

        // Totaux
        $totals = [
            'total_revenue' => array_sum($monthlyRevenue),
            'average_monthly' => count($monthlyRevenue) > 0 ? array_sum($monthlyRevenue) / 12 : 0,
            'ytd_growth' => 0, // TODO: Calculer la croissance
        ];

        return Inertia::render('Tenant/Reports/Revenue', [
            'revenueData' => $revenueData,
            'revenueBySite' => $revenueBySite,
            'totals' => $totals,
            'year' => $year,
            'availableYears' => range(now()->year - 5, now()->year),
        ]);
    }

    public function cashFlow(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        // Encaissements prévus (factures en attente)
        $expectedIncome = Invoice::where('tenant_id', $tenantId)
            ->where('status', 'pending')
            ->where('due_date', '>=', now())
            ->where('due_date', '<=', now()->addMonths(3))
            ->selectRaw('DATE_FORMAT(due_date, "%Y-%m") as month, SUM(total) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Impayés à recouvrer
        $overdue = Invoice::where('tenant_id', $tenantId)
            ->where('status', 'pending')
            ->where('due_date', '<', now())
            ->sum('total');

        // Revenus récurrents mensuels (MRR)
        $mrr = Contract::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->sum('monthly_price');

        return Inertia::render('Tenant/Reports/CashFlow', [
            'expectedIncome' => $expectedIncome,
            'overdue' => $overdue,
            'mrr' => $mrr,
            'projectedCashFlow' => $this->projectCashFlow($tenantId),
        ]);
    }

    protected function generateReportData(CustomReport $report, array $filters): array
    {
        $tenantId = Auth::user()->tenant_id;

        switch ($report->type) {
            case 'rent_roll':
                return $this->getRentRollData($tenantId, $filters);
            case 'revenue':
                return $this->getRevenueData($tenantId, $filters);
            case 'occupancy':
                return $this->getOccupancyData($tenantId, $filters);
            case 'aging':
                return $this->getAgingData($tenantId, $filters);
            case 'activity':
                return $this->getActivityData($tenantId, $filters);
            default:
                return $this->getCustomData($report, $tenantId, $filters);
        }
    }

    protected function getRentRollData(int $tenantId, array $filters): array
    {
        return Contract::with(['customer', 'box', 'site'])
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->get()
            ->map(fn($c) => [
                'contract' => $c->contract_number,
                'customer' => $c->customer->full_name,
                'site' => $c->site->name,
                'box' => $c->box->code,
                'size' => $c->box->size_m2,
                'rent' => $c->monthly_price,
                'start' => $c->start_date?->format('d/m/Y'),
            ])
            ->toArray();
    }

    protected function getRevenueData(int $tenantId, array $filters): array
    {
        return Payment::where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->when($filters['date_from'] ?? null, fn($q, $d) => $q->whereDate('payment_date', '>=', $d))
            ->when($filters['date_to'] ?? null, fn($q, $d) => $q->whereDate('payment_date', '<=', $d))
            ->with(['invoice.customer', 'invoice.contract.site'])
            ->get()
            ->map(fn($p) => [
                'date' => $p->payment_date->format('d/m/Y'),
                'customer' => $p->invoice->customer->full_name ?? '',
                'site' => $p->invoice->contract->site->name ?? '',
                'amount' => $p->amount,
                'method' => $p->payment_method,
            ])
            ->toArray();
    }

    protected function getOccupancyData(int $tenantId, array $filters): array
    {
        return Box::whereHas('site', fn($q) => $q->where('tenant_id', $tenantId))
            ->with(['site', 'contracts' => fn($q) => $q->where('status', 'active')])
            ->get()
            ->map(fn($b) => [
                'site' => $b->site->name,
                'box' => $b->code,
                'size' => $b->size_m2,
                'status' => $b->contracts->count() > 0 ? 'Occupé' : 'Disponible',
                'customer' => $b->contracts->first()?->customer?->full_name ?? '',
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
                'invoice' => $i->invoice_number,
                'customer' => $i->customer->full_name ?? '',
                'site' => $i->contract->site->name ?? '',
                'amount' => $i->total,
                'due_date' => $i->due_date->format('d/m/Y'),
                'days_overdue' => $i->due_date->diffInDays(now()),
            ])
            ->toArray();
    }

    protected function getActivityData(int $tenantId, array $filters): array
    {
        // Activité récente
        return [];
    }

    protected function getCustomData(CustomReport $report, int $tenantId, array $filters): array
    {
        return [];
    }

    protected function generateCsv(array $data, array $columns): string
    {
        if (empty($data)) return '';

        $output = fopen('php://temp', 'r+');
        fputcsv($output, array_keys($data[0] ?? []));

        foreach ($data as $row) {
            fputcsv($output, array_values($row));
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    protected function generateExcel(array $data, array $columns): string
    {
        // TODO: Implémenter avec PhpSpreadsheet
        return $this->generateCsv($data, $columns);
    }

    protected function generatePdf(array $data, CustomReport $report): string
    {
        // TODO: Implémenter avec DomPDF ou wkhtmltopdf
        return '';
    }

    protected function getContentType(string $format): string
    {
        return match ($format) {
            'csv' => 'text/csv',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'pdf' => 'application/pdf',
            default => 'text/plain',
        };
    }

    protected function getReportTypes(): array
    {
        return [
            ['value' => 'rent_roll', 'label' => 'Rent Roll'],
            ['value' => 'revenue', 'label' => 'Revenus'],
            ['value' => 'occupancy', 'label' => 'Occupation'],
            ['value' => 'aging', 'label' => 'Balance âgée'],
            ['value' => 'activity', 'label' => 'Activité'],
            ['value' => 'custom', 'label' => 'Personnalisé'],
        ];
    }

    protected function getAvailableColumns(): array
    {
        return [
            'contracts' => ['contract_number', 'customer_name', 'site_name', 'box_code', 'start_date', 'end_date', 'monthly_price', 'status'],
            'invoices' => ['invoice_number', 'customer_name', 'total', 'due_date', 'status', 'paid_at'],
            'payments' => ['payment_date', 'customer_name', 'amount', 'payment_method', 'reference'],
            'boxes' => ['code', 'site_name', 'size_m2', 'price', 'status'],
            'customers' => ['full_name', 'email', 'phone', 'created_at', 'contracts_count'],
        ];
    }

    protected function projectCashFlow(int $tenantId): array
    {
        $projection = [];
        $mrr = Contract::where('tenant_id', $tenantId)->where('status', 'active')->sum('monthly_price');

        for ($i = 0; $i < 6; $i++) {
            $month = now()->addMonths($i);
            $projection[] = [
                'month' => $month->format('M Y'),
                'projected' => $mrr,
                'actual' => $i === 0 ? Payment::where('tenant_id', $tenantId)
                    ->whereMonth('payment_date', $month->month)
                    ->whereYear('payment_date', $month->year)
                    ->sum('amount') : null,
            ];
        }

        return $projection;
    }
}
