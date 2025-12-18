<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsuranceProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'logo_url',
        'description',
        'contact_email',
        'contact_phone',
        'api_endpoint',
        'api_key',
        'commission_rate',
        'is_active',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'api_key',
    ];

    /**
     * Get the plans offered by this provider.
     */
    public function plans(): HasMany
    {
        return $this->hasMany(InsurancePlan::class, 'provider_id');
    }

    /**
     * Scope to active providers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
