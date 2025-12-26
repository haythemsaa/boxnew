<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewRequestSettings extends Model
{
    protected $fillable = [
        'tenant_id',
        'site_id',
        'is_enabled',
        'trigger_on_move_in',
        'move_in_delay_days',
        'trigger_on_renewal',
        'renewal_delay_days',
        'trigger_on_support_resolved',
        'send_email',
        'send_sms',
        'google_place_id',
        'google_review_url',
        'trustpilot_url',
        'facebook_page_url',
        'primary_platform',
        'email_subject',
        'email_template',
        'sms_template',
        'max_requests_per_customer',
        'min_days_between_requests',
        'skip_if_negative_interaction',
        'offer_incentive',
        'incentive_type',
        'incentive_value',
        'incentive_description',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'trigger_on_move_in' => 'boolean',
        'move_in_delay_days' => 'integer',
        'trigger_on_renewal' => 'boolean',
        'renewal_delay_days' => 'integer',
        'trigger_on_support_resolved' => 'boolean',
        'send_email' => 'boolean',
        'send_sms' => 'boolean',
        'max_requests_per_customer' => 'integer',
        'min_days_between_requests' => 'integer',
        'skip_if_negative_interaction' => 'boolean',
        'offer_incentive' => 'boolean',
        'incentive_value' => 'decimal:2',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    // Static helpers
    public static function getForSite(int $tenantId, ?int $siteId = null): ?self
    {
        return static::where('tenant_id', $tenantId)
            ->where(function ($q) use ($siteId) {
                $q->where('site_id', $siteId)
                    ->orWhereNull('site_id');
            })
            ->orderByRaw('site_id IS NULL')
            ->first();
    }

    public static function isEnabled(int $tenantId, ?int $siteId = null): bool
    {
        $settings = static::getForSite($tenantId, $siteId);
        return $settings ? $settings->is_enabled : false;
    }

    public function getGoogleReviewUrlFromPlaceId(): string
    {
        if ($this->google_place_id) {
            return "https://search.google.com/local/writereview?placeid={$this->google_place_id}";
        }
        return $this->google_review_url ?? '';
    }

    public function getDefaultEmailSubject(): string
    {
        return $this->email_subject ?? 'Votre avis compte pour nous !';
    }

    public function getDefaultEmailTemplate(): string
    {
        return $this->email_template ?? <<<'HTML'
Bonjour {{ customer_name }},

Merci d'avoir choisi {{ company_name }} pour votre stockage !

Nous espérons que votre expérience a été positive et aimerions connaître votre avis.

Cela ne prend que 2 minutes et nous aide énormément :

⭐ [Laisser un avis]({{ review_url }})

Votre avis sincère nous permet de nous améliorer et d'aider d'autres personnes à faire le bon choix.

Merci beaucoup !

L'équipe {{ company_name }}

---
[Se désinscrire]({{ unsubscribe_url }})
HTML;
    }

    public function getDefaultSmsTemplate(): string
    {
        return $this->sms_template ??
            "{{ company_name }}: Merci pour votre confiance! Partagez votre avis en 2 min: {{ short_url }}";
    }
}
