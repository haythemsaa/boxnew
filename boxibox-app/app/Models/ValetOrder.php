<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ValetOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'site_id',
        'customer_id',
        'order_number',
        'type',
        'status',
        'requested_date',
        'time_slot',
        'scheduled_time_start',
        'scheduled_time_end',
        'assigned_driver_id',
        'vehicle_type',
        'address_line1',
        'address_line2',
        'city',
        'postal_code',
        'country',
        'latitude',
        'longitude',
        'floor',
        'has_elevator',
        'access_code',
        'access_instructions',
        'contact_name',
        'contact_phone',
        'contact_email',
        'base_fee',
        'distance_fee',
        'floor_fee',
        'item_fee',
        'total_fee',
        'is_paid',
        'notes',
        'driver_notes',
        'started_at',
        'completed_at',
        'completion_photo',
        'signature',
    ];

    protected $casts = [
        'requested_date' => 'date',
        'scheduled_time_start' => 'datetime:H:i',
        'scheduled_time_end' => 'datetime:H:i',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'has_elevator' => 'boolean',
        'base_fee' => 'decimal:2',
        'distance_fee' => 'decimal:2',
        'floor_fee' => 'decimal:2',
        'item_fee' => 'decimal:2',
        'total_fee' => 'decimal:2',
        'is_paid' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = 'VO-' . date('Ymd') . '-' . strtoupper(Str::random(4));
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'assigned_driver_id');
    }

    public function orderItems()
    {
        return $this->hasMany(ValetOrderItem::class);
    }

    public function items()
    {
        return $this->hasManyThrough(
            ValetItem::class,
            ValetOrderItem::class,
            'valet_order_id',
            'id',
            'id',
            'valet_item_id'
        );
    }

    // Scopes
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeScheduled($query)
    {
        return $query->whereIn('status', ['confirmed', 'scheduled']);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('requested_date', $date);
    }

    public function scopeForDriver($query, $driverId)
    {
        return $query->where('assigned_driver_id', $driverId);
    }

    public function scopePickups($query)
    {
        return $query->whereIn('type', ['pickup', 'pickup_delivery']);
    }

    public function scopeDeliveries($query)
    {
        return $query->whereIn('type', ['delivery', 'pickup_delivery']);
    }

    // Helpers
    public function getTypeLabel(): string
    {
        return match($this->type) {
            'pickup' => 'Collecte',
            'delivery' => 'Livraison',
            'pickup_delivery' => 'Collecte & Livraison',
            default => $this->type,
        };
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => 'En attente',
            'confirmed' => 'Confirmé',
            'scheduled' => 'Planifié',
            'in_progress' => 'En cours',
            'completed' => 'Terminé',
            'cancelled' => 'Annulé',
            default => $this->status,
        };
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            'pending' => 'yellow',
            'confirmed' => 'blue',
            'scheduled' => 'indigo',
            'in_progress' => 'orange',
            'completed' => 'green',
            'cancelled' => 'red',
            default => 'gray',
        };
    }

    public function getTimeSlotLabel(): string
    {
        return match($this->time_slot) {
            'morning' => 'Matin (8h-12h)',
            'afternoon' => 'Après-midi (12h-18h)',
            'evening' => 'Soir (18h-20h)',
            default => $this->time_slot ?? '-',
        };
    }

    public function getFullAddress(): string
    {
        $parts = array_filter([
            $this->address_line1,
            $this->address_line2,
            $this->postal_code . ' ' . $this->city,
            $this->country,
        ]);
        return implode(', ', $parts);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed', 'scheduled']);
    }

    public function calculateFees(): void
    {
        $this->total_fee = $this->base_fee + $this->distance_fee + $this->floor_fee + $this->item_fee;
    }
}
