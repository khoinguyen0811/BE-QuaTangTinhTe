<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostCategory;
use App\Services\PostSeoAnalyzer;
use App\Services\SeoGateSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PostController extends Controller
{
    public function __construct(private readonly SeoGateSettings $seoGateSettings)
    {
    }

    /**
     * Display a listing of posts.
     */
    public function index(Request $request)
    {
        $posts = Post::query()
            ->with('category')
            ->when($request->query('q'), function ($query, $keyword) {
                $query->where('title', 'like', "%{$keyword}%")
                    ->orWhere('slug', 'like', "%{$keyword}%");
            })
            ->when($request->query('category_id'), function ($query, $categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when($request->filled('status'), function ($query) {
                $query->where('is_active', request('status'));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $categories = $this->categoryOptions();

        return view('admin.posts.index', compact('posts', 'categories'));
    }

    /**
     * Show the form for creating a new post.
     */
    public function create()
    {
        $post = new Post([
            'is_active' => false,
        ]);
        $categories = $this->categoryOptions();
        $seoStrictMode = $this->seoGateSettings->strictModeEnabled();

        return view('admin.posts.create', compact('post', 'categories', 'seoStrictMode'));
    }

    /**
     * Store a newly created post.
     */
    public function store(Request $request, PostSeoAnalyzer $seoAnalyzer)
    {
        $validated = $this->validatePost($request);

        $locale = app()->getLocale() ?: 'vi';
        $title = trim($validated['title']);
        $slug = $this->uniqueSlug($validated['slug'] ?? $title);
        $isActive = $request->boolean('is_active');
        $imageDimensions = $this->uploadedImageDimensions($request);

        $this->enforcePublishGate(
            $seoAnalyzer,
            array_merge($validated, ['slug' => $slug]),
            null,
            $locale,
            $isActive,
            $request->hasFile('image_file'),
            $imageDimensions
        );

        $imageUrl = null;
        if ($request->hasFile('image_file')) {
            $cloudinaryService = app(\App\Services\CloudinaryService::class);
            $imageUrl = $cloudinaryService->uploadFile($request->file('image_file'), 'posts');
        }

        $post = Post::create([
            'category_id' => $validated['category_id'] ?? null,
            'title' => [$locale => $title],
            'slug' => $slug,
            'summary' => ! empty(trim((string) ($validated['summary'] ?? ''))) ? [$locale => trim($validated['summary'])] : null,
            'content' => [$locale => $validated['content']],
            'image_url' => $imageUrl,
            'is_active' => $isActive,
            'seo_title' => ! empty(trim((string) ($validated['seo_title'] ?? ''))) ? [$locale => trim($validated['seo_title'])] : null,
            'seo_description' => ! empty(trim((string) ($validated['seo_description'] ?? ''))) ? [$locale => trim($validated['seo_description'])] : null,
            'seo_keys' => ! empty(trim((string) ($validated['seo_keys'] ?? ''))) ? trim($validated['seo_keys']) : null,
            'published_at' => $isActive ? now() : null,
        ]);

        return redirect()
            ->route('admin.posts.index')
            ->with('success', __('admin.posts.created'));
    }

    /**
     * Display post detail sheet (redirects or standard details).
     */
    public function show(string $locale, Post $post)
    {
        return redirect()->route('admin.posts.edit', $post);
    }

    /**
     * Show the form for editing the post.
     */
    public function edit(string $locale, Post $post)
    {
        $categories = $this->categoryOptions();
        $seoStrictMode = $this->seoGateSettings->strictModeEnabled();

        return view('admin.posts.edit', compact('post', 'categories', 'seoStrictMode'));
    }

    /**
     * Update the post.
     */
    public function update(Request $request, string $locale, Post $post, PostSeoAnalyzer $seoAnalyzer)
    {
        $validated = $this->validatePost($request, $post);

        $title = trim($validated['title']);
        $slug = $this->uniqueSlug($validated['slug'] ?? $title, $post);
        $isActive = $request->boolean('is_active');
        $imageDimensions = $this->uploadedImageDimensions($request);

        $this->enforcePublishGate(
            $seoAnalyzer,
            array_merge($validated, ['slug' => $slug]),
            $post,
            $locale,
            $isActive,
            $request->hasFile('image_file') || (bool) $post->image_url,
            $imageDimensions
        );

        $imageUrl = $post->image_url;
        if ($request->hasFile('image_file')) {
            $cloudinaryService = app(\App\Services\CloudinaryService::class);
            $imageUrl = $cloudinaryService->uploadFile($request->file('image_file'), 'posts');
        }

        $titleTrans = $post->getTranslations('title');
        $titleTrans[$locale] = $title;

        $summaryTrans = $post->getTranslations('summary');
        if (isset($validated['summary']) && trim($validated['summary']) !== '') {
            $summaryTrans[$locale] = trim($validated['summary']);
        } else {
            unset($summaryTrans[$locale]);
        }

        $contentTrans = $post->getTranslations('content');
        $contentTrans[$locale] = $validated['content'];

        $seoTitleTrans = $post->getTranslations('seo_title');
        if (isset($validated['seo_title']) && trim($validated['seo_title']) !== '') {
            $seoTitleTrans[$locale] = trim($validated['seo_title']);
        } else {
            unset($seoTitleTrans[$locale]);
        }

        $seoDescTrans = $post->getTranslations('seo_description');
        if (isset($validated['seo_description']) && trim($validated['seo_description']) !== '') {
            $seoDescTrans[$locale] = trim($validated['seo_description']);
        } else {
            unset($seoDescTrans[$locale]);
        }

        $post->update([
            'category_id' => $validated['category_id'] ?? null,
            'title' => $titleTrans,
            'slug' => $slug,
            'summary' => $summaryTrans,
            'content' => $contentTrans,
            'image_url' => $imageUrl,
            'is_active' => $isActive,
            'seo_title' => $seoTitleTrans,
            'seo_description' => $seoDescTrans,
            'seo_keys' => ! empty(trim((string) ($validated['seo_keys'] ?? ''))) ? trim($validated['seo_keys']) : null,
            'published_at' => $isActive ? ($post->published_at ?: now()) : null,
        ]);

        return redirect()
            ->route('admin.posts.index')
            ->with('success', __('admin.posts.updated'));
    }

    /**
     * Remove the post.
     */
    public function destroy(string $locale, Post $post)
    {
        $post->delete();
        return redirect()
            ->route('admin.posts.index')
            ->with('success', __('admin.posts.deleted'));
    }

    /**
     * Live analysis endpoint. The same service is used again on save, so the browser cannot bypass the gate.
     */
    public function analyzeSeo(Request $request, PostSeoAnalyzer $seoAnalyzer)
    {
        $validated = $request->validate([
            'post_id' => 'nullable|integer|exists:posts,id',
            'title' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255',
            'category_id' => 'nullable|integer|exists:post_categories,id',
            'summary' => 'nullable|string|max:1000',
            'content' => 'nullable|string',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'seo_keys' => 'nullable|string|max:255',
            'has_featured_image' => 'nullable|boolean',
            'featured_image_width' => 'nullable|integer|min:0|max:20000',
            'featured_image_height' => 'nullable|integer|min:0|max:20000',
        ]);
        $post = ! empty($validated['post_id']) ? Post::query()->find($validated['post_id']) : null;
        $validated['slug'] = Str::slug((string) ($validated['slug'] ?? $validated['title'] ?? ''));

        $analysis = $seoAnalyzer->analyze(
            $validated,
            $post,
            app()->getLocale() ?: 'vi',
            (bool) ($validated['has_featured_image'] ?? false),
            isset($validated['featured_image_width']) ? (int) $validated['featured_image_width'] : null,
            isset($validated['featured_image_height']) ? (int) $validated['featured_image_height'] : null,
        );
        $analysis['strict_mode_enabled'] = $this->seoGateSettings->strictModeEnabled();
        $analysis['publishing_allowed'] = ! $analysis['strict_mode_enabled'] || $analysis['ready_to_publish'];

        return response()->json($analysis);
    }

    private function validatePost(Request $request, ?Post $post = null): array
    {
        $postId = $post?->getKey();

        return $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:posts,slug'.($postId ? ','.$postId : ''),
            'category_id' => 'nullable|exists:post_categories,id',
            'summary' => 'nullable|string|max:1000',
            'content' => 'required|string',
            'image_file' => 'nullable|image|max:4096|dimensions:min_width=1200,min_height=630',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'seo_keys' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);
    }

    private function enforcePublishGate(
        PostSeoAnalyzer $seoAnalyzer,
        array $data,
        ?Post $post,
        string $locale,
        bool $isActive,
        bool $hasFeaturedImage,
        array $imageDimensions,
    ): void {
        if (! $isActive || ! $this->seoGateSettings->strictModeEnabled()) {
            return;
        }

        $analysis = $seoAnalyzer->analyze(
            $data,
            $post,
            $locale,
            $hasFeaturedImage,
            $imageDimensions['width'],
            $imageDimensions['height'],
        );

        if ($analysis['ready_to_publish']) {
            return;
        }

        throw ValidationException::withMessages([
            'seo_gate' => array_map(
                fn (array $rule): string => $rule['label'].' — '.$rule['detail'],
                $analysis['failed_rules']
            ),
        ]);
    }

    private function uploadedImageDimensions(Request $request): array
    {
        if (! $request->hasFile('image_file')) {
            return ['width' => null, 'height' => null];
        }

        $dimensions = @getimagesize($request->file('image_file')->getRealPath()) ?: [];

        return [
            'width' => isset($dimensions[0]) ? (int) $dimensions[0] : null,
            'height' => isset($dimensions[1]) ? (int) $dimensions[1] : null,
        ];
    }

    private function uniqueSlug(string $source, ?Post $post = null): string
    {
        $base = Str::slug($source) ?: Str::random(8);
        $slug = $base;
        $suffix = 2;

        while (Post::query()
            ->when($post, fn ($query) => $query->whereKeyNot($post->getKey()))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    /**
     * Helper to list hierarchy categories options
     */
    private function categoryOptions()
    {
        $allCategories = PostCategory::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $grouped = $allCategories->groupBy('parent_id');
        $rootCategories = $allCategories->whereNull('parent_id');

        $flatOptions = collect();

        $flatten = function ($categories, $depth = 0) use (&$flatten, &$flatOptions, $grouped) {
            foreach ($categories as $category) {
                $category->depth = $depth;
                $flatOptions->push($category);

                $children = $grouped->get($category->id) ?? collect();
                if ($children->isNotEmpty()) {
                    $flatten($children, $depth + 1);
                }
            }
        };

        $flatten($rootCategories);

        return $flatOptions;
    }
}
