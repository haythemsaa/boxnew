<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WasteRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'site_id',
        'record_date',
        'general_waste_kg',
        'recycling_kg',
        'cardboard_kg',
        'hazardous_kg',
        'organic_kg',
        'total_kg',
        'recycling_rate',
        'disposal_cost',
    ];

    protected $casts = [
        'record_date' => 'date',
        'general_waste_kg' => 'decimal:2',
        'recycling_kg' => 'decimal:2',
        'cardboard_kg' => 'decimal:2',
        'hazardous_kg' => 'decimal:2',
        'organic_kg' => 'decimal:2',
        'total_kg' => 'decimal:2',
        'recycling_rate' => 'decimal:2',
        'disposal_cost' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($record) {
            // Auto-calculate totals
            $record->total_kg = $record->general_waste_kg +
                               $record->recycling_kg +
                               $record->cardboard_kg +
                               $record->hazardous_kg +
                               $record->organic_kg;

            // Calculate recycling rate
            if ($record->total_kg > 0) {
                $recycled = $record->recycling_kg + $record->cardboard_kg + $record->organic_kg;
                $record->recycling_rate = round(($recycled / $record->total_kg) * 100, 2);
            }
        });
    }

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
    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('record_date', [$startDate, $endDate]);
    }

    public function scopeForYear($query, $year)
    {
        return $query->whereYear('record_date', $year);
    }

    // Accessors
    public function getRecycledKgAttribute(): float
    {
        return $this->recycling_kg + $this->cardboard_kg + $this->organic_kg;
    }

    public function getLandfillKgAttribute(): float
    {
        return $this->general_waste_kg + $this->hazardous_kg;
    }
}
