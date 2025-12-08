<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $query = Tenant::query()
            ->withCount(['users', 'sites', 'customers', 'contracts']);

        // Filter by plan
        if ($request->filled('plan')) {
            $query->where('plan', $request->plan);
        }

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            } elseif ($request->status === 'trial') {
                $query->whereNotNull('trial_ends_at')->where('trial_ends_at', '>=', now());
            }
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $subscriptions = $query->latest()->paginate(15)->withQueryString();

        // Transform for frontend
        $subscriptions->getCollection()->transform(function ($tenant) {
            $tenant->status = $this->getTenantStatus($tenant);
            $tenant->subscription_plan = $tenant->plan;
            return $tenant;
        });

        // Stats by plan
        $stats = [
            'free' => Tenant::where('plan', 'free')->where('is_active', true)->count(),
            'starter' => Tenant::where('plan', 'starter')->where('is_active', true)->count(),
            'professional' => Tenant::where('plan', 'professional')->where('is_active', true)->count(),
            'enterprise' => Tenant::where('plan', 'enterprise')->where('is_active', true)->count(),
            'trial' => Tenant::whereNotNull('trial_ends_at')->where('trial_ends_at', '>=', now())->count(),
            'total_mrr' => $this->calculateMRR(),
        ];

        // Plan pricing
        $plans = [
            'free' => ['name' => 'Free', 'price' => 0, 'features' => ['1 site', '50 boxes', '3 users']],
            'starter' => ['name' => 'Starter', 'price' => 49, 'features' => ['3 sites', '200 boxes', '10 users']],
            'professional' => ['name' => 'Professional', 'price' => 99, 'features' => ['10 sites', '1000 boxes', '25 users', 'Analytics']],
            'enterprise' => ['name' => 'Enterprise', 'price' => 249, 'features' => ['Illimité', 'API', 'Support prioritaire']],
        ];

        return Inertia::render('SuperAdmin/Subscriptions/Index', [
            'subscriptions' => $subscriptions,
            'stats' => $stats,
            'plans' => $plans,
            'filters' => $request->only(['search', 'plan', 'status']),
        ]);
    }

    public function changePlan(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'plan' => 'required|in:free,starter,professional,enterprise',
        ]);

        $oldPlan = $tenant->plan;
        $tenant->update([
            'plan' => $validated['plan'],
            'is_active' => true,
        ]);

        return back()->with('success', "Plan mis à jour vers {$validated['plan']}");
    }

    public function extendTrial(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'days' => 'required|integer|min:1|max:90',
        ]);

        $newEndDate = $tenant->trial_ends_at
            ? Carbon::parse($tenant->trial_ends_at)->addDays($validated['days'])
            : now()->addDays($validated['days']);

        $tenant->update([
            'is_active' => true,
            'trial_ends_at' => $newEndDate,
        ]);

        return back()->with('success', "Période d'essai prolongée jusqu'au {$newEndDate->format('d/m/Y')}");
    }

    private function calculateMRR(): float
    {
        $pricing = [
            'free' => 0,
            'starter' => 49,
            'professional' => 99,
            'enterprise' => 249,
        ];

        $mrr = 0;
        foreach ($pricing as $plan => $price) {
            $count = Tenant::where('plan', $plan)
                ->where('is_active', true)
                ->count();
            $mrr += $count * $price;
        }

        return $mrr;
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
