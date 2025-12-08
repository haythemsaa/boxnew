<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatrolCheckpoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'patrol_id',
        'status',
        'notes',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
    ];

    public function patrol(): BelongsTo
    {
        return $this->belongsTo(Patrol::class);
    }

    public function isScanned(): bool
    {
        return $this->scanned_at !== null;
    }

    public function hasIssues(): bool
    {
        return !empty($this->issues_found);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'ok' => 'green',
            'issue_found' => 'red',
            'pending' => 'yellow',
            'skipped' => 'gray',
            default => 'gray',
        };
    }
}
