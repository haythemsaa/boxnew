<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyReward extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'points_required',
        'value',
        'is_active',
    ];

    protected $casts = [
        'points_required' => 'integer',
        'value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function redemptions(): HasMany
    {
        return $this->hasMany(LoyaltyRedemption::class, 'reward_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isAvailable(): bool
    {
        return $this->is_active;
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'discount_percent' => 'Réduction %',
            'discount_amount' => 'Réduction €',
            'free_month' => 'Mois gratuit',
            'upgrade' => 'Surclassement',
            'gift' => 'Cadeau',
            'service' => 'Service gratuit',
            default => $this->type,
        };
    }
}
