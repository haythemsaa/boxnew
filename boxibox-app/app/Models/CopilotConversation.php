<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CopilotConversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'title',
        'context',
        'is_active',
    ];

    protected $casts = [
        'context' => 'array',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CopilotMessage::class, 'conversation_id');
    }

    public function latestMessages(int $limit = 10): HasMany
    {
        return $this->messages()->latest()->limit($limit);
    }
}
