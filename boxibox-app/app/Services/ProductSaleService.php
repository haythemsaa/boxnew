<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductSale;
use App\Models\ProductSaleItem;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class ProductSaleService
{
    /**
     * Create a new sale with items.
     */
    public function createSale(array $data, int $tenantId, int $userId): ProductSale
    {
        return DB::transaction(function () use ($data, $tenantId, $userId) {
            // Créer la vente
            $sale = ProductSale::create([
                'tenant_id' => $tenantId,
                'customer_id' => $data['customer_id'],
                'contract_id' => $data['contract_id'] ?? null,
                'site_id' => $data['site_id'] ?? null,
                'payment_method' => $data['payment_method'] ?? null,
                'notes' => $data['notes'] ?? null,
                'sold_by' => $userId,
                'discount_amount' => $data['discount_amount'] ?? 0,
            ]);

            // Ajouter les items
            $this->addItemsToSale($sale, $data['items']);

            // Recalculer les totaux
            $sale->calculateTotals();

            return $sale;
        });
    }

    /**
     * Add items to a sale.
     */
    public function addItemsToSale(ProductSale $sale, array $items): void
    {
        foreach ($items as $itemData) {
            $product = Product::findOrFail($itemData['product_id']);

            // Vérifier le stock
            if (!$product->hasStock($itemData['quantity'])) {
                throw new \Exception("Stock insuffisant pour le produit: {$product->name}");
            }

            // Vérifier la quantité
            if (!$product->validateQuantity($itemData['quantity'])) {
                throw new \Exception("Quantité invalide pour le produit: {$product->name}");
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
    }

    /**
     * Process payment for a sale.
     */
    public function processPayment(ProductSale $sale, string $method, ?string $reference = null): void
    {
        $sale->markAsPaid($method, $reference);
    }

    /**
     * Complete a sale and update inventory.
     */
    public function completeSale(ProductSale $sale): void
    {
        if (!$sale->canBeCompleted()) {
            throw new \Exception('Cette vente ne peut pas être complétée.');
        }

        DB::transaction(function () use ($sale) {
            $sale->update(['status' => 'completed']);

            // Décrémenter le stock
            foreach ($sale->items as $item) {
                $item->product->decrementStock($item->quantity);
            }
        });
    }

    /**
     * Cancel a sale.
     */
    public function cancelSale(ProductSale $sale): void
    {
        if (!$sale->canBeCancelled()) {
            throw new \Exception('Cette vente ne peut pas être annulée.');
        }

        $sale->update(['status' => 'cancelled']);
    }

    /**
     * Refund a sale.
     */
    public function refundSale(ProductSale $sale, float $amount, string $reason): void
    {
        if (!$sale->canBeRefunded()) {
            throw new \Exception('Cette vente ne peut pas être remboursée.');
        }

        $maxRefundable = $sale->total - $sale->refunded_amount;
        if ($amount > $maxRefundable) {
            throw new \Exception("Le montant maximum remboursable est {$maxRefundable}€.");
        }

        DB::transaction(function () use ($sale, $amount, $reason) {
            $sale->update([
                'status' => 'refunded',
                'refunded_amount' => $sale->refunded_amount + $amount,
                'refunded_at' => now(),
                'refund_reason' => $reason,
            ]);

            // Restaurer le stock
            foreach ($sale->items as $item) {
                $item->product->incrementStock($item->quantity);
            }
        });
    }

    /**
     * Generate an invoice for a sale.
     */
    public function generateInvoice(ProductSale $sale): Invoice
    {
        if ($sale->invoice_id) {
            return $sale->invoice;
        }

        $invoiceItems = [];

        foreach ($sale->items as $item) {
            $invoiceItems[] = [
                'type' => 'product',
                'product_id' => $item->product_id,
                'description' => $item->product_name,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'tax_rate' => $item->tax_rate,
                'tax_amount' => $item->tax_amount,
                'discount' => $item->discount_amount,
                'total' => $item->total,
            ];
        }

        $invoice = Invoice::create([
            'tenant_id' => $sale->tenant_id,
            'customer_id' => $sale->customer_id,
            'type' => 'invoice',
            'status' => $sale->isPaid() ? 'paid' : 'sent',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'items' => $invoiceItems,
            'subtotal' => $sale->subtotal,
            'tax_rate' => 20, // Moyenne
            'tax_amount' => $sale->tax_amount,
            'discount_amount' => $sale->discount_amount,
            'total' => $sale->total,
            'paid_amount' => $sale->isPaid() ? $sale->total : 0,
            'paid_at' => $sale->paid_at,
            'notes' => "Vente #{$sale->sale_number}",
        ]);

        $sale->update(['invoice_id' => $invoice->id]);

        return $invoice;
    }

    /**
     * Get sales statistics for a tenant.
     */
    public function getStatistics(int $tenantId, ?string $period = 'month'): array
    {
        $query = ProductSale::where('tenant_id', $tenantId)->completed();

        $startDate = match ($period) {
            'today' => today(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        $query->where('sold_at', '>=', $startDate);

        $sales = $query->get();

        return [
            'count' => $sales->count(),
            'total_revenue' => $sales->sum('total'),
            'average_sale' => $sales->count() > 0 ? $sales->avg('total') : 0,
            'total_items' => $sales->sum('items_count'),
        ];
    }

    /**
     * Get top selling products for a tenant.
     */
    public function getTopProducts(int $tenantId, int $limit = 10): array
    {
        return ProductSaleItem::query()
            ->join('product_sales', 'product_sale_items.product_sale_id', '=', 'product_sales.id')
            ->where('product_sales.tenant_id', $tenantId)
            ->where('product_sales.status', 'completed')
            ->selectRaw('product_id, product_name, SUM(quantity) as total_quantity, SUM(total) as total_revenue')
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
