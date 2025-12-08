<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalculatorWidget extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'site_id',
        'name',
        'embed_code',
        'style_config',
        'categories_enabled',
        'show_prices',
        'show_availability',
        'require_contact',
        'enable_booking',
        'redirect_url',
        'custom_css',
        'is_active',
        'views_count',
        'calculations_count',
        'leads_count',
    ];

    protected $casts = [
        'style_config' => 'array',
        'categories_enabled' => 'array',
        'show_prices' => 'boolean',
        'show_availability' => 'boolean',
        'require_contact' => 'boolean',
        'enable_booking' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function ($widget) {
            if (!$widget->embed_code) {
                $widget->embed_code = 'calc_' . bin2hex(random_bytes(8));
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getEmbedHtmlAttribute(): string
    {
        $url = config('app.url') . '/widget/calculator/' . $this->embed_code;
        return '<iframe src="' . $url . '" width="100%" height="600" frameborder="0"></iframe>';
    }
}
