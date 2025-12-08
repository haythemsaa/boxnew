<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SustainabilityGoal extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'metric',
        'baseline_value',
        'target_value',
        'current_value',
        'unit',
        'target_year',
        'description',
        'is_achieved',
        'achieved_at',
    ];

    protected $casts = [
        'baseline_value' => 'decimal:2',
        'target_value' => 'decimal:2',
        'current_value' => 'decimal:2',
        'target_year' => 'integer',
        'is_achieved' => 'boolean',
        'achieved_at' => 'datetime',
    ];

    public const METRICS = [
        'co2_reduction' => 'Réduction CO2',
        'energy_reduction' => 'Réduction énergie',
        'recycling_rate' => 'Taux de recyclage',
        'water_reduction' => 'Réduction eau',
        'renewable_energy' => 'Énergie renouvelable',
        'waste_reduction' => 'Réduction déchets',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_achieved', false)
            ->where('target_year', '>=', now()->year);
    }

    public function scopeAchieved($query)
    {
        return $query->where('is_achieved', true);
    }

    // Accessors
    public function getProgressPercentAttribute(): float
    {
        $totalChange = abs($this->target_value - $this->baseline_value);
        if ($totalChange == 0) return 100;

        $currentChange = abs($this->current_value - $this->baseline_value);
        return min(100, round(($currentChange / $totalChange) * 100, 1));
    }

    public function getRemainingAttribute(): float
    {
        return abs($this->target_value - $this->current_value);
    }

    public function getMetricLabelAttribute(): string
    {
        return self::METRICS[$this->metric] ?? $this->metric;
    }

    public function getIsOnTrackAttribute(): bool
    {
        $yearsTotal = $this->target_year - now()->year + 1;
        $yearsPassed = now()->year - ($this->target_year - $yearsTotal);
        $expectedProgress = min(100, ($yearsPassed / $yearsTotal) * 100);

        return $this->progress_percent >= $expectedProgress * 0.9; // 90% tolerance
    }

    // Methods
    public function checkAndMarkAchieved(): bool
    {
        $achieved = false;

        // Check if goal is achieved based on direction
        if ($this->target_value < $this->baseline_value) {
            // Reduction goal
            $achieved = $this->current_value <= $this->target_value;
        } else {
            // Increase goal
            $achieved = $this->current_value >= $this->target_value;
        }

        if ($achieved && !$this->is_achieved) {
            $this->update([
                'is_achieved' => true,
                'achieved_at' => now(),
            ]);
        }

        return $achieved;
    }
}
