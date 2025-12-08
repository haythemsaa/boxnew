<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\FeatureFlag;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FeatureFlagController extends Controller
{
    public function index()
    {
        $flags = FeatureFlag::latest()->paginate(20);

        $stats = [
            'total' => FeatureFlag::count(),
            'enabled' => FeatureFlag::where('is_enabled', true)->count(),
            'disabled' => FeatureFlag::where('is_enabled', false)->count(),
        ];

        return Inertia::render('SuperAdmin/FeatureFlags/Index', [
            'flags' => $flags,
            'stats' => $stats,
        ]);
    }

    public function create()
    {
        $tenants = Tenant::select('id', 'name', 'plan')->orderBy('name')->get();
        $plans = ['free', 'starter', 'professional', 'enterprise'];

        return Inertia::render('SuperAdmin/FeatureFlags/Create', [
            'tenants' => $tenants,
            'plans' => $plans,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'key' => 'required|string|max:255|unique:feature_flags,key',
            'description' => 'nullable|string',
            'is_enabled' => 'boolean',
            'enabled_for_tenants' => 'nullable|array',
            'enabled_for_tenants.*' => 'exists:tenants,id',
            'enabled_for_plans' => 'nullable|array',
            'enabled_for_plans.*' => 'in:free,starter,professional,enterprise',
        ]);

        FeatureFlag::create($validated);

        return redirect()->route('superadmin.feature-flags.index')
            ->with('success', 'Feature flag créé.');
    }

    public function edit(FeatureFlag $featureFlag)
    {
        $tenants = Tenant::select('id', 'name', 'plan')->orderBy('name')->get();
        $plans = ['free', 'starter', 'professional', 'enterprise'];

        return Inertia::render('SuperAdmin/FeatureFlags/Edit', [
            'flag' => $featureFlag,
            'tenants' => $tenants,
            'plans' => $plans,
        ]);
    }

    public function update(Request $request, FeatureFlag $featureFlag)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'key' => "required|string|max:255|unique:feature_flags,key,{$featureFlag->id}",
            'description' => 'nullable|string',
            'is_enabled' => 'boolean',
            'enabled_for_tenants' => 'nullable|array',
            'enabled_for_tenants.*' => 'exists:tenants,id',
            'enabled_for_plans' => 'nullable|array',
            'enabled_for_plans.*' => 'in:free,starter,professional,enterprise',
        ]);

        $featureFlag->update($validated);

        return redirect()->route('superadmin.feature-flags.index')
            ->with('success', 'Feature flag mis à jour.');
    }

    public function destroy(FeatureFlag $featureFlag)
    {
        $featureFlag->delete();

        return redirect()->route('superadmin.feature-flags.index')
            ->with('success', 'Feature flag supprimé.');
    }

    public function toggle(FeatureFlag $featureFlag)
    {
        $featureFlag->update(['is_enabled' => !$featureFlag->is_enabled]);

        return back()->with('success', $featureFlag->is_enabled ? 'Feature activée.' : 'Feature désactivée.');
    }
}
