<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class VirtualTourPanorama extends Model
{
    use HasFactory;

    protected $fillable = [
        'virtual_tour_id',
        'site_media_id',
        'name',
        'description',
        'image_path',
        'thumbnail_path',
        'sort_order',
        'initial_pitch',
        'initial_yaw',
        'initial_hfov',
        'hotspots',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'initial_pitch' => 'float',
        'initial_yaw' => 'float',
        'initial_hfov' => 'float',
        'hotspots' => 'array',
    ];

    protected $appends = ['image_url', 'thumbnail_url'];

    public function tour(): BelongsTo
    {
        return $this->belongsTo(VirtualTour::class, 'virtual_tour_id');
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(SiteMedia::class, 'site_media_id');
    }

    public function getImageUrlAttribute(): string
    {
        return Storage::url($this->image_path);
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if (!$this->thumbnail_path) {
            return null;
        }
        return Storage::url($this->thumbnail_path);
    }

    public function getFormattedHotspotsAttribute(): array
    {
        $hotspots = $this->hotspots ?? [];

        return array_map(function ($hotspot) {
            return [
                'id' => $hotspot['id'] ?? uniqid(),
                'pitch' => $hotspot['pitch'] ?? 0,
                'yaw' => $hotspot['yaw'] ?? 0,
                'type' => $hotspot['type'] ?? 'info', // info, scene, link
                'text' => $hotspot['text'] ?? '',
                'target_panorama_id' => $hotspot['target_panorama_id'] ?? null,
                'url' => $hotspot['url'] ?? null,
                'icon' => $hotspot['icon'] ?? 'info-circle',
            ];
        }, $hotspots);
    }
}
