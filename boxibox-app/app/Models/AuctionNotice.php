<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuctionNotice extends Model
{
    protected $fillable = [
        'auction_id',
        'notice_type',
        'channel',
        'status',
        'content',
        'tracking_number',
        'sent_at',
        'delivered_at',
        'metadata',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'metadata' => 'array',
    ];

    // Relationships
    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }

    // Accessors
    public function getNoticeTypeLabelAttribute(): string
    {
        return match ($this->notice_type) {
            'first_warning' => 'Premier avis',
            'second_warning' => 'Deuxième avis',
            'final_notice' => 'Mise en demeure',
            'auction_scheduled' => 'Avis de vente',
            'auction_reminder' => 'Rappel vente',
            'auction_result' => 'Résultat vente',
            default => ucfirst($this->notice_type),
        };
    }

    public function getChannelLabelAttribute(): string
    {
        return match ($this->channel) {
            'email' => 'Email',
            'sms' => 'SMS',
            'mail' => 'Courrier',
            'registered_mail' => 'Recommandé AR',
            default => ucfirst($this->channel),
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'En attente',
            'sent' => 'Envoyé',
            'delivered' => 'Reçu',
            'failed' => 'Échec',
            'returned' => 'Retourné',
            default => ucfirst($this->status),
        };
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    // Actions
    public function markSent(string $trackingNumber = null): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'tracking_number' => $trackingNumber,
        ]);
    }

    public function markDelivered(): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }

    public function markFailed(): void
    {
        $this->update(['status' => 'failed']);
    }

    public function markReturned(): void
    {
        $this->update(['status' => 'returned']);
    }
}
