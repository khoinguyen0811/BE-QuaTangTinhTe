<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateCustomPageLayoutRequest;
use App\Models\CustomPage;
use App\Services\CloudinaryService;
use App\Services\CustomPageLayoutService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

use Illuminate\Support\Facades\Log;

class CustomPageLayoutController extends Controller
{
    public function __construct(private readonly CloudinaryService $cloudinary)
    {
    }

    public function showDraft(string $locale, CustomPage $customPage, CustomPageLayoutService $layoutService)
    {
        \Illuminate\Support\Facades\Gate::authorize('update', $customPage);

        return ApiResponse::success([
            'id' => $customPage->id,
            'title' => $customPage->title,
            'slug' => $customPage->slug,
            'lock_version' => $customPage->lock_version,
            'draft' => $customPage->layout_draft ?: ['schema_version' => 1, 'blocks' => []],
            'published' => $customPage->layout_published,
            'published_at' => $customPage->published_at?->toIso8601String(),
            'updated_at' => $customPage->updated_at?->toIso8601String(),
        ]);
    }

    public function updateDraft(UpdateCustomPageLayoutRequest $request, string $locale, CustomPage $customPage, CustomPageLayoutService $layoutService)
    {
        $validated = $request->validated();

        // Normalize and validate limits/HTML sanitation before save
        $normalized = $layoutService->normalizeAndValidate($validated['layout']);

        // Concurrency Atomic Lock Check
        $affected = CustomPage::query()
            ->whereKey($customPage->id)
            ->where('lock_version', (int) $validated['lock_version'])
            ->update([
                'layout_draft' => $normalized,
                'updated_by' => auth()->id(),
                'lock_version' => DB::raw('lock_version + 1'),
                'updated_at' => now(),
            ]);

        if ($affected === 0) {
            $latest = CustomPage::find($customPage->id);
            return ApiResponse::error(
                'Bản nháp đã được một quản trị viên khác cập nhật hoặc xuất bản trước đó. Vui lòng tải lại trang.',
                409,
                ['latest_lock_version' => $latest?->lock_version]
            );
        }

        $fresh = $customPage->fresh();

        return ApiResponse::success([
            'lock_version' => $fresh->lock_version,
            'updated_at' => $fresh->updated_at?->toIso8601String(),
            'draft' => $fresh->layout_draft,
        ], 'Đã lưu bản nháp thành công.');
    }

    public function publish(Request $request, string $locale, CustomPage $customPage, CustomPageLayoutService $layoutService)
    {
        \Illuminate\Support\Facades\Gate::authorize('publish', $customPage);

        $validated = $request->validate([
            'lock_version' => ['required', 'integer'],
        ]);

        DB::transaction(function () use ($customPage, $layoutService, $validated) {
            // Concurrency Atomic Lock Check for publish
            $affected = CustomPage::query()
                ->whereKey($customPage->id)
                ->where('lock_version', (int) $validated['lock_version'])
                ->update([
                    'layout_published' => $customPage->layout_draft,
                    'published_at' => now(),
                    'updated_by' => auth()->id(),
                    'lock_version' => DB::raw('lock_version + 1'),
                    'updated_at' => now(),
                ]);

            if ($affected === 0) {
                throw ValidationException::withMessages([
                    'lock_version' => 'Không thể xuất bản do phiên bản chỉnh sửa bị xung đột. Vui lòng tải lại trang.',
                ]);
            }

            DB::afterCommit(function () use ($customPage) {
                Cache::forget("custom_page:data:{$customPage->slug}");
                // Sitemap rebuild triggered here in later phase
            });
        });

        $fresh = $customPage->fresh();

        Log::info('custom_page.published', [
            'page_id' => $customPage->id,
            'slug' => $customPage->slug,
            'actor_id' => auth()->id(),
            'lock_version' => $fresh->lock_version,
            'timestamp' => now()->toIso8601String(),
        ]);

        return ApiResponse::success([
            'lock_version' => $fresh->lock_version,
            'published_at' => $fresh->published_at?->toIso8601String(),
            'updated_at' => $fresh->updated_at?->toIso8601String(),
        ], 'Đã xuất bản trang thành công.');
    }

