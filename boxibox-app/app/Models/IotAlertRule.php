<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IotAlertRule extends Model
{
    use HasFactory;

    protected $table = 'iot_alert_rules';

    protected $fillable = [
        'tenant_id',
        'sensor_type_id',
        'name',
        'condition',
        'severity',
        'notification_channels',
        'cooldown_minutes',
        'is_active',
    ];

    protected $casts = [
        'notification_channels' => 'array',
        'is_active' => 'boolean',
        'threshold_value' => 'decimal:2',
        'threshold_value_2' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function sensorType(): BelongsTo
    {
        return $this->belongsTo(IotSensorType::class, 'sensor_type_id');
    }
}
