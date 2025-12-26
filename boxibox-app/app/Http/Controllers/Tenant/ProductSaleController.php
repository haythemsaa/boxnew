<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductSaleRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductSale;
use App\Models\ProductSaleItem;
use App\Models\Site;
use App\Services\ProductSaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProductSaleController extends Controller
{
    public function __construct(
        protected ProductSaleService $saleService
    ) {}

    /**
     * Display a listing of sales.
     */
    public function index(Request $request): Response
    {
        $tenantId = Auth::user()->tenant_id;

        $query = ProductSale::where('tenant_id', $tenantId)
            ->with(['customer', 'seller', 'site'])
            ->withCount('items');

        // Filtres
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('sale_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('company_name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('sold_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('sold_at', '<=', $request->date_to);
        }

        $sales = $query->latest('sold_at')
            ->paginate(20)
            ->withQueryString();

        // Stats
        $stats = [
            'total_sales' => ProductSale::where('tenant_id', $tenantId)->count(),
            'today_sales' => ProductSale::where('tenant_id', $tenantId)->today()->count(),
            'today_revenue' => ProductSale::where('tenant_id', $tenantId)
                ->today()
                ->completed()
                ->sum('total'),
            'month_revenue' => ProductSale::where('tenant_id', $tenantId)
                ->thisMonth()
                ->completed()
                ->sum('total'),
            'pending_payment' => ProductSale::where('tenant_id', $tenantId)
                ->where('payment_status', 'pending')
                ->sum('total'),
        ];

        return Inertia::render('Tenant/Sales/Index', [
            'sales' => $sales,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status', 'payment_status', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Show the form for creating a new sale.
     */
    public function create(Request $request): Response
    {
        $tenantId = Auth::user()->tenant_id;

        $customers = Customer::where('tenant_id', $tenantId)
            ->active()
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'company_name', 'type', 'email']);

        $products = Product::where('tenant_id', $tenantId)
            ->active()
            ->inStock()
            ->ordered()
            ->get();

        $sites = Site::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get(['id', 'name']);

        // Pre-select customer if provided
        $selectedCustomer = null;
        if ($request->filled('customer_id')) {
            $selectedCustomer = Customer::find($request->customer_id);
        }

        return Inertia::render('Tenant/Sales/Create', [
            'customers' => $customers,
            'products' => $products,
            'sites' => $sites,
            'selectedCustomer' => $selectedCustomer,
            'categories' => Product::CATEGORIES,
        ]);
    }

    /**
     * Store a newly created sale.
     */
    public function store(StoreProductSaleRequest $request)
    {
        $data = $request->validated();
        $tenantId = Auth::user()->tenant_id;

        try {
            $sale = DB::transaction(function () use ($data, $tenantId) {
                // Créer la vente
                $sale = ProductSale::create([
                    'tenant_id' => $tenantId,
                    'customer_id' => $data['customer_id'],
                    'contract_id' => $data['contract_id'] ?? null,
                    'site_id' => $data['site_id'] ?? null,
                    'payment_method' => $data['payment_method'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'sold_by' => Auth::id(),
                    'discount_amount' => $data['discount_amount'] ?? 0,
                ]);

                // Ajouter les items
                foreach ($data['items'] as $itemData) {
                    $product = Product::findOrFail($itemData['product_id']);

                    // Vérifier le stock
                    if (!$product->hasStock($itemData['quantity'])) {
                        throw new \Exception("Stock insuffisant pour le produit: {$product->name}");
                    }

                    ProductSaleItem::create([
                        'product_sale_id' => $sale->id,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'product_sku' => $product->sku,
                        'product_category' => $product->category,
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $itemData['unit_price'] ?? $product->price,
                        'tax_rate' => $product->tax_rate ?? 20,
                        'discount_amount' => $itemData['discount_amount'] ?? 0,
                        'notes' => $itemData['notes'] ?? null,
                    ]);
                }

                // Recalculer les totaux
                $sale->calculateTotals();

                // Marquer comme payé si demandé
                if (!empty($data['mark_as_paid']) && !empty($data['payment_method'])) {
                    $sale->markAsPaid(
                        $data['payment_method'],
                        $data['payment_reference'] ?? null
                    );
                    $sale->complete();
                }

                return $sale;
            });

            return redirect()
                ->route('tenant.sales.show', $sale)
                ->with('success', 'Vente créée avec succès.');

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Display the specified sale.
     */
    public function show(ProductSale $sale): Response
    {
        $this->authorizeSale($sale);

        $sale->load([
            'customer',
            'contract',
            'site',
            'seller',
            'invoice',
            'items.product',
        ]);

        return Inertia::render('Tenant/Sales/Show', [
            'sale' => $sale,
        ]);
    }

    /**
     * Mark sale as paid.
     */
    public function markAsPaid(Request $request, ProductSale $sale)
    {
        $this->authorizeSale($sale);

        $request->validate([
            'payment_method' => ['required', 'in:cash,card,bank_transfer,stripe,other'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
        ]);

        if ($sale->isPaid()) {
            return back()->with('error', 'Cette vente est déjà payée.');
        }

        $sale->markAsPaid(
            $request->payment_method,
            $request->payment_reference
        );

        return back()->with('success', 'Paiement enregistré avec succès.');
    }

    /**
     * Complete the sale.
     */
    public function complete(ProductSale $sale)
    {
        $this->authorizeSale($sale);

        try {
            $sale->complete();
            return back()->with('success', 'Vente complétée avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel the sale.
     */
    public function cancel(ProductSale $sale)
    {
        $this->authorizeSale($sale);

        try {
            $sale->cancel();
            return back()->with('success', 'Vente annulée avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Refund the sale.
     */
    public function refund(Request $request, ProductSale $sale)
    {
        $this->authorizeSale($sale);

        $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $sale->refund($request->amount, $request->reason);
            return back()->with('success', 'Remboursement effectué avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Authorize that the sale belongs to the current tenant.
     */
    private function authorizeSale(ProductSale $sale): void
    {
        if ($sale->tenant_id !== Auth::user()->tenant_id) {
            abort(403, 'Accès non autorisé.');
        }
    }
}
