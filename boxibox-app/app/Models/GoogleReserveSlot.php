<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoogleReserveSlot extends Model
{
    protected $table = 'google_reserve_slots';

    protected $fillable = [
        'tenant_id',
        'site_id',
        'date',
        'start_time',
        'end_time',
        'capacity',
        'booked',
        'is_available',
        'price',
        'service_type',
        'metadata',
    ];

    protected $casts = [
        'date' => 'date',
        'capacity' => 'integer',
        'booked' => 'integer',
        'is_available' => 'boolean',
        'price' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function getAvailableCapacityAttribute(): int
    {
        return max(0, $this->capacity - $this->booked);
    }

    public function hasAvailability(): bool
    {
        return $this->is_available && $this->available_capacity > 0;
    }

    public function book(): bool
    {
        if (!$this->hasAvailability()) {
            return false;
        }

        $this->increment('booked');
        return true;
    }
}
