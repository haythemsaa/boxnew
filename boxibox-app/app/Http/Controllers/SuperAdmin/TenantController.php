<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Site;
use App\Models\Box;
use App\Models\Contract;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class TenantController extends Controller
{
    public function index(Request $request)
    {
        $query = Tenant::query()
            ->withCount(['users', 'sites', 'customers', 'contracts']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by status (is_active)
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            } elseif ($request->status === 'trial') {
                $query->whereNotNull('trial_ends_at')->where('trial_ends_at', '>=', now());
            }
        }

        // Filter by plan
        if ($request->filled('plan')) {
            $query->where('plan', $request->plan);
        }

        $tenants = $query->latest()->paginate(15)->withQueryString();

        // Transform tenants for frontend
        $tenants->getCollection()->transform(function ($tenant) {
            $tenant->status = $this->getTenantStatus($tenant);
            $tenant->subscription_plan = $tenant->plan;
            return $tenant;
        });

        // Stats
        $stats = [
            'total' => Tenant::count(),
            'active' => Tenant::where('is_active', true)->count(),
            'trial' => Tenant::whereNotNull('trial_ends_at')->where('trial_ends_at', '>=', now())->count(),
            'suspended' => Tenant::where('is_active', false)->count(),
        ];

        return Inertia::render('SuperAdmin/Tenants/Index', [
            'tenants' => $tenants,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status', 'plan']),
        ]);
    }

    public function create()
    {
        return Inertia::render('SuperAdmin/Tenants/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:tenants,slug',
            'email' => 'required|email|unique:tenants,email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:255',
            'subscription_plan' => 'required|in:free,starter,professional,enterprise',
            'status' => 'required|in:active,trial,suspended',
            'trial_ends_at' => 'nullable|date',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|unique:users,email',
            'admin_password' => 'required|string|min:8',
        ]);

        // Create tenant
        $tenant = Tenant::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? Str::slug($validated['name']),
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'postal_code' => $validated['postal_code'] ?? null,
            'country' => $validated['country'] ?? 'FR',
            'plan' => $validated['subscription_plan'],
            'is_active' => $validated['status'] !== 'suspended',
            'trial_ends_at' => $validated['status'] === 'trial' ? ($validated['trial_ends_at'] ?? now()->addDays(14)) : null,
            'settings' => json_encode([
                'currency' => 'EUR',
                'timezone' => 'Europe/Paris',
                'language' => 'fr',
            ]),
        ]);

        // Create admin user for tenant
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['admin_name'],
            'email' => $validated['admin_email'],
            'password' => bcrypt($validated['admin_password']),
            'status' => 'active',
        ]);

        $user->assignRole('tenant_admin');

        return redirect()->route('superadmin.tenants.index')
            ->with('success', "Tenant '{$tenant->name}' créé avec succès.");
    }

    public function show(Tenant $tenant)
    {
        $tenant->load(['users.roles', 'sites.boxes']);

        // Get tenant statistics
        $stats = [
            'users' => $tenant->users()->count(),
            'sites' => $tenant->sites()->count(),
            'boxes' => Box::whereIn('site_id', $tenant->sites()->pluck('id'))->count(),
            'customers' => $tenant->customers()->count(),
            'active_contracts' => $tenant->contracts()->where('status', 'active')->count(),
            'total_revenue' => $tenant->invoices()->where('status', 'paid')->sum('total'),
            'pending_invoices' => $tenant->invoices()->whereIn('status', ['pending', 'overdue'])->sum('total'),
        ];

        // Recent activity
        $recentContracts = $tenant->contracts()
            ->with(['customer:id,first_name,last_name', 'box:id,number'])
            ->latest()
            ->take(5)
            ->get();

        $recentInvoices = $tenant->invoices()
            ->with(['customer:id,first_name,last_name'])
            ->latest()
            ->take(5)
            ->get();

        // Add computed status
        $tenant->status = $this->getTenantStatus($tenant);
        $tenant->subscription_plan = $tenant->plan;

        return Inertia::render('SuperAdmin/Tenants/Show', [
            'tenant' => $tenant,
            'stats' => $stats,
            'recentContracts' => $recentContracts,
            'recentInvoices' => $recentInvoices,
        ]);
    }

    public function edit(Tenant $tenant)
    {
        $tenant->status = $this->getTenantStatus($tenant);
        $tenant->subscription_plan = $tenant->plan;

        // Current usage for limits display
        $currentUsage = [
            'sites' => $tenant->sites()->count(),
            'boxes' => Box::whereIn('site_id', $tenant->sites()->pluck('id'))->count(),
            'users' => $tenant->users()->count(),
            'customers' => $tenant->customers()->count(),
        ];

        return Inertia::render('SuperAdmin/Tenants/Edit', [
            'tenant' => $tenant,
            'currentUsage' => $currentUsage,
        ]);
    }

    public function update(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('tenants', 'slug')->ignore($tenant->id)],
            'email' => ['required', 'email', Rule::unique('tenants', 'email')->ignore($tenant->id)],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:255',
            'subscription_plan' => 'required|in:free,starter,professional,enterprise',
            'status' => 'required|in:active,trial,suspended,cancelled',
            'trial_ends_at' => 'nullable|date',
            // Limites personnalisees
            'max_sites' => 'nullable|integer|min:1',
            'max_boxes' => 'nullable|integer|min:1',
            'max_users' => 'nullable|integer|min:1',
            'max_customers' => 'nullable|integer|min:1',
        ]);

        $tenant->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? $tenant->slug,
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'address' => $validated['address'],
            'city' => $validated['city'],
            'postal_code' => $validated['postal_code'],
            'country' => $validated['country'],
            'plan' => $validated['subscription_plan'],
            'is_active' => !in_array($validated['status'], ['suspended', 'cancelled']),
            'trial_ends_at' => $validated['status'] === 'trial' ? $validated['trial_ends_at'] : null,
            // Limites personnalisees
            'max_sites' => $validated['max_sites'],
            'max_boxes' => $validated['max_boxes'],
            'max_users' => $validated['max_users'],
            'max_customers' => $validated['max_customers'],
        ]);

        return redirect()->route('superadmin.tenants.show', $tenant)
            ->with('success', 'Tenant mis à jour avec succès.');
    }

    public function destroy(Tenant $tenant)
    {
        // Soft delete or hard delete based on business rules
        $tenant->update(['is_active' => false]);

        return redirect()->route('superadmin.tenants.index')
            ->with('success', "Tenant '{$tenant->name}' a été désactivé.");
    }

    public function suspend(Tenant $tenant)
    {
        $tenant->update(['is_active' => false]);

        return back()->with('success', "Tenant '{$tenant->name}' a été suspendu.");
    }

    public function activate(Tenant $tenant)
    {
        $tenant->update(['is_active' => true]);

        return back()->with('success', "Tenant '{$tenant->name}' a été activé.");
    }

    public function impersonate(Tenant $tenant)
    {
        // Get the first admin user of this tenant
        $adminUser = $tenant->users()->whereHas('roles', function ($q) {
            $q->where('name', 'tenant_admin');
        })->first();

        if (!$adminUser) {
            return back()->with('error', 'Aucun administrateur trouvé pour ce tenant.');
        }

        // Store original user ID in session
        session(['impersonating_from' => auth()->id()]);

        // Login as tenant admin
        auth()->login($adminUser);

        return redirect()->route('tenant.dashboard')
            ->with('info', "Vous êtes maintenant connecté en tant que {$adminUser->name}");
    }

    private function getTenantStatus(Tenant $tenant): string
    {
        if (!$tenant->is_active) {
            return 'suspended';
        }
        if ($tenant->trial_ends_at && $tenant->trial_ends_at >= now()) {
            return 'trial';
        }
        return 'active';
    }
}
