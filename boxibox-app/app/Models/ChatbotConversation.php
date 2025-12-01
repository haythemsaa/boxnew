<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatbotConversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'session_id',
        'channel',
        'language',
        'visitor_name',
        'visitor_email',
        'visitor_phone',
        'ip_address',
        'user_agent',
        'referrer',
        'status',
        'is_human_takeover',
        'assigned_to',
        'transferred_at',
        'context',
        'current_intent',
        'message_count',
        'satisfaction_score',
        'converted_to_lead',
        'converted_to_booking',
        'last_message_at',
    ];

    protected $casts = [
        'is_human_takeover' => 'boolean',
        'transferred_at' => 'datetime',
        'context' => 'array',
        'converted_to_lead' => 'boolean',
        'converted_to_booking' => 'boolean',
        'last_message_at' => 'datetime',
        'satisfaction_score' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatbotMessage::class, 'conversation_id');
    }
}
