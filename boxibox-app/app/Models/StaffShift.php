<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffShift extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'site_id',
        'shift_date',
        'start_time',
        'end_time',
        'type',
        'status',
        'notes',
    ];

    protected $casts = [
        'shift_date' => 'date',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function staffProfile(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class, 'user_id', 'user_id');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function getDurationHoursAttribute(): float
    {
        if (!$this->start_time || !$this->end_time) {
            return 0;
        }
        $start = \Carbon\Carbon::parse($this->shift_date->format('Y-m-d') . ' ' . $this->start_time);
        $end = \Carbon\Carbon::parse($this->shift_date->format('Y-m-d') . ' ' . $this->end_time);
        return round($start->diffInMinutes($end) / 60, 2);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('shift_date', $date);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('shift_date', '>=', now()->toDateString());
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
