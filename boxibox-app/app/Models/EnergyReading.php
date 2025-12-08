<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnergyReading extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'site_id',
        'reading_date',
        'electricity_kwh',
        'gas_m3',
        'water_m3',
        'solar_generated_kwh',
        'electricity_cost',
        'gas_cost',
        'water_cost',
        'source',
        'metadata',
    ];

    protected $casts = [
        'reading_date' => 'date',
        'electricity_kwh' => 'decimal:2',
        'gas_m3' => 'decimal:2',
        'water_m3' => 'decimal:2',
        'solar_generated_kwh' => 'decimal:2',
        'electricity_cost' => 'decimal:2',
        'gas_cost' => 'decimal:2',
        'water_cost' => 'decimal:2',
        'metadata' => 'array',
    ];

    // CO2 emission factors (kg CO2 per unit)
    public const CO2_FACTORS = [
        'electricity_kwh' => 0.233, // Average EU grid
        'gas_m3' => 2.0,           // Natural gas
        'water_m3' => 0.344,       // Water treatment
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

    // Scopes
    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('reading_date', [$startDate, $endDate]);
    }

    public function scopeForYear($query, $year)
    {
        return $query->whereYear('reading_date', $year);
    }

    public function scopeForMonth($query, $year, $month)
    {
        return $query->whereYear('reading_date', $year)
            ->whereMonth('reading_date', $month);
    }

    // Accessors
    public function getTotalCostAttribute(): float
    {
        return ($this->electricity_cost ?? 0) +
               ($this->gas_cost ?? 0) +
               ($this->water_cost ?? 0);
    }

    public function getNetElectricityKwhAttribute(): float
    {
        return max(0, $this->electricity_kwh - $this->solar_generated_kwh);
    }

    public function getCo2EmissionsKgAttribute(): float
    {
        return ($this->net_electricity_kwh * self::CO2_FACTORS['electricity_kwh']) +
               ($this->gas_m3 * self::CO2_FACTORS['gas_m3']) +
               ($this->water_m3 * self::CO2_FACTORS['water_m3']);
    }
}
