<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class SiteMedia extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'site_media';

    protected $fillable = [
        'tenant_id',
        'site_id',
        'box_id',
        'type',
        'title',
        'description',
        'alt_text',
        'filename',
        'original_name',
        'mime_type',
        'size',
        'path',
        'thumbnail_path',
        'width',
        'height',
        'is_360',
        'hotspots',
        'duration_seconds',
        'video_url',
        'tour_provider',
        'tour_embed_code',
        'tour_external_url',
        'sort_order',
        'is_featured',
        'is_public',
        'show_on_widget',
        'show_on_portal',
        'category',
        'uploaded_by',
    ];

    protected $casts = [
        'is_360' => 'boolean',
        'is_featured' => 'boolean',
        'is_public' => 'boolean',
        'show_on_widget' => 'boolean',
        'show_on_portal' => 'boolean',
        'hotspots' => 'array',
        'width' => 'integer',
        'height' => 'integer',
        'size' => 'integer',
        'duration_seconds' => 'integer',
        'sort_order' => 'integer',
    ];

    protected $appends = ['url', 'thumbnail_url', 'formatted_size', 'category_label'];

    // ==========================================
    // RELATIONS
    // ==========================================

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function box(): BelongsTo
    {
        return $this->belongsTo(Box::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    public function getUrlAttribute(): string
    {
        return Storage::url($this->path);
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if (!$this->thumbnail_path) {
            return null;
        }
        return Storage::url($this->thumbnail_path);
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

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'exterior' => 'Vue extérieure',
            'interior' => 'Vue intérieure',
            'entrance' => 'Entrée',
            'corridor' => 'Couloir',
            'box' => 'Box de stockage',
            'security' => 'Sécurité',
            'amenities' => 'Équipements',
            'surroundings' => 'Environnement',
            'team' => 'Équipe',
            'other' => 'Autre',
            default => ucfirst($this->category),
        };
    }

    public function getTypeIconAttribute(): string
    {
        return match ($this->type) {
            'photo' => 'image',
            'video' => 'video',
            'photo_360' => 'globe',
            'virtual_tour' => 'vr-cardboard',
            default => 'file',
        };
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopePhotos($query)
    {
        return $query->where('type', 'photo');
    }

    public function scopeVideos($query)
    {
        return $query->where('type', 'video');
    }

    public function scopePhotos360($query)
    {
        return $query->whereIn('type', ['photo_360', 'virtual_tour']);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeForWidget($query)
    {
        return $query->where('show_on_widget', true);
    }

    public function scopeForPortal($query)
    {
        return $query->where('show_on_portal', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at', 'desc');
    }

    // ==========================================
    // METHODS
    // ==========================================

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function isVideo(): bool
    {
        return str_starts_with($this->mime_type, 'video/') || $this->type === 'video';
    }

    public function is360(): bool
    {
        return $this->is_360 || $this->type === 'photo_360';
    }

    public static function getCategoryOptions(): array
    {
        return [
            'exterior' => 'Vue extérieure',
            'interior' => 'Vue intérieure',
            'entrance' => 'Entrée',
            'corridor' => 'Couloir',
            'box' => 'Box de stockage',
            'security' => 'Sécurité',
            'amenities' => 'Équipements',
            'surroundings' => 'Environnement',
            'team' => 'Équipe',
            'other' => 'Autre',
        ];
    }

    public static function getTypeOptions(): array
    {
        return [
            'photo' => 'Photo',
            'video' => 'Vidéo',
            'photo_360' => 'Photo 360°',
            'virtual_tour' => 'Visite virtuelle',
        ];
    }
}
