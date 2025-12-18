<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemandForecast extends Model
{
    protected $fillable = [
        'tenant_id',
        'site_id',
        'forecast_date',
        'box_category',
        'predicted_demand',
        'predicted_conversion',
        'confidence_lower',
        'confidence_upper',
        'recommended_price_modifier',
        'factors',
    ];

    protected $casts = [
        'forecast_date' => 'date',
        'predicted_demand' => 'decimal:2',
        'predicted_conversion' => 'decimal:4',
        'confidence_lower' => 'decimal:2',
        'confidence_upper' => 'decimal:2',
        'recommended_price_modifier' => 'decimal:4',
        'factors' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('forecast_date', $date);
    }

    public function scopeForCategory($query, $category)
    {
        return $query->where('box_category', $category);
    }

    public function getConfidenceRangeAttribute(): string
    {
        return "{$this->confidence_lower} - {$this->confidence_upper}";
    }

    public function getDemandLevelAttribute(): string
    {
        if ($this->predicted_demand >= 10) return 'very_high';
        if ($this->predicted_demand >= 5) return 'high';
        if ($this->predicted_demand >= 2) return 'medium';
        return 'low';
    }
}
