<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Contract extends Model
{
    use SoftDeletes, BelongsToTenant;

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

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function insurancePolicy(): HasOne
    {
        return $this->hasOne(InsurancePolicy::class)->latest();
    }

    public function insurancePolicies(): HasMany
    {
        return $this->hasMany(InsurancePolicy::class);
    }

    public function addons(): HasMany
    {
        return $this->hasMany(ContractAddon::class);
    }

    public function activeAddons(): HasMany
    {
        return $this->hasMany(ContractAddon::class)->where('status', 'active');
    }

    public function productSales(): HasMany
    {
        return $this->hasMany(ProductSale::class);
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
        $basePrice = $this->monthly_price;
        $totalDiscount = 0;

        // Calculate percentage discount (capped at 30%)
        $effectivePercentage = min(30, $this->discount_percentage ?? 0);
        if ($effectivePercentage > 0) {
            $totalDiscount += ($basePrice * ($effectivePercentage / 100));
        }

        // Add fixed discount amount
        if ($this->discount_amount > 0) {
            $totalDiscount += $this->discount_amount;
        }

        // Cap total discount at 30% of base price (protection against revenue loss)
        $maxDiscount = $basePrice * 0.30;
        $totalDiscount = min($totalDiscount, $maxDiscount);

        return max(0, $basePrice - $totalDiscount);
    }

    public function getMonthlyAddonsTotal(): float
    {
        return $this->activeAddons()
            ->where('billing_period', 'monthly')
            ->sum(\DB::raw('quantity * unit_price'));
    }

    public function getTotalMonthlyPrice(): float
    {
        return $this->calculateFinalPrice() + $this->getMonthlyAddonsTotal();
    }
}
