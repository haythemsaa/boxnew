<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductSale extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'contract_id',
        'invoice_id',
        'site_id',
        'sale_number',
        'status',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total',
        'currency',
        'payment_method',
        'payment_status',
        'payment_reference',
        'paid_at',
        'notes',
        'sold_by',
        'sold_at',
        'refunded_amount',
        'refunded_at',
        'refund_reason',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'sold_at' => 'datetime',
        'refunded_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($sale) {
            if (empty($sale->sale_number)) {
                $sale->sale_number = self::generateSaleNumber($sale->tenant_id);
            }
            if (empty($sale->sold_at)) {
                $sale->sold_at = now();
            }
        });
    }

    public static function generateSaleNumber(int $tenantId): string
    {
        $prefix = 'VNT';
        $date = now()->format('Ymd');
        $count = self::where('tenant_id', $tenantId)
            ->whereDate('created_at', today())
            ->count() + 1;

        return sprintf('%s%s%04d', $prefix, $date, $count);
    }

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sold_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProductSaleItem::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('payment_status', ['pending', 'failed']);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('sold_at', today());
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('sold_at', now()->month)
            ->whereYear('sold_at', now()->year);
    }

    // Helpers
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function canBeCompleted(): bool
    {
        return $this->isPending() && $this->isPaid();
    }

    public function canBeCancelled(): bool
    {
        return $this->isPending();
    }

    public function canBeRefunded(): bool
    {
        return $this->isCompleted() && $this->isPaid() && $this->refunded_amount < $this->total;
    }

    public function calculateTotals(): void
    {
        $subtotal = $this->items->sum(function ($item) {
            return $item->unit_price * $item->quantity;
        });

        $taxAmount = $this->items->sum('tax_amount');
        $discountAmount = $this->items->sum('discount_amount');
        $total = $subtotal + $taxAmount - $discountAmount;

        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total' => $total,
        ]);
    }

    public function markAsPaid(string $method, ?string $reference = null): void
    {
        $this->update([
            'payment_status' => 'paid',
            'payment_method' => $method,
            'payment_reference' => $reference,
            'paid_at' => now(),
        ]);
    }

    public function complete(): void
    {
        if (!$this->canBeCompleted()) {
            throw new \Exception('Cette vente ne peut pas être complétée.');
        }

        $this->update([
            'status' => 'completed',
        ]);

        // Décrémenter le stock
        foreach ($this->items as $item) {
            $item->product->decrementStock($item->quantity);
        }
    }

    public function cancel(): void
    {
        if (!$this->canBeCancelled()) {
            throw new \Exception('Cette vente ne peut pas être annulée.');
        }

        $this->update([
            'status' => 'cancelled',
        ]);
    }

    public function refund(float $amount, string $reason): void
    {
        if (!$this->canBeRefunded()) {
            throw new \Exception('Cette vente ne peut pas être remboursée.');
        }

        $maxRefundable = $this->total - $this->refunded_amount;
        if ($amount > $maxRefundable) {
            throw new \Exception("Le montant maximum remboursable est {$maxRefundable}€.");
        }

        $this->update([
            'status' => 'refunded',
            'refunded_amount' => $this->refunded_amount + $amount,
            'refunded_at' => now(),
            'refund_reason' => $reason,
        ]);

        // Restaurer le stock
        foreach ($this->items as $item) {
            $item->product->incrementStock($item->quantity);
        }
    }

    public function getItemsCountAttribute(): int
    {
        return $this->items->sum('quantity');
    }
}
