<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use App\Services\ExcelExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $tenantId = $request->user()->tenant_id;

        $customers = Customer::where('tenant_id', $tenantId)
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($request->type, function ($query, $type) {
                $query->where('type', $type);
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->withCount('contracts')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        // Optimized stats query - single DB call instead of 5
        $statsRaw = Customer::where('tenant_id', $tenantId)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN type = 'individual' THEN 1 ELSE 0 END) as individual,
                SUM(CASE WHEN type = 'company' THEN 1 ELSE 0 END) as company,
                SUM(total_revenue) as total_revenue
            ")
            ->first();

        $stats = [
            'total' => $statsRaw->total ?? 0,
            'active' => $statsRaw->active ?? 0,
            'individual' => $statsRaw->individual ?? 0,
            'company' => $statsRaw->company ?? 0,
            'total_revenue' => $statsRaw->total_revenue ?? 0,
        ];

        return Inertia::render('Tenant/Customers/Index', [
            'customers' => $customers,
            'stats' => $stats,
            'filters' => $request->only(['search', 'type', 'status']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Tenant/Customers/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = $request->user()->tenant_id;

        // Set user_id if authenticated user
        if ($request->user()) {
            $data['user_id'] = $request->user()->id;
        }

        // Initialize statistics
        $data['outstanding_balance'] = 0;
        $data['total_contracts'] = 0;
        $data['total_revenue'] = 0;

        $customer = Customer::create($data);

        return redirect()
            ->route('tenant.customers.index')
            ->with('success', 'Customer created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer): Response
    {
        $customer->load(['contracts' => function ($query) {
            $query->with('box:id,number,name')->latest()->limit(5);
        }, 'invoices' => function ($query) {
            $query->latest()->limit(5);
        }]);

        // Optimized stats - single query with counts
        $contractStats = $customer->contracts()
            ->selectRaw("
                COUNT(*) as total_contracts,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_contracts
            ")
            ->first();

        $stats = [
            'total_contracts' => $contractStats->total_contracts ?? 0,
            'active_contracts' => $contractStats->active_contracts ?? 0,
            'total_revenue' => $customer->total_revenue,
            'outstanding_balance' => $customer->outstanding_balance,
            'total_invoices' => $customer->invoices()->count(),
        ];

        return Inertia::render('Tenant/Customers/Show', [
            'customer' => $customer,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Customer $customer): Response
    {
        return Inertia::render('Tenant/Customers/Edit', [
            'customer' => $customer,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $data = $request->validated();

        $customer->update($data);

        return redirect()
            ->route('tenant.customers.index')
            ->with('success', 'Customer updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        return redirect()
            ->route('tenant.customers.index')
            ->with('success', 'Customer deleted successfully.');
    }

    /**
     * Export customers to Excel (CSV).
     */
    public function export(Request $request, ExcelExportService $exportService)
    {
        $tenantId = $request->user()->tenant_id;

        $result = $exportService->exportCustomers($tenantId);
        $csv = $exportService->generateCSV($result['data']);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $result['filename'] . '"',
        ]);
    }
}
