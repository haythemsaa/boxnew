<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceHistory extends Model
{
    protected $fillable = [
        'tenant_id',
        'box_id',
        'site_id',
        'base_price',
        'calculated_price',
        'final_price',
        'occupancy_rate',
        'season',
        'day_of_week',
        'is_holiday',
        'was_rented',
        'days_to_rent',
        'actual_rent_price',
        'features',
        'predicted_conversion',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'calculated_price' => 'decimal:2',
        'final_price' => 'decimal:2',
        'occupancy_rate' => 'decimal:2',
        'is_holiday' => 'boolean',
        'was_rented' => 'boolean',
        'actual_rent_price' => 'decimal:2',
        'features' => 'array',
        'predicted_conversion' => 'decimal:4',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function box(): BelongsTo
    {
        return $this->belongsTo(Box::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function scopeRented($query)
    {
        return $query->where('was_rented', true);
    }

    public function scopeNotRented($query)
    {
        return $query->where('was_rented', false);
    }

    public function scopeForTraining($query)
    {
        return $query->whereNotNull('was_rented')
            ->where('created_at', '>=', now()->subMonths(12));
    }
}
