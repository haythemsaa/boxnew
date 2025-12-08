<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalculatorItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'icon',
        'volume_m3',
        'width_m',
        'height_m',
        'depth_m',
        'is_stackable',
        'order',
        'is_active',
    ];

    protected $casts = [
        'volume_m3' => 'decimal:2',
        'width_m' => 'decimal:2',
        'height_m' => 'decimal:2',
        'depth_m' => 'decimal:2',
        'is_stackable' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(CalculatorCategory::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getFormattedVolumeAttribute(): string
    {
        return number_format($this->volume_m3, 1) . ' m³';
    }

    public function getDimensionsAttribute(): ?string
    {
        if ($this->width_m && $this->height_m && $this->depth_m) {
            return "{$this->width_m}m × {$this->height_m}m × {$this->depth_m}m";
        }
        return null;
    }
}
