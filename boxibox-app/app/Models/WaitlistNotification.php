<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaitlistNotification extends Model
{
    protected $fillable = [
        'waitlist_entry_id',
        'box_id',
        'channel',
        'status',
        'message_id',
        'sent_at',
        'clicked_at',
        'expires_at',
        'metadata',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'clicked_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    // Relationships
    public function waitlistEntry(): BelongsTo
    {
        return $this->belongsTo(WaitlistEntry::class);
    }

    public function box(): BelongsTo
    {
        return $this->belongsTo(Box::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    // Actions
    public function markSent(string $messageId = null): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'message_id' => $messageId,
        ]);
    }

    public function markClicked(): void
    {
        $this->update([
            'status' => 'clicked',
            'clicked_at' => now(),
        ]);
    }

    public function markConverted(): void
    {
        $this->update(['status' => 'converted']);
    }

    public function markFailed(): void
    {
        $this->update(['status' => 'failed']);
    }
}
