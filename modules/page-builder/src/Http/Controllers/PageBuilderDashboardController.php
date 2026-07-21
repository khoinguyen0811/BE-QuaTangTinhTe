<?php

namespace HansSchouten\LaravelPageBuilder\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CustomPage;
use App\Services\CreateCustomPageWithBuilderService;
use HansSchouten\LaravelPageBuilder\Models\PageBuilderPage;
use HansSchouten\LaravelPageBuilder\Models\PageBuilderPageTranslation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PageBuilderDashboardController extends Controller
{
    private const RESERVED_SLUGS = [
        'admin', 'api', 'login', 'logout', 'register', 'storage', 'assets', 'build',
        'san-pham', 'bai-viet', 'collection', 'cart', 'checkout', 'pages', 'posts',
        'products', 'categories',
    ];

    public function __construct()
    {
        if (!config('features.visual_page_builder_enabled')) {
            abort(403, 'Trình thiết kế Visual Page Builder chưa được kích hoạt ở chế độ thử nghiệm (Lab Mode).');
        }
    }

    public function dashboard(string $locale)
    {
        Gate::authorize('viewAny', CustomPage::class);

        $totalPages = CustomPage::count();
        $activePages = CustomPage::where('is_active', true)->count();
        $publishedPages = CustomPage::published()->count();

        return view('pagebuilder::dashboard', compact('totalPages', 'activePages', 'publishedPages'));
    }

    public function index(string $locale)
    {
        Gate::authorize('viewAny', CustomPage::class);

        $pages = CustomPage::latest()
            ->paginate(15);

        return view('pagebuilder::pages.index', compact('pages'));
    }

    public function create(string $locale)
    {
        Gate::authorize('create', CustomPage::class);
        return view('pagebuilder::pages.create');
    }

    public function store(Request $request, string $locale, CreateCustomPageWithBuilderService $createService)
    {
        Gate::authorize('create', CustomPage::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:1000'],
            'seo_image' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $slug = Str::slug($request->input('slug') ?: $request->input('title'));
        if (in_array($slug, self::RESERVED_SLUGS, true)) {
            return back()->withErrors(['slug' => 'Đường dẫn này trùng với các đường dẫn hệ thống đã được bảo vệ.'])->withInput();
        }

        if (CustomPage::query()->where('slug', $slug)->exists()) {
            return back()->withErrors(['slug' => 'Đường dẫn này đã được sử dụng bởi một trang khác.'])->withInput();
        }

        $validated['slug'] = $slug;
        $validated['locale'] = $locale;
        $validated['is_active'] = $request->has('is_active') ? (bool)$request->input('is_active') : true;

        $page = $createService->create($validated, auth()->id());

        Log::info('custom_page.created', [
            'page_id' => $page->id,
            'slug' => $page->slug,
            'actor_id' => auth()->id(),
            'lock_version' => $page->lock_version ?? 1,
            'timestamp' => now()->toIso8601String(),
        ]);

        return redirect()->route('pagebuilder.pages.index', ['locale' => $locale])
            ->with('success', 'Đã tạo trang visual page builder mới thành công.');
    }

    public function edit(string $locale, CustomPage $page)
    {
        Gate::authorize('update', $page);
        return view('pagebuilder::pages.edit', compact('page'));
    }

    public function update(Request $request, string $locale, CustomPage $page)
    {
        Gate::authorize('update', $page);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:1000'],
            'seo_image' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $slug = Str::slug($request->input('slug') ?: $request->input('title'));
        if (in_array($slug, self::RESERVED_SLUGS, true)) {
            return back()->withErrors(['slug' => 'Đường dẫn này trùng với các đường dẫn hệ thống đã được bảo vệ.'])->withInput();
        }

        if (CustomPage::query()->where('slug', $slug)->where('id', '!=', $page->id)->exists()) {
            return back()->withErrors(['slug' => 'Đường dẫn này đã được sử dụng bởi một trang khác.'])->withInput();
        }

        $oldSlug = $page->slug;

        DB::transaction(function () use ($page, $validated, $slug, $request, $locale) {
            $page->update([
                'title' => $validated['title'],
                'slug' => $slug,
                'seo_title' => $validated['seo_title'] ?? null,
                'seo_description' => $validated['seo_description'] ?? null,
                'seo_image' => $validated['seo_image'] ?? null,
                'is_active' => $request->has('is_active') ? (bool)$request->input('is_active') : true,
                'updated_by' => auth()->id(),
            ]);

            // Update associated builder translation
            if ($page->builder_page_id) {
                PageBuilderPageTranslation::updateOrCreate(
                    ['page_id' => $page->builder_page_id, 'locale' => $locale],
                    [
                        'title' => $validated['title'],
                        'meta_title' => $validated['seo_title'] ?? $validated['title'],
                        'meta_description' => $validated['seo_description'] ?? '',
                        'route' => $slug,
                    ]
                );
            }

            DB::afterCommit(function () use ($page) {
                Cache::forget("custom_page:data:{$page->slug}");
            });
        });

        Log::info('custom_page.updated', [
            'page_id' => $page->id,
            'old_slug' => $oldSlug,
            'new_slug' => $page->slug,
            'actor_id' => auth()->id(),
            'lock_version' => $page->lock_version ?? 1,
            'timestamp' => now()->toIso8601String(),
        ]);

        if ($oldSlug !== $slug) {
            Cache::forget("custom_page:data:{$oldSlug}");
        }

        return redirect()->route('pagebuilder.pages.index', ['locale' => $locale])
            ->with('success', 'Đã cập nhật thông tin trang thành công.');
    }

    public function destroy(string $locale, CustomPage $page)
    {
        Gate::authorize('delete', $page);
        $oldSlug = $page->slug;

        DB::transaction(function () use ($page, $oldSlug) {
            $page->slug = $oldSlug . '__deleted__' . time();
            $page->save();
            $page->delete();

            DB::afterCommit(function () use ($oldSlug) {
                Cache::forget("custom_page:data:{$oldSlug}");
            });
        });

        Log::info('custom_page.deleted', [
            'page_id' => $page->id,
            'slug' => $oldSlug,
            'actor_id' => auth()->id(),
            'lock_version' => $page->lock_version ?? 1,
            'timestamp' => now()->toIso8601String(),
        ]);

        return redirect()->route('pagebuilder.pages.index', ['locale' => $locale])
            ->with('success', 'Đã xóa trang tĩnh thành công.');
    }

    public function restore(string $locale, int $id)
    {
        $page = CustomPage::onlyTrashed()->findOrFail($id);
        Gate::authorize('restore', $page);

        $originalSlug = $page->slug;
        if (str_contains($originalSlug, '__deleted__')) {
            $originalSlug = explode('__deleted__', $originalSlug)[0];
        }

        $slug = $originalSlug;
        if (CustomPage::query()->where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . time();
        }

        DB::transaction(function () use ($page, $slug) {
            $page->slug = $slug;
            $page->restore();

            DB::afterCommit(function () use ($page) {
                Cache::forget("custom_page:data:{$page->slug}");
            });
        });

        Log::info('custom_page.restored', [
            'page_id' => $page->id,
            'original_slug' => $originalSlug,
            'restored_slug' => $page->slug,
            'actor_id' => auth()->id(),
            'lock_version' => $page->lock_version ?? 1,
            'timestamp' => now()->toIso8601String(),
        ]);

        return redirect()->route('pagebuilder.pages.index', ['locale' => $locale])
            ->with('success', 'Đã khôi phục trang thành công.');
    }
}
