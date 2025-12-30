<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\BoxInventoryItem;
use App\Models\Contract;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class MobileInventoryController extends Controller
{
    /**
     * List all inventory items for customer
     */
    public function index(Request $request)
    {
        $customer = $this->getCustomer($request);

        $query = BoxInventoryItem::where('customer_id', $customer->id)
            ->with(['box', 'contract']);

        // Filter by contract/box
        if ($request->filled('contract_id')) {
            $query->where('contract_id', $request->contract_id);
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', 'stored'); // Default to stored items
        }

        // Search
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $items = $query->latest()->paginate(20);

        // Get contracts for filter dropdown
        $contracts = Contract::where('customer_id', $customer->id)
            ->where('status', 'active')
            ->with('box')
            ->get();

        // Get stats
        $stats = [
            'total_items' => BoxInventoryItem::where('customer_id', $customer->id)->stored()->count(),
            'total_value' => BoxInventoryItem::where('customer_id', $customer->id)->stored()->sum('estimated_value'),
            'categories_count' => BoxInventoryItem::where('customer_id', $customer->id)->stored()
                ->distinct('category')->count('category'),
            'insured_count' => BoxInventoryItem::where('customer_id', $customer->id)->stored()->insured()->count(),
        ];

        return Inertia::render('Mobile/Inventory/Index', [
            'items' => $items,
            'contracts' => $contracts,
            'categories' => BoxInventoryItem::CATEGORIES,
            'stats' => $stats,
            'filters' => $request->only(['contract_id', 'category', 'status', 'search']),
        ]);
    }

    /**
     * Show create item form
     */
    public function create(Request $request)
    {
        $customer = $this->getCustomer($request);

        $contracts = Contract::where('customer_id', $customer->id)
            ->where('status', 'active')
            ->with('box')
            ->get();

        return Inertia::render('Mobile/Inventory/Create', [
            'contracts' => $contracts,
            'categories' => BoxInventoryItem::CATEGORIES,
            'conditions' => BoxInventoryItem::CONDITIONS,
            'preselectedContract' => $request->contract_id,
        ]);
    }

    /**
     * Store new inventory item
     */
    public function store(Request $request)
    {
        $customer = $this->getCustomer($request);

        $validated = $request->validate([
            'contract_id' => 'required|exists:contracts,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'required|in:' . implode(',', array_keys(BoxInventoryItem::CATEGORIES)),
            'quantity' => 'required|integer|min:1|max:999',
            'estimated_value' => 'nullable|numeric|min:0|max:9999999',
            'condition' => 'nullable|in:' . implode(',', array_keys(BoxInventoryItem::CONDITIONS)),
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:100',
            'purchase_date' => 'nullable|date|before_or_equal:today',
            'length' => 'nullable|integer|min:1|max:10000',
            'width' => 'nullable|integer|min:1|max:10000',
            'height' => 'nullable|integer|min:1|max:10000',
            'weight' => 'nullable|numeric|min:0|max:10000',
            'location_in_box' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'photos' => 'nullable|array|max:10',
            'photos.*' => 'image|max:5120', // 5MB max per photo
        ]);

        // Verify contract belongs to customer
        $contract = Contract::where('id', $validated['contract_id'])
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        // Handle photo uploads
        $photoPaths = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store("inventory/{$customer->id}", 'public');
                $photoPaths[] = $path;
            }
        }

        $item = BoxInventoryItem::create([
            'tenant_id' => $customer->tenant_id,
            'box_id' => $contract->box_id,
            'contract_id' => $contract->id,
            'customer_id' => $customer->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'category' => $validated['category'],
            'quantity' => $validated['quantity'],
            'estimated_value' => $validated['estimated_value'] ?? null,
            'condition' => $validated['condition'] ?? null,
            'brand' => $validated['brand'] ?? null,
            'model' => $validated['model'] ?? null,
            'serial_number' => $validated['serial_number'] ?? null,
            'purchase_date' => $validated['purchase_date'] ?? null,
            'length' => $validated['length'] ?? null,
            'width' => $validated['width'] ?? null,
            'height' => $validated['height'] ?? null,
            'weight' => $validated['weight'] ?? null,
            'location_in_box' => $validated['location_in_box'] ?? null,
            'tags' => $validated['tags'] ?? null,
            'photos' => $photoPaths,
            'stored_at' => now(),
        ]);

        return redirect()
            ->route('mobile.inventory.show', $item->uuid)
            ->with('success', 'Article ajouté à l\'inventaire');
    }

    /**
     * Show inventory item details
     */
    public function show(Request $request, string $uuid)
    {
        $customer = $this->getCustomer($request);

        $item = BoxInventoryItem::where('uuid', $uuid)
            ->where('customer_id', $customer->id)
            ->with(['box', 'contract'])
            ->firstOrFail();

        return Inertia::render('Mobile/Inventory/Show', [
            'item' => $item,
            'categories' => BoxInventoryItem::CATEGORIES,
            'conditions' => BoxInventoryItem::CONDITIONS,
        ]);
    }

    /**
     * Show edit form
     */
    public function edit(Request $request, string $uuid)
    {
        $customer = $this->getCustomer($request);

        $item = BoxInventoryItem::where('uuid', $uuid)
            ->where('customer_id', $customer->id)
            ->with(['box', 'contract'])
            ->firstOrFail();

        $contracts = Contract::where('customer_id', $customer->id)
            ->where('status', 'active')
            ->with('box')
            ->get();

        return Inertia::render('Mobile/Inventory/Edit', [
            'item' => $item,
            'contracts' => $contracts,
            'categories' => BoxInventoryItem::CATEGORIES,
            'conditions' => BoxInventoryItem::CONDITIONS,
        ]);
    }

    /**
     * Update inventory item
     */
    public function update(Request $request, string $uuid)
    {
        $customer = $this->getCustomer($request);

        $item = BoxInventoryItem::where('uuid', $uuid)
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'required|in:' . implode(',', array_keys(BoxInventoryItem::CATEGORIES)),
            'quantity' => 'required|integer|min:1|max:999',
            'estimated_value' => 'nullable|numeric|min:0|max:9999999',
            'condition' => 'nullable|in:' . implode(',', array_keys(BoxInventoryItem::CONDITIONS)),
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:100',
            'purchase_date' => 'nullable|date|before_or_equal:today',
            'length' => 'nullable|integer|min:1|max:10000',
            'width' => 'nullable|integer|min:1|max:10000',
            'height' => 'nullable|integer|min:1|max:10000',
            'weight' => 'nullable|numeric|min:0|max:10000',
            'location_in_box' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);

        $item->update($validated);

        return redirect()
            ->route('mobile.inventory.show', $item->uuid)
            ->with('success', 'Article mis à jour');
    }

    /**
     * Upload photos for item
     */
    public function uploadPhotos(Request $request, string $uuid)
    {
        $customer = $this->getCustomer($request);

        $item = BoxInventoryItem::where('uuid', $uuid)
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        $request->validate([
            'photos' => 'required|array|min:1|max:10',
            'photos.*' => 'image|max:5120',
        ]);

        $photos = $item->photos ?? [];

        if (count($photos) + count($request->file('photos')) > 10) {
            return response()->json([
                'error' => 'Maximum 10 photos par article',
            ], 422);
        }

        foreach ($request->file('photos') as $photo) {
            $path = $photo->store("inventory/{$customer->id}", 'public');
            $photos[] = $path;
        }

        $item->update(['photos' => $photos]);

        return response()->json([
            'success' => true,
            'photos' => $photos,
            'photos_urls' => array_map(fn($p) => Storage::disk('public')->url($p), $photos),
        ]);
    }

    /**
     * Delete photo from item
     */
    public function deletePhoto(Request $request, string $uuid)
    {
        $customer = $this->getCustomer($request);

        $item = BoxInventoryItem::where('uuid', $uuid)
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        $request->validate([
            'photo_path' => 'required|string',
        ]);

        $photoPath = $request->photo_path;
        $photos = $item->photos ?? [];

        if (in_array($photoPath, $photos)) {
            Storage::disk('public')->delete($photoPath);
            $item->removePhoto($photoPath);
        }

        return response()->json([
            'success' => true,
            'photos' => $item->fresh()->photos,
        ]);
    }

    /**
     * Mark item as removed
     */
    public function markRemoved(Request $request, string $uuid)
    {
        $customer = $this->getCustomer($request);

        $item = BoxInventoryItem::where('uuid', $uuid)
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        $item->markAsRemoved($request->input('note'));

        return response()->json([
            'success' => true,
            'message' => 'Article marqué comme retiré',
        ]);
    }

    /**
     * Mark item as stored again
     */
    public function markStored(Request $request, string $uuid)
    {
        $customer = $this->getCustomer($request);

        $item = BoxInventoryItem::where('uuid', $uuid)
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        $item->markAsStored();

        return response()->json([
            'success' => true,
            'message' => 'Article marqué comme stocké',
        ]);
    }

    /**
     * Delete inventory item
     */
    public function destroy(Request $request, string $uuid)
    {
        $customer = $this->getCustomer($request);

        $item = BoxInventoryItem::where('uuid', $uuid)
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        // Delete photos
        if ($item->photos) {
            foreach ($item->photos as $photo) {
                Storage::disk('public')->delete($photo);
            }
        }

        $item->delete();

        return redirect()
            ->route('mobile.inventory.index')
            ->with('success', 'Article supprimé de l\'inventaire');
    }

    /**
     * Export inventory as PDF or CSV
     */
    public function export(Request $request)
    {
        $customer = $this->getCustomer($request);

        $format = $request->input('format', 'csv');

        $items = BoxInventoryItem::where('customer_id', $customer->id)
            ->stored()
            ->with(['box', 'contract'])
            ->get();

        if ($format === 'csv') {
            $csv = "Nom,Catégorie,Quantité,Valeur estimée,Marque,Modèle,Box,Date de stockage\n";

            foreach ($items as $item) {
                $csv .= sprintf(
                    '"%s","%s",%d,%.2f,"%s","%s","%s","%s"' . "\n",
                    str_replace('"', '""', $item->name),
                    $item->category_label,
                    $item->quantity,
                    $item->estimated_value ?? 0,
                    str_replace('"', '""', $item->brand ?? ''),
                    str_replace('"', '""', $item->model ?? ''),
                    str_replace('"', '""', $item->box?->name ?? ''),
                    $item->stored_at?->format('d/m/Y') ?? ''
                );
            }

            return response($csv)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="inventaire_' . now()->format('Y-m-d') . '.csv"');
        }

        // TODO: PDF export with DomPDF
        return response()->json(['error' => 'Format non supporté'], 400);
    }

    /**
     * Get inventory statistics
     */
    public function stats(Request $request)
    {
        $customer = $this->getCustomer($request);

        $items = BoxInventoryItem::where('customer_id', $customer->id)->stored();

        $stats = [
            'total_items' => $items->count(),
            'total_quantity' => $items->sum('quantity'),
            'total_value' => $items->sum('estimated_value'),
            'categories' => $items->clone()
                ->selectRaw('category, COUNT(*) as count, SUM(estimated_value) as value')
                ->groupBy('category')
                ->get()
                ->map(fn($c) => [
                    'category' => $c->category,
                    'label' => BoxInventoryItem::CATEGORIES[$c->category] ?? 'Autre',
                    'count' => $c->count,
                    'value' => $c->value ?? 0,
                ]),
            'recent_items' => BoxInventoryItem::where('customer_id', $customer->id)
                ->stored()
                ->latest('stored_at')
                ->take(5)
                ->get(['uuid', 'name', 'category', 'estimated_value', 'stored_at']),
            'insured_value' => $items->clone()->insured()->sum('estimated_value'),
            'uninsured_value' => $items->clone()->where('is_insured', false)->sum('estimated_value'),
        ];

        return response()->json($stats);
    }

    /**
     * Get customer from session
     */
    protected function getCustomer(Request $request): Customer
    {
        $customerId = session('mobile_customer_id');

        if (!$customerId) {
            abort(401);
        }

        return Customer::findOrFail($customerId);
    }
}
