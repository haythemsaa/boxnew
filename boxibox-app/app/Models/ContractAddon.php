<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractAddon extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'contract_id',
        'product_id',
        'product_name',
        'product_sku',
        'quantity',
        'unit_price',
        'tax_rate',
        'billing_period',
        'start_date',
        'end_date',
        'next_billing_date',
        'status',
        'paused_at',
        'cancelled_at',
        'cancellation_reason',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'next_billing_date' => 'date',
        'paused_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($addon) {
            // Snapshot des données produit
            if ($addon->product) {
                $addon->product_name = $addon->product_name ?? $addon->product->name;
                $addon->product_sku = $addon->product_sku ?? $addon->product->sku;
                $addon->unit_price = $addon->unit_price ?? $addon->product->price;
                $addon->tax_rate = $addon->tax_rate ?? $addon->product->tax_rate ?? 20.00;
                $addon->billing_period = $addon->billing_period ?? $addon->product->billing_period ?? 'monthly';
            }

            // Définir la date de début par défaut
            if (empty($addon->start_date)) {
                $addon->start_date = now()->toDateString();
            }

            // Calculer la prochaine date de facturation
            if (empty($addon->next_billing_date)) {
                $addon->next_billing_date = $addon->calculateNextBillingDate();
            }
        });
    }

    // Relationships
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePaused($query)
    {
        return $query->where('status', 'paused');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeDueBilling($query)
    {
        return $query->active()
            ->where('next_billing_date', '<=', today());
    }

    public function scopeRecurring($query)
    {
        return $query->whereHas('product', function ($q) {
            $q->where('type', 'recurring');
        });
    }

    // Helpers
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isPaused(): bool
    {
        return $this->status === 'paused';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' ||
            ($this->end_date && $this->end_date->isPast());
    }

    public function canBePaused(): bool
    {
        return $this->isActive();
    }

    public function canBeResumed(): bool
    {
        return $this->isPaused();
    }

    public function canBeCancelled(): bool
    {
        return $this->isActive() || $this->isPaused();
    }

    public function pause(): void
    {
        if (!$this->canBePaused()) {
            throw new \Exception('Cet addon ne peut pas être mis en pause.');
        }

        $this->update([
            'status' => 'paused',
            'paused_at' => now(),
        ]);
    }

    public function resume(): void
    {
        if (!$this->canBeResumed()) {
            throw new \Exception('Cet addon ne peut pas être réactivé.');
        }

        $this->update([
            'status' => 'active',
            'paused_at' => null,
            'next_billing_date' => $this->calculateNextBillingDate(),
        ]);
    }

    public function cancel(?string $reason = null): void
    {
        if (!$this->canBeCancelled()) {
            throw new \Exception('Cet addon ne peut pas être annulé.');
        }

        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'end_date' => today(),
        ]);
    }

    public function calculateNextBillingDate(): string
    {
        $startDate = $this->start_date ?? now();

        return match ($this->billing_period) {
            'monthly' => $startDate->copy()->addMonth()->toDateString(),
            'quarterly' => $startDate->copy()->addMonths(3)->toDateString(),
            'yearly' => $startDate->copy()->addYear()->toDateString(),
            default => $startDate->copy()->addMonth()->toDateString(),
        };
    }

    public function advanceNextBillingDate(): void
    {
        $this->update([
            'next_billing_date' => $this->calculateNextBillingDate(),
        ]);
    }

    // Accessors
    public function getMonthlyAmountAttribute(): float
    {
        $amount = $this->unit_price * $this->quantity;

        return match ($this->billing_period) {
            'quarterly' => $amount / 3,
            'yearly' => $amount / 12,
            default => $amount,
        };
    }

    public function getTotalWithTaxAttribute(): float
    {
        $subtotal = $this->unit_price * $this->quantity;
        $tax = $subtotal * ($this->tax_rate / 100);

        return $subtotal + $tax;
    }

    public function getFormattedPriceAttribute(): string
    {
        $price = $this->unit_price * $this->quantity;
        $period = match ($this->billing_period) {
            'monthly' => '/mois',
            'quarterly' => '/trimestre',
            'yearly' => '/an',
            default => '',
        };

        return number_format($price, 2, ',', ' ') . ' €' . $period;
    }

    public function toInvoiceItem(): array
    {
        $subtotal = $this->unit_price * $this->quantity;
        $taxAmount = $subtotal * ($this->tax_rate / 100);

        return [
            'type' => 'addon',
            'product_id' => $this->product_id,
            'description' => $this->product_name . ($this->quantity > 1 ? " x{$this->quantity}" : ''),
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'tax_rate' => $this->tax_rate,
            'tax_amount' => $taxAmount,
            'discount' => 0,
            'total' => $subtotal + $taxAmount,
        ];
    }
}