    public function unpublish(Request $request, string $locale, CustomPage $customPage)
    {
        \Illuminate\Support\Facades\Gate::authorize('publish', $customPage);

        $validated = $request->validate([
            'lock_version' => ['required', 'integer'],
        ]);

        DB::transaction(function () use ($customPage, $validated) {
            $affected = CustomPage::query()
                ->whereKey($customPage->id)
                ->where('lock_version', (int) $validated['lock_version'])
                ->update([
                    'layout_published' => null,
                    'published_at' => null,
                    'lock_version' => DB::raw('lock_version + 1'),
                    'updated_at' => now(),
                ]);

            if ($affected === 0) {
                throw ValidationException::withMessages([
                    'lock_version' => 'Không thể hủy xuất bản do phiên bản chỉnh sửa bị xung đột. Vui lòng tải lại trang.',
                ]);
            }

            DB::afterCommit(function () use ($customPage) {
                Cache::forget("custom_page:data:{$customPage->slug}");
            });
        });

        $fresh = $customPage->fresh();

        Log::info('custom_page.unpublished', [
            'page_id' => $customPage->id,
            'slug' => $customPage->slug,
            'actor_id' => auth()->id(),
            'lock_version' => $fresh->lock_version,
            'timestamp' => now()->toIso8601String(),
        ]);

        return ApiResponse::success([
            'lock_version' => $fresh->lock_version,
            'published_at' => null,
        ], 'Đã hủy xuất bản trang.');
    }

    public function preview(Request $request, string $locale, CustomPage $customPage)
    {
        $hasPermission = auth()->user() && auth()->user()->can('update', $customPage);
        if (!$request->hasValidSignature() && !$hasPermission) {
            abort(403, 'Đường dẫn xem trước đã hết hạn hoặc bạn không có quyền truy cập.');
        }

        // Return a raw view render or Blade render matching custom-page preview
        // Using layout_draft instead of layout_published
        $layout = $customPage->layout_draft ?: ['schema_version' => 1, 'blocks' => []];

        return response()
            ->view('storefront.custom-pages.show', [
                'page' => $customPage,
                'layout' => $layout,
                'preview' => true,
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function mediaLibrary(Request $request, string $locale, CustomPage $customPage)
    {
        \Illuminate\Support\Facades\Gate::authorize('update', $customPage);
        $isConfigured = $this->cloudinary->isConfigured();
        $applicationUrl = rtrim($request->getSchemeAndHttpHost() . $request->getBaseUrl(), '/');

        $items = collect($this->cloudinary->listResources('all'))
            ->map(function (array $resource) use ($applicationUrl, $isConfigured) {
                $storage = (string) ($resource['storage'] ?? ($isConfigured ? 'cloudinary' : 'local'));
                $publicId = trim((string) ($resource['public_id'] ?? ''));
                $url = trim((string) ($resource['secure_url'] ?? ''));
                $format = strtolower((string) ($resource['format'] ?? pathinfo($publicId, PATHINFO_EXTENSION)));

                if (!in_array($format, ['avif', 'bmp', 'gif', 'jpeg', 'jpg', 'png', 'webp'], true)) {
                    return null;
                }

                if ($storage === 'local' && $publicId !== '') {
                    $url = $applicationUrl . '/storage/' . ltrim($publicId, '/');
                }

                if ($url === '') {
                    return null;
                }

                return [
                    'url' => $url,
                    'name' => basename($publicId ?: parse_url($url, PHP_URL_PATH) ?: 'image'),
                    'public_id' => $publicId,
                    'size' => (int) ($resource['bytes'] ?? 0),
                    'format' => $format,
                    'created_at' => $resource['created_at'] ?? null,
                    'storage' => $storage,
                ];
            })
            ->filter()
            ->values();

        return ApiResponse::success([
            'items' => $items,
            'cloudinary_configured' => $isConfigured,
            'preferred_storage' => $isConfigured ? 'cloudinary' : 'local',
            'upload_max_mb' => 8,
        ]);
    }

    public function upload(Request $request, string $locale, CustomPage $customPage)
    {
        \Illuminate\Support\Facades\Gate::authorize('update', $customPage);
        $request->validate([
            'file' => 'required|file|image|max:8192', // Max 8MB
            'folder' => 'nullable|string',
        ]);

        $file = $request->file('file');
        $folder = $request->input('folder', 'general');

        $url = $this->cloudinary->uploadFile($file, $folder);
        $storage = $this->cloudinary->isConfigured() ? 'cloudinary' : 'local';

        return ApiResponse::success([
            'url' => $url,
            'name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'storage' => $storage,
        ], 'Đã tải ảnh lên.');
    }
}
