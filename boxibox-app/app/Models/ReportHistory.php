<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportHistory extends Model
{
    use HasFactory;

    protected $table = 'report_history';

    protected $fillable = [
        'custom_report_id',
        'scheduled_report_id',
        'generated_by',
        'format',
        'file_path',
        'file_size',
        'row_count',
        'generation_time_ms',
        'parameters_used',
        'error_message',
        'status',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'row_count' => 'integer',
        'generation_time_ms' => 'float',
        'parameters_used' => 'array',
        'generated_at' => 'datetime',
    ];

    public function customReport(): BelongsTo
    {
        return $this->belongsTo(CustomReport::class);
    }

    public function scheduledReport(): BelongsTo
    {
        return $this->belongsTo(ScheduledReport::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function getFileSizeFormattedAttribute(): string
    {
        $bytes = $this->file_size ?? 0;
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }

    public function getGenerationTimeFormattedAttribute(): string
    {
        $ms = $this->generation_time_ms ?? 0;
        if ($ms >= 1000) {
            return round($ms / 1000, 2) . ' s';
        }
        return $ms . ' ms';
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
