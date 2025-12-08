<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InspectionSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'site_id',
        'name',
        'type',
        'frequency',
        'checklist_template',
        'next_due_date',
        'assigned_to',
        'is_active',
    ];

    protected $casts = [
        'checklist_template' => 'array',
        'next_due_date' => 'date',
        'is_active' => 'boolean',
        'last_completed_date' => 'date',
        'is_mandatory' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class, 'schedule_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDue($query)
    {
        return $query->where('next_due_date', '<=', now()->toDateString());
    }

    public function isDue(): bool
    {
        return $this->next_due_date && $this->next_due_date->isPast();
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'safety' => 'Sécurité',
            'cleanliness' => 'Propreté',
            'equipment' => 'Équipement',
            'fire_safety' => 'Sécurité incendie',
            'access_control' => 'Contrôle d\'accès',
            'general' => 'Général',
            default => $this->type,
        };
    }
}
