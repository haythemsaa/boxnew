<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingAiStrategy extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'site_id',
        'name',
        'description',
        'is_active',
        'strategy_type',
        'min_price_factor',
        'max_price_factor',
        'occupancy_threshold_low',
        'occupancy_threshold_high',
        'price_increase_step',
        'price_decrease_step',
        'seasonal_factors',
        'day_of_week_factors',
        'ml_model_params',
        'last_ml_training',
        'ml_confidence_score',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'min_price_factor' => 'decimal:2',
        'max_price_factor' => 'decimal:2',
        'price_increase_step' => 'decimal:2',
        'price_decrease_step' => 'decimal:2',
        'seasonal_factors' => 'array',
        'day_of_week_factors' => 'array',
        'ml_model_params' => 'array',
        'last_ml_training' => 'datetime',
        'ml_confidence_score' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
