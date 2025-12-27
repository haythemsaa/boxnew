<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteDailyStat extends Model
{
    protected $fillable = [
        'site_id',
        'stat_date',
        'total_boxes',
        'occupied_boxes',
        'available_boxes',
        'occupation_rate',
        'daily_revenue',
        'potential_revenue',
        'lost_revenue',
        'new_contracts',
        'terminated_contracts',
        'active_contracts',
        'iot_alerts_count',
        'avg_temperature',
        'avg_humidity',
        'access_events',
        'unique_visitors',
    ];

    protected $casts = [
        'stat_date' => 'date',
        'occupation_rate' => 'decimal:2',
        'daily_revenue' => 'decimal:2',
        'potential_revenue' => 'decimal:2',
        'lost_revenue' => 'decimal:2',
        'avg_temperature' => 'decimal:2',
        'avg_humidity' => 'decimal:2',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get or create stats for a specific date
     */
    public static function forDate(int $siteId, $date): self
    {
        return self::firstOrCreate(
            [
                'site_id' => $siteId,
                'stat_date' => $date,
            ],
            [
                'total_boxes' => 0,
                'occupied_boxes' => 0,
                'available_boxes' => 0,
            ]
        );
    }
}
