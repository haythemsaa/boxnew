<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentFailureAnalytics extends Model
{
    protected $table = 'payment_failure_analytics';

    protected $fillable = [
        'tenant_id',
        'retry_attempt_id',
        'failure_reason',
        'card_brand',
        'card_last4',
        'day_of_week',
        'hour_of_day',
        'date',
        'is_first_of_month',
        'is_end_of_month',
        'customer_tenure_days',
        'previous_successful_payments',
        'previous_failed_payments',
        'eventually_recovered',
        'recovery_attempt_number',
    ];

    protected $casts = [
        'date' => 'date',
        'is_first_of_month' => 'boolean',
        'is_end_of_month' => 'boolean',
        'eventually_recovered' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function retryAttempt(): BelongsTo
    {
        return $this->belongsTo(PaymentRetryAttempt::class, 'retry_attempt_id');
    }

    public function scopeRecovered($query)
    {
        return $query->where('eventually_recovered', true);
    }

    public function scopeNotRecovered($query)
    {
        return $query->where('eventually_recovered', false);
    }

    public function scopeByReason($query, string $reason)
    {
        return $query->where('failure_reason', $reason);
    }

    public static function getFailureReasons(): array
    {
        return [
            'insufficient_funds' => 'Fonds insuffisants',
            'card_declined' => 'Carte refusee',
            'expired_card' => 'Carte expiree',
            'incorrect_cvc' => 'CVC incorrect',
            'processing_error' => 'Erreur de traitement',
            'authentication_required' => 'Authentification requise',
            'do_not_honor' => 'Transaction refusee',
            'lost_card' => 'Carte perdue',
            'stolen_card' => 'Carte volee',
            'other' => 'Autre',
        ];
    }

    public static function getRecoveryRateByReason(int $tenantId): array
    {
        return static::where('tenant_id', $tenantId)
            ->selectRaw('failure_reason, COUNT(*) as total, SUM(eventually_recovered) as recovered')
            ->groupBy('failure_reason')
            ->get()
            ->mapWithKeys(function ($item) {
                $rate = $item->total > 0 ? round($item->recovered / $item->total * 100, 1) : 0;
                return [$item->failure_reason => [
                    'total' => $item->total,
                    'recovered' => $item->recovered,
                    'rate' => $rate,
                ]];
            })
            ->toArray();
    }

    public static function getBestRecoveryTimes(int $tenantId): array
    {
        return static::where('tenant_id', $tenantId)
            ->where('eventually_recovered', true)
            ->selectRaw('day_of_week, hour_of_day, COUNT(*) as success_count')
            ->groupBy('day_of_week', 'hour_of_day')
            ->orderByDesc('success_count')
            ->limit(10)
            ->get()
            ->toArray();
    }
}
