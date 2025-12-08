<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inspection extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'site_id',
        'schedule_id',
        'inspector_id',
        'type',
        'inspection_date',
        'status',
        'result',
        'checklist_results',
        'findings',
        'photos',
        'documents',
        'recommendations',
        'follow_up_date',
    ];

    protected $casts = [
        'inspection_date' => 'date',
        'follow_up_date' => 'date',
        'checklist_results' => 'array',
        'photos' => 'array',
        'documents' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(InspectionSchedule::class, 'schedule_id');
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function issues(): HasMany
    {
        return $this->hasMany(InspectionIssue::class);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['scheduled', 'in_progress']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('inspection_date', now()->month)
            ->whereYear('inspection_date', now()->year);
    }

    public function getResultColorAttribute(): string
    {
        return match ($this->result) {
            'pass' => 'green',
            'pass_with_issues' => 'yellow',
            'fail' => 'red',
            default => 'gray',
        };
    }
}
