<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContractAddonRequest;
use App\Models\Contract;
use App\Models\ContractAddon;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ContractAddonController extends Controller
{
    /**
     * Display addons for a contract.
     */
    public function index(Contract $contract)
    {
        $this->authorizeContract($contract);

        $addons = $contract->addons()
            ->with('product')
            ->orderBy('status')
            ->orderBy('created_at', 'desc')
            ->get();

        $tenantId = Auth::user()->tenant_id;

        $availableProducts = Product::where('tenant_id', $tenantId)
            ->active()
            ->recurring()
            ->ordered()
            ->get();

        // Stats
        $stats = [
            'active_count' => $addons->where('status', 'active')->count(),
            'monthly_total' => $addons->where('status', 'active')->sum('monthly_amount'),
            'paused_count' => $addons->where('status', 'paused')->count(),
        ];

        return Inertia::render('Tenant/Contracts/Addons', [
            'contract' => $contract->load(['customer', 'box', 'site']),
            'addons' => $addons,
            'products' => $availableProducts,
            'billingPeriods' => Product::BILLING_PERIODS,
        ]);
    }

    /**
     * Store a new addon for a contract.
     */
    public function store(StoreContractAddonRequest $request, Contract $contract)
    {
        $this->authorizeContract($contract);

        $data = $request->validated();
        $product = Product::findOrFail($data['product_id']);

        // Vérifier que le produit est récurrent
        if (!$product->isRecurring()) {
            return back()->with('error', 'Seuls les produits récurrents peuvent être ajoutés comme add-ons.');
        }

        // Vérifier si l'addon existe déjà
        $existingAddon = $contract->addons()
            ->where('product_id', $product->id)
            ->active()
            ->first();

        if ($existingAddon) {
            return back()->with('error', 'Ce produit est déjà ajouté à ce contrat.');
        }

        ContractAddon::create([
            'contract_id' => $contract->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'quantity' => $data['quantity'],
            'unit_price' => $data['unit_price'] ?? $product->price,
            'tax_rate' => $data['tax_rate'] ?? $product->tax_rate ?? 20,
            'billing_period' => $data['billing_period'] ?? $product->billing_period ?? 'monthly',
            'start_date' => $data['start_date'] ?? now()->toDateString(),
            'end_date' => $data['end_date'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'Service ajouté au contrat avec succès.');
    }

    /**
     * Update an addon.
     */
    public function update(Request $request, Contract $contract, ContractAddon $addon)
    {
        $this->authorizeContract($contract);
        $this->validateAddonBelongsToContract($addon, $contract);

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'end_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $addon->update($validated);

        return back()->with('success', 'Service mis à jour avec succès.');
    }

    /**
     * Remove an addon.
     */
    public function destroy(Contract $contract, ContractAddon $addon)
    {
        $this->authorizeContract($contract);
        $this->validateAddonBelongsToContract($addon, $contract);

        $addon->delete();

        return back()->with('success', 'Service supprimé du contrat.');
    }

    /**
     * Pause an addon.
     */
    public function pause(Request $request, Contract $contract, ContractAddon $addon)
    {
        $this->authorizeContract($contract);
        $this->validateAddonBelongsToContract($addon, $contract);

        try {
            $addon->pause();
            return back()->with('success', 'Service mis en pause.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Resume an addon.
     */
    public function resume(Contract $contract, ContractAddon $addon)
    {
        $this->authorizeContract($contract);
        $this->validateAddonBelongsToContract($addon, $contract);

        try {
            $addon->resume();
            return back()->with('success', 'Service réactivé.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel an addon.
     */
    public function cancel(Request $request, Contract $contract, ContractAddon $addon)
    {
        $this->authorizeContract($contract);
        $this->validateAddonBelongsToContract($addon, $contract);

        $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $addon->cancel($request->reason);
            return back()->with('success', 'Service annulé.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Authorize that the contract belongs to the current tenant.
     */
    private function authorizeContract(Contract $contract): void
    {
        if ($contract->tenant_id !== Auth::user()->tenant_id) {
            abort(403, 'Accès non autorisé.');
        }
    }

    /**
     * Validate that the addon belongs to the contract.
     */
    private function validateAddonBelongsToContract(ContractAddon $addon, Contract $contract): void
    {
        if ($addon->contract_id !== $contract->id) {
            abort(404, 'Add-on non trouvé pour ce contrat.');
        }
    }
}
