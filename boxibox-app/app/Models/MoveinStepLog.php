<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MoveinStepLog extends Model
{
    protected $fillable = [
        'movein_session_id',
        'step',
        'action',
        'data',
        'error_message',
        'ip_address',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(MoveinSession::class, 'movein_session_id');
    }

    // Action constants
    const ACTION_STARTED = 'started';
    const ACTION_COMPLETED = 'completed';
    const ACTION_FAILED = 'failed';
    const ACTION_SKIPPED = 'skipped';
    const ACTION_RETRIED = 'retried';
    const ACTION_CANCELLED = 'cancelled';

    public function scopeForStep($query, string $step)
    {
        return $query->where('step', $step);
    }

    public function scopeCompleted($query)
    {
        return $query->where('action', self::ACTION_COMPLETED);
    }

    public function scopeFailed($query)
    {
        return $query->where('action', self::ACTION_FAILED);
    }
}
