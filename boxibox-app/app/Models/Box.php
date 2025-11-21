<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Box extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'site_id',
        'building_id',
        'floor_id',
        'name',
        'code',
        'description',
        'length',
        'width',
        'height',
        'volume',
        'status',
        'base_price',
        'current_price',
        'climate_controlled',
        'has_electricity',
        'has_alarm',
        'has_24_7_access',
        'has_wifi',
        'has_shelving',
        'is_ground_floor',
        'position',
        'access_code',
        'notes',
    ];

    protected $casts = [
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'volume' => 'decimal:2',
        'base_price' => 'decimal:2',
        'current_price' => 'decimal:2',
        'climate_controlled' => 'boolean',
        'has_electricity' => 'boolean',
        'has_alarm' => 'boolean',
        'has_24_7_access' => 'boolean',
        'has_wifi' => 'boolean',
        'has_shelving' => 'boolean',
        'is_ground_floor' => 'boolean',
        'position' => 'array',
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

    public function contract(): HasOne
    {
        return $this->hasOne(Contract::class)->where('status', 'active');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeOccupied($query)
    {
        return $query->where('status', 'occupied');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    // Accessors
    public function getIsAvailableAttribute(): bool
    {
        return $this->status === 'available';
    }

    public function getFormattedVolumeAttribute(): string
    {
        return number_format($this->volume, 2) . ' m³';
    }

    public function getFormattedDimensionsAttribute(): string
    {
        return "{$this->length}m × {$this->width}m × {$this->height}m";
    }

    // Helper Methods
    public function calculateVolume(): float
    {
        return $this->length * $this->width * $this->height;
    }

    public function updatePrice(): void
    {
        // Apply pricing rules to calculate current_price from base_price
        $price = $this->base_price;

        // Get applicable pricing rules
        $rules = PricingRule::where('tenant_id', $this->tenant_id)
            ->where('is_active', true)
            ->orderBy('priority', 'desc')
            ->get();

        foreach ($rules as $rule) {
            // Apply rule logic here based on rule type and conditions
            if ($rule->adjustment_type === 'percentage') {
                $price += ($price * ($rule->adjustment_value / 100));
            } else {
                $price += $rule->adjustment_value;
            }

            // Apply min/max constraints
            if ($rule->min_price && $price < $rule->min_price) {
                $price = $rule->min_price;
            }
            if ($rule->max_price && $price > $rule->max_price) {
                $price = $rule->max_price;
            }

            if (!$rule->stackable) {
                break;
            }
        }

        $this->update(['current_price' => $price]);
    }

    public function makeAvailable(): void
    {
        $this->update(['status' => 'available']);
    }

    public function makeOccupied(): void
    {
        $this->update(['status' => 'occupied']);
    }
}
