<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Building extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'site_id',
        'name',
        'code',
        'type',
        'description',
        'floor_count',
        'total_floors',
        'total_boxes',
        'has_elevator',
        'has_security',
        'has_cctv',
        'status',
    ];

    protected $casts = [
        'has_elevator' => 'boolean',
        'has_security' => 'boolean',
        'has_cctv' => 'boolean',
        'floor_count' => 'integer',
        'total_floors' => 'integer',
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

    public function floors(): HasMany
    {
        return $this->hasMany(Floor::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // Helper Methods
    public function updateStatistics(): void
    {
        $totalFloors = $this->floors()->count();
        $totalBoxes = Box::whereHas('floor', function($q) {
            $q->where('building_id', $this->id);
        })->count();

        $this->update([
            'total_floors' => $totalFloors,
            'total_boxes' => $totalBoxes,
        ]);
    }
}
