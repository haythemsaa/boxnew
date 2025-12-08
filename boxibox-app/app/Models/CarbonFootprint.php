<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarbonFootprint extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'site_id',
        'year',
        'month',
        'electricity_co2_kg',
        'gas_co2_kg',
        'transport_co2_kg',
        'waste_co2_kg',
        'total_co2_kg',
        'offset_co2_kg',
        'net_co2_kg',
        'co2_per_sqm',
        'co2_per_box',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'electricity_co2_kg' => 'decimal:2',
        'gas_co2_kg' => 'decimal:2',
        'transport_co2_kg' => 'decimal:2',
        'waste_co2_kg' => 'decimal:2',
        'total_co2_kg' => 'decimal:2',
        'offset_co2_kg' => 'decimal:2',
        'net_co2_kg' => 'decimal:2',
        'co2_per_sqm' => 'decimal:4',
        'co2_per_box' => 'decimal:4',
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
    public function scopeForYear($query, $year)
    {
        return $query->where('year', $year);
    }

    public function scopeForPeriod($query, $startYear, $startMonth, $endYear, $endMonth)
    {
        return $query->where(function ($q) use ($startYear, $startMonth, $endYear, $endMonth) {
            $q->where(function ($q2) use ($startYear, $startMonth) {
                $q2->where('year', '>', $startYear)
                    ->orWhere(function ($q3) use ($startYear, $startMonth) {
                        $q3->where('year', $startYear)->where('month', '>=', $startMonth);
                    });
            })->where(function ($q2) use ($endYear, $endMonth) {
                $q2->where('year', '<', $endYear)
                    ->orWhere(function ($q3) use ($endYear, $endMonth) {
                        $q3->where('year', $endYear)->where('month', '<=', $endMonth);
                    });
            });
        });
    }

    // Methods
    public static function calculateForMonth(int $tenantId, ?int $siteId, int $year, int $month): self
    {
        $energyReadings = EnergyReading::where('tenant_id', $tenantId)
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->forMonth($year, $month)
            ->get();

        $wasteRecords = WasteRecord::where('tenant_id', $tenantId)
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->whereYear('record_date', $year)
            ->whereMonth('record_date', $month)
            ->get();

        $electricityCo2 = $energyReadings->sum(fn($r) =>
            ($r->electricity_kwh - $r->solar_generated_kwh) * EnergyReading::CO2_FACTORS['electricity_kwh']
        );
        $gasCo2 = $energyReadings->sum(fn($r) => $r->gas_m3 * EnergyReading::CO2_FACTORS['gas_m3']);
        $wasteCo2 = $wasteRecords->sum('general_waste_kg') * 0.5; // 0.5 kg CO2 per kg waste

        $total = max(0, $electricityCo2) + max(0, $gasCo2) + $wasteCo2;

        return static::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'site_id' => $siteId,
                'year' => $year,
                'month' => $month,
            ],
            [
                'electricity_co2_kg' => max(0, $electricityCo2),
                'gas_co2_kg' => max(0, $gasCo2),
                'waste_co2_kg' => $wasteCo2,
                'total_co2_kg' => $total,
                'net_co2_kg' => $total, // Will be adjusted with offsets
            ]
        );
    }

    public function getPeriodLabelAttribute(): string
    {
        $months = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];
        return $months[$this->month - 1] . ' ' . $this->year;
    }
}
