<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBoxRequest;
use App\Http\Requests\UpdateBoxRequest;
use App\Models\Box;
use App\Models\Site;
use App\Models\Building;
use App\Models\Floor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BoxController extends Controller
{
    /**
     * Display the floor plan with boxes.
     */
    public function plan(Request $request): Response
    {
        $tenantId = $request->user()->tenant_id;
        $tenant = $request->user()->tenant;

        // Get all boxes with their contracts and customers
        $boxes = Box::where('tenant_id', $tenantId)
            ->with(['contract.customer'])
            ->select(['id', 'number', 'name', 'status', 'volume', 'position', 'current_price', 'site_id'])
            ->get()
            ->map(function ($box) {
                return [
                    'id' => $box->id,
                    'number' => $box->number,
                    'name' => $box->name,
                    'status' => $box->status,
                    'volume' => round($box->volume, 1),
                    'position' => $box->position,
                    'current_price' => $box->current_price,
                    'contract' => $box->contract ? [
                        'id' => $box->contract->id,
                        'contract_number' => $box->contract->contract_number,
                        'start_date' => $box->contract->start_date,
                        'customer' => $box->contract->customer ? [
                            'id' => $box->contract->customer->id,
                            'name' => $box->contract->customer->first_name . ' ' . $box->contract->customer->last_name,
                        ] : null,
                    ] : null,
                ];
            });

        // Calculate statistics
        $stats = [
            'total' => Box::where('tenant_id', $tenantId)->count(),
            'occupied' => Box::where('tenant_id', $tenantId)->where('status', 'occupied')->count(),
            'available' => Box::where('tenant_id', $tenantId)->where('status', 'available')->count(),
            'reserved' => Box::where('tenant_id', $tenantId)->where('status', 'reserved')->count(),
            'maintenance' => Box::where('tenant_id', $tenantId)->where('status', 'maintenance')->count(),
        ];

        // Get plan elements from tenant
        $planElements = $tenant->plan_elements ?? [];

        return Inertia::render('Tenant/Boxes/Plan', [
            'boxes' => $boxes,
            'stats' => $stats,
            'planElements' => $planElements,
        ]);
    }

    /**
     * Display the plan editor.
     */
    public function planEdit(Request $request): Response
    {
        $tenantId = $request->user()->tenant_id;
        $tenant = $request->user()->tenant;

        // Get all boxes with minimal data for editing
        $boxes = Box::where('tenant_id', $tenantId)
            ->select(['id', 'number', 'name', 'status', 'volume', 'position'])
            ->get()
            ->map(function ($box) {
                return [
                    'id' => $box->id,
                    'number' => $box->number,
                    'name' => $box->name,
                    'status' => $box->status,
                    'volume' => round($box->volume, 1),
                    'position' => $box->position,
                ];
            });

        // Get plan elements from tenant
        $planElements = $tenant->plan_elements ?? [];

        return Inertia::render('Tenant/Boxes/PlanEdit', [
            'boxes' => $boxes,
            'planElements' => $planElements,
        ]);
    }

    /**
     * Save box positions on the plan.
     */
    public function planSave(Request $request): RedirectResponse
    {
        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'boxes' => 'required|array',
            'boxes.*.id' => ['required', 'exists:boxes,id', new \App\Rules\SameTenantResource(\App\Models\Box::class, $tenantId)],
            'boxes.*.position' => 'required|array',
            'boxes.*.position.x' => 'nullable|numeric',
            'boxes.*.position.y' => 'nullable|numeric',
            'boxes.*.position.width' => 'nullable|numeric',
            'boxes.*.position.height' => 'nullable|numeric',
            'elements' => 'nullable|array',
        ]);

        // Update positions for each box (already validated for tenant ownership)
        foreach ($validated['boxes'] as $boxData) {
            Box::where('id', $boxData['id'])
                ->where('tenant_id', $tenantId)
                ->update(['position' => $boxData['position']]);
        }

        // Save plan elements to tenant
        if (isset($validated['elements'])) {
            $tenant = $request->user()->tenant;
            $tenant->plan_elements = $validated['elements'];
            $tenant->save();
        }

        return redirect()
            ->route('tenant.boxes.plan')
            ->with('success', 'Plan sauvegardé avec succès!');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $tenantId = $request->user()->tenant_id;

        $boxes = Box::where('tenant_id', $tenantId)
            ->with(['site:id,name,code', 'building:id,name', 'floor:id,name'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('number', 'like', "%{$search}%")
                        ->orWhereHas('site', function ($sq) use ($search) {
                            $sq->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->site_id, function ($query, $siteId) {
                $query->where('site_id', $siteId);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'total' => Box::where('tenant_id', $tenantId)->count(),
            'available' => Box::where('tenant_id', $tenantId)->where('status', 'available')->count(),
            'occupied' => Box::where('tenant_id', $tenantId)->where('status', 'occupied')->count(),
            'maintenance' => Box::where('tenant_id', $tenantId)->where('status', 'maintenance')->count(),
            'total_revenue' => Box::where('tenant_id', $tenantId)->where('status', 'occupied')->sum('current_price'),
        ];

        $sites = Site::where('tenant_id', $tenantId)
            ->select('id', 'name', 'code')
            ->orderBy('name')
            ->get();

        return Inertia::render('Tenant/Boxes/Index', [
            'boxes' => $boxes,
            'stats' => $stats,
            'sites' => $sites,
            'filters' => $request->only(['search', 'status', 'site_id']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        $tenantId = $request->user()->tenant_id;

        $sites = Site::where('tenant_id', $tenantId)
            ->select('id', 'name', 'code')
            ->orderBy('name')
            ->get();

        $buildings = Building::where('tenant_id', $tenantId)
            ->select('id', 'name', 'site_id')
            ->orderBy('name')
            ->get();

        $floors = Floor::where('tenant_id', $tenantId)
            ->select('id', 'name', 'building_id', 'floor_number')
            ->orderBy('floor_number')
            ->get();

        return Inertia::render('Tenant/Boxes/Create', [
            'sites' => $sites,
            'buildings' => $buildings,
            'floors' => $floors,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBoxRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = $request->user()->tenant_id;

        // Auto-generate number if not provided
        if (empty($data['number'])) {
            $data['number'] = 'BOX-' . strtoupper(substr(uniqid(), -6));
        }

        // Calculate volume
        $data['volume'] = $data['length'] * $data['width'] * $data['height'];

        // Set current price to base price initially
        $data['current_price'] = $data['base_price'];

        $box = Box::create($data);

        // Update tenant statistics
        if ($box->tenant) {
            $box->tenant->updateStatistics();
        }

        // Update site statistics if applicable
        if ($box->site) {
            $box->site->updateStatistics();
        }

        return redirect()
            ->route('tenant.boxes.index')
            ->with('success', 'Box created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Box $box): Response
    {
        $box->load(['site', 'building', 'floor', 'contract.customer', 'contracts' => function ($query) {
            $query->latest()->limit(5);
        }]);

        $stats = [
            'total_contracts' => $box->contracts()->count(),
            'active_contract' => $box->contract ? true : false,
            'monthly_revenue' => $box->status === 'occupied' ? $box->current_price : 0,
            'occupation_rate' => $box->contracts()->where('status', 'active')->exists() ? 100 : 0,
        ];

        return Inertia::render('Tenant/Boxes/Show', [
            'box' => $box,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Box $box): Response
    {
        $tenantId = $request->user()->tenant_id;

        $sites = Site::where('tenant_id', $tenantId)
            ->select('id', 'name', 'code')
            ->orderBy('name')
            ->get();

        $buildings = Building::where('tenant_id', $tenantId)
            ->select('id', 'name', 'site_id')
            ->orderBy('name')
            ->get();

        $floors = Floor::where('tenant_id', $tenantId)
            ->select('id', 'name', 'building_id', 'floor_number')
            ->orderBy('floor_number')
            ->get();

        return Inertia::render('Tenant/Boxes/Edit', [
            'box' => $box,
            'sites' => $sites,
            'buildings' => $buildings,
            'floors' => $floors,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBoxRequest $request, Box $box): RedirectResponse
    {
        $data = $request->validated();

        // Recalculate volume if dimensions changed
        if (isset($data['length']) || isset($data['width']) || isset($data['height'])) {
            $data['volume'] = ($data['length'] ?? $box->length) *
                            ($data['width'] ?? $box->width) *
                            ($data['height'] ?? $box->height);
        }

        $box->update($data);

        return redirect()
            ->route('tenant.boxes.index')
            ->with('success', 'Box updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Box $box): RedirectResponse
    {
        $box->delete();

        // Update tenant statistics
        if ($box->tenant) {
            $box->tenant->updateStatistics();
        }

        // Update site statistics
        if ($box->site) {
            $box->site->updateStatistics();
        }

        return redirect()
            ->route('tenant.boxes.index')
            ->with('success', 'Box deleted successfully.');
    }
}
