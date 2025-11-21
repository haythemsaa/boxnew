<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = [
        'tenant_id',
        'sender_id',
        'recipient_id',
        'parent_id',
        'thread_id',
        'subject',
        'body',
        'attachments',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'parent_id');
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('recipient_id', $userId);
    }

    public function scopeByThread($query, string $threadId)
    {
        return $query->where('thread_id', $threadId)
            ->orderBy('created_at', 'asc');
    }

    // Helper Methods
    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function reply(int $senderId, string $body, array $attachments = []): self
    {
        return self::create([
            'tenant_id' => $this->tenant_id,
            'sender_id' => $senderId,
            'recipient_id' => $this->sender_id,
            'parent_id' => $this->id,
            'thread_id' => $this->thread_id,
            'subject' => 'Re: ' . $this->subject,
            'body' => $body,
            'attachments' => $attachments,
        ]);
    }
}
