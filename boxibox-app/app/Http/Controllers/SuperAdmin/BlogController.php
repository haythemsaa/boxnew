<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\BlogCategory;
use App\Models\BlogTag;
use App\Models\BlogComment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    /**
     * Display list of all blog posts for admin
     */
    public function index(Request $request): Response
    {
        $posts = BlogPost::with(['author:id,name', 'category:id,name'])
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->when($request->category, fn($q, $cat) => $q->where('category_id', $cat))
            ->when($request->search, fn($q, $search) => $q->search($search))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $categories = BlogCategory::ordered()->get(['id', 'name']);

        $stats = [
            'total' => BlogPost::count(),
            'published' => BlogPost::where('status', 'published')->count(),
            'draft' => BlogPost::where('status', 'draft')->count(),
            'scheduled' => BlogPost::where('status', 'scheduled')->count(),
        ];

        return Inertia::render('SuperAdmin/Blog/Index', [
            'posts' => $posts,
            'categories' => $categories,
            'stats' => $stats,
            'filters' => $request->only(['status', 'category', 'search']),
        ]);
    }

    /**
     * Show form for creating a new blog post
     */
    public function create(): Response
    {
        $categories = BlogCategory::ordered()->get(['id', 'name']);
        $tags = BlogTag::orderBy('name')->get(['id', 'name', 'slug']);
        $authors = User::where('is_super_admin', true)
            ->orWhereHas('roles', fn($q) => $q->where('name', 'admin'))
            ->get(['id', 'name']);

        return Inertia::render('SuperAdmin/Blog/Create', [
            'categories' => $categories,
            'tags' => $tags,
            'authors' => $authors,
            'locales' => [
                ['code' => 'fr', 'name' => 'Français'],
                ['code' => 'en', 'name' => 'English'],
                ['code' => 'nl', 'name' => 'Nederlands'],
            ],
        ]);
    }

    /**
     * Store a new blog post
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:blog_posts,slug',
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string',
            'category_id' => 'nullable|exists:blog_categories,id',
            'author_id' => 'nullable|exists:users,id',
            'featured_image' => 'nullable|image|max:5120',
            'featured_image_alt' => 'nullable|string|max:255',
            'meta_title' => 'nullable|string|max:70',
            'meta_description' => 'nullable|string|max:160',
            'meta_keywords' => 'nullable|string|max:255',
            'canonical_url' => 'nullable|url|max:255',
            'og_title' => 'nullable|string|max:70',
            'og_description' => 'nullable|string|max:200',
            'og_image' => 'nullable|image|max:2048',
            'status' => 'required|in:draft,published,scheduled',
            'scheduled_at' => 'nullable|required_if:status,scheduled|date|after:now',
            'is_featured' => 'boolean',
            'allow_comments' => 'boolean',
            'locale' => 'required|in:fr,en,nl,es',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:blog_tags,id',
        ]);

        // Handle featured image upload
        if ($request->hasFile('featured_image')) {
            $validated['featured_image'] = $request->file('featured_image')
                ->store('blog/images', 'public');
        }

        // Handle OG image upload
        if ($request->hasFile('og_image')) {
            $validated['og_image'] = $request->file('og_image')
                ->store('blog/og', 'public');
        }

        // Set author
        $validated['author_id'] = $validated['author_id'] ?? auth()->id();

        // Set published_at if publishing
        if ($validated['status'] === 'published') {
            $validated['published_at'] = now();
        }

        // Generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        // Extract tags before creating
        $tags = $validated['tags'] ?? [];
        unset($validated['tags']);

        $post = BlogPost::create($validated);

        // Attach tags
        if (!empty($tags)) {
            $post->tags()->attach($tags);
        }

        return redirect()
            ->route('superadmin.blog.index')
            ->with('success', __('Article créé avec succès.'));
    }

    /**
     * Show form for editing a blog post
     */
    public function edit(BlogPost $post): Response
    {
        $post->load(['tags:id,name,slug']);

        $categories = BlogCategory::ordered()->get(['id', 'name']);
        $tags = BlogTag::orderBy('name')->get(['id', 'name', 'slug']);
        $authors = User::where('is_super_admin', true)
            ->orWhereHas('roles', fn($q) => $q->where('name', 'admin'))
            ->get(['id', 'name']);

        return Inertia::render('SuperAdmin/Blog/Edit', [
            'post' => $post,
            'categories' => $categories,
            'tags' => $tags,
            'authors' => $authors,
            'locales' => [
                ['code' => 'fr', 'name' => 'Français'],
                ['code' => 'en', 'name' => 'English'],
                ['code' => 'nl', 'name' => 'Nederlands'],
            ],
        ]);
    }

    /**
     * Update a blog post
     */
    public function update(Request $request, BlogPost $post)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:blog_posts,slug,' . $post->id,
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string',
            'category_id' => 'nullable|exists:blog_categories,id',
            'author_id' => 'nullable|exists:users,id',
            'featured_image' => 'nullable|image|max:5120',
            'featured_image_alt' => 'nullable|string|max:255',
            'meta_title' => 'nullable|string|max:70',
            'meta_description' => 'nullable|string|max:160',
            'meta_keywords' => 'nullable|string|max:255',
            'canonical_url' => 'nullable|url|max:255',
            'og_title' => 'nullable|string|max:70',
            'og_description' => 'nullable|string|max:200',
            'og_image' => 'nullable|image|max:2048',
            'status' => 'required|in:draft,published,scheduled,archived',
            'scheduled_at' => 'nullable|required_if:status,scheduled|date|after:now',
            'is_featured' => 'boolean',
            'allow_comments' => 'boolean',
            'locale' => 'required|in:fr,en,nl,es',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:blog_tags,id',
        ]);

        // Handle featured image upload
        if ($request->hasFile('featured_image')) {
            // Delete old image
            if ($post->featured_image) {
                Storage::disk('public')->delete($post->featured_image);
            }
            $validated['featured_image'] = $request->file('featured_image')
                ->store('blog/images', 'public');
        }

        // Handle OG image upload
        if ($request->hasFile('og_image')) {
            if ($post->og_image) {
                Storage::disk('public')->delete($post->og_image);
            }
            $validated['og_image'] = $request->file('og_image')
                ->store('blog/og', 'public');
        }

        // Set published_at if publishing for the first time
        if ($validated['status'] === 'published' && !$post->published_at) {
            $validated['published_at'] = now();
        }

        // Extract tags before updating
        $tags = $validated['tags'] ?? [];
        unset($validated['tags']);

        $post->update($validated);

        // Sync tags
        $post->tags()->sync($tags);

        return redirect()
            ->route('superadmin.blog.index')
            ->with('success', __('Article mis à jour avec succès.'));
    }

    /**
     * Delete a blog post
     */
    public function destroy(BlogPost $post)
    {
        // Delete images
        if ($post->featured_image) {
            Storage::disk('public')->delete($post->featured_image);
        }
        if ($post->og_image) {
            Storage::disk('public')->delete($post->og_image);
        }

        $post->delete();

        return redirect()
            ->route('superadmin.blog.index')
            ->with('success', __('Article supprimé avec succès.'));
    }

    /**
     * Quick publish a post
     */
    public function publish(BlogPost $post)
    {
        $post->publish();

        return back()->with('success', __('Article publié avec succès.'));
    }

    /**
     * Quick archive a post
     */
    public function archive(BlogPost $post)
    {
        $post->archive();

        return back()->with('success', __('Article archivé avec succès.'));
    }

    // =============================================
    // CATEGORIES MANAGEMENT
    // =============================================

    public function categories(): Response
    {
        $categories = BlogCategory::withCount('publishedPosts')
            ->ordered()
            ->get();

        return Inertia::render('SuperAdmin/Blog/Categories', [
            'categories' => $categories,
        ]);
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:100|unique:blog_categories,slug',
            'description' => 'nullable|string|max:500',
            'meta_title' => 'nullable|string|max:70',
            'meta_description' => 'nullable|string|max:160',
            'image' => 'nullable|image|max:2048',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')
                ->store('blog/categories', 'public');
        }

        BlogCategory::create($validated);

        return back()->with('success', __('Catégorie créée avec succès.'));
    }

    public function updateCategory(Request $request, BlogCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:100|unique:blog_categories,slug,' . $category->id,
            'description' => 'nullable|string|max:500',
            'meta_title' => 'nullable|string|max:70',
            'meta_description' => 'nullable|string|max:160',
            'image' => 'nullable|image|max:2048',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('image')) {
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }
            $validated['image'] = $request->file('image')
                ->store('blog/categories', 'public');
        }

        $category->update($validated);

        return back()->with('success', __('Catégorie mise à jour avec succès.'));
    }

    public function destroyCategory(BlogCategory $category)
    {
        if ($category->posts()->count() > 0) {
            return back()->with('error', __('Impossible de supprimer une catégorie contenant des articles.'));
        }

        if ($category->image) {
            Storage::disk('public')->delete($category->image);
        }

        $category->delete();

        return back()->with('success', __('Catégorie supprimée avec succès.'));
    }

    // =============================================
    // TAGS MANAGEMENT
    // =============================================

    public function tags(): Response
    {
        $tags = BlogTag::withCount('publishedPosts')
            ->orderBy('name')
            ->get();

        return Inertia::render('SuperAdmin/Blog/Tags', [
            'tags' => $tags,
        ]);
    }

    public function storeTag(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'slug' => 'nullable|string|max:50|unique:blog_tags,slug',
        ]);

        BlogTag::create($validated);

        return back()->with('success', __('Tag créé avec succès.'));
    }

    public function updateTag(Request $request, BlogTag $tag)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'slug' => 'nullable|string|max:50|unique:blog_tags,slug,' . $tag->id,
        ]);

        $tag->update($validated);

        return back()->with('success', __('Tag mis à jour avec succès.'));
    }

    public function destroyTag(BlogTag $tag)
    {
        $tag->posts()->detach();
        $tag->delete();

        return back()->with('success', __('Tag supprimé avec succès.'));
    }

    // =============================================
    // COMMENTS MANAGEMENT
    // =============================================

    public function comments(Request $request): Response
    {
        $comments = BlogComment::with(['post:id,title,slug', 'user:id,name'])
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->when($request->search, fn($q, $search) =>
                $q->where('author_name', 'like', "%{$search}%")
                    ->orWhere('author_email', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%")
            )
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        $stats = [
            'pending' => BlogComment::where('status', 'pending')->count(),
            'approved' => BlogComment::where('status', 'approved')->count(),
            'spam' => BlogComment::where('status', 'spam')->count(),
        ];

        return Inertia::render('SuperAdmin/Blog/Comments', [
            'comments' => $comments,
            'stats' => $stats,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    public function approveComment(BlogComment $comment)
    {
        $comment->approve();
        return back()->with('success', __('Commentaire approuvé.'));
    }

    public function rejectComment(BlogComment $comment)
    {
        $comment->reject();
        return back()->with('success', __('Commentaire rejeté.'));
    }

    public function spamComment(BlogComment $comment)
    {
        $comment->markAsSpam();
        return back()->with('success', __('Commentaire marqué comme spam.'));
    }

    public function destroyComment(BlogComment $comment)
    {
        $comment->delete();
        return back()->with('success', __('Commentaire supprimé.'));
    }

    /**
     * Bulk actions for comments
     */
    public function bulkCommentAction(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:blog_comments,id',
            'action' => 'required|in:approve,reject,spam,delete',
        ]);

        $comments = BlogComment::whereIn('id', $validated['ids']);

        switch ($validated['action']) {
            case 'approve':
                $comments->update(['status' => 'approved']);
                break;
            case 'reject':
                $comments->update(['status' => 'rejected']);
                break;
            case 'spam':
                $comments->update(['status' => 'spam']);
                break;
            case 'delete':
                $comments->delete();
                break;
        }

        return back()->with('success', __('Action effectuée avec succès.'));
    }
}
