<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetitorPrice extends Model
{
    protected $fillable = [
        'tenant_id',
        'site_id',
        'competitor_name',
        'competitor_location',
        'distance_km',
        'box_category',
        'box_size_m2',
        'monthly_price',
        'weekly_price',
        'has_promotion',
        'promotion_details',
        'source',
        'collected_at',
    ];

    protected $casts = [
        'distance_km' => 'decimal:2',
        'box_size_m2' => 'decimal:2',
        'monthly_price' => 'decimal:2',
        'weekly_price' => 'decimal:2',
        'has_promotion' => 'boolean',
        'collected_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function scopeForCategory($query, string $category)
    {
        return $query->where('box_category', $category);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('collected_at', '>=', now()->subDays($days));
    }

    public function scopeNearby($query, float $maxDistance = 20)
    {
        return $query->where('distance_km', '<=', $maxDistance);
    }

    public function getPricePerM2Attribute(): ?float
    {
        if ($this->box_size_m2 > 0) {
            return round($this->monthly_price / $this->box_size_m2, 2);
        }
        return null;
    }

    public static function getAverageByCategory(int $siteId, string $category): ?float
    {
        return static::where('site_id', $siteId)
            ->where('box_category', $category)
            ->recent()
            ->avg('monthly_price');
    }

    public static function getCategories(): array
    {
        return ['xs', 'small', 'medium', 'large', 'xl', 'xxl'];
    }

    public static function getCategoryLabel(string $category): string
    {
        return match($category) {
            'xs' => 'Très petit (< 2m²)',
            'small' => 'Petit (2-5m²)',
            'medium' => 'Moyen (5-10m²)',
            'large' => 'Grand (10-20m²)',
            'xl' => 'Très grand (20-30m²)',
            'xxl' => 'XXL (> 30m²)',
            default => $category,
        };
    }
}
