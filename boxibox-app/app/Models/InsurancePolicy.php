<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsurancePolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'contract_id',
        'plan_id',
        'policy_number',
        'external_policy_id',
        'coverage_amount',
        'premium_monthly',
        'premium_yearly',
        'deductible',
        'status',
        'start_date',
        'end_date',
        'cancelled_at',
        'cancellation_reason',
        'declared_value',
        'items_description',
        'items_inventory',
        'payment_frequency',
        'auto_renew',
        'next_payment_date',
        'notes',
    ];

    protected $casts = [
        'coverage_amount' => 'decimal:2',
        'premium_monthly' => 'decimal:2',
        'premium_yearly' => 'decimal:2',
        'deductible' => 'decimal:2',
        'declared_value' => 'decimal:2',
        'items_inventory' => 'array',
        'auto_renew' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'cancelled_at' => 'date',
        'next_payment_date' => 'date',
    ];

    /**
     * Get the tenant that owns this policy.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the customer that owns this policy.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the contract associated with this policy.
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Get the insurance plan.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(InsurancePlan::class);
    }

    /**
     * Get the claims for this policy.
     */
    public function claims(): HasMany
    {
        return $this->hasMany(InsuranceClaim::class, 'policy_id');
    }

    /**
     * Get the payments for this policy.
     */
    public function insurancePayments(): HasMany
    {
        return $this->hasMany(InsurancePayment::class, 'policy_id');
    }

    /**
     * Check if the policy is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active' &&
               $this->start_date <= now() &&
               (!$this->end_date || $this->end_date >= now());
    }

    /**
     * Scope to active policies.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            });
    }

    /**
     * Generate a unique policy number.
     */
    public static function generatePolicyNumber(): string
    {
        $prefix = 'POL';
        $year = date('Y');
        $lastPolicy = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastPolicy ? intval(substr($lastPolicy->policy_number, -6)) + 1 : 1;

        return sprintf('%s-%s-%06d', $prefix, $year, $sequence);
    }
}
