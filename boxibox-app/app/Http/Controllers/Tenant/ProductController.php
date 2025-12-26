<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    /**
     * Display a listing of products.
     */
    public function index(Request $request): Response
    {
        $tenantId = Auth::user()->tenant_id;

        $query = Product::where('tenant_id', $tenantId)
            ->withCount(['saleItems', 'contractAddons']);

        // Filtres
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            } elseif ($request->status === 'low_stock') {
                $query->where('track_inventory', true)
                    ->where('stock_quantity', '<=', 5)
                    ->where('stock_quantity', '>', 0);
            } elseif ($request->status === 'out_of_stock') {
                $query->where('track_inventory', true)
                    ->where('stock_quantity', '<=', 0);
            }
        }

        $products = $query->ordered()
            ->paginate(20)
            ->withQueryString();

        // Stats
        $stats = [
            'total' => Product::where('tenant_id', $tenantId)->count(),
            'active' => Product::where('tenant_id', $tenantId)->active()->count(),
            'low_stock' => Product::where('tenant_id', $tenantId)
                ->where('track_inventory', true)
                ->where('stock_quantity', '<=', 5)
                ->where('stock_quantity', '>', 0)
                ->count(),
            'out_of_stock' => Product::where('tenant_id', $tenantId)
                ->where('track_inventory', true)
                ->where('stock_quantity', '<=', 0)
                ->count(),
            'recurring' => Product::where('tenant_id', $tenantId)->recurring()->count(),
        ];

        return Inertia::render('Tenant/Products/Index', [
            'products' => $products,
            'stats' => $stats,
            'filters' => $request->only(['search', 'category', 'type', 'status']),
            'categories' => Product::CATEGORIES,
            'types' => Product::TYPES,
        ]);
    }

    /**
     * Show the form for creating a new product.
     */
    public function create(): Response
    {
        return Inertia::render('Tenant/Products/Create', [
            'categories' => Product::CATEGORIES,
            'types' => Product::TYPES,
            'billingPeriods' => Product::BILLING_PERIODS,
            'units' => Product::UNITS,
        ]);
    }

    /**
     * Store a newly created product.
     */
    public function store(StoreProductRequest $request)
    {
        $data = $request->validated();
        $data['tenant_id'] = Auth::user()->tenant_id;

        // Générer SKU si non fourni
        if (empty($data['sku'])) {
            $data['sku'] = $this->generateSku($data['category'], $data['tenant_id']);
        }

        $product = Product::create($data);

        return redirect()
            ->route('tenant.products.show', $product)
            ->with('success', 'Produit créé avec succès.');
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product): Response
    {
        $this->authorizeProduct($product);

        $product->loadCount(['saleItems', 'contractAddons']);

        // Historique des ventes récentes
        $recentSales = $product->saleItems()
            ->with(['sale.customer'])
            ->latest()
            ->take(10)
            ->get();

        // Addons actifs
        $activeAddons = $product->contractAddons()
            ->with(['contract.customer'])
            ->active()
            ->get();

        // Stats du produit
        $salesStats = [
            'total_sold' => $product->saleItems()->sum('quantity'),
            'total_revenue' => $product->saleItems()->sum('total'),
            'active_addons' => $product->contractAddons()->active()->count(),
            'monthly_recurring' => $product->contractAddons()
                ->active()
                ->get()
                ->sum('monthly_amount'),
        ];

        return Inertia::render('Tenant/Products/Show', [
            'product' => $product,
            'recentSales' => $recentSales,
            'activeAddons' => $activeAddons,
            'salesStats' => $salesStats,
        ]);
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Product $product): Response
    {
        $this->authorizeProduct($product);

        return Inertia::render('Tenant/Products/Edit', [
            'product' => $product,
            'categories' => Product::CATEGORIES,
            'types' => Product::TYPES,
            'billingPeriods' => Product::BILLING_PERIODS,
            'units' => Product::UNITS,
        ]);
    }

    /**
     * Update the specified product.
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $this->authorizeProduct($product);

        $product->update($request->validated());

        return redirect()
            ->route('tenant.products.show', $product)
            ->with('success', 'Produit mis à jour avec succès.');
    }

    /**
     * Remove the specified product.
     */
    public function destroy(Product $product)
    {
        $this->authorizeProduct($product);

        // Vérifier s'il y a des addons actifs
        if ($product->contractAddons()->active()->exists()) {
            return back()->with('error', 'Ce produit ne peut pas être supprimé car il est utilisé dans des contrats actifs.');
        }

        $product->delete();

        return redirect()
            ->route('tenant.products.index')
            ->with('success', 'Produit supprimé avec succès.');
    }

    /**
     * Toggle product active status.
     */
    public function toggleActive(Product $product)
    {
        $this->authorizeProduct($product);

        $product->update(['is_active' => !$product->is_active]);

        $status = $product->is_active ? 'activé' : 'désactivé';

        return back()->with('success', "Produit {$status} avec succès.");
    }

    /**
     * Adjust product stock.
     */
    public function adjustStock(Request $request, Product $product)
    {
        $this->authorizeProduct($product);

        $request->validate([
            'adjustment' => ['required', 'integer'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        if (!$product->track_inventory) {
            return back()->with('error', 'Ce produit ne suit pas l\'inventaire.');
        }

        $newQuantity = $product->stock_quantity + $request->adjustment;

        if ($newQuantity < 0) {
            return back()->with('error', 'La quantité en stock ne peut pas être négative.');
        }

        $product->update(['stock_quantity' => $newQuantity]);

        $action = $request->adjustment > 0 ? 'augmenté' : 'diminué';

        return back()->with('success', "Stock {$action} de " . abs($request->adjustment) . " unité(s).");
    }

    /**
     * Generate a unique SKU for a product.
     */
    private function generateSku(string $category, int $tenantId): string
    {
        $prefix = strtoupper(substr($category, 0, 3));
        $count = Product::where('tenant_id', $tenantId)
            ->where('category', $category)
            ->count() + 1;

        return sprintf('%s-%04d', $prefix, $count);
    }

    /**
     * Authorize that the product belongs to the current tenant.
     */
    private function authorizeProduct(Product $product): void
    {
        if ($product->tenant_id !== Auth::user()->tenant_id) {
            abort(403, 'Accès non autorisé.');
        }
    }
}
