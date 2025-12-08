<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmartLockConfiguration extends Model
{
    use HasFactory;

    protected $table = 'smart_lock_configurations';

    protected $fillable = [
        'tenant_id',
        'site_id',
        'provider_id',
        'api_key',
        'api_secret',
        'account_id',
        'settings',
        'is_active',
        'auto_lock_on_overdue',
        'overdue_days_before_lock',
        'send_lock_notification',
        'last_sync_at',
        'sync_status',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'auto_lock_on_overdue' => 'boolean',
        'send_lock_notification' => 'boolean',
        'last_sync_at' => 'datetime',
    ];

    protected $hidden = [
        'api_key',
        'api_secret',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(SmartLockProvider::class, 'provider_id');
    }

    public function smartLocks(): HasMany
    {
        return $this->hasMany(SmartLock::class, 'configuration_id');
    }
}
