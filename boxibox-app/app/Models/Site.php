<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'address',
        'city',
        'postal_code',
        'country',
        'latitude',
        'longitude',
        'opening_hours',
        'occupation_rate',
        'total_boxes',
        'phone',
        'email',
    ];

    protected $casts = [
        'opening_hours' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'occupation_rate' => 'decimal:2',
        'total_boxes' => 'integer',
        'is_active' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function buildings(): HasMany
    {
        return $this->hasMany(Building::class);
    }

    public function boxes(): HasMany
    {
        return $this->hasMany(Box::class);
    }

    public function floorPlans(): HasMany
    {
        return $this->hasMany(FloorPlan::class);
    }

    public function pricingRules(): HasMany
    {
        return $this->hasMany(PricingRule::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    // Helper Methods
    public function updateOccupationRate(): void
    {
        $totalBoxes = $this->boxes()->count();
        $occupiedBoxes = $this->boxes()->where('status', 'occupied')->count();

        $occupationRate = $totalBoxes > 0 ? ($occupiedBoxes / $totalBoxes) * 100 : 0;

        $this->update([
            'occupation_rate' => $occupationRate,
            'total_boxes' => $totalBoxes,
            'occupied_capacity' => $occupiedBoxes,
            'available_capacity' => $totalBoxes - $occupiedBoxes,
        ]);
    }

    public function updateStatistics(): void
    {
        $this->update([
            'total_buildings' => $this->buildings()->count(),
            'total_floors' => Floor::whereHas('building', function($q) {
                $q->where('site_id', $this->id);
            })->count(),
            'total_boxes' => $this->boxes()->count(),
        ]);
    }
}
