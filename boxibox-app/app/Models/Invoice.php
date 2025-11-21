<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'contract_id',
        'invoice_number',
        'invoice_type',
        'status',
        'issue_date',
        'due_date',
        'paid_date',
        'items',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'discount_amount',
        'total',
        'paid_amount',
        'currency',
        'notes',
        'is_recurring',
        'recurring_frequency',
        'next_billing_date',
        'reminder_count',
        'last_reminder_sent_at',
        'pdf_path',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'paid_date' => 'date',
        'next_billing_date' => 'date',
        'last_reminder_sent_at' => 'datetime',
        'items' => 'array',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'is_recurring' => 'boolean',
        'reminder_count' => 'integer',
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

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // Scopes
    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue')
            ->orWhere(function($q) {
                $q->whereIn('status', ['sent'])
                    ->whereDate('due_date', '<', now());
            });
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', ['sent', 'overdue', 'partial']);
    }

    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    // Accessors
    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date && $this->due_date->isPast() &&
               in_array($this->status, ['sent', 'partial']);
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->status === 'paid';
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0, $this->total - $this->paid_amount);
    }

    // Helper Methods
    public function markAsPaid(): void
    {
        $this->update([
            'status' => 'paid',
            'paid_date' => now(),
            'paid_amount' => $this->total,
        ]);
    }

    public function recordPayment(float $amount): void
    {
        $newPaidAmount = $this->paid_amount + $amount;

        if ($newPaidAmount >= $this->total) {
            $this->markAsPaid();
        } else {
            $this->update([
                'status' => 'partial',
                'paid_amount' => $newPaidAmount,
            ]);
        }
    }

    public function sendReminder(): void
    {
        $this->increment('reminder_count');
        $this->update(['last_reminder_sent_at' => now()]);
    }

    public function calculateTotal(): void
    {
        $subtotal = collect($this->items)->sum('total');
        $taxAmount = $subtotal * ($this->tax_rate / 100);
        $total = $subtotal + $taxAmount - $this->discount_amount;

        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
        ]);
    }
}
