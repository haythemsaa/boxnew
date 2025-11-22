<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'box_id',
        'customer_id',
        'smart_lock_id',
        'access_method',
        'status',
        'user_identifier',
        'reason',
        'accessed_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'accessed_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function box(): BelongsTo
    {
        return $this->belongsTo(Box::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function smartLock(): BelongsTo
    {
        return $this->belongsTo(SmartLock::class);
    }

    public function scopeGranted($query)
    {
        return $query->where('status', 'granted');
    }

    public function scopeDenied($query)
    {
        return $query->where('status', 'denied');
    }

    public function scopeForced($query)
    {
        return $query->where('status', 'forced');
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('accessed_at', '>=', now()->subHours($hours));
    }

    public function scopeSuspicious($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'denied')
                ->orWhere('status', 'forced');
        });
    }
}
