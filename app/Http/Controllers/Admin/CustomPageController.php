<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomPage;
use Illuminate\Http\Request;
use App\Http\Requests\Admin\StoreCustomPageRequest;
use App\Http\Requests\Admin\UpdateCustomPageRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CustomPageController extends Controller
{
    private const RESERVED_SLUGS = [
        'admin',
        'api',
        'login',
        'logout',
        'register',
        'storage',
        'assets',
        'build',
        'san-pham',
        'bai-viet',
        'collection',
        'cart',
        'checkout',
        'pages',
        'posts',
        'products',
        'categories',
    ];

    public function index(string $locale = 'vi')
    {
        \Illuminate\Support\Facades\Gate::authorize('viewAny', CustomPage::class);
        return redirect()->route('pagebuilder.pages.index', ['locale' => $locale]);
    }

    public function create(string $locale = 'vi')
    {
        \Illuminate\Support\Facades\Gate::authorize('create', CustomPage::class);
        return redirect()->route('pagebuilder.pages.create', ['locale' => $locale]);
    }

    public function store(StoreCustomPageRequest $request, string $locale = 'vi', \App\Services\CreateCustomPageWithBuilderService $createService = null)
    {
        $validated = $request->validated();

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

        $createService = $createService ?? app(\App\Services\CreateCustomPageWithBuilderService::class);
        $page = $createService->create($validated, auth()->id());

        Log::info('custom_page.created', [
            'page_id' => $page->id,
            'slug' => $page->slug,
            'actor_id' => auth()->id(),
            'lock_version' => $page->lock_version ?? 1,
            'timestamp' => now()->toIso8601String(),
        ]);

        return redirect()->route('pagebuilder.pages.index', ['locale' => $locale])->with('success', 'Đã tạo trang tĩnh mới thành công.');
    }

    public function edit(string $locale, CustomPage $customPage)
    {
        \Illuminate\Support\Facades\Gate::authorize('update', $customPage);
        return view('admin.custom-pages.edit', compact('customPage'));
    }

    public function update(UpdateCustomPageRequest $request, string $locale, CustomPage $customPage)
    {
        $validated = $request->validated();

        $slug = Str::slug($request->input('slug') ?: $request->input('title'));
        if (in_array($slug, self::RESERVED_SLUGS, true)) {
            return back()->withErrors(['slug' => 'Đường dẫn này trùng với các đường dẫn hệ thống đã được bảo vệ.'])->withInput();
        }

        if (CustomPage::query()->where('slug', $slug)->where('id', '!=', $customPage->id)->exists()) {
            return back()->withErrors(['slug' => 'Đường dẫn này đã được sử dụng bởi một trang khác.'])->withInput();
        }

        $oldSlug = $customPage->slug;

        DB::transaction(function () use ($customPage, $validated, $slug, $request) {
            $customPage->update([
                'title' => $validated['title'],
                'slug' => $slug,
                'seo_title' => $validated['seo_title'] ?? null,
                'seo_description' => $validated['seo_description'] ?? null,
                'seo_image' => $validated['seo_image'] ?? null,
                'is_active' => $request->has('is_active') ? (bool)$request->input('is_active') : true,
                'updated_by' => auth()->id(),
            ]);

            DB::afterCommit(function () use ($customPage, $slug) {
                Cache::forget("custom_page:data:{$customPage->slug}");
                // Sitemap rebuild triggered here in later phase
            });
        });

        if ($oldSlug !== $slug) {
            Cache::forget("custom_page:data:{$oldSlug}");
        }

        Log::info('custom_page.updated', [
            'page_id' => $customPage->id,
            'old_slug' => $oldSlug,
            'new_slug' => $slug,
            'actor_id' => auth()->id(),
            'lock_version' => $customPage->lock_version,
            'timestamp' => now()->toIso8601String(),
        ]);

        return redirect()->route('admin.custom-pages.index')->with('success', 'Đã cập nhật thông tin trang thành công.');
    }

    public function destroy(string $locale, CustomPage $customPage)
    {
        \Illuminate\Support\Facades\Gate::authorize('delete', $customPage);
        $oldSlug = $customPage->slug;

        DB::transaction(function () use ($customPage, $oldSlug) {
            $customPage->slug = $oldSlug . '__deleted__' . time();
            $customPage->save();
            $customPage->delete();

            DB::afterCommit(function () use ($oldSlug) {
                Cache::forget("custom_page:data:{$oldSlug}");
            });
        });

        Log::info('custom_page.deleted', [
            'page_id' => $customPage->id,
            'slug' => $oldSlug,
            'actor_id' => auth()->id(),
            'lock_version' => $customPage->lock_version,
            'timestamp' => now()->toIso8601String(),
        ]);

        return redirect()->route('admin.custom-pages.index')->with('success', 'Đã xóa trang tĩnh thành công.');
    }

    public function restore(string $locale, int $id)
    {
        $customPage = CustomPage::onlyTrashed()->findOrFail($id);
        \Illuminate\Support\Facades\Gate::authorize('restore', $customPage);

        $originalSlug = $customPage->slug;
        if (str_contains($originalSlug, '__deleted__')) {
            $originalSlug = explode('__deleted__', $originalSlug)[0];
        }

        $slug = $originalSlug;
        if (CustomPage::query()->where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . time();
        }

        DB::transaction(function () use ($customPage, $slug) {
            $customPage->slug = $slug;
            $customPage->restore();

            DB::afterCommit(function () use ($customPage) {
                Cache::forget("custom_page:data:{$customPage->slug}");
            });
        });

        Log::info('custom_page.restored', [
            'page_id' => $customPage->id,
            'original_slug' => $originalSlug,
            'restored_slug' => $slug,
            'actor_id' => auth()->id(),
            'lock_version' => $customPage->lock_version,
            'timestamp' => now()->toIso8601String(),
        ]);

        return redirect()->route('admin.custom-pages.index')->with('success', 'Trang tĩnh đã được khôi phục thành công.');
    }

    public function builder(string $locale, CustomPage $customPage)
    {
        \Illuminate\Support\Facades\Gate::authorize('update', $customPage);

        return redirect()->route('pagebuilder.pages.builder', [
            'locale' => $locale,
            'page' => $customPage->id,
        ]);
    }

    private function storefrontUrl(): string
    {
        $configuredFrontend = trim((string) config('app.frontend_url', ''));
        if ($configuredFrontend !== '') {
            return rtrim($configuredFrontend, '/');
        }

        $requestBase = rtrim(request()->getSchemeAndHttpHost() . request()->getBaseUrl(), '/');
        return preg_replace('#/backend/public$#', '', $requestBase) ?: request()->getSchemeAndHttpHost();
    }
}
