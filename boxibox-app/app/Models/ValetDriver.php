<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ValetDriver extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'phone',
        'license_number',
        'vehicle_type',
        'vehicle_plate',
        'max_capacity_kg',
        'max_capacity_m3',
        'status',
        'current_latitude',
        'current_longitude',
        'location_updated_at',
        'is_active',
    ];

    protected $casts = [
        'max_capacity_kg' => 'decimal:2',
        'max_capacity_m3' => 'decimal:2',
        'current_latitude' => 'decimal:8',
        'current_longitude' => 'decimal:8',
        'location_updated_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function routes()
    {
        return $this->hasMany(ValetRoute::class);
    }

    public function orders()
    {
        return $this->hasMany(ValetOrder::class, 'assigned_driver_id', 'user_id');
    }

    public function todaysOrders()
    {
        return $this->orders()->whereDate('requested_date', today());
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function getVehicleTypeLabel(): string
    {
        return match($this->vehicle_type) {
            'bike' => 'Vélo cargo',
            'van' => 'Camionnette',
            'truck' => 'Camion',
            default => $this->vehicle_type,
        };
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'available' => 'Disponible',
            'busy' => 'Occupé',
            'offline' => 'Hors ligne',
            default => $this->status,
        };
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            'available' => 'green',
            'busy' => 'yellow',
            'offline' => 'gray',
            default => 'gray',
        };
    }

    public function isAvailable(): bool
    {
        return $this->is_active && $this->status === 'available';
    }

    public function updateLocation(float $latitude, float $longitude): void
    {
        $this->update([
            'current_latitude' => $latitude,
            'current_longitude' => $longitude,
            'location_updated_at' => now(),
        ]);
    }
}
