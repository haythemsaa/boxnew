<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'box_id',
        'contract_number',
        'status',
        'start_date',
        'end_date',
        'notice_period_days',
        'auto_renew',
        'monthly_price',
        'deposit_amount',
        'discount_percentage',
        'discount_amount',
        'terms_and_conditions',
        'notes',
        'signed_by_customer',
        'signed_by_tenant',
        'customer_signed_at',
        'tenant_signed_at',
        'customer_signature',
        'tenant_signature',
        'access_code',
        'termination_date',
        'termination_reason',
        'terminated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'customer_signed_at' => 'datetime',
        'tenant_signed_at' => 'datetime',
        'termination_date' => 'date',
        'monthly_price' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'notice_period_days' => 'integer',
        'signed_by_customer' => 'boolean',
        'signed_by_tenant' => 'boolean',
        'auto_renew' => 'boolean',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function box(): BelongsTo
    {
        return $this->belongsTo(Box::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('status', 'active')
            ->whereDate('end_date', '<=', now()->addDays($days))
            ->whereDate('end_date', '>=', now());
    }

    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    // Accessors
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->end_date && $this->end_date->isPast();
    }

    public function getIsFullySignedAttribute(): bool
    {
        return $this->signed_by_customer && $this->signed_by_tenant;
    }

    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (!$this->end_date) {
            return null;
        }
        return now()->diffInDays($this->end_date, false);
    }

    // Helper Methods
    public function activate(): void
    {
        $this->update(['status' => 'active']);
        $this->box->makeOccupied();
    }

    public function terminate(string $reason = null, int $terminatedBy = null): void
    {
        $this->update([
            'status' => 'terminated',
            'termination_date' => now(),
            'termination_reason' => $reason,
            'terminated_by' => $terminatedBy,
        ]);
        $this->box->makeAvailable();
    }

    public function renew(int $months = 12): void
    {
        $this->update([
            'end_date' => $this->end_date->addMonths($months),
        ]);
    }

    public function calculateFinalPrice(): float
    {
        $price = $this->monthly_price;

        if ($this->discount_percentage > 0) {
            $price -= ($price * ($this->discount_percentage / 100));
        }

        if ($this->discount_amount > 0) {
            $price -= $this->discount_amount;
        }

        return max(0, $price);
    }
}
