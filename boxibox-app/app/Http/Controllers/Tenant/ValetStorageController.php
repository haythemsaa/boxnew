<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Site;
use App\Models\ValetDriver;
use App\Models\ValetItem;
use App\Models\ValetOrder;
use App\Models\ValetOrderItem;
use App\Models\ValetPricing;
use App\Models\ValetRoute;
use App\Models\ValetSettings;
use App\Models\ValetZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ValetStorageController extends Controller
{
    protected function tenantId()
    {
        return Auth::user()->tenant_id;
    }

    // ============ DASHBOARD ============

    public function index()
    {
        $tenantId = $this->tenantId();

        // Stats
        $stats = [
            'total_items' => ValetItem::forTenant($tenantId)->count(),
            'stored_items' => ValetItem::forTenant($tenantId)->stored()->count(),
            'pending_pickups' => ValetOrder::forTenant($tenantId)->pickups()->pending()->count(),
            'pending_deliveries' => ValetOrder::forTenant($tenantId)->deliveries()->pending()->count(),
            'todays_orders' => ValetOrder::forTenant($tenantId)->forDate(today())->count(),
            'active_drivers' => ValetDriver::forTenant($tenantId)->available()->count(),
            'monthly_revenue' => ValetOrder::forTenant($tenantId)
                ->where('is_paid', true)
                ->whereMonth('completed_at', now()->month)
                ->sum('total_fee'),
            'total_volume_m3' => ValetItem::forTenant($tenantId)->stored()->sum('volume_m3'),
        ];

        // Recent orders
        $recentOrders = ValetOrder::forTenant($tenantId)
            ->with(['customer', 'driver', 'site'])
            ->latest()
            ->take(10)
            ->get();

        // Today's schedule
        $todaysOrders = ValetOrder::forTenant($tenantId)
            ->with(['customer', 'driver', 'site'])
            ->forDate(today())
            ->orderBy('scheduled_time_start')
            ->get();

        // Available drivers
        $drivers = ValetDriver::forTenant($tenantId)
            ->with('user')
            ->active()
            ->get();

        return Inertia::render('Tenant/ValetStorage/Index', [
            'stats' => $stats,
            'recentOrders' => $recentOrders,
            'todaysOrders' => $todaysOrders,
            'drivers' => $drivers,
        ]);
    }

    // ============ ITEMS ============

    public function items(Request $request)
    {
        $tenantId = $this->tenantId();

        $query = ValetItem::forTenant($tenantId)
            ->with(['customer', 'site']);

        // Filters
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }
        if ($request->filled('customer_id')) {
            $query->byCustomer($request->customer_id);
        }
        if ($request->filled('site_id')) {
            $query->where('site_id', $request->site_id);
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $items = $query->latest()->paginate(20)->withQueryString();

        $customers = Customer::where('tenant_id', $tenantId)->get(['id', 'first_name', 'last_name']);
        $sites = Site::where('tenant_id', $tenantId)->get(['id', 'name']);

        return Inertia::render('Tenant/ValetStorage/Items/Index', [
            'items' => $items,
            'customers' => $customers,
            'sites' => $sites,
            'filters' => $request->only(['status', 'customer_id', 'site_id', 'category', 'search']),
            'categories' => $this->getItemCategories(),
            'statuses' => $this->getItemStatuses(),
        ]);
    }

    public function createItem()
    {
        $tenantId = $this->tenantId();

        return Inertia::render('Tenant/ValetStorage/Items/Create', [
            'customers' => Customer::where('tenant_id', $tenantId)->get(['id', 'first_name', 'last_name', 'email']),
            'sites' => Site::where('tenant_id', $tenantId)->get(['id', 'name']),
            'categories' => $this->getItemCategories(),
            'sizes' => $this->getItemSizes(),
            'conditions' => $this->getItemConditions(),
        ]);
    }

    public function storeItem(Request $request)
    {
        $tenantId = $this->tenantId();

        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id', new \App\Rules\SameTenantResource(\App\Models\Customer::class, $tenantId)],
            'site_id' => ['required', 'exists:sites,id', new \App\Rules\SameTenantResource(\App\Models\Site::class, $tenantId)],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'size' => 'required|in:small,medium,large,extra_large',
            'weight_kg' => 'nullable|numeric|min:0',
            'volume_m3' => 'nullable|numeric|min:0',
            'condition' => 'required|in:excellent,good,fair,damaged',
            'storage_location' => 'nullable|string|max:100',
            'monthly_fee' => 'required|numeric|min:0',
            'declared_value' => 'nullable|numeric|min:0',
            'is_fragile' => 'boolean',
            'requires_climate_control' => 'boolean',
            'special_instructions' => 'nullable|string',
            'photos' => 'nullable|array',
            'photos.*' => 'string',
        ]);

        $validated['tenant_id'] = $this->tenantId();
        $validated['status'] = 'stored';
        $validated['storage_start_date'] = now();

        $item = ValetItem::create($validated);

        return redirect()->route('tenant.valet.items')
            ->with('success', 'Article créé avec succès. Code-barres: ' . $item->barcode);
    }

    public function showItem(ValetItem $valetItem)
    {
        $valetItem->load(['customer', 'site', 'orderItems.order']);

        return Inertia::render('Tenant/ValetStorage/Items/Show', [
            'item' => $valetItem,
            'history' => $valetItem->orderItems()->with('order')->latest()->get(),
        ]);
    }

    public function editItem(ValetItem $valetItem)
    {
        $tenantId = $this->tenantId();

        return Inertia::render('Tenant/ValetStorage/Items/Edit', [
            'item' => $valetItem,
            'customers' => Customer::where('tenant_id', $tenantId)->get(['id', 'first_name', 'last_name', 'email']),
            'sites' => Site::where('tenant_id', $tenantId)->get(['id', 'name']),
            'categories' => $this->getItemCategories(),
            'sizes' => $this->getItemSizes(),
            'conditions' => $this->getItemConditions(),
            'statuses' => $this->getItemStatuses(),
        ]);
    }

    public function updateItem(Request $request, ValetItem $valetItem)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'size' => 'required|in:small,medium,large,extra_large',
            'weight_kg' => 'nullable|numeric|min:0',
            'volume_m3' => 'nullable|numeric|min:0',
            'condition' => 'required|in:excellent,good,fair,damaged',
            'storage_location' => 'nullable|string|max:100',
            'status' => 'required|string',
            'monthly_fee' => 'required|numeric|min:0',
            'declared_value' => 'nullable|numeric|min:0',
            'is_fragile' => 'boolean',
            'requires_climate_control' => 'boolean',
            'special_instructions' => 'nullable|string',
            'photos' => 'nullable|array',
        ]);

        $valetItem->update($validated);

        return redirect()->route('tenant.valet.items.show', $valetItem)
            ->with('success', 'Article mis à jour avec succès.');
    }

    public function destroyItem(ValetItem $valetItem)
    {
        $valetItem->delete();

        return redirect()->route('tenant.valet.items')
            ->with('success', 'Article supprimé avec succès.');
    }

    // ============ ORDERS ============

    public function orders(Request $request)
    {
        $tenantId = $this->tenantId();

        $query = ValetOrder::forTenant($tenantId)
            ->with(['customer', 'driver', 'site', 'orderItems']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('requested_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('requested_date', '<=', $request->date_to);
        }
        if ($request->filled('driver_id')) {
            $query->where('assigned_driver_id', $request->driver_id);
        }

        $orders = $query->latest('requested_date')->paginate(20)->withQueryString();

        $drivers = ValetDriver::forTenant($tenantId)->with('user')->get();

        return Inertia::render('Tenant/ValetStorage/Orders/Index', [
            'orders' => $orders,
            'drivers' => $drivers,
            'filters' => $request->only(['status', 'type', 'date_from', 'date_to', 'driver_id']),
            'statuses' => $this->getOrderStatuses(),
            'types' => $this->getOrderTypes(),
        ]);
    }

    public function createOrder()
    {
        $tenantId = $this->tenantId();

        $customers = Customer::where('tenant_id', $tenantId)
            ->with(['valetItems' => fn($q) => $q->stored()])
            ->get();

        return Inertia::render('Tenant/ValetStorage/Orders/Create', [
            'customers' => $customers,
            'sites' => Site::where('tenant_id', $tenantId)->get(['id', 'name']),
            'drivers' => ValetDriver::forTenant($tenantId)->with('user')->active()->get(),
            'types' => $this->getOrderTypes(),
            'timeSlots' => $this->getTimeSlots(),
            'categories' => $this->getItemCategories(),
            'sizes' => $this->getItemSizes(),
        ]);
    }

    public function storeOrder(Request $request)
    {
        $tenantId = $this->tenantId();

        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id', new \App\Rules\SameTenantResource(\App\Models\Customer::class, $tenantId)],
            'site_id' => ['required', 'exists:sites,id', new \App\Rules\SameTenantResource(\App\Models\Site::class, $tenantId)],
            'type' => 'required|in:pickup,delivery,pickup_delivery',
            'requested_date' => 'required|date|after_or_equal:today',
            'time_slot' => 'required|string',
            'assigned_driver_id' => ['nullable', 'exists:users,id', new \App\Rules\SameTenantUser($tenantId)],
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'floor' => 'nullable|string|max:20',
            'has_elevator' => 'boolean',
            'access_code' => 'nullable|string|max:50',
            'access_instructions' => 'nullable|string',
            'contact_name' => 'required|string|max:255',
            'contact_phone' => 'required|string|max:50',
            'contact_email' => 'nullable|email',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.valet_item_id' => 'nullable|exists:valet_items,id',
            'items.*.item_description' => 'nullable|string',
            'items.*.category' => 'nullable|string',
            'items.*.size' => 'nullable|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.is_new_item' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            // Calculate fees
            $fees = $this->calculateOrderFees($tenantId, $validated);

            $order = ValetOrder::create([
                'tenant_id' => $tenantId,
                'site_id' => $validated['site_id'],
                'customer_id' => $validated['customer_id'],
                'type' => $validated['type'],
                'status' => 'pending',
                'requested_date' => $validated['requested_date'],
                'time_slot' => $validated['time_slot'],
                'assigned_driver_id' => $validated['assigned_driver_id'] ?? null,
                'address_line1' => $validated['address_line1'],
                'address_line2' => $validated['address_line2'] ?? null,
                'city' => $validated['city'],
                'postal_code' => $validated['postal_code'],
                'country' => 'France',
                'floor' => $validated['floor'] ?? null,
                'has_elevator' => $validated['has_elevator'] ?? false,
                'access_code' => $validated['access_code'] ?? null,
                'access_instructions' => $validated['access_instructions'] ?? null,
                'contact_name' => $validated['contact_name'],
                'contact_phone' => $validated['contact_phone'],
                'contact_email' => $validated['contact_email'] ?? null,
                'base_fee' => $fees['base_fee'],
                'distance_fee' => $fees['distance_fee'],
                'floor_fee' => $fees['floor_fee'],
                'item_fee' => $fees['item_fee'],
                'total_fee' => $fees['total_fee'],
                'notes' => $validated['notes'] ?? null,
            ]);

            // Create order items
            foreach ($validated['items'] as $itemData) {
                ValetOrderItem::create([
                    'valet_order_id' => $order->id,
                    'valet_item_id' => $itemData['valet_item_id'] ?? null,
                    'item_description' => $itemData['item_description'] ?? null,
                    'category' => $itemData['category'] ?? null,
                    'size' => $itemData['size'] ?? null,
                    'quantity' => $itemData['quantity'],
                    'is_new_item' => $itemData['is_new_item'] ?? false,
                ]);

                // Update item status if delivery
                if ($itemData['valet_item_id'] && in_array($validated['type'], ['delivery', 'pickup_delivery'])) {
                    ValetItem::where('id', $itemData['valet_item_id'])
                        ->update(['status' => 'pending_delivery']);
                }
            }

            DB::commit();

            return redirect()->route('tenant.valet.orders.show', $order)
                ->with('success', 'Commande créée avec succès. N°' . $order->order_number);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Erreur lors de la création: ' . $e->getMessage()]);
        }
    }

    public function showOrder(ValetOrder $valetOrder)
    {
        $valetOrder->load(['customer', 'driver', 'site', 'orderItems.item']);

        return Inertia::render('Tenant/ValetStorage/Orders/Show', [
            'order' => $valetOrder,
            'statuses' => $this->getOrderStatuses(),
            'availableDrivers' => ValetDriver::forTenant($this->tenantId())
                ->with('user')
                ->active()
                ->get(),
        ]);
    }

    public function updateOrderStatus(Request $request, ValetOrder $valetOrder)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,scheduled,in_progress,completed,cancelled',
            'driver_notes' => 'nullable|string',
        ]);

        $valetOrder->update($validated);

        // Update item statuses based on order status
        if ($validated['status'] === 'in_progress') {
            $valetOrder->update(['started_at' => now()]);
            $this->updateItemStatuses($valetOrder, 'in_transit');
        } elseif ($validated['status'] === 'completed') {
            $valetOrder->update(['completed_at' => now()]);
            $this->updateItemStatuses($valetOrder, 'completed');
        }

        return back()->with('success', 'Statut mis à jour.');
    }

    public function assignDriver(Request $request, ValetOrder $valetOrder)
    {
        $tenantId = $this->tenantId();

        $validated = $request->validate([
            'driver_id' => ['required', 'exists:users,id', new \App\Rules\SameTenantUser($tenantId)],
        ]);

        $valetOrder->update([
            'assigned_driver_id' => $validated['driver_id'],
            'status' => $valetOrder->status === 'pending' ? 'confirmed' : $valetOrder->status,
        ]);

        return back()->with('success', 'Chauffeur assigné.');
    }

    public function cancelOrder(ValetOrder $valetOrder)
    {
        if (!$valetOrder->canBeCancelled()) {
            return back()->withErrors(['error' => 'Cette commande ne peut plus être annulée.']);
        }

        $valetOrder->update(['status' => 'cancelled']);

        // Reset item statuses
        foreach ($valetOrder->orderItems as $orderItem) {
            if ($orderItem->valet_item_id) {
                ValetItem::where('id', $orderItem->valet_item_id)
                    ->update(['status' => 'stored']);
            }
        }

        return back()->with('success', 'Commande annulée.');
    }

    // ============ DRIVERS ============

    public function drivers()
    {
        $tenantId = $this->tenantId();

        $drivers = ValetDriver::forTenant($tenantId)
            ->with(['user', 'routes' => fn($q) => $q->whereDate('date', today())])
            ->withCount(['orders as todays_orders_count' => fn($q) => $q->whereDate('requested_date', today())])
            ->get();

        return Inertia::render('Tenant/ValetStorage/Drivers/Index', [
            'drivers' => $drivers,
        ]);
    }

    public function createDriver()
    {
        $tenantId = $this->tenantId();

        // Get users who aren't already drivers
        $existingDriverUserIds = ValetDriver::forTenant($tenantId)->pluck('user_id');
        $availableUsers = \App\Models\User::where('tenant_id', $tenantId)
            ->whereNotIn('id', $existingDriverUserIds)
            ->get(['id', 'name', 'email']);

        return Inertia::render('Tenant/ValetStorage/Drivers/Create', [
            'users' => $availableUsers,
            'vehicleTypes' => $this->getVehicleTypes(),
        ]);
    }

    public function storeDriver(Request $request)
    {
        $tenantId = $this->tenantId();

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id', new \App\Rules\SameTenantUser($tenantId)],
            'phone' => 'required|string|max:50',
            'license_number' => 'nullable|string|max:100',
            'vehicle_type' => 'required|in:bike,van,truck',
            'vehicle_plate' => 'nullable|string|max:20',
            'max_capacity_kg' => 'nullable|numeric|min:0',
            'max_capacity_m3' => 'nullable|numeric|min:0',
        ]);

        $validated['tenant_id'] = $this->tenantId();
        $validated['status'] = 'available';
        $validated['is_active'] = true;

        ValetDriver::create($validated);

        return redirect()->route('tenant.valet.drivers')
            ->with('success', 'Chauffeur ajouté avec succès.');
    }

    public function updateDriverStatus(Request $request, ValetDriver $valetDriver)
    {
        $validated = $request->validate([
            'status' => 'required|in:available,busy,offline',
        ]);

        $valetDriver->update($validated);

        return back()->with('success', 'Statut du chauffeur mis à jour.');
    }

    public function updateDriverLocation(Request $request, ValetDriver $valetDriver)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $valetDriver->updateLocation($validated['latitude'], $validated['longitude']);

        return response()->json(['success' => true]);
    }

    // ============ ROUTES/PLANNING ============

    public function planning(Request $request)
    {
        $tenantId = $this->tenantId();
        $date = $request->get('date', today()->toDateString());

        $orders = ValetOrder::forTenant($tenantId)
            ->with(['customer', 'driver', 'site', 'orderItems'])
            ->forDate($date)
            ->orderBy('scheduled_time_start')
            ->get();

        $drivers = ValetDriver::forTenant($tenantId)
            ->with(['user', 'routes' => fn($q) => $q->forDate($date)])
            ->active()
            ->get();

        $routes = ValetRoute::forTenant($tenantId)
            ->with(['driver.user'])
            ->forDate($date)
            ->get();

        return Inertia::render('Tenant/ValetStorage/Planning/Index', [
            'orders' => $orders,
            'drivers' => $drivers,
            'routes' => $routes,
            'date' => $date,
        ]);
    }

    public function createRoute(Request $request)
    {
        $validated = $request->validate([
            'driver_id' => 'required|exists:valet_drivers,id',
            'date' => 'required|date',
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'exists:valet_orders,id',
        ]);

        $route = ValetRoute::create([
            'tenant_id' => $this->tenantId(),
            'valet_driver_id' => $validated['driver_id'],
            'date' => $validated['date'],
            'status' => 'planned',
            'total_stops' => count($validated['order_ids']),
            'optimized_order' => $validated['order_ids'],
        ]);

        // Update orders with driver assignment
        $driver = ValetDriver::find($validated['driver_id']);
        ValetOrder::whereIn('id', $validated['order_ids'])
            ->update([
                'assigned_driver_id' => $driver->user_id,
                'status' => 'scheduled',
            ]);

        return back()->with('success', 'Tournée créée avec succès.');
    }

    // ============ ZONES & PRICING ============

    public function zones()
    {
        $tenantId = $this->tenantId();

        $zones = ValetZone::where('tenant_id', $tenantId)
            ->with('site')
            ->get();

        $sites = Site::where('tenant_id', $tenantId)->get(['id', 'name']);

        return Inertia::render('Tenant/ValetStorage/Settings/Zones', [
            'zones' => $zones,
            'sites' => $sites,
        ]);
    }

    public function storeZone(Request $request)
    {
        $tenantId = $this->tenantId();

        $validated = $request->validate([
            'site_id' => ['required', 'exists:sites,id', new \App\Rules\SameTenantResource(\App\Models\Site::class, $tenantId)],
            'name' => 'required|string|max:255',
            'postal_codes' => 'required|array|min:1',
            'postal_codes.*' => 'string|max:10',
            'pickup_fee' => 'required|numeric|min:0',
            'delivery_fee' => 'required|numeric|min:0',
            'min_lead_time_hours' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['tenant_id'] = $this->tenantId();

        ValetZone::create($validated);

        return back()->with('success', 'Zone créée avec succès.');
    }

    public function updateZone(Request $request, ValetZone $valetZone)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'postal_codes' => 'required|array|min:1',
            'pickup_fee' => 'required|numeric|min:0',
            'delivery_fee' => 'required|numeric|min:0',
            'min_lead_time_hours' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $valetZone->update($validated);

        return back()->with('success', 'Zone mise à jour.');
    }

    public function destroyZone(ValetZone $valetZone)
    {
        $valetZone->delete();
        return back()->with('success', 'Zone supprimée.');
    }

    public function pricing()
    {
        $tenantId = $this->tenantId();

        $pricing = ValetPricing::where('tenant_id', $tenantId)
            ->with('site')
            ->get();

        $sites = Site::where('tenant_id', $tenantId)->get(['id', 'name']);

        return Inertia::render('Tenant/ValetStorage/Settings/Pricing', [
            'pricing' => $pricing,
            'sites' => $sites,
            'pricingTypes' => $this->getPricingTypes(),
            'units' => $this->getPricingUnits(),
        ]);
    }

    public function storePricing(Request $request)
    {
        $tenantId = $this->tenantId();

        $validated = $request->validate([
            'site_id' => ['nullable', 'exists:sites,id', new \App\Rules\SameTenantResource(\App\Models\Site::class, $tenantId)],
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'price' => 'required|numeric|min:0',
            'unit' => 'required|string',
            'min_quantity' => 'required|integer|min:0',
            'max_quantity' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['tenant_id'] = $this->tenantId();

        ValetPricing::create($validated);

        return back()->with('success', 'Tarif créé avec succès.');
    }

    public function updatePricing(Request $request, ValetPricing $valetPricing)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'unit' => 'required|string',
            'min_quantity' => 'required|integer|min:0',
            'max_quantity' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $valetPricing->update($validated);

        return back()->with('success', 'Tarif mis à jour.');
    }

    public function destroyPricing(ValetPricing $valetPricing)
    {
        $valetPricing->delete();
        return back()->with('success', 'Tarif supprimé.');
    }

    // ============ SETTINGS ============

    public function settings()
    {
        $tenantId = $this->tenantId();

        $settings = ValetSettings::getForTenant($tenantId);
        $sites = Site::where('tenant_id', $tenantId)->get(['id', 'name']);

        return Inertia::render('Tenant/ValetStorage/Settings/General', [
            'settings' => $settings,
            'sites' => $sites,
        ]);
    }

    public function updateSettings(Request $request)
    {
        $tenantId = $this->tenantId();

        $validated = $request->validate([
            'site_id' => ['nullable', 'exists:sites,id', new \App\Rules\SameTenantResource(\App\Models\Site::class, $tenantId)],
            'valet_enabled' => 'boolean',
            'allow_same_day' => 'boolean',
            'min_lead_time_hours' => 'required|integer|min:0',
            'max_items_per_order' => 'required|integer|min:1',
            'earliest_time' => 'required|date_format:H:i',
            'latest_time' => 'required|date_format:H:i',
            'available_days' => 'required|array',
            'available_days.*' => 'integer|between:1,7',
            'time_slots' => 'nullable|array',
            'free_delivery_threshold' => 'nullable|numeric|min:0',
            'terms_conditions' => 'nullable|string',
            'pickup_instructions' => 'nullable|string',
            'delivery_instructions' => 'nullable|string',
        ]);

        $tenantId = $this->tenantId();

        ValetSettings::updateOrCreate(
            ['tenant_id' => $tenantId, 'site_id' => $validated['site_id'] ?? null],
            $validated
        );

        return back()->with('success', 'Paramètres mis à jour.');
    }

    // ============ CUSTOMER INVENTORY ============

    public function customerInventory(Customer $customer)
    {
        $items = ValetItem::where('customer_id', $customer->id)
            ->with('site')
            ->get();

        $stats = [
            'total_items' => $items->count(),
            'stored_items' => $items->where('status', 'stored')->count(),
            'total_volume' => $items->sum('volume_m3'),
            'monthly_cost' => $items->where('status', 'stored')->sum('monthly_fee'),
        ];

        return Inertia::render('Tenant/ValetStorage/CustomerInventory', [
            'customer' => $customer,
            'items' => $items,
            'stats' => $stats,
        ]);
    }

    // ============ HELPERS ============

    protected function calculateOrderFees(int $tenantId, array $data): array
    {
        $fees = [
            'base_fee' => 0,
            'distance_fee' => 0,
            'floor_fee' => 0,
            'item_fee' => 0,
            'total_fee' => 0,
        ];

        // Get pricing rules
        $pricing = ValetPricing::forTenant($tenantId)
            ->active()
            ->where(function ($q) use ($data) {
                $q->whereNull('site_id')
                    ->orWhere('site_id', $data['site_id']);
            })
            ->get();

        // Base fee for pickup/delivery
        $basePricing = $pricing->where('type', $data['type'])->first();
        $fees['base_fee'] = $basePricing ? $basePricing->price : 29.90;

        // Floor fee
        if (!empty($data['floor']) && !($data['has_elevator'] ?? false)) {
            $floorNum = (int) $data['floor'];
            $floorPricing = $pricing->where('type', 'floor_fee')->first();
            $fees['floor_fee'] = $floorPricing ? $floorPricing->price * $floorNum : 5 * $floorNum;
        }

        // Item fees
        $itemCount = collect($data['items'])->sum('quantity');
        $itemPricing = $pricing->where('type', 'per_item')->first();
        if ($itemPricing && $itemCount > 5) {
            $fees['item_fee'] = $itemPricing->price * ($itemCount - 5);
        }

        // Zone-based fees
        $zone = ValetZone::findForPostalCode($tenantId, $data['site_id'], $data['postal_code']);
        if ($zone) {
            if (in_array($data['type'], ['pickup', 'pickup_delivery'])) {
                $fees['distance_fee'] += $zone->pickup_fee;
            }
            if (in_array($data['type'], ['delivery', 'pickup_delivery'])) {
                $fees['distance_fee'] += $zone->delivery_fee;
            }
        }

        $fees['total_fee'] = array_sum($fees);

        return $fees;
    }

    protected function updateItemStatuses(ValetOrder $order, string $stage): void
    {
        foreach ($order->orderItems as $orderItem) {
            if (!$orderItem->valet_item_id) continue;

            $newStatus = match([$order->type, $stage]) {
                ['pickup', 'in_transit'] => 'in_transit_to_storage',
                ['pickup', 'completed'] => 'stored',
                ['delivery', 'in_transit'] => 'in_transit_to_customer',
                ['delivery', 'completed'] => 'delivered',
                ['pickup_delivery', 'in_transit'] => 'in_transit_to_storage',
                ['pickup_delivery', 'completed'] => 'stored',
                default => null,
            };

            if ($newStatus) {
                ValetItem::where('id', $orderItem->valet_item_id)->update(['status' => $newStatus]);

                if ($newStatus === 'stored') {
                    ValetItem::where('id', $orderItem->valet_item_id)
                        ->whereNull('storage_start_date')
                        ->update(['storage_start_date' => now()]);
                } elseif ($newStatus === 'delivered') {
                    ValetItem::where('id', $orderItem->valet_item_id)
                        ->update(['storage_end_date' => now()]);
                }
            }
        }
    }

    protected function getItemCategories(): array
    {
        return [
            'furniture' => 'Mobilier',
            'electronics' => 'Électronique',
            'boxes' => 'Cartons',
            'clothing' => 'Vêtements',
            'sports' => 'Sports & Loisirs',
            'books' => 'Livres & Documents',
            'decorations' => 'Décorations',
            'appliances' => 'Électroménager',
            'tools' => 'Outils',
            'other' => 'Autre',
        ];
    }

    protected function getItemSizes(): array
    {
        return [
            'small' => 'Petit (< 0.1 m³)',
            'medium' => 'Moyen (0.1 - 0.5 m³)',
            'large' => 'Grand (0.5 - 1 m³)',
            'extra_large' => 'Très grand (> 1 m³)',
        ];
    }

    protected function getItemConditions(): array
    {
        return [
            'excellent' => 'Excellent',
            'good' => 'Bon',
            'fair' => 'Correct',
            'damaged' => 'Endommagé',
        ];
    }

    protected function getItemStatuses(): array
    {
        return [
            'pending_pickup' => 'En attente de collecte',
            'in_transit_to_storage' => 'En transit vers stockage',
            'stored' => 'Stocké',
            'pending_delivery' => 'En attente de livraison',
            'in_transit_to_customer' => 'En transit vers client',
            'delivered' => 'Livré',
            'disposed' => 'Éliminé',
        ];
    }

    protected function getOrderStatuses(): array
    {
        return [
            'pending' => 'En attente',
            'confirmed' => 'Confirmé',
            'scheduled' => 'Planifié',
            'in_progress' => 'En cours',
            'completed' => 'Terminé',
            'cancelled' => 'Annulé',
        ];
    }

    protected function getOrderTypes(): array
    {
        return [
            'pickup' => 'Collecte',
            'delivery' => 'Livraison',
            'pickup_delivery' => 'Collecte & Livraison',
        ];
    }

    protected function getTimeSlots(): array
    {
        return [
            'morning' => 'Matin (8h-12h)',
            'afternoon' => 'Après-midi (12h-18h)',
            'evening' => 'Soir (18h-20h)',
        ];
    }

    protected function getVehicleTypes(): array
    {
        return [
            'bike' => 'Vélo cargo',
            'van' => 'Camionnette',
            'truck' => 'Camion',
        ];
    }

    protected function getPricingTypes(): array
    {
        return [
            'pickup' => 'Collecte (forfait)',
            'delivery' => 'Livraison (forfait)',
            'pickup_delivery' => 'Collecte & Livraison',
            'storage_small' => 'Stockage petit article',
            'storage_medium' => 'Stockage article moyen',
            'storage_large' => 'Stockage grand article',
            'storage_xlarge' => 'Stockage très grand article',
            'per_item' => 'Par article supplémentaire',
            'floor_fee' => 'Frais par étage',
            'distance_per_km' => 'Distance par km',
        ];
    }

    protected function getPricingUnits(): array
    {
        return [
            'fixed' => 'Forfait',
            'per_km' => 'Par km',
            'per_floor' => 'Par étage',
            'per_item' => 'Par article',
            'per_m3' => 'Par m³',
            'monthly' => 'Par mois',
        ];
    }
}
