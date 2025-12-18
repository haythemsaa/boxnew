<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class PaymentRetryAttempt extends Model
{
    protected $fillable = [
        'tenant_id',
        'invoice_id',
        'customer_id',
        'amount',
        'currency',
        'payment_method_id',
        'status',
        'attempt_number',
        'max_attempts',
        'scheduled_at',
        'attempted_at',
        'next_retry_at',
        'failure_code',
        'failure_message',
        'decline_code',
        'stripe_payment_intent_id',
        'stripe_charge_id',
        'succeeded_at',
        'card_update_token',
        'card_update_token_expires_at',
        'card_was_updated',
        'notifications_sent',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'scheduled_at' => 'datetime',
        'attempted_at' => 'datetime',
        'next_retry_at' => 'datetime',
        'succeeded_at' => 'datetime',
        'card_update_token_expires_at' => 'datetime',
        'card_was_updated' => 'boolean',
        'notifications_sent' => 'array',
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

    public function analytics(): HasOne
    {
        return $this->hasOne(PaymentFailureAnalytics::class, 'retry_attempt_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeSucceeded($query)
    {
        return $query->where('status', 'succeeded');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'scheduled', 'processing']);
    }

    public function scopeReadyToProcess($query)
    {
        return $query->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now());
    }

    // Status helpers
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isSucceeded(): bool
    {
        return $this->status === 'succeeded';
    }

    public function canRetry(): bool
    {
        return $this->attempt_number < $this->max_attempts
            && in_array($this->status, ['failed', 'pending']);
    }

    public function isFinalFailure(): bool
    {
        return $this->status === 'failed'
            && $this->attempt_number >= $this->max_attempts;
    }

    // Actions
    public function markAsScheduled(\DateTime $scheduledAt): void
    {
        $this->update([
            'status' => 'scheduled',
            'scheduled_at' => $scheduledAt,
        ]);
    }

    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'attempted_at' => now(),
        ]);
    }

    public function markAsSucceeded(string $paymentIntentId, ?string $chargeId = null): void
    {
        $this->update([
            'status' => 'succeeded',
            'stripe_payment_intent_id' => $paymentIntentId,
            'stripe_charge_id' => $chargeId,
            'succeeded_at' => now(),
        ]);
    }

    public function markAsFailed(string $code, string $message, ?string $declineCode = null): void
    {
        $this->update([
            'status' => 'failed',
            'failure_code' => $code,
            'failure_message' => $message,
            'decline_code' => $declineCode,
        ]);
    }

    public function scheduleNextRetry(\DateTime $nextRetryAt): void
    {
        $this->update([
            'next_retry_at' => $nextRetryAt,
            'attempt_number' => $this->attempt_number + 1,
            'status' => 'scheduled',
            'scheduled_at' => $nextRetryAt,
        ]);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    // Card update token
    public function generateCardUpdateToken(int $expiryHours = 72): string
    {
        $token = Str::random(64);

        $this->update([
            'card_update_token' => $token,
            'card_update_token_expires_at' => now()->addHours($expiryHours),
        ]);

        return $token;
    }

    public function isCardUpdateTokenValid(): bool
    {
        return $this->card_update_token
            && $this->card_update_token_expires_at
            && $this->card_update_token_expires_at->isFuture();
    }

    public function markCardAsUpdated(): void
    {
        $this->update([
            'card_was_updated' => true,
            'status' => 'card_updated',
            'card_update_token' => null,
            'card_update_token_expires_at' => null,
        ]);
    }

    // Notifications
    public function recordNotificationSent(string $type, string $channel = 'email'): void
    {
        $notifications = $this->notifications_sent ?? [];
        $notifications[] = [
            'type' => $type,
            'channel' => $channel,
            'sent_at' => now()->toIso8601String(),
            'attempt' => $this->attempt_number,
        ];

        $this->update(['notifications_sent' => $notifications]);
    }

    public function wasNotificationSent(string $type): bool
    {
        $notifications = $this->notifications_sent ?? [];
        return collect($notifications)->where('type', $type)->isNotEmpty();
    }

    // Accessors
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2, ',', ' ') . ' ' . strtoupper($this->currency);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'En attente',
            'scheduled' => 'Planifie',
            'processing' => 'En cours',
            'succeeded' => 'Reussi',
            'failed' => 'Echoue',
            'cancelled' => 'Annule',
            'card_updated' => 'Carte mise a jour',
            default => $this->status,
        };
    }

    public function getFailureReasonLabelAttribute(): ?string
    {
        if (!$this->decline_code) {
            return $this->failure_message;
        }

        return match ($this->decline_code) {
            'insufficient_funds' => 'Fonds insuffisants',
            'card_declined' => 'Carte refusee',
            'expired_card' => 'Carte expiree',
            'incorrect_cvc' => 'CVC incorrect',
            'processing_error' => 'Erreur de traitement',
            'lost_card' => 'Carte declaree perdue',
            'stolen_card' => 'Carte declaree volee',
            'card_not_supported' => 'Carte non supportee',
            'currency_not_supported' => 'Devise non supportee',
            'duplicate_transaction' => 'Transaction en double',
            'fraudulent' => 'Transaction suspecte',
            'generic_decline' => 'Refus generique',
            default => $this->failure_message ?? 'Erreur inconnue',
        };
    }
}
