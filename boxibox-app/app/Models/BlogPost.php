<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'author_id',
        'category_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'featured_image_alt',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'canonical_url',
        'og_title',
        'og_description',
        'og_image',
        'schema_data',
        'status',
        'published_at',
        'scheduled_at',
        'views_count',
        'reading_time',
        'is_featured',
        'allow_comments',
        'locale',
    ];

    protected $casts = [
        'schema_data' => 'array',
        'published_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'views_count' => 'integer',
        'reading_time' => 'integer',
        'is_featured' => 'boolean',
        'allow_comments' => 'boolean',
    ];

    protected $appends = ['url', 'formatted_date', 'schema_markup'];

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($post) {
            if (empty($post->slug)) {
                $post->slug = Str::slug($post->title);
            }
            // Calculate reading time (average 200 words per minute)
            $post->reading_time = max(1, ceil(str_word_count(strip_tags($post->content)) / 200));
        });

        static::updating(function ($post) {
            // Recalculate reading time on update
            $post->reading_time = max(1, ceil(str_word_count(strip_tags($post->content)) / 200));
        });
    }

    // Relationships
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'category_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(BlogTag::class, 'blog_post_tag');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(BlogComment::class, 'post_id');
    }

    public function approvedComments(): HasMany
    {
        return $this->hasMany(BlogComment::class, 'post_id')
            ->where('status', 'approved')
            ->whereNull('parent_id');
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->where('published_at', '<=', now());
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled')
            ->where('scheduled_at', '>', now());
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByLocale($query, $locale)
    {
        return $query->where('locale', $locale);
    }

    public function scopeByCategory($query, $categorySlug)
    {
        return $query->whereHas('category', function ($q) use ($categorySlug) {
            $q->where('slug', $categorySlug);
        });
    }

    public function scopeByTag($query, $tagSlug)
    {
        return $query->whereHas('tags', function ($q) use ($tagSlug) {
            $q->where('slug', $tagSlug);
        });
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
                ->orWhere('content', 'like', "%{$term}%")
                ->orWhere('excerpt', 'like', "%{$term}%");
        });
    }

    // Accessors
    public function getUrlAttribute(): string
    {
        return route('blog.show', $this->slug);
    }

    public function getFormattedDateAttribute(): string
    {
        return $this->published_at?->format('d M Y') ?? '';
    }

    public function getMetaTitleComputedAttribute(): string
    {
        return $this->meta_title ?? $this->title;
    }

    public function getMetaDescriptionComputedAttribute(): string
    {
        return $this->meta_description ?? Str::limit(strip_tags($this->excerpt ?? $this->content), 160);
    }

    public function getOgTitleComputedAttribute(): string
    {
        return $this->og_title ?? $this->title;
    }

    public function getOgDescriptionComputedAttribute(): string
    {
        return $this->og_description ?? $this->meta_description_computed;
    }

    public function getOgImageComputedAttribute(): ?string
    {
        return $this->og_image ?? $this->featured_image;
    }

    public function getSchemaMarkupAttribute(): array
    {
        // Generate Schema.org Article markup for SEO
        return $this->schema_data ?? [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $this->title,
            'description' => $this->meta_description_computed,
            'image' => $this->featured_image ? url($this->featured_image) : null,
            'author' => [
                '@type' => 'Person',
                'name' => $this->author?->name ?? 'Boxibox',
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'Boxibox',
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => url('/images/logo.png'),
                ],
            ],
            'datePublished' => $this->published_at?->toIso8601String(),
            'dateModified' => $this->updated_at?->toIso8601String(),
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $this->url,
            ],
            'wordCount' => str_word_count(strip_tags($this->content)),
            'articleSection' => $this->category?->name,
            'keywords' => $this->meta_keywords,
            'inLanguage' => $this->locale,
        ];
    }

    // Methods
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    public function publish(): void
    {
        $this->update([
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    public function archive(): void
    {
        $this->update(['status' => 'archived']);
    }

    public function getRelatedPosts(int $limit = 3)
    {
        return static::published()
            ->where('id', '!=', $this->id)
            ->where(function ($query) {
                // Same category
                if ($this->category_id) {
                    $query->where('category_id', $this->category_id);
                }
                // Same tags
                if ($this->tags->isNotEmpty()) {
                    $query->orWhereHas('tags', function ($q) {
                        $q->whereIn('blog_tags.id', $this->tags->pluck('id'));
                    });
                }
            })
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }
}
