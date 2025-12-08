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
        'payment_number',
        'type',
        'status',
        'amount',
        'fee',
        'currency',
        'method',
        'payment_method',
        'gateway',
        'gateway_payment_id',
        'gateway_customer_id',
        'gateway_response',
        'stripe_charge_id',
        'stripe_payment_intent_id',
        'card_brand',
        'card_last_four',
        'paid_at',
        'processed_at',
        'failed_at',
        'refund_for_payment_id',
        'refunded_amount',
        'failure_code',
        'failure_message',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'processed_at' => 'datetime',
        'failed_at' => 'datetime',
        'gateway_response' => 'array',
        'deleted_at' => 'datetime',
        'auto_pay' => 'boolean',
        'next_retry_at' => 'datetime',
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

    public function markAsFailed(string $code = null, string $message = null): void
    {
        $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'failure_code' => $code,
            'failure_message' => $message,
        ]);
    }

    public function refund(float $amount = null, string $notes = null): self
    {
        $refundAmount = $amount ?? $this->amount;

        $refundPayment = self::create([
            'tenant_id' => $this->tenant_id,
            'customer_id' => $this->customer_id,
            'invoice_id' => $this->invoice_id,
            'contract_id' => $this->contract_id,
            'payment_number' => 'REF-' . strtoupper(substr(uniqid(), -8)),
            'type' => 'refund',
            'amount' => -$refundAmount,
            'currency' => $this->currency,
            'status' => 'completed',
            'method' => $this->method,
            'gateway' => $this->gateway,
            'refund_for_payment_id' => $this->id,
            'notes' => $notes,
            'paid_at' => now(),
        ]);

        $this->update([
            'status' => 'refunded',
            'refunded_amount' => $refundAmount,
        ]);

        return $refundPayment;
    }
}
