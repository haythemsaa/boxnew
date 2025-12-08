<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanTemplate extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'thumbnail',
        'category',
        'suggested_width',
        'suggested_height',
        'template_data',
        'is_system',
        'is_public',
    ];

    protected $casts = [
        'template_data' => 'array',
        'is_public' => 'boolean',
        'is_system' => 'boolean',
        'suggested_width' => 'integer',
        'suggested_height' => 'integer',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // Scopes
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    // Methods
    public function getCategories(): array
    {
        return ['standard', 'premium', 'custom'];
    }
}
