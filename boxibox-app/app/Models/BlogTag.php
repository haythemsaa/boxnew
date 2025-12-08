<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class BlogTag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    // Boot method to auto-generate slug
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });
    }

    // Relationships
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(BlogPost::class, 'blog_post_tag');
    }

    public function publishedPosts(): BelongsToMany
    {
        return $this->belongsToMany(BlogPost::class, 'blog_post_tag')
            ->where('status', 'published')
            ->where('published_at', '<=', now());
    }

    // Scopes
    public function scopePopular($query, int $limit = 10)
    {
        return $query->withCount('publishedPosts')
            ->orderByDesc('published_posts_count')
            ->limit($limit);
    }

    public function scopeWithPostCount($query)
    {
        return $query->withCount('publishedPosts');
    }

    // Accessors
    public function getPostsCountAttribute(): int
    {
        return $this->publishedPosts()->count();
    }

    public function getUrlAttribute(): string
    {
        return route('blog.tag', $this->slug);
    }
}
