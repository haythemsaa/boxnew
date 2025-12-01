<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'avatar_url',
        'welcome_message',
        'system_prompt',
        'primary_color',
        'position',
        'show_on_mobile',
        'can_book',
        'can_quote',
        'can_check_availability',
        'can_answer_faq',
        'can_transfer_to_human',
        'human_availability',
        'transfer_email',
        'transfer_phone',
        'languages',
        'default_language',
        'whatsapp_enabled',
        'whatsapp_number',
        'whatsapp_api_key',
        'is_active',
    ];

    protected $casts = [
        'show_on_mobile' => 'boolean',
        'can_book' => 'boolean',
        'can_quote' => 'boolean',
        'can_check_availability' => 'boolean',
        'can_answer_faq' => 'boolean',
        'can_transfer_to_human' => 'boolean',
        'human_availability' => 'array',
        'languages' => 'array',
        'whatsapp_enabled' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
