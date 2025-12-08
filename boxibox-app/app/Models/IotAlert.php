<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IotAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'sensor_id',
        'reading_id',
        'box_id',
        'site_id',
        'alert_type',
        'severity',
        'title',
        'message',
        'trigger_value',
        'threshold_value',
        'status',
        'acknowledged_by',
        'acknowledged_at',
        'resolved_by',
        'resolved_at',
        'resolution_notes',
        'notification_sent',
        'notification_channels',
        'notification_sent_at',
    ];

    protected $casts = [
        'trigger_value' => 'float',
        'threshold_value' => 'float',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
        'notification_sent' => 'boolean',
        'notification_channels' => 'array',
        'notification_sent_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function sensor(): BelongsTo
    {
        return $this->belongsTo(IotSensor::class, 'sensor_id');
    }

    public function reading(): BelongsTo
    {
        return $this->belongsTo(IotReading::class, 'reading_id');
    }

    public function box(): BelongsTo
    {
        return $this->belongsTo(Box::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function isAcknowledged(): bool
    {
        return $this->acknowledged_at !== null;
    }

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeUnacknowledged($query)
    {
        return $query->whereNull('acknowledged_at');
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    public function scopeWarning($query)
    {
        return $query->where('severity', 'warning');
    }
}
