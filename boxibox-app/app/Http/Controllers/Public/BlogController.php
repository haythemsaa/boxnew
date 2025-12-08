<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\BlogCategory;
use App\Models\BlogTag;
use App\Models\BlogComment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    /**
     * Display blog index with all posts
     */
    public function index(Request $request): Response
    {
        $locale = app()->getLocale();

        $posts = BlogPost::published()
            ->byLocale($locale)
            ->with(['author:id,name', 'category:id,name,slug', 'tags:id,name,slug'])
            ->when($request->search, fn($q, $search) => $q->search($search))
            ->orderByDesc('published_at')
            ->paginate(12)
            ->withQueryString();

        $categories = BlogCategory::active()
            ->ordered()
            ->withCount(['publishedPosts' => fn($q) => $q->byLocale($locale)])
            ->get();

        $featuredPosts = BlogPost::published()
            ->byLocale($locale)
            ->featured()
            ->with(['author:id,name', 'category:id,name,slug'])
            ->limit(3)
            ->get();

        $popularTags = BlogTag::popular(15)->get();

        return Inertia::render('Public/Blog/Index', [
            'posts' => $posts,
            'categories' => $categories,
            'featuredPosts' => $featuredPosts,
            'popularTags' => $popularTags,
            'filters' => $request->only(['search']),
            'seo' => [
                'title' => __('Blog - Actualités Self-Stockage | Boxibox'),
                'description' => __('Découvrez nos articles sur le self-stockage, conseils pratiques, tendances du marché et actualités du secteur.'),
            ],
        ]);
    }

    /**
     * Display a single blog post
     */
    public function show(string $slug): Response
    {
        $post = BlogPost::published()
            ->where('slug', $slug)
            ->with([
                'author:id,name',
                'category:id,name,slug',
                'tags:id,name,slug',
                'approvedComments' => fn($q) => $q->with('replies')->orderByDesc('created_at'),
            ])
            ->firstOrFail();

        // Increment views (use session to avoid duplicate counts)
        $viewedKey = 'blog_viewed_' . $post->id;
        if (!session()->has($viewedKey)) {
            $post->incrementViews();
            session()->put($viewedKey, true);
        }

        $relatedPosts = $post->getRelatedPosts(4);

        return Inertia::render('Public/Blog/Show', [
            'post' => $post,
            'relatedPosts' => $relatedPosts,
            'seo' => [
                'title' => $post->meta_title_computed,
                'description' => $post->meta_description_computed,
                'ogTitle' => $post->og_title_computed,
                'ogDescription' => $post->og_description_computed,
                'ogImage' => $post->og_image_computed,
                'canonical' => $post->canonical_url ?? $post->url,
                'schema' => $post->schema_markup,
            ],
        ]);
    }

    /**
     * Display posts by category
     */
    public function category(string $slug): Response
    {
        $locale = app()->getLocale();

        $category = BlogCategory::where('slug', $slug)
            ->active()
            ->firstOrFail();

        $posts = BlogPost::published()
            ->byLocale($locale)
            ->where('category_id', $category->id)
            ->with(['author:id,name', 'tags:id,name,slug'])
            ->orderByDesc('published_at')
            ->paginate(12);

        $categories = BlogCategory::active()
            ->ordered()
            ->withCount(['publishedPosts' => fn($q) => $q->byLocale($locale)])
            ->get();

        return Inertia::render('Public/Blog/Category', [
            'category' => $category,
            'posts' => $posts,
            'categories' => $categories,
            'seo' => [
                'title' => $category->meta_title ?? $category->name . ' - Blog Boxibox',
                'description' => $category->meta_description ?? "Articles sur {$category->name} - conseils et actualités self-stockage",
            ],
        ]);
    }

    /**
     * Display posts by tag
     */
    public function tag(string $slug): Response
    {
        $locale = app()->getLocale();

        $tag = BlogTag::where('slug', $slug)->firstOrFail();

        $posts = BlogPost::published()
            ->byLocale($locale)
            ->byTag($slug)
            ->with(['author:id,name', 'category:id,name,slug', 'tags:id,name,slug'])
            ->orderByDesc('published_at')
            ->paginate(12);

        return Inertia::render('Public/Blog/Tag', [
            'tag' => $tag,
            'posts' => $posts,
            'seo' => [
                'title' => "Articles tagués \"{$tag->name}\" - Blog Boxibox",
                'description' => "Découvrez tous les articles sur {$tag->name} - conseils et actualités self-stockage",
            ],
        ]);
    }

    /**
     * Store a comment on a blog post
     */
    public function storeComment(Request $request, BlogPost $post)
    {
        if (!$post->allow_comments) {
            return back()->with('error', __('Les commentaires sont désactivés pour cet article.'));
        }

        $validated = $request->validate([
            'author_name' => 'required|string|max:100',
            'author_email' => 'required|email|max:255',
            'content' => 'required|string|min:10|max:2000',
            'parent_id' => 'nullable|exists:blog_comments,id',
        ]);

        $comment = $post->comments()->create([
            ...$validated,
            'user_id' => auth()->id(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => 'pending', // Moderation required
        ]);

        return back()->with('success', __('Votre commentaire a été soumis et sera publié après modération.'));
    }

    /**
     * Generate RSS feed
     */
    public function rss()
    {
        $locale = app()->getLocale();

        $posts = BlogPost::published()
            ->byLocale($locale)
            ->with(['author:id,name', 'category:id,name'])
            ->orderByDesc('published_at')
            ->limit(50)
            ->get();

        return response()
            ->view('blog.rss', ['posts' => $posts])
            ->header('Content-Type', 'application/rss+xml; charset=utf-8');
    }

    /**
     * Generate sitemap for blog
     */
    public function sitemap()
    {
        $posts = BlogPost::published()
            ->select(['slug', 'updated_at'])
            ->orderByDesc('updated_at')
            ->get();

        $categories = BlogCategory::active()
            ->select(['slug', 'updated_at'])
            ->get();

        return response()
            ->view('blog.sitemap', [
                'posts' => $posts,
                'categories' => $categories,
            ])
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }
}
