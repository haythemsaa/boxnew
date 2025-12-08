<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IotReading extends Model
{
    use HasFactory;

    protected $table = 'iot_readings';

    protected $fillable = [
        'sensor_id',
        'value',
        'recorded_at',
        'is_anomaly',
        'triggered_alert',
    ];

    protected $casts = [
        'value' => 'float',
        'recorded_at' => 'datetime',
        'is_anomaly' => 'boolean',
        'triggered_alert' => 'boolean',
    ];

    public function sensor(): BelongsTo
    {
        return $this->belongsTo(IotSensor::class, 'sensor_id');
    }
}
