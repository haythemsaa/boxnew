<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CopilotMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'actions',
        'context',
        'intent',
        'feedback',
    ];

    protected $casts = [
        'actions' => 'array',
        'context' => 'array',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(CopilotConversation::class, 'conversation_id');
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    public function isAssistant(): bool
    {
        return $this->role === 'assistant';
    }
}
