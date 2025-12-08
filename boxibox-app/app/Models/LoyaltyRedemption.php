<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyRedemption extends Model
{
    use HasFactory;

    protected $fillable = [
        'loyalty_points_id',
        'reward_id',
        'points_spent',
        'status',
        'notes',
    ];

    protected $casts = [
        'points_spent' => 'integer',
        'fulfilled_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($redemption) {
            if (!$redemption->code) {
                $redemption->code = strtoupper(bin2hex(random_bytes(6)));
            }
        });
    }

    public function loyaltyPoints(): BelongsTo
    {
        return $this->belongsTo(LoyaltyPoints::class);
    }

    public function reward(): BelongsTo
    {
        return $this->belongsTo(LoyaltyReward::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast() && $this->status !== 'used';
    }

    public function isUsable(): bool
    {
        return $this->status === 'active' && !$this->isExpired();
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active' => 'Actif',
            'used' => 'Utilisé',
            'expired' => 'Expiré',
            'cancelled' => 'Annulé',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'active' => 'green',
            'used' => 'blue',
            'expired' => 'gray',
            'cancelled' => 'red',
            default => 'gray',
        };
    }
}
