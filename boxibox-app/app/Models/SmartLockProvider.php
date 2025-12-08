<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmartLockProvider extends Model
{
    use HasFactory;

    protected $table = 'smart_lock_providers';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo_url',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function configurations(): HasMany
    {
        return $this->hasMany(SmartLockConfiguration::class, 'provider_id');
    }
}
