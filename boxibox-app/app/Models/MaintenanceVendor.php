<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceVendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'contact_name',
        'email',
        'phone',
        'address',
        'hourly_rate',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'hourly_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(MaintenanceTicket::class, 'vendor_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithValidContract($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('contract_expires_at')
              ->orWhere('contract_expires_at', '>=', now());
        });
    }

    public function hasSpecialty(string $specialty): bool
    {
        return in_array($specialty, $this->specialties ?? []);
    }
}
