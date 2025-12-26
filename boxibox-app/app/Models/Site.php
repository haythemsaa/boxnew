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
