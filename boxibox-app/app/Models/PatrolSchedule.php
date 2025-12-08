<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatrolSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'site_id',
        'name',
        'frequency',
        'days_of_week',
        'duration_minutes',
        'checkpoints',
        'is_active',
    ];

    protected $casts = [
        'days_of_week' => 'array',
        'checkpoints' => 'array',
        'duration_minutes' => 'integer',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function patrols(): HasMany
    {
        return $this->hasMany(Patrol::class, 'schedule_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getFrequencyLabelAttribute(): string
    {
        return match ($this->frequency) {
            'hourly' => 'Toutes les heures',
            'daily' => 'Quotidien',
            'weekly' => 'Hebdomadaire',
            'custom' => 'Personnalisé',
            default => $this->frequency,
        };
    }
}
