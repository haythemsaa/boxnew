<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'tenant_id',
        'customer_id',
        'invoice_id',
        'contract_id',
        'amount',
        'currency',
        'status',
        'gateway',
        'gateway_payment_id',
        'gateway_response',
        'payment_method',
        'card_brand',
        'card_last_four',
        'paid_at',
        'failed_at',
        'failure_reason',
        'refunded_amount',
        'refunded_at',
        'refund_reason',
        'refund_for_payment_id',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
        'refunded_at' => 'datetime',
        'gateway_response' => 'array',
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

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function refundForPayment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'refund_for_payment_id');
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeByGateway($query, string $gateway)
    {
        return $query->where('gateway', $gateway);
    }

    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    // Accessors
    public function getIsCompletedAttribute(): bool
    {
        return $this->status === 'completed';
    }

    public function getIsRefundedAttribute(): bool
    {
        return $this->status === 'refunded';
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2) . ' ' . strtoupper($this->currency);
    }

    // Helper Methods
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'paid_at' => now(),
        ]);

        if ($this->invoice) {
            $this->invoice->recordPayment($this->amount);
        }
    }

    public function markAsFailed(string $reason = null): void
    {
        $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'failure_reason' => $reason,
        ]);
    }

    public function refund(float $amount = null, string $reason = null): self
    {
        $refundAmount = $amount ?? $this->amount;

        $refundPayment = self::create([
            'tenant_id' => $this->tenant_id,
            'customer_id' => $this->customer_id,
            'invoice_id' => $this->invoice_id,
            'contract_id' => $this->contract_id,
            'amount' => -$refundAmount,
            'currency' => $this->currency,
            'status' => 'completed',
            'gateway' => $this->gateway,
            'refund_for_payment_id' => $this->id,
            'refund_reason' => $reason,
            'refunded_at' => now(),
        ]);

        $this->update([
            'status' => 'refunded',
            'refunded_amount' => $refundAmount,
            'refunded_at' => now(),
        ]);

        return $refundPayment;
    }
}
