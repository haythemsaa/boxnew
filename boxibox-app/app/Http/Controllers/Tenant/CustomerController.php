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
        $this->authorize('view_customers');

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

        $stats = [
            'total' => Customer::where('tenant_id', $tenantId)->count(),
            'active' => Customer::where('tenant_id', $tenantId)->where('status', 'active')->count(),
            'individual' => Customer::where('tenant_id', $tenantId)->where('type', 'individual')->count(),
            'company' => Customer::where('tenant_id', $tenantId)->where('type', 'company')->count(),
            'total_revenue' => Customer::where('tenant_id', $tenantId)->sum('total_revenue'),
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
        $this->authorize('create_customers');

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
        $this->authorize('view_customers');

        $customer->load(['contracts' => function ($query) {
            $query->with('box')->latest()->limit(5);
        }, 'invoices' => function ($query) {
            $query->latest()->limit(5);
        }]);

        $stats = [
            'total_contracts' => $customer->contracts()->count(),
            'active_contracts' => $customer->contracts()->where('status', 'active')->count(),
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
        $this->authorize('edit_customers');

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
        $this->authorize('delete_customers');

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
        $this->authorize('view_customers');

        $tenantId = $request->user()->tenant_id;

        $result = $exportService->exportCustomers($tenantId);
        $csv = $exportService->generateCSV($result['data']);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $result['filename'] . '"',
        ]);
    }
}
