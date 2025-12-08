<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IotHub extends Model
{
    use HasFactory;

    protected $table = 'iot_hubs';

    protected $fillable = [
        'tenant_id',
        'site_id',
        'name',
        'serial_number',
        'model',
        'manufacturer',
        'ip_address',
        'mac_address',
        'firmware_version',
        'connection_type',
        'status',
        'last_seen_at',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
        'last_seen_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function sensors(): HasMany
    {
        return $this->hasMany(IotSensor::class, 'hub_id');
    }

    public function isOnline(): bool
    {
        return $this->status === 'online' &&
               $this->last_seen_at &&
               $this->last_seen_at->greaterThan(now()->subMinutes(10));
    }
}
