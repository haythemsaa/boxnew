<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\CalculatorCategory;
use App\Models\CalculatorItem;
use App\Models\CalculatorWidget;
use App\Models\CalculatorSession;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CalculatorController extends Controller
{
    public function index()
    {
        $tenantId = Auth::user()->tenant_id;

        $widgets = CalculatorWidget::where('tenant_id', $tenantId)
            ->with('site')
            ->get();

        $recentSessions = CalculatorSession::where('tenant_id', $tenantId)
            ->latest()
            ->take(5)
            ->get();

        $stats = [
            'total_sessions' => CalculatorSession::where('tenant_id', $tenantId)->count(),
            'converted_sessions' => CalculatorSession::where('tenant_id', $tenantId)->converted()->count(),
            'conversion_rate' => $this->calculateConversionRate($tenantId),
            'avg_volume' => CalculatorSession::where('tenant_id', $tenantId)->avg('total_volume') ?? 0,
            'total_widgets' => $widgets->count(),
        ];

        return Inertia::render('Tenant/Calculator/Index', [
            'widgets' => $widgets,
            'stats' => $stats,
            'recentSessions' => $recentSessions,
        ]);
    }

    /**
     * Display the 3D vehicle loading simulator
     */
    public function vehicleSimulator()
    {
        $tenantId = Auth::user()->tenant_id;

        $sites = Site::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get(['id', 'name', 'code']);

        return Inertia::render('Tenant/Calculator/VehicleSimulator', [
            'sites' => $sites,
        ]);
    }

    public function categories()
    {
        $tenantId = Auth::user()->tenant_id;

        $categories = CalculatorCategory::forTenant($tenantId)
            ->with(['items' => fn($q) => $q->orderBy('order')])
            ->orderBy('order')
            ->get();

        return Inertia::render('Tenant/Calculator/Categories', [
            'categories' => $categories,
        ]);
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:50',
            'order' => 'nullable|integer|min:0',
        ]);

        CalculatorCategory::create([
            'tenant_id' => Auth::user()->tenant_id,
            'name' => $validated['name'],
            'icon' => $validated['icon'] ?? null,
            'order' => $validated['order'] ?? 0,
            'is_global' => false,
            'is_active' => true,
        ]);

        return back()->with('success', 'Catégorie créée.');
    }

    public function updateCategory(Request $request, CalculatorCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:50',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $category->update($validated);

        return back()->with('success', 'Catégorie mise à jour.');
    }

    public function destroyCategory(CalculatorCategory $category)
    {
        if ($category->is_global) {
            return back()->with('error', 'Impossible de supprimer une catégorie globale.');
        }

        $category->items()->delete();
        $category->delete();

        return back()->with('success', 'Catégorie supprimée.');
    }

    public function storeItem(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:calculator_categories,id',
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:50',
            'volume_m3' => 'required|numeric|min:0.01',
            'width_m' => 'nullable|numeric|min:0',
            'height_m' => 'nullable|numeric|min:0',
            'depth_m' => 'nullable|numeric|min:0',
            'is_stackable' => 'boolean',
            'order' => 'nullable|integer|min:0',
        ]);

        CalculatorItem::create([
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'icon' => $validated['icon'] ?? null,
            'volume_m3' => $validated['volume_m3'],
            'width_m' => $validated['width_m'] ?? null,
            'height_m' => $validated['height_m'] ?? null,
            'depth_m' => $validated['depth_m'] ?? null,
            'is_stackable' => $validated['is_stackable'] ?? true,
            'order' => $validated['order'] ?? 0,
            'is_active' => true,
        ]);

        return back()->with('success', 'Article ajouté.');
    }

    public function updateItem(Request $request, CalculatorItem $item)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:50',
            'volume_m3' => 'required|numeric|min:0.01',
            'width_m' => 'nullable|numeric|min:0',
            'height_m' => 'nullable|numeric|min:0',
            'depth_m' => 'nullable|numeric|min:0',
            'is_stackable' => 'boolean',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $item->update($validated);

        return back()->with('success', 'Article mis à jour.');
    }

    public function destroyItem(CalculatorItem $item)
    {
        $item->delete();
        return back()->with('success', 'Article supprimé.');
    }

    // Widgets
    public function widgets()
    {
        $tenantId = Auth::user()->tenant_id;

        $widgets = CalculatorWidget::where('tenant_id', $tenantId)
            ->with('site')
            ->get();

        return Inertia::render('Tenant/Calculator/Widgets', [
            'widgets' => $widgets,
            'sites' => Site::where('tenant_id', $tenantId)->get(['id', 'name']),
        ]);
    }

    public function storeWidget(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'site_id' => ['nullable', 'exists:sites,id', new \App\Rules\SameTenantResource(\App\Models\Site::class, $tenantId)],
            'style_config' => 'nullable|array',
            'categories_enabled' => 'nullable|array',
            'show_prices' => 'boolean',
            'show_availability' => 'boolean',
            'require_contact' => 'boolean',
            'enable_booking' => 'boolean',
            'redirect_url' => 'nullable|url',
            'custom_css' => 'nullable|string',
        ]);

        $widget = CalculatorWidget::create([
            'tenant_id' => Auth::user()->tenant_id,
            'site_id' => $validated['site_id'] ?? null,
            'name' => $validated['name'],
            'style_config' => $validated['style_config'] ?? null,
            'categories_enabled' => $validated['categories_enabled'] ?? null,
            'show_prices' => $validated['show_prices'] ?? true,
            'show_availability' => $validated['show_availability'] ?? true,
            'require_contact' => $validated['require_contact'] ?? false,
            'enable_booking' => $validated['enable_booking'] ?? true,
            'redirect_url' => $validated['redirect_url'] ?? null,
            'custom_css' => $validated['custom_css'] ?? null,
            'is_active' => true,
        ]);

        return back()->with('success', 'Widget créé. Code d\'intégration: ' . $widget->embed_html);
    }

    public function updateWidget(Request $request, CalculatorWidget $widget)
    {
        $tenantId = Auth::user()->tenant_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'site_id' => ['nullable', 'exists:sites,id', new \App\Rules\SameTenantResource(\App\Models\Site::class, $tenantId)],
            'style_config' => 'nullable|array',
            'categories_enabled' => 'nullable|array',
            'show_prices' => 'boolean',
            'show_availability' => 'boolean',
            'require_contact' => 'boolean',
            'enable_booking' => 'boolean',
            'redirect_url' => 'nullable|url',
            'custom_css' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $widget->update($validated);

        return back()->with('success', 'Widget mis à jour.');
    }

    public function destroyWidget(CalculatorWidget $widget)
    {
        $widget->delete();

        return back()->with('success', 'Widget supprimé.');
    }

    // Sessions/Analytics
    public function sessions(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $query = CalculatorSession::where('tenant_id', $tenantId)
            ->with(['lead', 'recommendedBox']);

        if ($request->filled('converted')) {
            if ($request->converted === 'yes') {
                $query->converted();
            } else {
                $query->where('converted_to_lead', false);
            }
        }

        $sessions = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('Tenant/Calculator/Sessions', [
            'sessions' => $sessions,
            'filters' => $request->only(['converted']),
        ]);
    }

    public function sessionDetails(CalculatorSession $session)
    {
        $session->load(['lead', 'recommendedBox', 'booking']);

        return Inertia::render('Tenant/Calculator/SessionDetails', [
            'session' => $session,
        ]);
    }

    protected function calculateConversionRate(int $tenantId): float
    {
        $total = CalculatorSession::where('tenant_id', $tenantId)->count();
        if ($total === 0) return 0;

        $converted = CalculatorSession::where('tenant_id', $tenantId)->converted()->count();
        return round(($converted / $total) * 100, 1);
    }
}
