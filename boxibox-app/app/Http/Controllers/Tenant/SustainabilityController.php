<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\CarbonFootprint;
use App\Models\EnergyReading;
use App\Models\Site;
use App\Models\SustainabilityCertification;
use App\Models\SustainabilityGoal;
use App\Models\SustainabilityInitiative;
use App\Models\WasteRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SustainabilityController extends Controller
{
    /**
     * Display sustainability dashboard
     */
    public function index(Request $request): Response
    {
        $tenantId = Auth::user()->tenant_id;
        $siteId = $request->input('site_id');
        $year = $request->input('year', now()->year);

        // Get sites for filter
        $sites = Site::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        // Carbon Footprint Data
        $carbonData = CarbonFootprint::where('tenant_id', $tenantId)
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->forYear($year)
            ->orderBy('month')
            ->get();

        // Current year totals
        $yearlyTotals = [
            'total_co2' => $carbonData->sum('total_co2_kg'),
            'electricity_co2' => $carbonData->sum('electricity_co2_kg'),
            'gas_co2' => $carbonData->sum('gas_co2_kg'),
            'waste_co2' => $carbonData->sum('waste_co2_kg'),
            'offset_co2' => $carbonData->sum('offset_co2_kg'),
            'net_co2' => $carbonData->sum('net_co2_kg'),
        ];

        // Previous year comparison
        $previousYearCo2 = CarbonFootprint::where('tenant_id', $tenantId)
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->forYear($year - 1)
            ->sum('total_co2_kg');

        $co2Change = $previousYearCo2 > 0
            ? round((($yearlyTotals['total_co2'] - $previousYearCo2) / $previousYearCo2) * 100, 1)
            : 0;

        // Energy consumption data
        $energyData = EnergyReading::where('tenant_id', $tenantId)
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->forYear($year)
            ->orderBy('reading_date')
            ->get();

        $energyTotals = [
            'electricity_kwh' => $energyData->sum('electricity_kwh'),
            'gas_m3' => $energyData->sum('gas_m3'),
            'water_m3' => $energyData->sum('water_m3'),
            'solar_generated' => $energyData->sum('solar_generated_kwh'),
            'total_cost' => $energyData->sum('total_cost'),
        ];

        // Waste management data
        $wasteData = WasteRecord::where('tenant_id', $tenantId)
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->forYear($year)
            ->orderBy('record_date')
            ->get();

        $wasteTotals = [
            'total_kg' => $wasteData->sum('total_kg'),
            'recycled_kg' => $wasteData->sum('recycled_kg'),
            'landfill_kg' => $wasteData->sum('landfill_kg'),
            'avg_recycling_rate' => $wasteData->avg('recycling_rate') ?? 0,
            'disposal_cost' => $wasteData->sum('disposal_cost'),
        ];

        // Active goals
        $goals = SustainabilityGoal::where('tenant_id', $tenantId)
            ->active()
            ->orderBy('target_year')
            ->get();

        // Active initiatives
        $initiatives = SustainabilityInitiative::where('tenant_id', $tenantId)
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->active()
            ->orderBy('end_date')
            ->with('site')
            ->get();

        // Active certifications
        $certifications = SustainabilityCertification::where('tenant_id', $tenantId)
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->active()
            ->orderBy('expiry_date')
            ->get();

        // Chart data - monthly CO2 emissions
        $monthlyEmissions = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthData = $carbonData->firstWhere('month', $m);
            $monthlyEmissions[] = [
                'month' => $m,
                'label' => ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'][$m - 1],
                'electricity' => $monthData?->electricity_co2_kg ?? 0,
                'gas' => $monthData?->gas_co2_kg ?? 0,
                'waste' => $monthData?->waste_co2_kg ?? 0,
                'total' => $monthData?->total_co2_kg ?? 0,
            ];
        }

        // Energy consumption trend
        $energyTrend = $energyData->groupBy(fn($item) => $item->reading_date->format('Y-m'))
            ->map(fn($group) => [
                'electricity' => $group->sum('electricity_kwh'),
                'gas' => $group->sum('gas_m3'),
                'water' => $group->sum('water_m3'),
                'solar' => $group->sum('solar_generated_kwh'),
            ])->toArray();

        // Waste breakdown
        $wasteBreakdown = [
            ['name' => 'Déchets généraux', 'value' => $wasteData->sum('general_waste_kg'), 'color' => '#6b7280'],
            ['name' => 'Recyclage', 'value' => $wasteData->sum('recycling_kg'), 'color' => '#10b981'],
            ['name' => 'Carton', 'value' => $wasteData->sum('cardboard_kg'), 'color' => '#f59e0b'],
            ['name' => 'Dangereux', 'value' => $wasteData->sum('hazardous_kg'), 'color' => '#ef4444'],
            ['name' => 'Organique', 'value' => $wasteData->sum('organic_kg'), 'color' => '#84cc16'],
        ];

        return Inertia::render('Tenant/Sustainability/Index', [
            'sites' => $sites,
            'filters' => [
                'site_id' => $siteId,
                'year' => $year,
            ],
            'yearlyTotals' => $yearlyTotals,
            'co2Change' => $co2Change,
            'energyTotals' => $energyTotals,
            'wasteTotals' => $wasteTotals,
            'goals' => $goals,
            'initiatives' => $initiatives,
            'certifications' => $certifications,
            'charts' => [
                'monthlyEmissions' => $monthlyEmissions,
                'energyTrend' => $energyTrend,
                'wasteBreakdown' => $wasteBreakdown,
            ],
            'constants' => [
                'metrics' => SustainabilityGoal::METRICS,
                'categories' => SustainabilityInitiative::CATEGORIES,
                'statuses' => SustainabilityInitiative::STATUSES,
                'certificationTypes' => SustainabilityCertification::TYPES,
                'certificationLevels' => SustainabilityCertification::LEVELS,
            ],
        ]);
    }

    /**
     * Energy readings management
     */
    public function energy(Request $request): Response
    {
        $tenantId = Auth::user()->tenant_id;
        $query = EnergyReading::where('tenant_id', $tenantId)
            ->with('site')
            ->orderBy('reading_date', 'desc');

        if ($request->filled('site_id')) {
            $query->where('site_id', $request->input('site_id'));
        }

        if ($request->filled('year')) {
            $query->forYear($request->input('year'));
        }

        $readings = $query->paginate(25);
        $sites = Site::where('tenant_id', $tenantId)->get(['id', 'name']);

        return Inertia::render('Tenant/Sustainability/Energy', [
            'readings' => $readings,
            'sites' => $sites,
            'filters' => $request->only(['site_id', 'year']),
        ]);
    }

    public function storeEnergy(Request $request)
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'reading_date' => 'required|date',
            'electricity_kwh' => 'required|numeric|min:0',
            'gas_m3' => 'nullable|numeric|min:0',
            'water_m3' => 'nullable|numeric|min:0',
            'solar_generated_kwh' => 'nullable|numeric|min:0',
            'electricity_cost' => 'nullable|numeric|min:0',
            'gas_cost' => 'nullable|numeric|min:0',
            'water_cost' => 'nullable|numeric|min:0',
        ]);

        $validated['tenant_id'] = Auth::user()->tenant_id;

        $reading = EnergyReading::create($validated);

        // Recalculate carbon footprint for this month
        CarbonFootprint::calculateForMonth(
            $validated['tenant_id'],
            $validated['site_id'],
            date('Y', strtotime($validated['reading_date'])),
            date('n', strtotime($validated['reading_date']))
        );

        return back()->with('success', 'Relevé enregistré avec succès.');
    }

    public function updateEnergy(Request $request, EnergyReading $reading)
    {
        $this->authorize('update', $reading);

        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'reading_date' => 'required|date',
            'electricity_kwh' => 'required|numeric|min:0',
            'gas_m3' => 'nullable|numeric|min:0',
            'water_m3' => 'nullable|numeric|min:0',
            'solar_generated_kwh' => 'nullable|numeric|min:0',
            'electricity_cost' => 'nullable|numeric|min:0',
            'gas_cost' => 'nullable|numeric|min:0',
            'water_cost' => 'nullable|numeric|min:0',
        ]);

        $reading->update($validated);

        // Recalculate carbon footprint
        CarbonFootprint::calculateForMonth(
            $reading->tenant_id,
            $reading->site_id,
            $reading->reading_date->year,
            $reading->reading_date->month
        );

        return back()->with('success', 'Relevé mis à jour.');
    }

    public function destroyEnergy(EnergyReading $reading)
    {
        $this->authorize('delete', $reading);
        $reading->delete();
        return back()->with('success', 'Relevé supprimé.');
    }

    /**
     * Waste records management
     */
    public function waste(Request $request): Response
    {
        $tenantId = Auth::user()->tenant_id;
        $query = WasteRecord::where('tenant_id', $tenantId)
            ->with('site')
            ->orderBy('record_date', 'desc');

        if ($request->filled('site_id')) {
            $query->where('site_id', $request->input('site_id'));
        }

        $records = $query->paginate(25);
        $sites = Site::where('tenant_id', $tenantId)->get(['id', 'name']);

        return Inertia::render('Tenant/Sustainability/Waste', [
            'records' => $records,
            'sites' => $sites,
            'filters' => $request->only(['site_id']),
        ]);
    }

    public function storeWaste(Request $request)
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'record_date' => 'required|date',
            'general_waste_kg' => 'nullable|numeric|min:0',
            'recycling_kg' => 'nullable|numeric|min:0',
            'cardboard_kg' => 'nullable|numeric|min:0',
            'hazardous_kg' => 'nullable|numeric|min:0',
            'organic_kg' => 'nullable|numeric|min:0',
            'disposal_cost' => 'nullable|numeric|min:0',
        ]);

        $validated['tenant_id'] = Auth::user()->tenant_id;
        WasteRecord::create($validated);

        return back()->with('success', 'Enregistrement créé avec succès.');
    }

    public function updateWaste(Request $request, WasteRecord $record)
    {
        $this->authorize('update', $record);

        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'record_date' => 'required|date',
            'general_waste_kg' => 'nullable|numeric|min:0',
            'recycling_kg' => 'nullable|numeric|min:0',
            'cardboard_kg' => 'nullable|numeric|min:0',
            'hazardous_kg' => 'nullable|numeric|min:0',
            'organic_kg' => 'nullable|numeric|min:0',
            'disposal_cost' => 'nullable|numeric|min:0',
        ]);

        $record->update($validated);
        return back()->with('success', 'Enregistrement mis à jour.');
    }

    public function destroyWaste(WasteRecord $record)
    {
        $this->authorize('delete', $record);
        $record->delete();
        return back()->with('success', 'Enregistrement supprimé.');
    }

    /**
     * Initiatives management
     */
    public function initiatives(Request $request): Response
    {
        $tenantId = Auth::user()->tenant_id;
        $query = SustainabilityInitiative::where('tenant_id', $tenantId)
            ->with('site')
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('category')) {
            $query->byCategory($request->input('category'));
        }

        $initiatives = $query->paginate(15);
        $sites = Site::where('tenant_id', $tenantId)->get(['id', 'name']);

        return Inertia::render('Tenant/Sustainability/Initiatives', [
            'initiatives' => $initiatives,
            'sites' => $sites,
            'filters' => $request->only(['status', 'category']),
            'categories' => SustainabilityInitiative::CATEGORIES,
            'statuses' => SustainabilityInitiative::STATUSES,
        ]);
    }

    public function storeInitiative(Request $request)
    {
        $validated = $request->validate([
            'site_id' => 'nullable|exists:sites,id',
            'name' => 'required|string|max:255',
            'category' => 'required|in:' . implode(',', array_keys(SustainabilityInitiative::CATEGORIES)),
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'investment_cost' => 'nullable|numeric|min:0',
            'annual_savings' => 'nullable|numeric|min:0',
            'co2_reduction_kg' => 'nullable|numeric|min:0',
            'status' => 'required|in:' . implode(',', array_keys(SustainabilityInitiative::STATUSES)),
            'progress_percent' => 'nullable|integer|min:0|max:100',
        ]);

        $validated['tenant_id'] = Auth::user()->tenant_id;
        SustainabilityInitiative::create($validated);

        return back()->with('success', 'Initiative créée avec succès.');
    }

    public function updateInitiative(Request $request, SustainabilityInitiative $initiative)
    {
        $this->authorize('update', $initiative);

        $validated = $request->validate([
            'site_id' => 'nullable|exists:sites,id',
            'name' => 'required|string|max:255',
            'category' => 'required|in:' . implode(',', array_keys(SustainabilityInitiative::CATEGORIES)),
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'investment_cost' => 'nullable|numeric|min:0',
            'annual_savings' => 'nullable|numeric|min:0',
            'co2_reduction_kg' => 'nullable|numeric|min:0',
            'status' => 'required|in:' . implode(',', array_keys(SustainabilityInitiative::STATUSES)),
            'progress_percent' => 'nullable|integer|min:0|max:100',
        ]);

        $initiative->update($validated);
        return back()->with('success', 'Initiative mise à jour.');
    }

    public function destroyInitiative(SustainabilityInitiative $initiative)
    {
        $this->authorize('delete', $initiative);
        $initiative->delete();
        return back()->with('success', 'Initiative supprimée.');
    }

    /**
     * Goals management
     */
    public function goals(Request $request): Response
    {
        $tenantId = Auth::user()->tenant_id;
        $query = SustainabilityGoal::where('tenant_id', $tenantId)
            ->orderBy('target_year');

        if ($request->filled('status')) {
            if ($request->input('status') === 'active') {
                $query->active();
            } else {
                $query->achieved();
            }
        }

        $goals = $query->paginate(15);

        return Inertia::render('Tenant/Sustainability/Goals', [
            'goals' => $goals,
            'filters' => $request->only(['status']),
            'metrics' => SustainabilityGoal::METRICS,
        ]);
    }

    public function storeGoal(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'metric' => 'required|in:' . implode(',', array_keys(SustainabilityGoal::METRICS)),
            'baseline_value' => 'required|numeric',
            'target_value' => 'required|numeric',
            'current_value' => 'nullable|numeric',
            'unit' => 'required|string|max:50',
            'target_year' => 'required|integer|min:' . now()->year,
            'description' => 'nullable|string',
        ]);

        $validated['tenant_id'] = Auth::user()->tenant_id;
        $validated['current_value'] = $validated['current_value'] ?? $validated['baseline_value'];

        $goal = SustainabilityGoal::create($validated);
        $goal->checkAndMarkAchieved();

        return back()->with('success', 'Objectif créé avec succès.');
    }

    public function updateGoal(Request $request, SustainabilityGoal $goal)
    {
        $this->authorize('update', $goal);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'metric' => 'required|in:' . implode(',', array_keys(SustainabilityGoal::METRICS)),
            'baseline_value' => 'required|numeric',
            'target_value' => 'required|numeric',
            'current_value' => 'required|numeric',
            'unit' => 'required|string|max:50',
            'target_year' => 'required|integer|min:' . now()->year,
            'description' => 'nullable|string',
        ]);

        $goal->update($validated);
        $goal->checkAndMarkAchieved();

        return back()->with('success', 'Objectif mis à jour.');
    }

    public function destroyGoal(SustainabilityGoal $goal)
    {
        $this->authorize('delete', $goal);
        $goal->delete();
        return back()->with('success', 'Objectif supprimé.');
    }

    /**
     * Certifications management
     */
    public function certifications(Request $request): Response
    {
        $tenantId = Auth::user()->tenant_id;
        $query = SustainabilityCertification::where('tenant_id', $tenantId)
            ->with('site')
            ->orderBy('expiry_date');

        if ($request->filled('status')) {
            match ($request->input('status')) {
                'active' => $query->active(),
                'expiring' => $query->expiringSoon(),
                'expired' => $query->expired(),
                default => null,
            };
        }

        $certifications = $query->paginate(15);
        $sites = Site::where('tenant_id', $tenantId)->get(['id', 'name']);

        return Inertia::render('Tenant/Sustainability/Certifications', [
            'certifications' => $certifications,
            'sites' => $sites,
            'filters' => $request->only(['status']),
            'types' => SustainabilityCertification::TYPES,
            'levels' => SustainabilityCertification::LEVELS,
        ]);
    }

    public function storeCertification(Request $request)
    {
        $validated = $request->validate([
            'site_id' => 'nullable|exists:sites,id',
            'name' => 'required|string|max:255',
            'type' => 'required|in:' . implode(',', array_keys(SustainabilityCertification::TYPES)),
            'issuing_body' => 'required|string|max:255',
            'certification_number' => 'nullable|string|max:100',
            'issue_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:issue_date',
            'level' => 'nullable|in:' . implode(',', array_keys(SustainabilityCertification::LEVELS)),
            'score' => 'nullable|integer|min:0|max:100',
            'document_path' => 'nullable|string',
        ]);

        $validated['tenant_id'] = Auth::user()->tenant_id;
        $validated['is_active'] = true;

        SustainabilityCertification::create($validated);

        return back()->with('success', 'Certification ajoutée avec succès.');
    }

    public function updateCertification(Request $request, SustainabilityCertification $certification)
    {
        $this->authorize('update', $certification);

        $validated = $request->validate([
            'site_id' => 'nullable|exists:sites,id',
            'name' => 'required|string|max:255',
            'type' => 'required|in:' . implode(',', array_keys(SustainabilityCertification::TYPES)),
            'issuing_body' => 'required|string|max:255',
            'certification_number' => 'nullable|string|max:100',
            'issue_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:issue_date',
            'level' => 'nullable|in:' . implode(',', array_keys(SustainabilityCertification::LEVELS)),
            'score' => 'nullable|integer|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        $certification->update($validated);
        return back()->with('success', 'Certification mise à jour.');
    }

    public function destroyCertification(SustainabilityCertification $certification)
    {
        $this->authorize('delete', $certification);
        $certification->delete();
        return back()->with('success', 'Certification supprimée.');
    }

    /**
     * Generate sustainability report
     */
    public function report(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $year = $request->input('year', now()->year);
        $siteId = $request->input('site_id');

        // Gather all data for report
        $carbonData = CarbonFootprint::where('tenant_id', $tenantId)
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->forYear($year)
            ->get();

        $energyData = EnergyReading::where('tenant_id', $tenantId)
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->forYear($year)
            ->get();

        $wasteData = WasteRecord::where('tenant_id', $tenantId)
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->forYear($year)
            ->get();

        $goals = SustainabilityGoal::where('tenant_id', $tenantId)->get();
        $initiatives = SustainabilityInitiative::where('tenant_id', $tenantId)
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->get();
        $certifications = SustainabilityCertification::where('tenant_id', $tenantId)
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->active()
            ->get();

        return Inertia::render('Tenant/Sustainability/Report', [
            'year' => $year,
            'summary' => [
                'total_co2_kg' => $carbonData->sum('total_co2_kg'),
                'total_energy_kwh' => $energyData->sum('electricity_kwh'),
                'solar_generated_kwh' => $energyData->sum('solar_generated_kwh'),
                'total_waste_kg' => $wasteData->sum('total_kg'),
                'recycling_rate' => $wasteData->avg('recycling_rate'),
                'active_initiatives' => $initiatives->where('status', 'in_progress')->count(),
                'completed_initiatives' => $initiatives->where('status', 'completed')->count(),
                'goals_achieved' => $goals->where('is_achieved', true)->count(),
                'certifications_count' => $certifications->count(),
            ],
            'carbonData' => $carbonData,
            'energyData' => $energyData,
            'wasteData' => $wasteData,
            'goals' => $goals,
            'initiatives' => $initiatives,
            'certifications' => $certifications,
        ]);
    }
}
