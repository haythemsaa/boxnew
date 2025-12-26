<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplaceIntegration extends Model
{
    protected $fillable = [
        'tenant_id',
        'platform',
        'platform_account_id',
        'is_active',
        'api_key',
        'api_secret',
        'access_token',
        'token_expires_at',
        'webhook_url',
        'webhook_secret',
        'auto_sync_inventory',
        'auto_sync_prices',
        'auto_accept_leads',
        'sync_interval_minutes',
        'price_markup_percent',
        'commission_percent',
        'lead_cost',
        'commission_type',
        'last_sync_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'auto_sync_inventory' => 'boolean',
        'auto_sync_prices' => 'boolean',
        'auto_accept_leads' => 'boolean',
        'token_expires_at' => 'datetime',
        'last_sync_at' => 'datetime',
        'price_markup_percent' => 'decimal:2',
        'commission_percent' => 'decimal:2',
        'lead_cost' => 'decimal:2',
    ];

    protected $hidden = [
        'api_key',
        'api_secret',
        'access_token',
        'webhook_secret',
    ];

    // Platform constants
    const PLATFORM_SPAREFOOT = 'sparefoot';
    const PLATFORM_SELFSTORAGE = 'selfstorage';
    const PLATFORM_STORAGECAFE = 'storagecafe';
    const PLATFORM_GOOGLE_BUSINESS = 'google_business';
    const PLATFORM_JESTOCKE = 'jestocke';
    const PLATFORM_COSTOCKAGE = 'costockage';

    public static function getPlatforms(): array
    {
        return [
            self::PLATFORM_SPAREFOOT => 'SpareFoot',
            self::PLATFORM_SELFSTORAGE => 'SelfStorage.com',
            self::PLATFORM_STORAGECAFE => 'StorageCafe',
            self::PLATFORM_GOOGLE_BUSINESS => 'Google Business',
            self::PLATFORM_JESTOCKE => 'JeStocke',
            self::PLATFORM_COSTOCKAGE => 'Costockage',
        ];
    }

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function listings(): HasMany
    {
        return $this->hasMany(MarketplaceListing::class, 'integration_id');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(MarketplaceLead::class, 'integration_id');
    }

    public function analytics(): HasMany
    {
        return $this->hasMany(MarketplaceAnalytics::class, 'integration_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    // Helpers
    public function getPlatformNameAttribute(): string
    {
        return self::getPlatforms()[$this->platform] ?? $this->platform;
    }

    public function isTokenExpired(): bool
    {
        if (!$this->token_expires_at) {
            return false;
        }
        return $this->token_expires_at->isPast();
    }

    public function needsSync(): bool
    {
        if (!$this->last_sync_at) {
            return true;
        }
        return $this->last_sync_at->addMinutes($this->sync_interval_minutes)->isPast();
    }
}
