<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FloorPlan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'site_id',
        'building_id',
        'floor_id',
        'name',
        'description',
        'version',
        'is_active',
        'width',
        'height',
        'scale',
        'unit',
        'elements',
        'background_image',
        'background_opacity',
        'show_grid',
        'grid_size',
        'total_boxes_on_plan',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'version' => 'integer',
        'is_active' => 'boolean',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'scale' => 'decimal:2',
        'background_opacity' => 'decimal:2',
        'show_grid' => 'boolean',
        'grid_size' => 'integer',
        'total_boxes_on_plan' => 'integer',
        'elements' => 'array',
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

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLatestVersion($query)
    {
        return $query->orderBy('version', 'desc');
    }

    // Helper Methods
    public function createNewVersion(): self
    {
        // Deactivate current version
        $this->update(['is_active' => false]);

        // Create new version
        $newVersion = $this->replicate();
        $newVersion->version = $this->version + 1;
        $newVersion->is_active = true;
        $newVersion->save();

        return $newVersion;
    }

    public function addElement(array $element): void
    {
        $elements = $this->elements ?? [];
        $elements[] = $element;
        $this->update(['elements' => $elements]);
    }

    public function removeElement(int $index): void
    {
        $elements = $this->elements ?? [];

        if (isset($elements[$index])) {
            unset($elements[$index]);
            $this->update(['elements' => array_values($elements)]);
        }
    }

    public function updateElement(int $index, array $element): void
    {
        $elements = $this->elements ?? [];

        if (isset($elements[$index])) {
            $elements[$index] = $element;
            $this->update(['elements' => $elements]);
        }
    }

    public function countBoxes(): int
    {
        $boxCount = 0;

        foreach ($this->elements ?? [] as $element) {
            if (isset($element['type']) && $element['type'] === 'box') {
                $boxCount++;
            }
        }

        return $boxCount;
    }
}
