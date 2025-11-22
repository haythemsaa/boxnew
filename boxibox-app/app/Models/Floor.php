<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Floor extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'site_id',
        'building_id',
        'floor_number',
        'name',
        'description',
        'total_area',
        'total_boxes',
        'floor_plan_id',
    ];

    protected $casts = [
        'floor_number' => 'integer',
        'total_area' => 'decimal:2',
        'total_boxes' => 'integer',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function boxes(): HasMany
    {
        return $this->hasMany(Box::class);
    }

    public function floorPlan(): BelongsTo
    {
        return $this->belongsTo(FloorPlan::class);
    }

    // Scopes
    public function scopeByBuilding($query, int $buildingId)
    {
        return $query->where('building_id', $buildingId);
    }

    public function scopeOrderByFloor($query)
    {
        return $query->orderBy('floor_number', 'asc');
    }

    // Helper Methods
    public function updateStatistics(): void
    {
        $this->update([
            'total_boxes' => $this->boxes()->count(),
        ]);
    }

    public function getFloorLabelAttribute(): string
    {
        if ($this->floor_number == 0) {
            return 'Ground Floor';
        } elseif ($this->floor_number < 0) {
            return 'Basement ' . abs($this->floor_number);
        } else {
            return 'Floor ' . $this->floor_number;
        }
    }
}
