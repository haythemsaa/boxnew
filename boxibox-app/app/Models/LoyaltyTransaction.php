<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'loyalty_points_id',
        'type',
        'points',
        'description',
    ];

    protected $casts = [
        'points' => 'integer',
    ];

    public function loyaltyPoints(): BelongsTo
    {
        return $this->belongsTo(LoyaltyPoints::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'earned' => 'Points gagnés',
            'redeemed' => 'Points utilisés',
            'bonus' => 'Bonus',
            'expired' => 'Points expirés',
            'adjustment' => 'Ajustement',
            'referral' => 'Parrainage',
            default => $this->type,
        };
    }

    public function getTypeColorAttribute(): string
    {
        return match ($this->type) {
            'earned', 'bonus', 'referral' => 'green',
            'redeemed' => 'blue',
            'expired' => 'gray',
            'adjustment' => 'yellow',
            default => 'gray',
        };
    }

    public function scopeCredits($query)
    {
        return $query->whereIn('type', ['earned', 'bonus', 'referral', 'adjustment'])
            ->where('points', '>', 0);
    }

    public function scopeDebits($query)
    {
        return $query->whereIn('type', ['redeemed', 'expired'])
            ->orWhere(function ($q) {
                $q->where('type', 'adjustment')->where('points', '<', 0);
            });
    }
}
