<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ValetRoute extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'valet_driver_id',
        'date',
        'status',
        'total_stops',
        'completed_stops',
        'total_distance_km',
        'start_time',
        'end_time',
        'optimized_order',
    ];

    protected $casts = [
        'date' => 'date',
        'total_stops' => 'integer',
        'completed_stops' => 'integer',
        'total_distance_km' => 'decimal:2',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'optimized_order' => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function driver()
    {
        return $this->belongsTo(ValetDriver::class, 'valet_driver_id');
    }

    public function getOrders()
    {
        if (empty($this->optimized_order)) {
            return collect();
        }

        return ValetOrder::whereIn('id', $this->optimized_order)
            ->get()
            ->sortBy(fn($order) => array_search($order->id, $this->optimized_order));
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    public function scopeForDriver($query, $driverId)
    {
        return $query->where('valet_driver_id', $driverId);
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'planned' => 'Planifiée',
            'in_progress' => 'En cours',
            'completed' => 'Terminée',
            default => $this->status,
        };
    }

    public function getProgressPercentage(): int
    {
        if ($this->total_stops === 0) {
            return 0;
        }
        return (int) (($this->completed_stops / $this->total_stops) * 100);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function incrementCompletedStops(): void
    {
        $this->increment('completed_stops');

        if ($this->completed_stops >= $this->total_stops) {
            $this->update(['status' => 'completed']);
        }
    }
}
