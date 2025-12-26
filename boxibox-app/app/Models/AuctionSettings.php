<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuctionSettings extends Model
{
    protected $fillable = [
        'tenant_id',
        'is_enabled',
        'days_before_first_notice',
        'days_before_second_notice',
        'days_before_final_notice',
        'days_before_auction',
        'minimum_debt_amount',
        'auction_duration_days',
        'starting_bid_percentage',
        'require_reserve_price',
        'allow_proxy_bidding',
        'preferred_platform',
        'platform_credentials',
        'auto_list_on_platform',
        'legal_jurisdiction',
        'first_notice_template',
        'second_notice_template',
        'final_notice_template',
        'platform_fee_percentage',
        'admin_fee',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'days_before_first_notice' => 'integer',
        'days_before_second_notice' => 'integer',
        'days_before_final_notice' => 'integer',
        'days_before_auction' => 'integer',
        'minimum_debt_amount' => 'decimal:2',
        'auction_duration_days' => 'integer',
        'starting_bid_percentage' => 'decimal:2',
        'require_reserve_price' => 'boolean',
        'allow_proxy_bidding' => 'boolean',
        'platform_credentials' => 'encrypted:array',
        'auto_list_on_platform' => 'boolean',
        'platform_fee_percentage' => 'decimal:2',
        'admin_fee' => 'decimal:2',
    ];

    protected $hidden = [
        'platform_credentials',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // Static helpers
    public static function getForTenant(int $tenantId): ?self
    {
        return static::where('tenant_id', $tenantId)->first();
    }

    public static function isEnabled(int $tenantId): bool
    {
        $settings = static::getForTenant($tenantId);
        return $settings ? $settings->is_enabled : false;
    }

    public function calculateStartingBid(float $debt): float
    {
        return round($debt * ($this->starting_bid_percentage / 100), 2);
    }

    public function getDefaultFirstNoticeTemplate(): string
    {
        return $this->first_notice_template ?? <<<'HTML'
PREMIER AVIS DE RETARD DE PAIEMENT

Madame, Monsieur {{ customer_name }},

Nous vous informons que votre compte présente un solde impayé de {{ debt_amount }} €.

Détails :
- Box n° {{ box_number }}
- Site : {{ site_name }}
- Jours de retard : {{ days_overdue }}

Nous vous invitons à régulariser votre situation dans les plus brefs délais afin d'éviter des frais supplémentaires.

Régularisez en ligne : {{ payment_url }}

Cordialement,
{{ company_name }}
HTML;
    }

    public function getDefaultFinalNoticeTemplate(): string
    {
        return $this->final_notice_template ?? <<<'HTML'
MISE EN DEMEURE AVANT VENTE AUX ENCHÈRES

Madame, Monsieur {{ customer_name }},

Par la présente, nous vous mettons en demeure de régler sous 15 jours la somme de {{ debt_amount }} €.

À défaut de paiement, conformément à la loi, le contenu de votre box n° {{ box_number }} sera vendu aux enchères publiques.

Date limite de paiement : {{ deadline_date }}

Régularisez en ligne : {{ payment_url }}

Cette mise en demeure constitue le dernier avis avant procédure.

Cordialement,
{{ company_name }}
HTML;
    }
}
