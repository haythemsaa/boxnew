<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffPerformance extends Model
{
    use HasFactory;

    protected $table = 'staff_performance';

    protected $fillable = [
        'period_start',
        'period_end',
        'attendance_rate',
        'notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'attendance_rate' => 'decimal:2',
    ];

    public function getOverallScoreAttribute(): float
    {
        return round($this->attendance_rate ?? 0, 1);
    }
}
