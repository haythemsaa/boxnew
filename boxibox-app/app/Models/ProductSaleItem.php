<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_sale_id',
        'product_id',
        'product_name',
        'product_sku',
        'product_category',
        'quantity',
        'unit_price',
        'tax_rate',
        'tax_amount',
        'discount_amount',
        'total',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            // Snapshot des données produit
            if ($item->product) {
                $item->product_name = $item->product_name ?? $item->product->name;
                $item->product_sku = $item->product_sku ?? $item->product->sku;
                $item->product_category = $item->product_category ?? $item->product->category;
                $item->unit_price = $item->unit_price ?? $item->product->price;
                $item->tax_rate = $item->tax_rate ?? $item->product->tax_rate ?? 20.00;
            }

            // Calculer les montants
            $item->calculateAmounts();
        });

        static::updating(function ($item) {
            $item->calculateAmounts();
        });
    }

    public function calculateAmounts(): void
    {
        $subtotal = $this->unit_price * $this->quantity;
        $this->tax_amount = $subtotal * ($this->tax_rate / 100);
        $this->total = $subtotal + $this->tax_amount - ($this->discount_amount ?? 0);
    }

    // Relationships
    public function sale(): BelongsTo
    {
        return $this->belongsTo(ProductSale::class, 'product_sale_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Accessors
    public function getSubtotalAttribute(): float
    {
        return $this->unit_price * $this->quantity;
    }

    public function getFormattedTotalAttribute(): string
    {
        return number_format($this->total, 2, ',', ' ') . ' €';
    }

    public function getFormattedUnitPriceAttribute(): string
    {
        return number_format($this->unit_price, 2, ',', ' ') . ' €';
    }
}
