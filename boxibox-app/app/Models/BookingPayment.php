<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingPayment extends Model
{
    protected $fillable = [
        'booking_id',
        'type',
        'amount',
        'payment_method',
        'transaction_id',
        'status',
        'notes',
        'payment_data',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_data' => 'array',
    ];

    // Relationships
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    // Accessors
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'deposit' => 'Acompte',
            'first_month' => 'Premier mois',
            'full_payment' => 'Paiement complet',
            default => ucfirst($this->type),
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'En attente',
            'processing' => 'En cours',
            'completed' => 'Complété',
            'failed' => 'Échoué',
            'refunded' => 'Remboursé',
            default => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => '#FF9800',
            'processing' => '#2196F3',
            'completed' => '#4CAF50',
            'failed' => '#f44336',
            'refunded' => '#9C27B0',
            default => '#9E9E9E',
        };
    }

    // Actions
    public function markAsCompleted(string $transactionId = null): void
    {
        $this->update([
            'status' => 'completed',
            'transaction_id' => $transactionId ?? $this->transaction_id,
        ]);

        // Update booking status if this was a deposit payment
        if ($this->type === 'deposit' && $this->booking->status === 'confirmed') {
            $this->booking->markDepositPaid();
        }
    }

    public function markAsFailed(string $reason = null): void
    {
        $this->update([
            'status' => 'failed',
            'notes' => $reason,
        ]);
    }

    public function refund(): void
    {
        $this->update(['status' => 'refunded']);
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
}
