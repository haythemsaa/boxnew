<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'type',
        'title',
        'message',
        'channels',
        'data',
        'is_sent',
        'sent_at',
        'scheduled_for',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'channels' => 'array',
        'data' => 'array',
        'is_sent' => 'boolean',
        'is_read' => 'boolean',
        'sent_at' => 'datetime',
        'scheduled_for' => 'datetime',
        'read_at' => 'datetime',
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

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeUnsent($query)
    {
        return $query->where('is_sent', false);
    }

    public function scopePending($query)
    {
        return $query->where('is_sent', false)
            ->where(function($q) {
                $q->whereNull('scheduled_for')
                    ->orWhere('scheduled_for', '<=', now());
            });
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Helper Methods
    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function markAsSent(): void
    {
        $this->update([
            'is_sent' => true,
            'sent_at' => now(),
        ]);
    }

    public function shouldSendViaEmail(): bool
    {
        return in_array('email', $this->channels);
    }

    public function shouldSendViaSms(): bool
    {
        return in_array('sms', $this->channels);
    }

    public function shouldSendInApp(): bool
    {
        return in_array('in_app', $this->channels);
    }
}
