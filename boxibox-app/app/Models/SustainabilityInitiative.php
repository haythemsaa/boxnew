<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SustainabilityInitiative extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'site_id',
        'name',
        'category',
        'description',
        'start_date',
        'end_date',
        'investment_cost',
        'annual_savings',
        'co2_reduction_kg',
        'status',
        'progress_percent',
        'milestones',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'investment_cost' => 'decimal:2',
        'annual_savings' => 'decimal:2',
        'co2_reduction_kg' => 'decimal:2',
        'progress_percent' => 'integer',
        'milestones' => 'array',
    ];

    public const CATEGORIES = [
        'energy' => 'Énergie',
        'waste' => 'Déchets',
        'transport' => 'Transport',
        'water' => 'Eau',
        'materials' => 'Matériaux',
        'biodiversity' => 'Biodiversité',
    ];

    public const STATUSES = [
        'planned' => 'Planifié',
        'in_progress' => 'En cours',
        'completed' => 'Terminé',
        'cancelled' => 'Annulé',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['planned', 'in_progress']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    // Accessors
    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getRoiYearsAttribute(): ?float
    {
        if (!$this->investment_cost || !$this->annual_savings || $this->annual_savings <= 0) {
            return null;
        }
        return round($this->investment_cost / $this->annual_savings, 1);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->end_date && $this->end_date->isPast() && $this->status !== 'completed';
    }
}
