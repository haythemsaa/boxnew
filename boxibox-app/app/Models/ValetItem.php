<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ValetItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'site_id',
        'customer_id',
        'barcode',
        'name',
        'description',
        'category',
        'size',
        'weight_kg',
        'volume_m3',
        'condition',
        'photos',
        'storage_location',
        'status',
        'monthly_fee',
        'declared_value',
        'is_fragile',
        'requires_climate_control',
        'special_instructions',
        'storage_start_date',
        'storage_end_date',
    ];

    protected $casts = [
        'photos' => 'array',
        'weight_kg' => 'decimal:2',
        'volume_m3' => 'decimal:3',
        'monthly_fee' => 'decimal:2',
        'declared_value' => 'decimal:2',
        'is_fragile' => 'boolean',
        'requires_climate_control' => 'boolean',
        'storage_start_date' => 'date',
        'storage_end_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            if (empty($item->barcode)) {
                $item->barcode = 'VLT-' . strtoupper(Str::random(8));
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

    public function orderItems()
    {
        return $this->hasMany(ValetOrderItem::class);
    }

    public function orders()
    {
        return $this->hasManyThrough(
            ValetOrder::class,
            ValetOrderItem::class,
            'valet_item_id',
            'id',
            'id',
            'valet_order_id'
        );
    }

    // Scopes
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeStored($query)
    {
        return $query->where('status', 'stored');
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // Helpers
    public function isStored(): bool
    {
        return $this->status === 'stored';
    }

    public function isInTransit(): bool
    {
        return in_array($this->status, ['in_transit_to_storage', 'in_transit_to_customer']);
    }

    public function getSizeLabel(): string
    {
        return match($this->size) {
            'small' => 'Petit',
            'medium' => 'Moyen',
            'large' => 'Grand',
            'extra_large' => 'Très grand',
            default => $this->size,
        };
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'pending_pickup' => 'En attente de collecte',
            'in_transit_to_storage' => 'En transit vers stockage',
            'stored' => 'Stocké',
            'pending_delivery' => 'En attente de livraison',
            'in_transit_to_customer' => 'En transit vers client',
            'delivered' => 'Livré',
            'disposed' => 'Éliminé',
            default => $this->status,
        };
    }

    public function getMonthsStored(): int
    {
        if (!$this->storage_start_date) {
            return 0;
        }
        return $this->storage_start_date->diffInMonths(now());
    }

    public function getTotalStorageCost(): float
    {
        return $this->monthly_fee * $this->getMonthsStored();
    }
}
