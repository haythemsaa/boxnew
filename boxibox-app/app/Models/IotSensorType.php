<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IotSensorType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'unit',
        'icon',
        'min_value',
        'max_value',
    ];

    protected $casts = [
        'min_value' => 'float',
        'max_value' => 'float',
        'default_alert_min' => 'decimal:2',
        'default_alert_max' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function sensors(): HasMany
    {
        return $this->hasMany(IotSensor::class, 'sensor_type_id');
    }
}
