<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FecExport extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'fiscal_year',
        'period_start',
        'period_end',
        'file_path',
        'file_name',
        'file_size',
        'checksum',
        'entries_count',
        'total_debit',
        'total_credit',
        'status',
        'error_message',
        'generated_by',
        'generated_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'generated_at' => 'datetime',
        'total_debit' => 'decimal:2',
        'total_credit' => 'decimal:2',
        'entries_count' => 'integer',
        'file_size' => 'integer',
        'fiscal_year' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Get the download URL for the FEC file
     */
    public function getDownloadUrlAttribute(): ?string
    {
        if (!$this->file_path) {
            return null;
        }
        return route('tenant.fec.download', $this);
    }

    /**
     * Check if the export is ready for download
     */
    public function isReady(): bool
    {
        return $this->status === 'ready' && $this->file_path !== null;
    }

    /**
     * Get formatted file size
     */
    public function getFormattedFileSizeAttribute(): string
    {
        if (!$this->file_size) {
            return '-';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    /**
     * Scope by fiscal year
     */
    public function scopeForYear($query, int $year)
    {
        return $query->where('fiscal_year', $year);
    }

    /**
     * Scope for ready exports
     */
    public function scopeReady($query)
    {
        return $query->where('status', 'ready');
    }
}
