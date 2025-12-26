<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaitlistSettings extends Model
{
    protected $fillable = [
        'tenant_id',
        'site_id',
        'is_enabled',
        'max_entries_per_box',
        'notification_expiry_hours',
        'max_notifications_per_entry',
        'auto_notify',
        'priority_by_date',
        'notification_email_template',
        'notification_sms_template',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'max_entries_per_box' => 'integer',
        'notification_expiry_hours' => 'integer',
        'max_notifications_per_entry' => 'integer',
        'auto_notify' => 'boolean',
        'priority_by_date' => 'boolean',
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

    public function getDefaultEmailTemplate(): string
    {
        return $this->notification_email_template ?? <<<'HTML'
Bonjour {{ customer_name }},

Bonne nouvelle ! Un box correspondant à vos critères est maintenant disponible.

📦 **Box {{ box_name }}**
- Taille : {{ box_size }} m²
- Prix : {{ box_price }}€/mois
- Site : {{ site_name }}

⏰ Cette offre est valable pendant **{{ expiry_hours }} heures**.

[Réserver maintenant]({{ booking_url }})

Si vous n'êtes plus intéressé, vous pouvez [vous désinscrire]({{ unsubscribe_url }}).

Cordialement,
L'équipe {{ company_name }}
HTML;
    }

    public function getDefaultSmsTemplate(): string
    {
        return $this->notification_sms_template ??
            "{{ company_name }}: Un box {{ box_size }}m² est dispo à {{ box_price }}€/mois! Réservez vite: {{ short_url }} (valable {{ expiry_hours }}h)";
    }
}
