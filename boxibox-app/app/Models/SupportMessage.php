<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportMessage extends Model
{
    protected $fillable = [
        'ticket_id',
        'sender_type',
        'user_id',
        'customer_id',
        'message',
        'attachments',
        'is_internal',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_internal' => 'boolean',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($message) {
            // Update ticket's last_message_at
            $message->ticket->update(['last_message_at' => now()]);
        });
    }

    // Relationships
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // Helpers
    public function getSenderNameAttribute(): string
    {
        if ($this->sender_type === 'user' && $this->user) {
            return $this->user->name;
        }
        if ($this->sender_type === 'customer' && $this->customer) {
            return $this->customer->full_name;
        }
        if ($this->sender_type === 'system') {
            return 'Systeme';
        }
        return 'Inconnu';
    }

    public function getSenderAvatarAttribute(): ?string
    {
        if ($this->sender_type === 'user' && $this->user) {
            return $this->user->avatar_url ?? null;
        }
        return null;
    }

    public function isFromStaff(): bool
    {
        return $this->sender_type === 'user';
    }

    public function isFromCustomer(): bool
    {
        return $this->sender_type === 'customer';
    }
}
