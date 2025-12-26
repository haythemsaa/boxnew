<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class GoogleReserveBooking extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'site_id',
        'customer_id',
        'box_id',
        'booking_id',
        'google_booking_id',
        'google_merchant_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'booking_date',
        'start_time',
        'end_time',
        'service_type',
        'box_size_requested',
        'customer_notes',
        'status',
        'confirmed_at',
        'cancelled_at',
        'cancellation_reason',
        'completed_at',
        'converted_contract_id',
        'converted_value',
        'last_synced_at',
        'google_raw_data',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'google_raw_data' => 'array',
        'box_size_requested' => 'decimal:2',
        'converted_value' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function box(): BelongsTo
    {
        return $this->belongsTo(Box::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function convertedContract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'converted_contract_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('booking_date', '>=', now()->toDateString())
            ->whereIn('status', ['pending', 'confirmed']);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('booking_date', now()->toDateString());
    }

    // Helpers
    public function confirm(): bool
    {
        $this->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);
        return true;
    }

    public function cancel(string $reason = null, bool $byCustomer = true): bool
    {
        $this->update([
            'status' => $byCustomer ? 'cancelled_by_customer' : 'cancelled_by_merchant',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
        return true;
    }

    public function markCompleted(): bool
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
        return true;
    }

    public function markConverted(int $contractId, float $value): bool
    {
        $this->update([
            'status' => 'converted',
            'converted_contract_id' => $contractId,
            'converted_value' => $value,
        ]);
        return true;
    }

    public function getFormattedTimeSlotAttribute(): string
    {
        return $this->start_time . ' - ' . $this->end_time;
    }
}
