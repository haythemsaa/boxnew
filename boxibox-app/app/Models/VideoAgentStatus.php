<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoAgentStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'status',
        'current_call_id',
        'status_changed_at',
        'last_activity_at',
    ];

    protected $casts = [
        'status_changed_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    public const STATUSES = [
        'online' => 'En ligne',
        'busy' => 'Occupé',
        'away' => 'Absent',
        'offline' => 'Hors ligne',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function currentCall(): BelongsTo
    {
        return $this->belongsTo(VideoCall::class, 'current_call_id');
    }

    // Scopes
    public function scopeOnline($query)
    {
        return $query->where('status', 'online');
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'online')
            ->whereNull('current_call_id');
    }

    // Methods
    public function goOnline(): bool
    {
        return $this->update([
            'status' => 'online',
            'status_changed_at' => now(),
            'last_activity_at' => now(),
        ]);
    }

    public function goOffline(): bool
    {
        return $this->update([
            'status' => 'offline',
            'status_changed_at' => now(),
            'current_call_id' => null,
        ]);
    }

    public function setAway(): bool
    {
        return $this->update([
            'status' => 'away',
            'status_changed_at' => now(),
        ]);
    }

    public function startCall(VideoCall $call): bool
    {
        return $this->update([
            'status' => 'busy',
            'current_call_id' => $call->id,
            'status_changed_at' => now(),
        ]);
    }

    public function endCall(): bool
    {
        return $this->update([
            'status' => 'online',
            'current_call_id' => null,
            'status_changed_at' => now(),
        ]);
    }

    public function ping(): bool
    {
        return $this->update(['last_activity_at' => now()]);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getIsAvailableAttribute(): bool
    {
        return $this->status === 'online' && !$this->current_call_id;
    }
}
