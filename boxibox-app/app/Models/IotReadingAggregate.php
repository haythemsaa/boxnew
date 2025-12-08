<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IotReadingAggregate extends Model
{
    use HasFactory;

    protected $table = 'iot_reading_aggregates';

    protected $fillable = [
        'sensor_id',
        'period',
        'period_start',
        'period_end',
        'min_value',
        'max_value',
        'avg_value',
        'reading_count',
        'anomaly_count',
        'alert_count',
    ];

    protected $casts = [
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'min_value' => 'float',
        'max_value' => 'float',
        'avg_value' => 'float',
    ];

    public function sensor(): BelongsTo
    {
        return $this->belongsTo(IotSensor::class, 'sensor_id');
    }
}
