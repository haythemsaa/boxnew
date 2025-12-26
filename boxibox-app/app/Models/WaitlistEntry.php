<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class WaitlistEntry extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'site_id',
        'box_id',
        'customer_id',
        'customer_email',
        'customer_first_name',
        'customer_last_name',
        'customer_phone',
        'min_size',
        'max_size',
        'max_price',
        'needs_climate_control',
        'needs_ground_floor',
        'needs_drive_up',
        'desired_start_date',
        'notes',
        'status',
        'priority',
        'position',
        'source',
        'notified_at',
        'expires_at',
        'converted_booking_id',
    ];

    protected $casts = [
        'min_size' => 'decimal:2',
        'max_size' => 'decimal:2',
        'max_price' => 'decimal:2',
        'needs_climate_control' => 'boolean',
        'needs_ground_floor' => 'boolean',
        'needs_drive_up' => 'boolean',
        'desired_start_date' => 'date',
        'notified_at' => 'datetime',
        'expires_at' => 'datetime',
        'priority' => 'integer',
        'position' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($entry) {
            if (empty($entry->uuid)) {
                $entry->uuid = (string) Str::uuid();
            }
            // Calculate position based on existing entries
            if (empty($entry->position)) {
                $entry->position = static::where('tenant_id', $entry->tenant_id)
                    ->where('site_id', $entry->site_id)
                    ->where('status', 'active')
                    ->when($entry->box_id, fn($q) => $q->where('box_id', $entry->box_id))
                    ->max('position') + 1;
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

    public function box(): BelongsTo
    {
        return $this->belongsTo(Box::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(WaitlistNotification::class);
    }

    public function convertedBooking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'converted_booking_id');
    }

    // Accessors
    public function getCustomerFullNameAttribute(): string
    {
        return trim("{$this->customer_first_name} {$this->customer_last_name}");
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active' => 'En attente',
            'notified' => 'Notifié',
            'converted' => 'Converti',
            'expired' => 'Expiré',
            'cancelled' => 'Annulé',
            default => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'active' => '#2196F3',
            'notified' => '#FF9800',
            'converted' => '#4CAF50',
            'expired' => '#9E9E9E',
            'cancelled' => '#f44336',
            default => '#9E9E9E',
        };
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeBySite($query, int $siteId)
    {
        return $query->where('site_id', $siteId);
    }

    public function scopeForBox($query, int $boxId)
    {
        return $query->where('box_id', $boxId);
    }

    public function scopeMatchingBox($query, Box $box)
    {
        return $query->where('status', 'active')
            ->where('site_id', $box->site_id)
            ->where(function ($q) use ($box) {
                $q->whereNull('box_id')
                    ->orWhere('box_id', $box->id);
            })
            ->where(function ($q) use ($box) {
                $q->whereNull('min_size')
                    ->orWhere('min_size', '<=', $box->size_m2);
            })
            ->where(function ($q) use ($box) {
                $q->whereNull('max_size')
                    ->orWhere('max_size', '>=', $box->size_m2);
            })
            ->where(function ($q) use ($box) {
                $q->whereNull('max_price')
                    ->orWhere('max_price', '>=', $box->current_price);
            })
            ->when($box->climate_controlled, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('needs_climate_control', false)
                        ->orWhere('needs_climate_control', true);
                });
            }, function ($q) {
                $q->where('needs_climate_control', false);
            })
            ->orderBy('priority', 'desc')
            ->orderBy('position', 'asc');
    }

    // Actions
    public function notify(): void
    {
        $this->update([
            'status' => 'notified',
            'notified_at' => now(),
            'expires_at' => now()->addHours(
                WaitlistSettings::where('tenant_id', $this->tenant_id)
                    ->where(function ($q) {
                        $q->where('site_id', $this->site_id)
                            ->orWhereNull('site_id');
                    })
                    ->value('notification_expiry_hours') ?? 48
            ),
        ]);
    }

    public function convert(Booking $booking): void
    {
        $this->update([
            'status' => 'converted',
            'converted_booking_id' => $booking->id,
        ]);
    }

    public function expire(): void
    {
        $this->update(['status' => 'expired']);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public function reactivate(): void
    {
        $this->update([
            'status' => 'active',
            'notified_at' => null,
            'expires_at' => null,
        ]);
    }
}
