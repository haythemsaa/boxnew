<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BookingWidget extends Model
{
    protected $fillable = [
        'tenant_id',
        'site_id',
        'widget_key',
        'name',
        'allowed_domain',
        'allowed_domains',
        'widget_type',
        'style_config',
        'is_active',
        'views_count',
        'bookings_count',
    ];

    protected $casts = [
        'allowed_domains' => 'array',
        'style_config' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($widget) {
            if (empty($widget->widget_key)) {
                $widget->widget_key = (string) Str::uuid();
            }
        });
    }

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    // Helpers
    public function getEmbedCodeAttribute(): string
    {
        $scriptUrl = url('/js/booking-widget.js');
        $dataAttrs = "data-widget-key=\"{$this->widget_key}\"";

        if ($this->site_id) {
            $dataAttrs .= " data-site-id=\"{$this->site_id}\"";
        }

        return <<<HTML
<!-- BoxiBox Booking Widget -->
<div id="boxibox-booking-widget" {$dataAttrs}></div>
<script src="{$scriptUrl}" async></script>
HTML;
    }

    public function getIframeCodeAttribute(): string
    {
        $iframeUrl = url("/widget/booking/{$this->widget_key}");

        return <<<HTML
<iframe
    src="{$iframeUrl}"
    width="100%"
    height="600"
    frameborder="0"
    style="border: none; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
</iframe>
HTML;
    }

    public function isDomainAllowed(?string $domain): bool
    {
        if (!$domain) {
            return true; // Allow if no domain check
        }

        if ($this->allowed_domain && $this->allowed_domain === $domain) {
            return true;
        }

        if ($this->allowed_domains && in_array($domain, $this->allowed_domains)) {
            return true;
        }

        // If no restrictions are set, allow all
        if (!$this->allowed_domain && !$this->allowed_domains) {
            return true;
        }

        return false;
    }

    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    public function incrementBookings(): void
    {
        $this->increment('bookings_count');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function findByKey(string $key): ?self
    {
        return static::where('widget_key', $key)
            ->active()
            ->first();
    }
}
