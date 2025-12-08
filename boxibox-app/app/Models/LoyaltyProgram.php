<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyProgram extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'points_per_euro',
        'points_value_in_euro',
        'minimum_redeem_points',
        'tiers',
        'is_active',
    ];

    protected $casts = [
        'points_per_euro' => 'decimal:2',
        'points_value_in_euro' => 'decimal:4',
        'tiers' => 'array',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customerPoints(): HasMany
    {
        return $this->hasMany(LoyaltyPoints::class, 'program_id');
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(LoyaltyReward::class, 'program_id');
    }

    public function calculatePointsForAmount(float $amount): int
    {
        return (int) floor($amount * $this->points_per_euro);
    }

    public function calculateValueForPoints(int $points): float
    {
        return round($points * $this->points_value_in_euro, 2);
    }

    public function getTierForPoints(int $points): string
    {
        $tiers = $this->tiers ?? [];
        $currentTier = 'bronze';

        foreach ($tiers as $tier) {
            if ($points >= ($tier['min_points'] ?? 0)) {
                $currentTier = $tier['name'] ?? $currentTier;
            }
        }

        return $currentTier;
    }
}
