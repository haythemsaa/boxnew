<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'site_id',
        'employee_id',
        'position',
        'department',
        'hire_date',
        'hourly_rate',
        'monthly_salary',
        'emergency_contact_name',
        'emergency_contact_phone',
        'skills',
        'certifications',
        'is_active',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'hourly_rate' => 'decimal:2',
        'monthly_salary' => 'decimal:2',
        'is_active' => 'boolean',
        'skills' => 'array',
        'certifications' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(StaffShift::class, 'user_id', 'user_id');
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class, 'user_id', 'user_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(StaffTask::class, 'assigned_to', 'user_id');
    }

    public function getTenureInMonthsAttribute(): int
    {
        return $this->hire_date ? $this->hire_date->diffInMonths(now()) : 0;
    }
}
