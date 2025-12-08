<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoCallInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'video_call_id',
        'token',
        'email',
        'phone',
        'status',
        'sent_at',
        'opened_at',
        'joined_at',
        'expires_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'opened_at' => 'datetime',
        'joined_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    // Relationships
    public function videoCall(): BelongsTo
    {
        return $this->belongsTo(VideoCall::class);
    }

    // Scopes
    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now())
            ->whereNotIn('status', ['joined', 'expired']);
    }

    // Accessors
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at->isPast();
    }

    public function getIsValidAttribute(): bool
    {
        return !$this->is_expired && !in_array($this->status, ['joined', 'expired']);
    }

    public function getJoinUrlAttribute(): string
    {
        return route('video.guest.join', ['token' => $this->token]);
    }

    // Methods
    public function markAsSent(): bool
    {
        return $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function markAsOpened(): bool
    {
        return $this->update([
            'status' => 'opened',
            'opened_at' => now(),
        ]);
    }

    public function markAsJoined(): bool
    {
        return $this->update([
            'status' => 'joined',
            'joined_at' => now(),
        ]);
    }
}
