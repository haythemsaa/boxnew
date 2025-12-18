<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Suivi de consommation mensuelle d'un tenant
 */
class TenantUsage extends Model
{
    protected $table = 'tenant_usage';

    protected $fillable = [
        'tenant_id',
        'period_start',
        'period_end',
        'emails_sent',
        'emails_quota',
        'emails_from_credits',
        'sms_sent',
        'sms_quota',
        'sms_from_credits',
        'emails_opened',
        'emails_clicked',
        'sms_delivered',
        'sms_replied',
        'overage_cost',
        'overage_billed',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'emails_sent' => 'integer',
        'emails_quota' => 'integer',
        'emails_from_credits' => 'integer',
        'sms_sent' => 'integer',
        'sms_quota' => 'integer',
        'sms_from_credits' => 'integer',
        'emails_opened' => 'integer',
        'emails_clicked' => 'integer',
        'sms_delivered' => 'integer',
        'sms_replied' => 'integer',
        'overage_cost' => 'decimal:2',
        'overage_billed' => 'boolean',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // Accessors
    public function getEmailsRemainingAttribute(): int
    {
        return max(0, $this->emails_quota - $this->emails_sent);
    }

    public function getSmsRemainingAttribute(): int
    {
        return max(0, $this->sms_quota - $this->sms_sent);
    }

    public function getEmailUsagePercentAttribute(): float
    {
        if ($this->emails_quota == 0) return 0;
        return min(100, round(($this->emails_sent / $this->emails_quota) * 100, 1));
    }

    public function getSmsUsagePercentAttribute(): float
    {
        if ($this->sms_quota == 0) return 0;
        return min(100, round(($this->sms_sent / $this->sms_quota) * 100, 1));
    }

    public function getEmailOpenRateAttribute(): float
    {
        if ($this->emails_sent == 0) return 0;
        return round(($this->emails_opened / $this->emails_sent) * 100, 1);
    }

    public function getEmailClickRateAttribute(): float
    {
        if ($this->emails_opened == 0) return 0;
        return round(($this->emails_clicked / $this->emails_opened) * 100, 1);
    }

    public function getSmsDeliveryRateAttribute(): float
    {
        if ($this->sms_sent == 0) return 0;
        return round(($this->sms_delivered / $this->sms_sent) * 100, 1);
    }

    public function getSmsReplyRateAttribute(): float
    {
        if ($this->sms_delivered == 0) return 0;
        return round(($this->sms_replied / $this->sms_delivered) * 100, 1);
    }

    // Methods

    /**
     * Incrémenter les emails envoyés
     */
    public function incrementEmails(int $count = 1, bool $fromCredits = false): void
    {
        $this->increment('emails_sent', $count);
        if ($fromCredits) {
            $this->increment('emails_from_credits', $count);
        }
    }

    /**
     * Incrémenter les SMS envoyés
     */
    public function incrementSms(int $count = 1, bool $fromCredits = false): void
    {
        $this->increment('sms_sent', $count);
        if ($fromCredits) {
            $this->increment('sms_from_credits', $count);
        }
    }

    /**
     * Vérifier si le quota email est atteint
     */
    public function isEmailQuotaReached(): bool
    {
        return $this->emails_quota > 0 && $this->emails_sent >= $this->emails_quota;
    }

    /**
     * Vérifier si le quota SMS est atteint
     */
    public function isSmsQuotaReached(): bool
    {
        return $this->sms_quota > 0 && $this->sms_sent >= $this->sms_quota;
    }

    // Static Methods

    /**
     * Obtenir ou créer l'usage du mois courant
     */
    public static function currentMonth(int $tenantId): self
    {
        $periodStart = now()->startOfMonth()->toDateString();
        $periodEnd = now()->endOfMonth()->toDateString();

        $tenant = Tenant::find($tenantId);
        $plan = $tenant?->subscriptionPlan;

        return static::firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'period_start' => $periodStart,
            ],
            [
                'period_end' => $periodEnd,
                'emails_quota' => $plan?->emails_per_month ?? 500,
                'sms_quota' => $plan?->sms_per_month ?? 0,
            ]
        );
    }
}
