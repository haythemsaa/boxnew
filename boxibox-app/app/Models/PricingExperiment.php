<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PricingExperiment extends Model
{
    protected $fillable = [
        'tenant_id',
        'site_id',
        'name',
        'description',
        'status',
        'variants',
        'traffic_percentage',
        'min_sample_size',
        'confidence_level',
        'started_at',
        'ended_at',
        'duration_days',
        'results',
        'winning_variant',
        'revenue_lift',
    ];

    protected $casts = [
        'variants' => 'array',
        'results' => 'array',
        'traffic_percentage' => 'decimal:2',
        'confidence_level' => 'decimal:2',
        'revenue_lift' => 'decimal:2',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function exposures(): HasMany
    {
        return $this->hasMany(ExperimentExposure::class, 'experiment_id');
    }

    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function start(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    public function pause(): void
    {
        $this->update(['status' => 'paused']);
    }

    public function complete(array $results): void
    {
        $this->update([
            'status' => 'completed',
            'ended_at' => now(),
            'results' => $results,
        ]);
    }

    public function getVariantForVisitor(string $visitorId): array
    {
        // Deterministic assignment based on visitor ID
        $hash = crc32($visitorId . $this->id);
        $bucket = $hash % 100;

        $cumulative = 0;
        foreach ($this->variants as $variant) {
            $cumulative += $variant['weight'];
            if ($bucket < $cumulative) {
                return $variant;
            }
        }

        return $this->variants[0];
    }

    public function calculateResults(): array
    {
        $results = [];

        foreach ($this->variants as $variant) {
            $exposures = $this->exposures()
                ->where('variant_name', $variant['name'])
                ->get();

            $total = $exposures->count();
            $conversions = $exposures->where('converted', true)->count();
            $revenue = $exposures->sum('revenue') ?? 0;

            $results[$variant['name']] = [
                'exposures' => $total,
                'conversions' => $conversions,
                'conversion_rate' => $total > 0 ? round($conversions / $total * 100, 2) : 0,
                'total_revenue' => $revenue,
                'avg_revenue_per_conversion' => $conversions > 0 ? round($revenue / $conversions, 2) : 0,
            ];
        }

        return $results;
    }

    public function determineWinner(): ?string
    {
        $results = $this->calculateResults();
        $control = $results['control'] ?? null;

        if (!$control) {
            return null;
        }

        $bestVariant = 'control';
        $bestRevenue = $control['total_revenue'];

        foreach ($results as $name => $data) {
            if ($name !== 'control' && $data['total_revenue'] > $bestRevenue) {
                // Check statistical significance
                if ($this->isStatisticallySignificant($control, $data)) {
                    $bestVariant = $name;
                    $bestRevenue = $data['total_revenue'];
                }
            }
        }

        return $bestVariant;
    }

    protected function isStatisticallySignificant(array $control, array $variant): bool
    {
        // Simple Z-test for conversion rates
        $n1 = $control['exposures'];
        $n2 = $variant['exposures'];

        if ($n1 < $this->min_sample_size || $n2 < $this->min_sample_size) {
            return false;
        }

        $p1 = $control['conversion_rate'] / 100;
        $p2 = $variant['conversion_rate'] / 100;

        $pooledP = ($p1 * $n1 + $p2 * $n2) / ($n1 + $n2);
        $se = sqrt($pooledP * (1 - $pooledP) * (1/$n1 + 1/$n2));

        if ($se == 0) {
            return false;
        }

        $z = abs($p2 - $p1) / $se;

        // 95% confidence = z > 1.96
        $confidenceThreshold = match(true) {
            $this->confidence_level >= 99 => 2.576,
            $this->confidence_level >= 95 => 1.96,
            $this->confidence_level >= 90 => 1.645,
            default => 1.96,
        };

        return $z > $confidenceThreshold;
    }
}
