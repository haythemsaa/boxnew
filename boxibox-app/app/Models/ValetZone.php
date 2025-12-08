<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ValetZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'site_id',
        'name',
        'postal_codes',
        'pickup_fee',
        'delivery_fee',
        'min_lead_time_hours',
        'is_active',
    ];

    protected $casts = [
        'postal_codes' => 'array',
        'pickup_fee' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'min_lead_time_hours' => 'integer',
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

    public function coversPostalCode(string $postalCode): bool
    {
        return in_array($postalCode, $this->postal_codes ?? []);
    }

    public static function findForPostalCode(int $tenantId, int $siteId, string $postalCode): ?self
    {
        return static::where('tenant_id', $tenantId)
            ->where('site_id', $siteId)
            ->where('is_active', true)
            ->get()
            ->first(fn($zone) => $zone->coversPostalCode($postalCode));
    }
}
