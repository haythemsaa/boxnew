<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'sender_type',
        'agent_id',
        'content',
        'content_type',
        'attachments',
        'quick_replies',
        'buttons',
        'intent_detected',
        'confidence_score',
        'knowledge_base_id',
        'is_read',
    ];

    protected $casts = [
        'attachments' => 'array',
        'quick_replies' => 'array',
        'buttons' => 'array',
        'confidence_score' => 'decimal:2',
        'is_read' => 'boolean',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatbotConversation::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function knowledgeBase(): BelongsTo
    {
        return $this->belongsTo(ChatbotKnowledgeBase::class);
    }
}
