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

        $filters = $request->only(['date_from', 'date_to', 'site_id', 'status', 'customer_id']);
        $data = $this->generateReportData($report, $filters);

        return Inertia::render('Tenant/Reports/Show', [
            'report' => $report,
            'data' => $data,
            'filters' => $filters,
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
            $filters = $request->only(['date_from', 'date_to', 'site_id', 'status', 'customer_id']);
            $data = $this->generateReportData($report, $filters);

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
                'parameters_used' => $filters,
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
                'parameters_used' => $request->only(['date_from', 'date_to', 'site_id', 'status', 'customer_id']),
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
        $search = $request->input('search');
        $sortBy = $request->input('sort_by', 'site');
        $sortDir = $request->input('sort_dir', 'asc');

        // OPTIMIZED: Get sites with box counts in single query
        $sites = Site::where('tenant_id', $tenantId)
            ->withCount('boxes')
            ->withCount(['boxes as occupied_boxes_count' => function ($q) {
                $q->where('status', 'occupied');
            }])
            ->get();

        // OPTIMIZED: Get contracts with selective eager loading
        $query = Contract::with([
                'customer:id,first_name,last_name,company_name,email,phone,type',
                'box:id,number,name,length,width,site_id',
                'site:id,name',
            ])
            ->withSum(['invoices as overdue_amount' => function ($q) {
                $q->where('status', 'overdue');
            }], 'total')
            ->where('tenant_id', $tenantId);

        if ($status === 'active') {
            $query->where('status', 'active');
        } elseif ($status === 'ending_soon') {
            $query->where('status', 'active')
                ->whereNotNull('end_date')
                ->whereBetween('end_date', [now(), now()->addDays(30)]);
        } elseif ($status === 'ending_60') {
            $query->where('status', 'active')
                ->whereNotNull('end_date')
                ->whereBetween('end_date', [now(), now()->addDays(60)]);
        } elseif ($status === 'ending_90') {
            $query->where('status', 'active')
                ->whereNotNull('end_date')
                ->whereBetween('end_date', [now(), now()->addDays(90)]);
        }

        if ($siteId) {
            $query->where('site_id', $siteId);
        }

        // Search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('contract_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', fn($q2) => $q2->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%"))
                  ->orWhereHas('box', fn($q2) => $q2->where('number', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"));
            });
        }

        // Sorting
        match ($sortBy) {
            'customer' => $query->join('customers', 'contracts.customer_id', '=', 'customers.id')
                ->orderBy('customers.last_name', $sortDir)
                ->select('contracts.*'),
            'rent' => $query->orderBy('monthly_price', $sortDir),
            'start_date' => $query->orderBy('start_date', $sortDir),
            'end_date' => $query->orderBy('end_date', $sortDir),
            'size' => $query->join('boxes', 'contracts.box_id', '=', 'boxes.id')
                ->orderByRaw('(boxes.length * boxes.width) ' . $sortDir)
                ->select('contracts.*'),
            default => $query->orderBy('site_id', $sortDir),
        };

        // OPTIMIZED: Use withSum result instead of loading invoices collection
        $contracts = $query->get()->map(function ($contract) {
            $overdueAmount = $contract->overdue_amount ?? 0;
            $daysUntilEnd = $contract->end_date ? now()->diffInDays($contract->end_date, false) : null;
            $contractDuration = $contract->start_date ? $contract->start_date->diffInMonths(now()) : 0;

            return [
                'id' => $contract->id,
                'contract_number' => $contract->contract_number,
                'customer_id' => $contract->customer_id,
                'customer' => $contract->customer ? [
                    'id' => $contract->customer->id,
                    'full_name' => $contract->customer->full_name,
                    'email' => $contract->customer->email,
                    'phone' => $contract->customer->phone,
                ] : null,
                'site_id' => $contract->site_id,
                'site' => $contract->site ? [
                    'id' => $contract->site->id,
                    'name' => $contract->site->name,
                ] : null,
                'box_id' => $contract->box_id,
                'box' => $contract->box ? [
                    'id' => $contract->box->id,
                    'code' => $contract->box->number,
                    'number' => $contract->box->number,
                    'name' => $contract->box->name,
                    'size_m2' => $contract->box->length && $contract->box->width ? round($contract->box->length * $contract->box->width, 2) : 0,
                ] : null,
                'monthly_rent' => $contract->monthly_price ?? 0,
                'start_date' => $contract->start_date?->format('Y-m-d'),
                'end_date' => $contract->end_date?->format('Y-m-d'),
                'status' => $contract->status,
                'overdue_amount' => $overdueAmount,
                'has_overdue' => $overdueAmount > 0,
                'days_until_end' => $daysUntilEnd,
                'is_ending_soon' => $daysUntilEnd !== null && $daysUntilEnd >= 0 && $daysUntilEnd <= 30,
                'contract_duration_months' => $contractDuration,
            ];
        });

        // Calculate summary
        $totalArea = $contracts->sum(fn($c) => $c['box']['size_m2'] ?? 0);
        $monthlyRent = $contracts->sum('monthly_rent');
        $totalContracts = $contracts->count();
        $totalOverdue = $contracts->sum('overdue_amount');
        $contractsWithOverdue = $contracts->where('has_overdue', true)->count();
        $avgDuration = $contracts->avg('contract_duration_months');
        $endingSoon30 = $contracts->where('is_ending_soon', true)->count();

        // OPTIMIZED: Single query for previous period and ending contracts stats
        $lastMonth = now()->subMonth();
        $statsQuery = Contract::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->selectRaw("
                SUM(CASE WHEN start_date <= ? AND (end_date IS NULL OR end_date >= ?) THEN 1 ELSE 0 END) as last_month_contracts,
                SUM(CASE WHEN start_date <= ? AND (end_date IS NULL OR end_date >= ?) THEN monthly_price ELSE 0 END) as last_month_rent,
                SUM(CASE WHEN end_date IS NOT NULL AND end_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as ending_in_30,
                SUM(CASE WHEN end_date IS NOT NULL AND end_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as ending_in_60,
                SUM(CASE WHEN end_date IS NOT NULL AND end_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as ending_in_90
            ", [
                $lastMonth, $lastMonth,
                $lastMonth, $lastMonth,
                now(), now()->addDays(30),
                now()->addDays(31), now()->addDays(60),
                now()->addDays(61), now()->addDays(90)
            ])
            ->first();

        $lastMonthContracts = $statsQuery->last_month_contracts ?? 0;
        $lastMonthRent = $statsQuery->last_month_rent ?? 0;
        $endingIn30 = $statsQuery->ending_in_30 ?? 0;
        $endingIn60 = $statsQuery->ending_in_60 ?? 0;
        $endingIn90 = $statsQuery->ending_in_90 ?? 0;

        $summary = [
            'total_contracts' => $totalContracts,
            'total_area' => round($totalArea, 2),
            'monthly_rent' => $monthlyRent,
            'annual_rent' => $monthlyRent * 12,
            'avg_price_per_m2' => $totalArea > 0 ? round($monthlyRent / $totalArea, 2) : 0,
            'total_overdue' => $totalOverdue,
            'contracts_with_overdue' => $contractsWithOverdue,
            'avg_duration_months' => round($avgDuration, 1),
            'ending_soon_30' => $endingIn30,
            'ending_soon_60' => $endingIn60,
            'ending_soon_90' => $endingIn90,
            // Comparisons
            'prev_contracts' => $lastMonthContracts,
            'prev_rent' => $lastMonthRent,
            'contracts_change' => $lastMonthContracts > 0 ? round((($totalContracts - $lastMonthContracts) / $lastMonthContracts) * 100, 1) : 0,
            'rent_change' => $lastMonthRent > 0 ? round((($monthlyRent - $lastMonthRent) / $lastMonthRent) * 100, 1) : 0,
        ];

        // Site breakdown with occupancy
        $siteBreakdown = $contracts->groupBy('site_id')->map(function ($group, $siteId) use ($sites) {
            $site = $sites->firstWhere('id', $siteId);
            $totalBoxes = $site?->boxes_count ?? 0;
            $occupiedBoxes = $group->count();
            return [
                'id' => $siteId,
                'name' => $site?->name ?? 'Non assigné',
                'count' => $occupiedBoxes,
                'total_rent' => $group->sum('monthly_rent'),
                'total_boxes' => $totalBoxes,
                'occupancy_rate' => $totalBoxes > 0 ? round(($occupiedBoxes / $totalBoxes) * 100, 1) : 0,
                'overdue_count' => $group->where('has_overdue', true)->count(),
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
            $filtered = $contracts->filter(function ($c) use ($range) {
                $size = $c['box']['size_m2'] ?? 0;
                return $size >= $range[0] && $size < $range[1];
            });

            return [
                'range' => $label,
                'count' => $filtered->count(),
                'total_rent' => $filtered->sum('monthly_rent'),
            ];
        })->values();

        // OPTIMIZED: Revenue evolution (12 months) - single query with UNION
        $revenueHistoryData = Contract::where('tenant_id', $tenantId)
            ->selectRaw("
                DATE_FORMAT(months.month_date, '%Y-%m') as month_key,
                COUNT(DISTINCT contracts.id) as contracts_count,
                COALESCE(SUM(contracts.monthly_price), 0) as total_rent
            ")
            ->crossJoin(DB::raw("(
                SELECT DATE_SUB(CURDATE(), INTERVAL n MONTH) as month_date
                FROM (SELECT 0 n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
                      UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION SELECT 11) nums
            ) months"))
            ->whereRaw('contracts.start_date <= LAST_DAY(months.month_date)')
            ->whereRaw('(contracts.end_date IS NULL OR contracts.end_date >= DATE_FORMAT(months.month_date, "%Y-%m-01"))')
            ->groupByRaw('month_key')
            ->orderByRaw('month_key ASC')
            ->get()
            ->keyBy('month_key');

        $revenueHistory = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthKey = $date->format('Y-m');
            $data = $revenueHistoryData->get($monthKey);

            $revenueHistory[] = [
                'month' => $date->format('M Y'),
                'short_month' => $date->format('M'),
                'rent' => $data->total_rent ?? 0,
                'contracts' => $data->contracts_count ?? 0,
            ];
        }

        // Occupancy by site
        $occupancyBySite = $sites->map(function ($site) {
            $totalBoxes = $site->boxes_count ?? 0;
            $occupiedBoxes = $site->occupied_boxes_count ?? 0;
            return [
                'id' => $site->id,
                'name' => $site->name,
                'total_boxes' => $totalBoxes,
                'occupied_boxes' => $occupiedBoxes,
                'available_boxes' => $totalBoxes - $occupiedBoxes,
                'occupancy_rate' => $totalBoxes > 0 ? round(($occupiedBoxes / $totalBoxes) * 100, 1) : 0,
            ];
        });

        return Inertia::render('Tenant/Reports/RentRoll', [
            'contracts' => $contracts,
            'sites' => $sites->map(fn($s) => ['id' => $s->id, 'name' => $s->name]),
            'summary' => $summary,
            'siteBreakdown' => $siteBreakdown,
            'sizeBreakdown' => $sizeBreakdown,
            'revenueHistory' => $revenueHistory,
            'occupancyBySite' => $occupancyBySite,
            'filters' => [
                'site_id' => $siteId,
                'status' => $status,
                'search' => $search,
                'sort_by' => $sortBy,
                'sort_dir' => $sortDir,
            ],
        ]);
    }

    public function occupancy(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $siteId = $request->input('site_id');

        // OPTIMIZED: Get sites with aggregated box stats in single query
        $query = Site::where('tenant_id', $tenantId);
        if ($siteId) {
            $query->where('id', $siteId);
        }

        $sites = $query->withCount('boxes')
            ->withCount(['boxes as occupied_boxes_count' => fn($q) => $q->where('status', 'occupied')])
            ->withSum('boxes', DB::raw('length * width'))
            ->withSum(['boxes as occupied_surface' => fn($q) => $q->where('status', 'occupied')], DB::raw('length * width'))
            ->get();

        $occupancyData = $sites->map(function ($site) {
            $totalBoxes = $site->boxes_count ?? 0;
            $occupiedBoxes = $site->occupied_boxes_count ?? 0;
            $totalSurface = $site->boxes_sum_lengthwidth ?? 0;
            $occupiedSurface = $site->occupied_surface ?? 0;

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

        // OPTIMIZED: Get total boxes count once
        $totalBoxesCount = Box::whereHas('site', fn($q) => $q->where('tenant_id', $tenantId))->count();

        // OPTIMIZED: Historique d'occupation (12 derniers mois) - single query
        $occupancyHistoryData = Contract::where('tenant_id', $tenantId)
            ->selectRaw("
                DATE_FORMAT(months.month_date, '%Y-%m') as month_key,
                COUNT(DISTINCT contracts.id) as active_contracts
            ")
            ->crossJoin(DB::raw("(
                SELECT DATE_SUB(LAST_DAY(CURDATE()), INTERVAL n MONTH) as month_date
                FROM (SELECT 0 n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
                      UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION SELECT 11) nums
            ) months"))
            ->whereRaw('contracts.start_date <= months.month_date')
            ->whereRaw('(contracts.end_date IS NULL OR contracts.end_date >= months.month_date)')
            ->groupByRaw('month_key')
            ->get()
            ->keyBy('month_key');

        $occupancyHistory = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthKey = $date->format('Y-m');
            $activeContracts = $occupancyHistoryData->get($monthKey)?->active_contracts ?? 0;

            $occupancyHistory[] = [
                'month' => $date->format('M Y'),
                'rate' => $totalBoxesCount > 0 ? round(($activeContracts / $totalBoxesCount) * 100, 1) : 0,
            ];
        }

        // Reuse sites data instead of another query
        $sitesForFilter = $sites->map(fn($s) => ['id' => $s->id, 'name' => $s->name]);

        return Inertia::render('Tenant/Reports/Occupancy', [
            'occupancyData' => $occupancyData,
            'occupancyHistory' => $occupancyHistory,
            'sites' => $sitesForFilter,
            'filters' => $request->only(['site_id']),
        ]);
    }

    public function revenue(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $year = $request->input('year', now()->year);

        // Revenus mensuels
        $monthlyRevenue = Payment::where('tenant_id', $tenantId)
            ->whereYear('paid_at', $year)
            ->where('status', 'completed')
            ->selectRaw('MONTH(paid_at) as month, SUM(amount) as total')
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
            ->whereYear('paid_at', $year)
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
                'box' => $c->box->number ?? $c->box->name,
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
            ->when($filters['date_from'] ?? null, fn($q, $d) => $q->whereDate('paid_at', '>=', $d))
            ->when($filters['date_to'] ?? null, fn($q, $d) => $q->whereDate('paid_at', '<=', $d))
            ->with(['invoice.customer', 'invoice.contract.site'])
            ->get()
            ->map(fn($p) => [
                'date' => $p->paid_at->format('d/m/Y'),
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
                'box' => $b->number ?? $b->name,
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
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // En-tetes
        if (!empty($data)) {
            $headers = array_keys($data[0]);
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . '1', ucfirst(str_replace('_', ' ', $header)));
                $col++;
            }

            // Style en-tetes
            $lastCol = chr(64 + count($headers));
            $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1E3A8A'],
                ],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            ]);

            // Donnees
            $row = 2;
            foreach ($data as $rowData) {
                $col = 'A';
                foreach ($rowData as $value) {
                    $sheet->setCellValue($col . $row, $value);
                    $col++;
                }
                $row++;
            }

            // Auto-width
            foreach (range('A', $lastCol) as $colLetter) {
                $sheet->getColumnDimension($colLetter)->setAutoSize(true);
            }

            // Filtres automatiques
            $sheet->setAutoFilter('A1:' . $lastCol . '1');
        }

        // Generer le fichier
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $tempPath = tempnam(sys_get_temp_dir(), 'xlsx_');
        $writer->save($tempPath);

        $content = file_get_contents($tempPath);
        unlink($tempPath);

        return $content;
    }

    protected function generatePdf(array $data, ?CustomReport $report): string
    {
        $html = '<html><head><style>
            body { font-family: Arial, sans-serif; font-size: 10px; }
            h1 { color: #1E3A8A; font-size: 18px; margin-bottom: 10px; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th { background-color: #1E3A8A; color: white; padding: 8px; text-align: left; font-size: 9px; }
            td { border: 1px solid #ddd; padding: 6px; font-size: 9px; }
            tr:nth-child(even) { background-color: #f9f9f9; }
            .footer { margin-top: 20px; font-size: 8px; color: #666; }
        </style></head><body>';

        $html .= '<h1>' . ($report?->name ?? 'Rapport BoxiBox') . '</h1>';
        $html .= '<p>Genere le ' . now()->format('d/m/Y a H:i') . '</p>';

        if (!empty($data)) {
            $html .= '<table>';
            $html .= '<thead><tr>';
            foreach (array_keys($data[0]) as $header) {
                $html .= '<th>' . ucfirst(str_replace('_', ' ', $header)) . '</th>';
            }
            $html .= '</tr></thead><tbody>';

            foreach ($data as $row) {
                $html .= '<tr>';
                foreach ($row as $value) {
                    $html .= '<td>' . htmlspecialchars($value ?? '') . '</td>';
                }
                $html .= '</tr>';
            }

            $html .= '</tbody></table>';
        }

        $html .= '<div class="footer">BoxiBox - Logiciel de gestion de self-stockage</div>';
        $html .= '</body></html>';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->output();
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
            'payments' => ['paid_at', 'customer_name', 'amount', 'payment_method', 'reference'],
            'boxes' => ['number', 'site_name', 'size_m2', 'price', 'status'],
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
                    ->whereMonth('paid_at', $month->month)
                    ->whereYear('paid_at', $month->year)
                    ->sum('amount') : null,
            ];
        }

        return $projection;
    }

    // ============================================
    // Scheduled Reports Management
    // ============================================

    public function scheduledIndex()
    {
        $tenantId = Auth::user()->tenant_id;

        $scheduledReports = ScheduledReport::where('tenant_id', $tenantId)
            ->with('customReport')
            ->latest()
            ->get()
            ->map(function ($report) {
                return [
                    'id' => $report->id,
                    'name' => $report->name,
                    'report_type' => $report->report_type,
                    'custom_report' => $report->customReport ? [
                        'id' => $report->customReport->id,
                        'name' => $report->customReport->name,
                    ] : null,
                    'frequency' => $report->frequency,
                    'format' => $report->format,
                    'recipients' => $report->recipients,
                    'is_active' => $report->is_active,
                    'next_send_at' => $report->next_send_at?->format('Y-m-d H:i'),
                    'last_sent_at' => $report->last_sent_at?->format('Y-m-d H:i'),
                    'send_count' => $report->send_count ?? 0,
                    'created_at' => $report->created_at->format('Y-m-d'),
                ];
            });

        // Stats
        $stats = [
            'active_reports' => $scheduledReports->where('is_active', true)->count(),
            'sent_this_month' => ScheduledReport::where('tenant_id', $tenantId)
                ->whereMonth('last_sent_at', now()->month)
                ->whereYear('last_sent_at', now()->year)
                ->sum('send_count'),
            'next_send' => ScheduledReport::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->whereNotNull('next_send_at')
                ->orderBy('next_send_at')
                ->first()?->next_send_at?->format('d/m/Y H:i'),
            'total_recipients' => $scheduledReports->sum(fn($r) => count($r['recipients'] ?? [])),
        ];

        // Recent history
        $history = ReportHistory::whereHas('customReport', fn($q) => $q->where('tenant_id', $tenantId))
            ->with(['customReport', 'generatedBy'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn($h) => [
                'id' => $h->id,
                'report_name' => $h->customReport?->name ?? 'Rapport',
                'generated_by' => $h->generatedBy?->name ?? 'Système',
                'format' => $h->format,
                'row_count' => $h->row_count,
                'status' => $h->status,
                'created_at' => $h->created_at->format('d/m/Y H:i'),
            ]);

        // Available report types for creation
        $reportTypes = $this->getReportTypes();

        // Custom reports for selection
        $customReports = CustomReport::where('tenant_id', $tenantId)
            ->get(['id', 'name', 'type']);

        return Inertia::render('Tenant/Reports/Scheduled', [
            'scheduledReports' => $scheduledReports,
            'stats' => $stats,
            'history' => $history,
            'reportTypes' => $reportTypes,
            'customReports' => $customReports,
        ]);
    }

    public function scheduledStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'report_type' => 'required|string',
            'custom_report_id' => 'nullable|exists:custom_reports,id',
            'frequency' => 'required|in:daily,weekly,monthly',
            'day_of_week' => 'nullable|integer|min:0|max:6',
            'day_of_month' => 'nullable|integer|min:1|max:28',
            'time' => 'required|date_format:H:i',
            'format' => 'required|in:pdf,csv,xlsx',
            'recipients' => 'required|array|min:1',
            'recipients.*' => 'email',
            'filters' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $tenantId = Auth::user()->tenant_id;

        // Calculate next send time
        $nextSendAt = $this->calculateNextSendTime(
            $validated['frequency'],
            $validated['time'],
            $validated['day_of_week'] ?? null,
            $validated['day_of_month'] ?? null
        );

        $scheduledReport = ScheduledReport::create([
            'tenant_id' => $tenantId,
            'name' => $validated['name'],
            'report_type' => $validated['report_type'],
            'custom_report_id' => $validated['custom_report_id'] ?? null,
            'frequency' => $validated['frequency'],
            'day_of_week' => $validated['day_of_week'] ?? null,
            'day_of_month' => $validated['day_of_month'] ?? null,
            'time' => $validated['time'],
            'format' => $validated['format'],
            'recipients' => $validated['recipients'],
            'filters' => $validated['filters'] ?? [],
            'is_active' => $validated['is_active'] ?? true,
            'next_send_at' => $nextSendAt,
        ]);

        return redirect()->route('tenant.reports.scheduled.index')
            ->with('success', 'Rapport programmé créé avec succès.');
    }

    public function scheduledUpdate(Request $request, ScheduledReport $scheduledReport)
    {
        // Ensure belongs to tenant
        if ($scheduledReport->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'report_type' => 'required|string',
            'custom_report_id' => 'nullable|exists:custom_reports,id',
            'frequency' => 'required|in:daily,weekly,monthly',
            'day_of_week' => 'nullable|integer|min:0|max:6',
            'day_of_month' => 'nullable|integer|min:1|max:28',
            'time' => 'required|date_format:H:i',
            'format' => 'required|in:pdf,csv,xlsx',
            'recipients' => 'required|array|min:1',
            'recipients.*' => 'email',
            'filters' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        // Recalculate next send time if schedule changed
        $nextSendAt = $this->calculateNextSendTime(
            $validated['frequency'],
            $validated['time'],
            $validated['day_of_week'] ?? null,
            $validated['day_of_month'] ?? null
        );

        $scheduledReport->update([
            'name' => $validated['name'],
            'report_type' => $validated['report_type'],
            'custom_report_id' => $validated['custom_report_id'] ?? null,
            'frequency' => $validated['frequency'],
            'day_of_week' => $validated['day_of_week'] ?? null,
            'day_of_month' => $validated['day_of_month'] ?? null,
            'time' => $validated['time'],
            'format' => $validated['format'],
            'recipients' => $validated['recipients'],
            'filters' => $validated['filters'] ?? [],
            'is_active' => $validated['is_active'] ?? true,
            'next_send_at' => $nextSendAt,
        ]);

        return redirect()->route('tenant.reports.scheduled.index')
            ->with('success', 'Rapport programmé mis à jour.');
    }

    public function scheduledDestroy(ScheduledReport $scheduledReport)
    {
        // Ensure belongs to tenant
        if ($scheduledReport->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $scheduledReport->delete();

        return redirect()->route('tenant.reports.scheduled.index')
            ->with('success', 'Rapport programmé supprimé.');
    }

    public function scheduledSend(ScheduledReport $scheduledReport)
    {
        // Ensure belongs to tenant
        if ($scheduledReport->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        try {
            // Generate the report
            $reportData = $this->generateScheduledReportData($scheduledReport);

            if (empty($reportData)) {
                return back()->with('warning', 'Aucune donnée à envoyer pour ce rapport.');
            }

            // Generate file
            $content = match ($scheduledReport->format) {
                'csv' => $this->generateCsv($reportData, array_keys($reportData[0] ?? [])),
                'xlsx' => $this->generateExcel($reportData, array_keys($reportData[0] ?? [])),
                'pdf' => $this->generatePdf($reportData, null),
                default => $this->generateCsv($reportData, array_keys($reportData[0] ?? [])),
            };

            // Send email to recipients
            foreach ($scheduledReport->recipients as $recipient) {
                // In production, use Mail facade with proper attachment
                // Mail::to($recipient)->send(new ScheduledReportMail($scheduledReport, $content));
            }

            // Update send count and timestamps
            $scheduledReport->update([
                'last_sent_at' => now(),
                'send_count' => ($scheduledReport->send_count ?? 0) + 1,
                'next_send_at' => $this->calculateNextSendTime(
                    $scheduledReport->frequency,
                    $scheduledReport->time,
                    $scheduledReport->day_of_week,
                    $scheduledReport->day_of_month
                ),
            ]);

            // Record in history
            if ($scheduledReport->custom_report_id) {
                ReportHistory::create([
                    'custom_report_id' => $scheduledReport->custom_report_id,
                    'generated_by' => Auth::id(),
                    'format' => $scheduledReport->format,
                    'row_count' => count($reportData),
                    'generation_time_ms' => 0,
                    'parameters_used' => $scheduledReport->filters ?? [],
                    'status' => 'completed',
                ]);
            }

            return back()->with('success', 'Rapport envoyé avec succès à ' . count($scheduledReport->recipients) . ' destinataire(s).');

        } catch (\Exception $e) {
            \Log::error("Failed to send scheduled report #{$scheduledReport->id}: " . $e->getMessage());
            return back()->with('error', 'Erreur lors de l\'envoi du rapport: ' . $e->getMessage());
        }
    }

    protected function generateScheduledReportData(ScheduledReport $scheduledReport): array
    {
        $tenantId = $scheduledReport->tenant_id;
        $filters = $scheduledReport->filters ?? [];

        // If linked to custom report, use that
        if ($scheduledReport->customReport) {
            return $this->generateReportData($scheduledReport->customReport, $filters);
        }

        // Otherwise, use report type
        return match ($scheduledReport->report_type) {
            'rent_roll' => $this->getRentRollData($tenantId, $filters),
            'revenue' => $this->getRevenueData($tenantId, $filters),
            'occupancy' => $this->getOccupancyData($tenantId, $filters),
            'aging' => $this->getAgingData($tenantId, $filters),
            'activity' => $this->getActivityData($tenantId, $filters),
            default => [],
        };
    }

    protected function calculateNextSendTime(string $frequency, string $time, ?int $dayOfWeek, ?int $dayOfMonth): Carbon
    {
        $timeParts = explode(':', $time);
        $hour = (int) $timeParts[0];
        $minute = (int) ($timeParts[1] ?? 0);

        $next = now()->setTime($hour, $minute, 0);

        switch ($frequency) {
            case 'daily':
                if ($next->isPast()) {
                    $next->addDay();
                }
                break;

            case 'weekly':
                $targetDay = $dayOfWeek ?? 1; // Default Monday
                $next->next($targetDay);
                if ($next->isPast()) {
                    $next->addWeek();
                }
                break;

            case 'monthly':
                $targetDay = $dayOfMonth ?? 1;
                $next->day($targetDay);
                if ($next->isPast()) {
                    $next->addMonth();
                }
                break;
        }

        return $next;
    }

    // ============================================
    // Advanced Export Methods
    // ============================================

    /**
     * Export full report (multi-sheet Excel)
     */
    public function exportFullReport(Request $request)
    {
        $tenant = Auth::user()->tenant;

        $options = [
            'include_sites' => $request->boolean('sites', true),
            'include_boxes' => $request->boolean('boxes', true),
            'include_customers' => $request->boolean('customers', true),
            'include_contracts' => $request->boolean('contracts', true),
            'include_invoices' => $request->boolean('invoices', true),
            'include_payments' => $request->boolean('payments', true),
        ];

        try {
            $exportService = app(\App\Services\SpreadsheetExportService::class);
            $filePath = $exportService->exportFullReport($tenant, $options);

            return response()->download($filePath)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            \Log::error('Failed to export full report: ' . $e->getMessage(), [
                'tenant_id' => $tenant->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de l\'export. Veuillez reessayer.');
        }
    }

    /**
     * Export Rent Roll to Excel
     */
    public function exportRentRoll(Request $request)
    {
        $tenant = Auth::user()->tenant;
        $siteId = $request->input('site_id');

        try {
            $exportService = app(\App\Services\SpreadsheetExportService::class);
            $filePath = $exportService->exportRentRoll($tenant, $siteId);

            return response()->download($filePath)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            \Log::error('Failed to export rent roll: ' . $e->getMessage(), [
                'tenant_id' => $tenant->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de l\'export du Rent Roll.');
        }
    }

    /**
     * Export FEC (Fichier des Ecritures Comptables)
     */
    public function exportFec(Request $request)
    {
        $tenant = Auth::user()->tenant;
        $fiscalYear = $request->input('year', now()->year);

        try {
            $fecService = app(\App\Services\FecExportService::class);
            $fecExport = $fecService->generate($tenant, $fiscalYear);

            if ($fecExport->isReady()) {
                $download = $fecService->download($fecExport);

                return response($download['content'])
                    ->header('Content-Type', $download['mime_type'])
                    ->header('Content-Disposition', 'attachment; filename="' . $download['filename'] . '"');
            }

            return redirect()->back()
                ->with('error', 'L\'export FEC n\'est pas pret.');

        } catch (\Exception $e) {
            \Log::error('Failed to export FEC: ' . $e->getMessage(), [
                'tenant_id' => $tenant->id,
                'fiscal_year' => $fiscalYear,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de l\'export FEC: ' . $e->getMessage());
        }
    }
}
