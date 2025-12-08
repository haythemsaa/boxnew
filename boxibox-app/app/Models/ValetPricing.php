<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ValetPricing extends Model
{
    use HasFactory;

    protected $table = 'valet_pricing';

    protected $fillable = [
        'tenant_id',
        'site_id',
        'name',
        'type',
        'price',
        'unit',
        'min_quantity',
        'max_quantity',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'min_quantity' => 'integer',
        'max_quantity' => 'integer',
        'is_active' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function getUnitLabel(): string
    {
        return match($this->unit) {
            'fixed' => 'Forfait',
            'per_km' => 'Par km',
            'per_floor' => 'Par étage',
            'per_item' => 'Par article',
            'per_m3' => 'Par m³',
            'monthly' => 'Par mois',
            default => $this->unit,
        };
    }
}
