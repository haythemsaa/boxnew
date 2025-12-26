<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class KioskDevice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'site_id',
        'name',
        'device_code',
        'location_description',
        'device_type',
        'os',
        'browser',
        'screen_resolution',
        'is_active',
        'is_online',
        'last_heartbeat_at',
        'current_ip',
        'language',
        'allow_new_rentals',
        'allow_payments',
        'allow_access_code_generation',
        'show_prices',
        'require_id_verification',
        'logo_path',
        'background_image_path',
        'primary_color',
        'secondary_color',
        'welcome_message',
        'idle_timeout_seconds',
        'enable_screensaver',
        'screensaver_images',
        'admin_pin',
        'require_pin_for_settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_online' => 'boolean',
        'last_heartbeat_at' => 'datetime',
        'allow_new_rentals' => 'boolean',
        'allow_payments' => 'boolean',
        'allow_access_code_generation' => 'boolean',
        'show_prices' => 'boolean',
        'require_id_verification' => 'boolean',
        'enable_screensaver' => 'boolean',
        'screensaver_images' => 'array',
        'require_pin_for_settings' => 'boolean',
    ];

    protected $hidden = ['admin_pin'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
            if (empty($model->device_code)) {
                $model->device_code = strtoupper(Str::random(8));
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

    public function sessions(): HasMany
    {
        return $this->hasMany(KioskSession::class, 'kiosk_id');
    }

    public function issues(): HasMany
    {
        return $this->hasMany(KioskIssue::class, 'kiosk_id');
    }

    public function analytics(): HasMany
    {
        return $this->hasMany(KioskAnalytics::class, 'kiosk_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOnline($query)
    {
        return $query->where('is_online', true);
    }

    public function scopeOffline($query)
    {
        return $query->where('is_online', false)
            ->orWhere('last_heartbeat_at', '<', now()->subMinutes(5));
    }

    // Helpers
    public function updateHeartbeat(string $ip = null): void
    {
        $this->update([
            'is_online' => true,
            'last_heartbeat_at' => now(),
            'current_ip' => $ip,
        ]);
    }

    public function markOffline(): void
    {
        $this->update(['is_online' => false]);
    }

    public function isOnline(): bool
    {
        if (!$this->last_heartbeat_at) {
            return false;
        }
        return $this->last_heartbeat_at->diffInMinutes(now()) < 5;
    }

    public function getStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'disabled';
        }
        return $this->isOnline() ? 'online' : 'offline';
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'online' => 'green',
            'offline' => 'red',
            'disabled' => 'gray',
            default => 'gray',
        };
    }

    public function generateNewCode(): string
    {
        $this->device_code = strtoupper(Str::random(8));
        $this->save();
        return $this->device_code;
    }

    public function verifyPin(string $pin): bool
    {
        return $this->admin_pin === $pin;
    }

    public function getTodayStats(): array
    {
        $today = now()->toDateString();
        $sessions = $this->sessions()->whereDate('started_at', $today);

        return [
            'total_sessions' => $sessions->count(),
            'completed_rentals' => $sessions->where('completed_rental', true)->count(),
            'payments' => $sessions->where('made_payment', true)->count(),
            'revenue' => $sessions->sum('transaction_amount'),
        ];
    }
}
