<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Insurance Payment Model
 * Tracks payments made for insurance policies
 */
class InsurancePayment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'policy_id',
        'customer_id',
        'invoice_id',
        'amount',
        'payment_method',
        'payment_reference',
        'status',
        'paid_at',
        'period_start',
        'period_end',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(InsurancePolicy::class, 'policy_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeForPeriod($query, $start, $end)
    {
        return $query->whereBetween('paid_at', [$start, $end]);
    }
}
