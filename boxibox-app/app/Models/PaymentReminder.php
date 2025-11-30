<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentReminder extends Model
{
    protected $fillable = [
        'tenant_id',
        'invoice_id',
        'customer_id',
        'type',
        'level',
        'days_before_due',
        'days_after_due',
        'status',
        'message',
        'method',
        'amount_due',
        'scheduled_at',
        'sent_at',
        'error_message',
        'metadata',
        'notes',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'metadata' => 'array',
        'level' => 'integer',
        'amount_due' => 'decimal:2',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'pending')
                    ->whereNotNull('scheduled_at')
                    ->where('scheduled_at', '<=', now());
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // Helper Methods
    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }
}
