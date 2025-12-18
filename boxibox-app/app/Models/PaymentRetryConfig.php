<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentRetryConfig extends Model
{
    protected $fillable = [
        'tenant_id',
        'max_retries',
        'retry_intervals',
        'retry_times',
        'use_smart_timing',
        'avoid_weekends',
        'avoid_holidays',
        'notify_customer_before',
        'notify_hours_before',
        'notify_customer_after_failure',
        'notify_customer_after_success',
        'notify_admin_after_all_failures',
        'allow_card_update',
        'card_update_link_expiry_hours',
        'final_failure_action',
        'grace_period_days',
        'escalation_messages',
        'is_active',
    ];

    protected $casts = [
        'retry_intervals' => 'array',
        'retry_times' => 'array',
        'escalation_messages' => 'array',
        'use_smart_timing' => 'boolean',
        'avoid_weekends' => 'boolean',
        'avoid_holidays' => 'boolean',
        'notify_customer_before' => 'boolean',
        'notify_customer_after_failure' => 'boolean',
        'notify_customer_after_success' => 'boolean',
        'notify_admin_after_all_failures' => 'boolean',
        'allow_card_update' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'max_retries' => 4,
        'retry_intervals' => '[1, 3, 7, 14]',
        'retry_times' => '["09:00", "14:00", "18:00"]',
        'use_smart_timing' => true,
        'avoid_weekends' => true,
        'avoid_holidays' => true,
        'notify_customer_before' => true,
        'notify_hours_before' => 24,
        'notify_customer_after_failure' => true,
        'notify_customer_after_success' => true,
        'notify_admin_after_all_failures' => true,
        'allow_card_update' => true,
        'card_update_link_expiry_hours' => 72,
        'final_failure_action' => 'suspend',
        'grace_period_days' => 7,
        'is_active' => true,
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public static function getOrCreateForTenant(int $tenantId): self
    {
        return static::firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'retry_intervals' => [1, 3, 7, 14],
                'retry_times' => ['09:00', '14:00', '18:00'],
                'escalation_messages' => self::getDefaultEscalationMessages(),
            ]
        );
    }

    public static function getDefaultEscalationMessages(): array
    {
        return [
            1 => [
                'subject' => 'Echec de paiement - Action requise',
                'body' => 'Nous n\'avons pas pu debiter votre carte pour votre facture. Nous reessaierons automatiquement.',
            ],
            2 => [
                'subject' => 'Deuxieme tentative de paiement echouee',
                'body' => 'Une nouvelle tentative de paiement a echoue. Veuillez verifier votre moyen de paiement.',
            ],
            3 => [
                'subject' => 'Urgent - Mise a jour de paiement requise',
                'body' => 'Plusieurs tentatives de paiement ont echoue. Mettez a jour votre carte pour eviter la suspension.',
            ],
            4 => [
                'subject' => 'Derniere tentative - Risque de suspension',
                'body' => 'C\'est notre derniere tentative. Sans paiement, votre acces sera suspendu.',
            ],
        ];
    }

    public function getRetryIntervalForAttempt(int $attemptNumber): int
    {
        $intervals = $this->retry_intervals;
        $index = min($attemptNumber - 1, count($intervals) - 1);
        return $intervals[$index] ?? 7;
    }

    public function getEscalationMessageForAttempt(int $attemptNumber): ?array
    {
        $messages = $this->escalation_messages ?? self::getDefaultEscalationMessages();
        return $messages[$attemptNumber] ?? null;
    }
}
