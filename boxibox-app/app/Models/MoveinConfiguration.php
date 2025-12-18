<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MoveinConfiguration extends Model
{
    protected $fillable = [
        'tenant_id',
        'enabled',
        'require_identity_verification',
        'require_selfie',
        'allow_video_verification',
        'require_document_scan',
        'enable_digital_signature',
        'require_initials',
        'contract_template_id',
        'require_upfront_payment',
        'allow_sepa_mandate',
        'allow_card_payment',
        'deposit_months',
        'access_code_validity_hours',
        'access_code_max_uses',
        'send_access_code_sms',
        'send_access_code_email',
        'enable_qr_code',
        'notify_staff_on_completion',
        'notify_customer_reminders',
        'reminder_hours_before_expiry',
        'session_expiry_hours',
        'max_retry_attempts',
        'welcome_message',
        'completion_message',
        'custom_steps',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'require_identity_verification' => 'boolean',
        'require_selfie' => 'boolean',
        'allow_video_verification' => 'boolean',
        'require_document_scan' => 'boolean',
        'enable_digital_signature' => 'boolean',
        'require_initials' => 'boolean',
        'require_upfront_payment' => 'boolean',
        'allow_sepa_mandate' => 'boolean',
        'allow_card_payment' => 'boolean',
        'send_access_code_sms' => 'boolean',
        'send_access_code_email' => 'boolean',
        'enable_qr_code' => 'boolean',
        'notify_staff_on_completion' => 'boolean',
        'notify_customer_reminders' => 'boolean',
        'welcome_message' => 'array',
        'completion_message' => 'array',
        'custom_steps' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public static function getOrCreateForTenant(int $tenantId): self
    {
        return self::firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'enabled' => true,
                'require_identity_verification' => true,
                'require_selfie' => false,
                'allow_video_verification' => false,
                'require_document_scan' => true,
                'enable_digital_signature' => true,
                'require_initials' => false,
                'require_upfront_payment' => true,
                'allow_sepa_mandate' => true,
                'allow_card_payment' => true,
                'deposit_months' => 1,
                'access_code_validity_hours' => 48,
                'access_code_max_uses' => 3,
                'send_access_code_sms' => true,
                'send_access_code_email' => true,
                'enable_qr_code' => true,
                'notify_staff_on_completion' => true,
                'notify_customer_reminders' => true,
                'reminder_hours_before_expiry' => 24,
                'session_expiry_hours' => 72,
                'max_retry_attempts' => 3,
            ]
        );
    }

    public function getWelcomeMessageForLanguage(string $lang = 'fr'): ?string
    {
        return $this->welcome_message[$lang] ?? $this->welcome_message['fr'] ?? null;
    }

    public function getCompletionMessageForLanguage(string $lang = 'fr'): ?string
    {
        return $this->completion_message[$lang] ?? $this->completion_message['fr'] ?? null;
    }
}
