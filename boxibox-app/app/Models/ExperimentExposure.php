<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExperimentExposure extends Model
{
    protected $fillable = [
        'experiment_id',
        'box_id',
        'visitor_id',
        'variant_name',
        'price_shown',
        'converted',
        'revenue',
        'converted_at',
    ];

    protected $casts = [
        'price_shown' => 'decimal:2',
        'converted' => 'boolean',
        'revenue' => 'decimal:2',
        'converted_at' => 'datetime',
    ];

    public function experiment(): BelongsTo
    {
        return $this->belongsTo(PricingExperiment::class, 'experiment_id');
    }

    public function box(): BelongsTo
    {
        return $this->belongsTo(Box::class);
    }

    public function markConverted(float $revenue): void
    {
        $this->update([
            'converted' => true,
            'revenue' => $revenue,
            'converted_at' => now(),
        ]);
    }
}
