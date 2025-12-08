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
        'site_id',
        'customer_id',
        'box_id',
        'contract_number',
        'status',
        'type',
        'start_date',
        'end_date',
        'actual_end_date',
        'notice_period_days',
        'auto_renew',
        'renewal_period',
        'monthly_price',
        'deposit_amount',
        'deposit_paid',
        'discount_percentage',
        'discount_amount',
        'billing_frequency',
        'billing_day',
        'payment_method',
        'auto_pay',
        'access_code',
        'key_given',
        'key_returned',
        'signed_by_customer',
        'customer_signed_at',
        'customer_signature',
        'signed_by_staff',
        'staff_user_id',
        'staff_signed_at',
        'staff_signature',
        'pdf_path',
        'termination_reason',
        'termination_notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'actual_end_date' => 'date',
        'customer_signed_at' => 'datetime',
        'staff_signed_at' => 'datetime',
        'monthly_price' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'notice_period_days' => 'integer',
        'billing_day' => 'integer',
        'signed_by_customer' => 'boolean',
        'signed_by_staff' => 'boolean',
        'auto_renew' => 'boolean',
        'deposit_paid' => 'boolean',
        'auto_pay' => 'boolean',
        'key_given' => 'boolean',
        'key_returned' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function box(): BelongsTo
    {
        return $this->belongsTo(Box::class);
    }

    public function staffUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_user_id');
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
        return $this->signed_by_customer && $this->signed_by_staff;
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

    public function terminate(string $reason = null, string $notes = null): void
    {
        $this->update([
            'status' => 'terminated',
            'actual_end_date' => now(),
            'termination_reason' => $reason,
            'termination_notes' => $notes,
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
