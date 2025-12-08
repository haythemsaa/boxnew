<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patrol extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'site_id',
        'schedule_id',
        'conducted_by',
        'started_at',
        'completed_at',
        'status',
        'notes',
        'issues_found',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
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
        return $this->belongsTo(PatrolSchedule::class, 'schedule_id');
    }

    public function conductor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'conducted_by');
    }

    public function checkpoints(): HasMany
    {
        return $this->hasMany(PatrolCheckpoint::class);
    }

    public function getDurationAttribute(): ?int
    {
        if ($this->started_at && $this->completed_at) {
            return $this->started_at->diffInMinutes($this->completed_at);
        }
        return null;
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeWithIssues($query)
    {
        return $query->where('issues_found', '>', 0);
    }
}
