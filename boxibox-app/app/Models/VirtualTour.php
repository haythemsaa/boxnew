<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VirtualTour extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'site_id',
        'name',
        'description',
        'is_active',
        'is_public',
        'autoplay',
        'start_panorama_id',
        'provider',
        'external_id',
        'embed_code',
        'external_url',
        'view_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'autoplay' => 'boolean',
        'view_count' => 'integer',
    ];

    protected $appends = ['panorama_count'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function panoramas(): HasMany
    {
        return $this->hasMany(VirtualTourPanorama::class)->orderBy('sort_order');
    }

    public function startPanorama(): BelongsTo
    {
        return $this->belongsTo(VirtualTourPanorama::class, 'start_panorama_id');
    }

    public function getPanoramaCountAttribute(): int
    {
        return $this->panoramas()->count();
    }

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function isExternal(): bool
    {
        return !empty($this->embed_code) || !empty($this->external_url);
    }

    public static function getProviderOptions(): array
    {
        return [
            'custom' => 'Personnalise',
            'matterport' => 'Matterport',
            'kuula' => 'Kuula',
            'cloudpano' => 'CloudPano',
            'pano2vr' => 'Pano2VR',
            'roundme' => 'Round.me',
            'teliportme' => 'TeliportMe',
            'other' => 'Autre',
        ];
    }
}
