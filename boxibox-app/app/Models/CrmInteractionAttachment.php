<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CrmInteractionAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'crm_interaction_id',
        'filename',
        'original_name',
        'mime_type',
        'size',
        'path',
    ];

    protected $appends = ['url', 'formatted_size'];

    public function interaction(): BelongsTo
    {
        return $this->belongsTo(CrmInteraction::class, 'crm_interaction_id');
    }

    public function getUrlAttribute(): string
    {
        return Storage::url($this->path);
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function getIconAttribute(): string
    {
        if ($this->isImage()) {
            return 'image';
        }
        if ($this->isPdf()) {
            return 'file-pdf';
        }
        if (str_contains($this->mime_type, 'word') || str_contains($this->mime_type, 'document')) {
            return 'file-word';
        }
        if (str_contains($this->mime_type, 'excel') || str_contains($this->mime_type, 'spreadsheet')) {
            return 'file-excel';
        }
        return 'file';
    }
}
