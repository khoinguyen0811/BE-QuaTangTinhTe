<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PageLayout;
use App\Models\PageLayoutRevision;
use App\Services\CloudinaryService;
use App\Services\HomeLayoutService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminHomeLayoutController extends Controller
{
    public function showDraft(Request $request, HomeLayoutService $layouts)
    {
        $this->authorizeEditor($request);
        $layout = $this->requireLayout($layouts);

        return ApiResponse::success($this->payload($layout, $layouts));
    }

    public function updateDraft(Request $request, HomeLayoutService $layouts)
    {
        $this->authorizeEditor($request);
        $validated = $request->validate([
            'layout' => ['required', 'array'],
            'layout.sections' => ['required', 'array', 'min:1', 'max:20'],
            'revision' => ['nullable', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $normalized = $layouts->normalize($validated['layout']);
        $expectedRevision = array_key_exists('revision', $validated) ? (int) $validated['revision'] : null;
        $result = DB::transaction(function () use ($request, $layouts, $normalized, $expectedRevision, $validated) {
            $current = PageLayout::query()
                ->where('page_key', HomeLayoutService::PAGE_KEY)
                ->lockForUpdate()
                ->first() ?: $this->requireLayout($layouts);

            if ($expectedRevision !== null && $expectedRevision !== $current->draft_revision) {
                return ['conflict' => true, 'layout' => $current];
            }

            $current->draft_revision++;
            $current->draft_content = $normalized;
            $current->schema_version = HomeLayoutService::SCHEMA_VERSION;
            $current->updated_by = $request->user()->id;
            $current->save();

            $current->revisions()->create([
                'revision' => $current->draft_revision,
                'event' => 'draft',
                'content' => $normalized,
                'created_by' => $request->user()->id,
                'note' => $validated['note'] ?? 'Lưu bản nháp',
            ]);

            return ['conflict' => false, 'layout' => $current];
        });

        if ($result['conflict']) {
            return ApiResponse::error(
                'Bản nháp đã được một quản trị viên khác cập nhật. Vui lòng tải lại trước khi lưu.',
                409,
                ['latest_revision' => [$result['layout']->draft_revision]]
            );
        }

        return ApiResponse::success(
            $this->payload($result['layout']->fresh(), $layouts),
            'Đã lưu bản nháp trang chủ.'
        );
    }

    public function publish(Request $request, HomeLayoutService $layouts)
    {
        $this->authorizeEditor($request);
        $validated = $request->validate([
            'revision' => ['nullable', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $expectedRevision = array_key_exists('revision', $validated) ? (int) $validated['revision'] : null;
        $result = DB::transaction(function () use ($request, $layouts, $expectedRevision, $validated) {
            $current = PageLayout::query()
                ->where('page_key', HomeLayoutService::PAGE_KEY)
                ->lockForUpdate()
                ->first() ?: $this->requireLayout($layouts);

            if ($expectedRevision !== null && $expectedRevision !== $current->draft_revision) {
                return ['conflict' => true, 'layout' => $current];
            }

            $content = $layouts->normalize($current->draft_content ?: $layouts->defaultLayout());
            $current->published_content = $content;
            $current->published_revision = $current->draft_revision;
            $current->published_by = $request->user()->id;
            $current->published_at = now();
            $current->save();

            $current->revisions()->create([
                'revision' => $current->published_revision,
                'event' => 'published',
                'content' => $content,
                'created_by' => $request->user()->id,
                'note' => $validated['note'] ?? 'Xuất bản trang chủ',
            ]);

            return ['conflict' => false, 'layout' => $current];
        });

        if ($result['conflict']) {
            return ApiResponse::error('Bản nháp đã thay đổi. Vui lòng tải lại trước khi xuất bản.', 409);
        }

        return ApiResponse::success(
            $this->payload($result['layout']->fresh(), $layouts),
            'Đã xuất bản trang chủ.'
        );
    }

    public function versions(Request $request, HomeLayoutService $layouts)
    {
        $this->authorizeEditor($request);
        $layout = $this->requireLayout($layouts);
        $versions = $layout->revisions()
            ->with('author:id,name')
            ->latest('id')
            ->limit(50)
            ->get()
            ->map(fn (PageLayoutRevision $revision) => [
                'id' => $revision->id,
                'revision' => $revision->revision,
                'event' => $revision->event,
                'note' => $revision->note,
                'author' => $revision->author?->name ?: 'Hệ thống',
                'created_at' => $revision->created_at?->toIso8601String(),
            ]);

        return ApiResponse::success($versions);
    }

    public function rollback(Request $request, PageLayoutRevision $revision, HomeLayoutService $layouts)
    {
        $this->authorizeEditor($request);
        $layout = $this->requireLayout($layouts);
        abort_unless($revision->page_layout_id === $layout->id, 404);

        $layout = DB::transaction(function () use ($request, $layout, $revision, $layouts) {
            $current = PageLayout::query()->lockForUpdate()->findOrFail($layout->id);
            $content = $layouts->normalize($revision->content);
            $current->draft_revision++;
            $current->draft_content = $content;
            $current->updated_by = $request->user()->id;
            $current->save();

            $current->revisions()->create([
                'revision' => $current->draft_revision,
                'event' => 'rollback',
                'content' => $content,
                'created_by' => $request->user()->id,
                'note' => "Khôi phục từ phiên bản #{$revision->revision}",
            ]);

            return $current;
        });

        return ApiResponse::success(
            $this->payload($layout->fresh(), $layouts),
            'Đã khôi phục phiên bản vào bản nháp. Hãy kiểm tra trước khi xuất bản.'
        );
    }

    public function upload(Request $request, CloudinaryService $cloudinary)
    {
        $this->authorizeEditor($request);
        $validated = $request->validate([
            'file' => ['required', 'image', 'max:8192'],
        ]);

        $file = $validated['file'];
        $url = $cloudinary->uploadFile($file, 'home-builder');
        $storage = str_starts_with($url, '/') ? 'local' : 'cloudinary';
        if (str_starts_with($url, '/')) {
            $url = rtrim($request->getSchemeAndHttpHost().$request->getBaseUrl(), '/').$url;
        }

        return ApiResponse::success([
            'url' => $url,
            'name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'storage' => $storage,
        ], 'Đã tải ảnh lên.');
    }

    public function mediaLibrary(Request $request, CloudinaryService $cloudinary)
    {
        $this->authorizeEditor($request);
        $isConfigured = $cloudinary->isConfigured();
        $applicationUrl = rtrim($request->getSchemeAndHttpHost().$request->getBaseUrl(), '/');

        $items = collect($cloudinary->listResources('all'))
            ->map(function (array $resource) use ($applicationUrl, $isConfigured) {
                $storage = (string) ($resource['storage'] ?? ($isConfigured ? 'cloudinary' : 'local'));
                $publicId = trim((string) ($resource['public_id'] ?? ''));
                $url = trim((string) ($resource['secure_url'] ?? ''));
                $format = strtolower((string) ($resource['format'] ?? pathinfo($publicId, PATHINFO_EXTENSION)));

                if (! in_array($format, ['avif', 'bmp', 'gif', 'jpeg', 'jpg', 'png', 'webp'], true)) {
                    return null;
                }

                if ($storage === 'local' && $publicId !== '') {
                    $url = $applicationUrl.'/storage/'.ltrim($publicId, '/');
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

    private function requireLayout(HomeLayoutService $layouts): PageLayout
    {
        $layout = $layouts->findOrCreate();
        abort_unless($layout, 503, 'Chưa chạy migration cho Home Builder.');

        return $layout;
    }

    private function authorizeEditor(Request $request): void
    {
        $user = $request->user();
        $permissions = $user?->role?->permissions ?? [];
        $roleName = strtolower((string) ($user?->role?->name ?? ''));
        $allowedRole = str_contains($roleName, 'admin') || $roleName === 'system';

        abort_unless(
            $user && $user->role_id && (
                $user->isSuperAdmin()
                || $allowedRole
                || in_array('*', $permissions, true)
                || in_array('settings:write', $permissions, true)
                || in_array('manage_settings', $permissions, true)
            ),
            403,
            'Bạn không có quyền chỉnh sửa giao diện.'
        );
    }

    private function payload(PageLayout $layout, HomeLayoutService $layouts): array
    {
        return [
            'page_key' => $layout->page_key,
            'schema_version' => $layout->schema_version,
            'draft_revision' => $layout->draft_revision,
            'published_revision' => $layout->published_revision,
            'has_unpublished_changes' => $layout->draft_revision !== $layout->published_revision,
            'draft' => $layouts->normalize($layout->draft_content ?: $layouts->defaultLayout()),
            'published' => $layouts->normalize($layout->published_content ?: $layouts->defaultLayout()),
            'updated_at' => $layout->updated_at?->toIso8601String(),
            'published_at' => $layout->published_at?->toIso8601String(),
        ];
    }
}
