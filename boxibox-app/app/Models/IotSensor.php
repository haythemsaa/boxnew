<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IotSensor extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'box_id',
        'sensor_type_id',
        'name',
        'serial_number',
        'status',
        'battery_level',
        'last_reading_at',
        'last_value',
        'metadata',
    ];

    protected $casts = [
        'last_reading_at' => 'datetime',
        'battery_level' => 'integer',
        'last_value' => 'float',
        'metadata' => 'array',
        'latitude' => 'decimal:2',
        'longitude' => 'decimal:2',
        'alert_min' => 'decimal:2',
        'alert_max' => 'decimal:2',
        'alerts_enabled' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function box(): BelongsTo
    {
        return $this->belongsTo(Box::class);
    }

    public function sensorType(): BelongsTo
    {
        return $this->belongsTo(IotSensorType::class, 'sensor_type_id');
    }

    public function readings(): HasMany
    {
        return $this->hasMany(IotSensorReading::class, 'sensor_id');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(IotAlert::class, 'sensor_id');
    }

    public function isOnline(): bool
    {
        return $this->status === 'online';
    }

    public function isInAlert(): bool
    {
        if (!$this->alert_enabled || $this->last_value === null) {
            return false;
        }

        if ($this->min_threshold !== null && $this->last_value < $this->min_threshold) {
            return true;
        }

        if ($this->max_threshold !== null && $this->last_value > $this->max_threshold) {
            return true;
        }

        return false;
    }

    public function scopeOnline($query)
    {
        return $query->where('status', 'online');
    }

    public function scopeOffline($query)
    {
        return $query->where('status', 'offline');
    }

    public function scopeInAlert($query)
    {
        return $query->where('status', 'alert');
    }
}
