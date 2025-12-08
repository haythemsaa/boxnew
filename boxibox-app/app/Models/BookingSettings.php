<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingSettings extends Model
{
    protected $table = 'booking_settings';

    protected $fillable = [
        'tenant_id',
        'site_id',
        'is_enabled',
        'booking_url_slug',
        'company_name',
        'company_logo',
        'primary_color',
        'secondary_color',
        'welcome_message',
        'terms_conditions',
        'require_deposit',
        'deposit_amount',
        'deposit_percentage',
        'min_rental_days',
        'max_advance_booking_days',
        'auto_confirm',
        'require_id_verification',
        'allow_promo_codes',
        'available_payment_methods',
        'stripe_publishable_key',
        'stripe_secret_key',
        'online_payment_enabled',
        'business_hours',
        'contact_email',
        'contact_phone',
        'custom_css',
        'meta_data',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'require_deposit' => 'boolean',
        'deposit_amount' => 'decimal:2',
        'deposit_percentage' => 'decimal:2',
        'auto_confirm' => 'boolean',
        'require_id_verification' => 'boolean',
        'allow_promo_codes' => 'boolean',
        'online_payment_enabled' => 'boolean',
        'available_payment_methods' => 'array',
        'business_hours' => 'array',
        'meta_data' => 'array',
    ];

    // Hide sensitive data from JSON serialization
    protected $hidden = [
        'stripe_secret_key',
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

    // Helpers
    public function getDepositForBox(Box $box): float
    {
        if (!$this->require_deposit) {
            return 0;
        }

        if ($this->deposit_percentage > 0) {
            return $box->current_price * ($this->deposit_percentage / 100);
        }

        return $this->deposit_amount;
    }

    public function getBookingUrlAttribute(): string
    {
        if ($this->booking_url_slug) {
            return url("/book/{$this->booking_url_slug}");
        }
        return url("/book/tenant/{$this->tenant_id}");
    }

    public static function getForTenant(int $tenantId, ?int $siteId = null): ?self
    {
        $query = static::where('tenant_id', $tenantId);

        if ($siteId) {
            $settings = $query->where('site_id', $siteId)->first();
            if ($settings) {
                return $settings;
            }
        }

        return $query->whereNull('site_id')->first();
    }

    public static function getBySlug(string $slug): ?self
    {
        return static::where('booking_url_slug', $slug)
            ->where('is_enabled', true)
            ->first();
    }
}
