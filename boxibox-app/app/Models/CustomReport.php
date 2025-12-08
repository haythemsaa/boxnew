<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'created_by',
        'name',
        'description',
        'type',
        'columns',
        'filters',
        'grouping',
        'sorting',
        'is_public',
        'is_favorite',
    ];

    protected $casts = [
        'columns' => 'array',
        'filters' => 'array',
        'grouping' => 'array',
        'sorting' => 'array',
        'is_public' => 'boolean',
        'is_favorite' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function history(): HasMany
    {
        return $this->hasMany(ReportHistory::class, 'custom_report_id');
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeFavorites($query)
    {
        return $query->where('is_favorite', true);
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'rent_roll' => 'Rent Roll',
            'revenue' => 'Revenus',
            'occupancy' => 'Occupation',
            'aging' => 'Balance âgée',
            'activity' => 'Activité',
            'custom' => 'Personnalisé',
            default => $this->type,
        };
    }
}
