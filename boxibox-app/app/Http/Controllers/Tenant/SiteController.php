<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Http\Requests\StoreSiteRequest;
use App\Http\Requests\UpdateSiteRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class SiteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $tenantId = $request->user()->tenant_id;

        $sites = Site::where('tenant_id', $tenantId)
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%");
                });
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->withCount('boxes')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'total' => Site::where('tenant_id', $tenantId)->count(),
            'active' => Site::where('tenant_id', $tenantId)->where('status', 'active')->count(),
            'total_boxes' => \App\Models\Box::where('tenant_id', $tenantId)->count(),
        ];

        return Inertia::render('Tenant/Sites/Index', [
            'sites' => $sites,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Tenant/Sites/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSiteRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = $request->user()->tenant_id;

        // Generate code if not provided
        if (empty($data['code'])) {
            $data['code'] = 'SITE-' . strtoupper(substr(uniqid(), -6));
        }

        $site = Site::create($data);

        // Update tenant statistics
        $site->tenant->updateStatistics();

        return redirect()
            ->route('tenant.sites.index')
            ->with('success', 'Site created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Site $site): Response
    {
        $this->authorize('view_sites');

        $site->load(['boxes' => function ($query) {
            $query->withCount('contracts');
        }, 'buildings.floors']);

        $stats = [
            'total_boxes' => $site->boxes()->count(),
            'available_boxes' => $site->boxes()->where('status', 'available')->count(),
            'occupied_boxes' => $site->boxes()->where('status', 'occupied')->count(),
            'occupation_rate' => $site->occupation_rate ?? 0,
        ];

        return Inertia::render('Tenant/Sites/Show', [
            'site' => $site,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Site $site): Response
    {
        $this->authorize('edit_sites');

        return Inertia::render('Tenant/Sites/Edit', [
            'site' => $site,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSiteRequest $request, Site $site): RedirectResponse
    {
        $site->update($request->validated());

        return redirect()
            ->route('tenant.sites.index')
            ->with('success', 'Site updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Site $site): RedirectResponse
    {
        $this->authorize('delete_sites');

        $site->delete();

        // Update tenant statistics
        if ($site->tenant) {
            $site->tenant->updateStatistics();
        }

        return redirect()
            ->route('tenant.sites.index')
            ->with('success', 'Site deleted successfully.');
    }
}
