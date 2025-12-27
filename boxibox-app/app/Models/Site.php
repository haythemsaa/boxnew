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
        'is_active',
        'self_service_enabled',
        'gate_system_type',
        'gate_api_endpoint',
        'gate_api_key',
        'access_hours',
        // Insurance auto-enrollment
        'insurance_auto_enroll',
        'insurance_required',
        'default_insurance_plan_id',
        'insurance_min_coverage',
        'insurance_auto_enroll_message',
    ];

    protected $casts = [
        'opening_hours' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'occupation_rate' => 'decimal:2',
        'total_boxes' => 'integer',
        'is_active' => 'boolean',
        'self_service_enabled' => 'boolean',
        'access_hours' => 'array',
        'deleted_at' => 'datetime',
        // Insurance
        'insurance_auto_enroll' => 'boolean',
        'insurance_required' => 'boolean',
        'insurance_min_coverage' => 'decimal:2',
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

    public function media(): HasMany
    {
        return $this->hasMany(SiteMedia::class);
    }

    public function defaultInsurancePlan(): BelongsTo
    {
        return $this->belongsTo(InsurancePlan::class, 'default_insurance_plan_id');
    }

    public function insurancePlans(): HasMany
    {
        return $this->hasMany(InsurancePlan::class)
            ->orWhereNull('site_id') // Include global plans
            ->where('is_active', true);
    }

    /**
     * Check if insurance auto-enrollment is enabled for this site.
     */
    public function hasAutoEnrollInsurance(): bool
    {
        return $this->insurance_auto_enroll && $this->default_insurance_plan_id;
    }

    /**
     * Get the insurance plan for auto-enrollment.
     */
    public function getAutoEnrollInsurancePlan(): ?InsurancePlan
    {
        if (!$this->hasAutoEnrollInsurance()) {
            return null;
        }

        return $this->defaultInsurancePlan;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    // Helper Methods
    /**
     * Update occupation rate using a single optimized query
     */
    public function updateOccupationRate(): void
    {
        // Single query instead of 2 separate queries
        $stats = $this->boxes()
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied,
                SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available
            ")
            ->first();

        $totalBoxes = $stats->total ?? 0;
        $occupiedBoxes = $stats->occupied ?? 0;
        $availableBoxes = $stats->available ?? 0;

        $occupationRate = $totalBoxes > 0 ? ($occupiedBoxes / $totalBoxes) * 100 : 0;

        $updateData = [
            'occupation_rate' => $occupationRate,
            'total_boxes' => $totalBoxes,
        ];

        // Only update if columns exist
        if (\Schema::hasColumn('sites', 'occupied_capacity')) {
            $updateData['occupied_capacity'] = $occupiedBoxes;
        }
        if (\Schema::hasColumn('sites', 'available_capacity')) {
            $updateData['available_capacity'] = $availableBoxes;
        }

        $this->update($updateData);
    }

    /**
     * Update statistics using optimized queries
     */
    public function updateStatistics(): void
    {
        // Get all stats in a single subquery
        $buildingCount = $this->buildings()->count();
        $floorCount = Floor::whereIn('building_id', $this->buildings()->select('id'))->count();
        $boxCount = $this->boxes()->count();

        $updateData = ['total_boxes' => $boxCount];

        if (\Schema::hasColumn('sites', 'total_buildings')) {
            $updateData['total_buildings'] = $buildingCount;
        }
        if (\Schema::hasColumn('sites', 'total_floors')) {
            $updateData['total_floors'] = $floorCount;
        }

        $this->update($updateData);
    }
}
