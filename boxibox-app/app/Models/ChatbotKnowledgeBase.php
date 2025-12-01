<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotKnowledgeBase extends Model
{
    use HasFactory;

    protected $table = 'chatbot_knowledge_base';

    protected $fillable = [
        'tenant_id',
        'category',
        'question',
        'answer',
        'keywords',
        'language',
        'usage_count',
        'helpfulness_score',
        'is_active',
        'order',
    ];

    protected $casts = [
        'keywords' => 'array',
        'is_active' => 'boolean',
        'helpfulness_score' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
