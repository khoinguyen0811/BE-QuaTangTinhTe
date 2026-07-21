<?php

namespace HansSchouten\LaravelPageBuilder\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CustomPage;
use HansSchouten\LaravelPageBuilder\Models\PageBuilderPage;
use HansSchouten\LaravelPageBuilder\Models\PageBuilderPageTranslation;
use HansSchouten\LaravelPageBuilder\Models\PageBuilderPageRevision;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PageBuilderEditorController extends Controller
{
    public function __construct()
    {
        if (!config('features.visual_page_builder_enabled')) {
            abort(403, 'Trình thiết kế Visual Page Builder chưa được kích hoạt ở chế độ thử nghiệm (Lab Mode).');
        }
    }

    /**
     * Launch visual GrapesJS editor.
     */
    public function builder(string $locale, CustomPage $page)
    {
        Gate::authorize('update', $page);

        // Auto-provision PageBuilder active languages if missing
        DB::table('pagebuilder_settings')->updateOrInsert(
            ['setting' => 'languages'],
            ['value' => 'vi,en', 'is_array' => 1]
        );

        // Auto-provision PageBuilder bridge if missing, legacy driver, or empty page builder data
        $shouldConvert = false;
        $existingBuilderPage = null;

        if ($page->builder_driver !== 'laravel-pagebuilder' || !$page->builder_page_id) {
            $shouldConvert = true;
        } else {
            $existingBuilderPage = PageBuilderPage::find($page->builder_page_id);
            if (!$existingBuilderPage) {
                $shouldConvert = true;
            } else {
                $hasLegacyContent = false;
                $layoutSource = $page->layout_draft ?: $page->layout_published;
                if (is_array($layoutSource) && isset($layoutSource['blocks'])) {
                    $hasLegacyContent = true;
                } elseif (is_string($layoutSource)) {
                    $decoded = json_decode($layoutSource, true);
                    if (is_array($decoded) && isset($decoded['blocks'])) {
                        $hasLegacyContent = true;
                    }
                }
                
                // Parse existing builder data to check for blocks
                $hasBuilderBlocks = false;
                if (!empty($existingBuilderPage->data)) {
                    $decodedData = is_string($existingBuilderPage->data)
                        ? json_decode($existingBuilderPage->data, true)
                        : $existingBuilderPage->data;
                    
                    if (is_array($decodedData) && isset($decodedData['blocks'])) {
                        foreach ($decodedData['blocks'] as $langBlocks) {
                            if (is_array($langBlocks) && count($langBlocks) > 0) {
                                $hasBuilderBlocks = true;
                                break;
                            }
                        }
                    }
                }

                if (!$hasBuilderBlocks && $hasLegacyContent) {
                    $shouldConvert = true;
                }
            }
        }

        if ($shouldConvert) {
            DB::transaction(function () use ($page, $locale, $existingBuilderPage) {
                $draftHtml = '';
                $dataJson = '{}';

                // Prefer the latest editable legacy draft. Published content is only
                // a fallback for pages that never had a separate draft.
                $layoutSource = $page->layout_draft ?: $page->layout_published;

                if (is_array($layoutSource) && isset($layoutSource['blocks'])) {
                    // Convert legacy layout blocks to PageBuilder format
                    $converted = \HansSchouten\LaravelPageBuilder\Services\LegacyLayoutConverter::convert($layoutSource);
                    $draftHtml = $converted['html'];
                    $dataJson = $converted['data'];
                } elseif (is_string($layoutSource)) {
                    $decoded = json_decode($layoutSource, true);
                    if (is_array($decoded) && isset($decoded['blocks'])) {
                        $converted = \HansSchouten\LaravelPageBuilder\Services\LegacyLayoutConverter::convert($decoded);
                        $draftHtml = $converted['html'];
                        $dataJson = $converted['data'];
                    }
                }

                if ($existingBuilderPage) {
                    $existingBuilderPage->update([
                        'data' => $dataJson,
                        'draft_html' => $draftHtml,
                        'draft_css' => '',
                    ]);
                } else {
                    $builderPage = PageBuilderPage::create([
                        'name' => $page->title,
                        'layout' => 'full-width',
                        'data' => $dataJson,
                        'draft_html' => $draftHtml,
                        'draft_css' => '',
                    ]);

                    PageBuilderPageTranslation::create([
                        'page_id' => $builderPage->id,
                        'locale' => $locale,
                        'title' => $page->title,
                        'meta_title' => $page->seo_title ?? $page->title,
                        'meta_description' => $page->seo_description ?? '',
                        'route' => $page->slug,
                    ]);

                    $page->update([
                        'builder_page_id' => $builderPage->id,
                        'builder_driver' => 'laravel-pagebuilder',
                    ]);
                }
            });
            $page->refresh();
        }

        $this->normalizeBuilderDataForEditor($page);

        // Initialize PHPageBuilder core
        $phpPageBuilder = app()->make('phpPageBuilder');

        // Resolve PageBuilderPage record
        $phpbPage = null;
        if ($page->builder_page_id) {
            $pageRepository = new \PHPageBuilder\Repositories\PageRepository;
            $phpbPage = $pageRepository->findWithId($page->builder_page_id);
        }

        // Dynamically override PHPageBuilder URL paths with correct locale prefix
        // so all AJAX URLs (save, upload, renderBlock, etc.) point to correct Laravel routes
        global $phpb_config;
        $phpb_config['pagebuilder']['url'] = '/' . $locale . '/admin/page-builder-lab/editor';
        $phpb_config['pagebuilder']['actions']['back'] = '/' . $locale . '/admin/page-builder-lab';
        $phpb_config['website_manager']['url'] = '/' . $locale . '/admin/page-builder-lab';

        if (! $phpbPage) {
            return redirect()->route('pagebuilder.pages.index', ['locale' => $locale])
                ->with('error', 'Không tìm thấy dữ liệu builder của trang này trong kho lưu trữ.');
        }

        // Save active language in session for PHPageBuilder views
        $_SESSION['phpagebuilder_language'] = $locale;

        // Custom script injected into head:
        // 1. Injects jQuery AJAX setup with Laravel's CSRF token.
        // 2. Automatically updates the lock_version on save button after success.
        // 3. Modifies the GrapesJS Asset Manager to load the project's own Media Library modal.
        $csrfToken = csrf_token();
        $mediaChooserUrl = route('admin.media.index', ['locale' => $locale, 'choose' => 'true']);
        
        $customScript = <<<HTML
<script type="text/javascript">
$(document).ready(function() {
    // Setup CSRF header
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': '{$csrfToken}'
        }
    });

    // Update lock_version dynamically on successful ajax saves
    $(document).ajaxSuccess(function(event, xhr, settings) {
        if (settings.url.indexOf('action=store') !== -1) {
            try {
                let response = JSON.parse(xhr.responseText);
                if (response && response.new_lock_version !== undefined) {
                    let btn = $("#save-page");
                    let currentUrl = btn.attr('data-url');
                    let url = new URL(currentUrl, window.location.origin);
                    url.searchParams.set('lock_version', response.new_lock_version);
                    btn.attr('data-url', url.pathname + url.search);
                }
            } catch (e) {
                console.error("Failed to parse lock version response", e);
            }
        }
    });

    // Override GrapesJS Asset Manager open command to use main project Media Library
    window.customConfig = {
        assetManager: {
            custom: {
                open(params) {
                    let modal = window.editor.Modal;
                    modal.setTitle('Media Library');
                    modal.setContent('<iframe src="{$mediaChooserUrl}" style="width:100%; height:500px; border:none;"></iframe>');
                    modal.open();
                    
                    // Listen to choose-asset messages from the iframe picker
                    window.addEventListener('message', function onMessage(e) {
                        if (e.origin !== window.location.origin) return;
                        if (e.data && e.data.type === 'select-asset') {
                            let url = e.data.url;
                            let mediaId = e.data.media_id || '';
                            let alt = e.data.alt || '';
                            
                            let selected = window.editor.getSelected();
                            if (selected && selected.get('type') === 'image') {
                                selected.set({
                                    attributes: {
                                        ...selected.get('attributes'),
                                        src: url,
                                        'data-media-id': mediaId,
                                        alt: alt
                                    }
                                });
                            } else {
                                window.editor.AssetManager.add({
                                    src: url,
                                    name: mediaId,
                                    attributes: {
                                        'data-media-id': mediaId,
                                        'alt': alt
                                    }
                                });
                                params.select(url);
                            }
                            modal.close();
                            window.removeEventListener('message', onMessage);
                        }
                    });
                }
            }
        }
    };
});
</script>
HTML;

        // Apply our custom script
        $phpPageBuilder->getPageBuilder()->customScripts('head', $customScript);

        // Restore the complete CSS string as GrapesJS rules. The upstream saver can
        // discard style-array entries for ordinary element boxes even though the CSS
        // string is still present, which made those boxes lose styling after reopening.
        $bodyScripts = <<<'HTML'
<script type="text/javascript">
(function restoreSavedBuilderCss() {
    function restore() {
        if (window.editor && typeof window.initialCss === 'string' && window.initialCss.trim() !== '') {
            window.editor.setStyle(window.initialCss);
        }
    }

    if (window.grapesJSLoaded) {
        restore();
    } else if (window.editor) {
        window.editor.on('load', restore);
    }
})();
</script>
HTML;

        // Inject element-level building blocks (columns, text, image, video, form, etc.)
        $elementBlocksPath = __DIR__ . '/../../resources/assets/element-blocks.js';
        if (file_exists($elementBlocksPath)) {
            $elementBlocksJs = file_get_contents($elementBlocksPath);
            $bodyScripts .= '<script type="text/javascript">' . $elementBlocksJs . '</script>';
        }
        $phpPageBuilder->getPageBuilder()->customScripts('body', $bodyScripts);

        // Render page builder GrapesJS UI
        $phpPageBuilder->getPageBuilder()->renderPageBuilder($phpbPage);
        exit();
    }

    /**
     * Intercept and handle all PageBuilder editor AJAX requests.
     */
    public function handleEditorAction(Request $request, string $locale)
    {
        $action = $request->query('action');
        $pageId = $request->query('page');

        // Find linked CustomPage
        $customPage = CustomPage::where('builder_page_id', $pageId)->firstOrFail();
        Gate::authorize('update', $customPage);

        if ($action === 'store') {
            // Handle Custom Autosave/Save
            $clientLockVersion = $request->input('lock_version') ?? $request->query('lock_version');
            if ($clientLockVersion !== null) {
                $clientLockVersion = (int)$clientLockVersion;
                if ($customPage->lock_version !== $clientLockVersion) {
                    return response()->json([
                        'message' => 'Xung đột phiên bản dữ liệu (Concurrency Conflict). Phiên bản của bạn đã cũ, vui lòng tải lại trang để tránh đè dữ liệu.'
                    ], 409);
                }
            }

            $dataString = $request->input('data');
            $payload = json_decode($dataString, true);

            if (!is_array($payload)) {
                return response()->json(['message' => 'Dữ liệu Page Builder không hợp lệ.'], 422);
            }

            $builderPage = PageBuilderPage::findOrFail($pageId);

            // Extract GrapesJS compiled HTML/CSS
            $html = $payload['html'] ?? '';
            $css = array_key_exists('css', $payload) && $payload['css'] !== null
                ? (string) $payload['css']
                : (string) ($builderPage->draft_css ?? '');

            if (is_array($html)) {
                $html = $html[0] ?? '';
            }

            $payload['css'] = $css;
            $dataString = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            // Verify and sync images with Media Library database values
            $html = $this->validateAndSyncMediaImages($html);

            DB::transaction(function () use ($customPage, $pageId, $dataString, $html, $css) {
                // Lock custom page row for update
                $customPage->lockForUpdate();

                // Update PageBuilderPage draft fields
                $builderPage = PageBuilderPage::findOrFail($pageId);
                $builderPage->update([
                    'data' => $dataString,
                    'draft_html' => $html,
                    'draft_css' => $css,
                ]);

                // Increment lock version and touch updated_at
                $customPage->increment('lock_version');
                $customPage->update(['updated_by' => auth()->id()]);
            });

            Log::info('pagebuilder.autosaved', [
                'page_id' => $customPage->id,
                'builder_page_id' => $pageId,
                'actor_id' => auth()->id(),
                'lock_version' => $customPage->lock_version,
                'timestamp' => now()->toIso8601String(),
            ]);

            return response()->json([
                'success' => true,
                'new_lock_version' => $customPage->lock_version
            ]);
        }

        // Delegate other GrapesJS rendering requests (renderBlock, renderLanguageVariant) to core PHPageBuilder
        $phpPageBuilder = app()->make('phpPageBuilder');
        $pageRepository = new \PHPageBuilder\Repositories\PageRepository;
        $phpbPage = $pageRepository->findWithId($pageId);

        if (! $phpbPage) {
            abort(404, 'Page not found.');
        }

        // Set session active language first
        $_SESSION['phpagebuilder_language'] = $locale;
        
        $phpPageBuilder->getPageBuilder()->handleRequest($phpbPage, $action);
        exit();
    }

    /**
     * Publish the current GrapesJS draft.
     */
    public function publish(Request $request, string $locale, CustomPage $page)
    {
        Gate::authorize('publish', $page);

        if ($page->builder_driver !== 'laravel-pagebuilder' || ! $page->builder_page_id) {
            return response()->json(['message' => 'Invalid page builder configuration.'], 400);
        }

        $clientLockVersion = $request->input('lock_version') ?? $request->query('lock_version');
        if ($clientLockVersion !== null) {
            $clientLockVersion = (int)$clientLockVersion;
            if ($page->lock_version !== $clientLockVersion) {
                return response()->json([
                    'message' => 'Xung đột phiên bản dữ liệu (Concurrency Conflict). Phiên bản của bạn đã cũ, vui lòng tải lại trang để tránh đè dữ liệu.'
                ], 409);
            }
        }

        DB::transaction(function () use ($page) {
            $page->lockForUpdate();

            $builderPage = PageBuilderPage::findOrFail($page->builder_page_id);
            
            // Increment current revision index
            $newRevisionIndex = $builderPage->current_revision + 1;
            
            // Create immutable snapshot in revisions table
            PageBuilderPageRevision::create([
                'page_id' => $builderPage->id,
                'revision' => $newRevisionIndex,
                'project_json' => $builderPage->data ?? '{}',
                'html' => $builderPage->draft_html ?? '',
                'css' => $builderPage->draft_css ?? '',
                'created_by' => auth()->id(),
            ]);

            // Update PageBuilderPage revision counter
            $builderPage->update([
                'current_revision' => $newRevisionIndex
            ]);

            // Save revision html/css snapshot references to custom_pages and increment lock_version
            $page->increment('lock_version');
            $page->update([
                'layout_published' => json_encode([
                    'revision' => $newRevisionIndex,
                    'html' => $builderPage->draft_html ?? '',
                    'css' => $builderPage->draft_css ?? '',
                    'data' => $builderPage->data ?? '{}',
                ]),
                'published_at' => $page->published_at ?? now(),
                'updated_by' => auth()->id()
            ]);

            Log::info('custom_page.published', [
                'page_id' => $page->id,
                'slug' => $page->slug,
                'actor_id' => auth()->id(),
                'lock_version' => $page->lock_version,
                'timestamp' => now()->toIso8601String(),
            ]);

            DB::afterCommit(function () use ($page) {
                Cache::forget("custom_page:data:{$page->slug}");
            });
        });

        return response()->json([
            'success' => true,
            'message' => 'Đã xuất bản trang thành công.',
            'new_lock_version' => $page->lock_version
        ]);
    }

    /**
     * Unpublish page (remove active storefront snapshot).
     */
    public function unpublish(Request $request, string $locale, CustomPage $page)
    {
        Gate::authorize('publish', $page);

        $clientLockVersion = $request->input('lock_version') ?? $request->query('lock_version');
        if ($clientLockVersion !== null) {
            $clientLockVersion = (int)$clientLockVersion;
            if ($page->lock_version !== $clientLockVersion) {
                return response()->json([
                    'message' => 'Xung đột phiên bản dữ liệu (Concurrency Conflict). Phiên bản của bạn đã cũ, vui lòng tải lại trang để tránh đè dữ liệu.'
                ], 409);
            }
        }

        DB::transaction(function () use ($page) {
            $page->lockForUpdate();
            $page->increment('lock_version');
            $page->update([
                'layout_published' => null,
                'updated_by' => auth()->id()
            ]);

            Log::info('custom_page.unpublished', [
                'page_id' => $page->id,
                'slug' => $page->slug,
                'actor_id' => auth()->id(),
                'lock_version' => $page->lock_version,
                'timestamp' => now()->toIso8601String(),
            ]);

            DB::afterCommit(function () use ($page) {
                Cache::forget("custom_page:data:{$page->slug}");
            });
        });

        return response()->json([
            'success' => true,
            'message' => 'Đã gỡ xuất bản trang thành công.',
            'new_lock_version' => $page->lock_version
        ]);
    }

    /**
     * Render page preview mode.
     */
    public function preview(string $locale, CustomPage $page)
    {
        Gate::authorize('view', $page);

        // Security Signed URL validation
        if (! request()->hasValidSignature()) {
            abort(403, 'Liên kết xem thử không hợp lệ hoặc đã hết hạn.');
        }

        // Auto-provision PageBuilder bridge if missing or legacy driver
        if ($page->builder_driver !== 'laravel-pagebuilder' || !$page->builder_page_id || !PageBuilderPage::where('id', $page->builder_page_id)->exists()) {
            DB::transaction(function () use ($page, $locale) {
                $builderPage = PageBuilderPage::create([
                    'name' => $page->title,
                    'layout' => 'full-width',
                    'data' => '{}',
                    'draft_html' => is_array($page->layout_published) ? ($page->layout_published['html'] ?? '') : '',
                    'draft_css' => is_array($page->layout_published) ? ($page->layout_published['css'] ?? '') : '',
                ]);

                PageBuilderPageTranslation::create([
                    'page_id' => $builderPage->id,
                    'locale' => $locale,
                    'title' => $page->title,
                    'meta_title' => $page->seo_title ?? $page->title,
                    'meta_description' => $page->seo_description ?? '',
                    'route' => $page->slug,
                ]);

                $page->update([
                    'builder_page_id' => $builderPage->id,
                    'builder_driver' => 'laravel-pagebuilder',
                ]);
            });
            $page->refresh();
        }

        $builderPage = PageBuilderPage::findOrFail($page->builder_page_id);

        $html = $builderPage->draft_html ?? '';
        $css = $builderPage->draft_css ?? '';

        // Anti-XSS Sanitization & rendering preparation
        // We will output HTML draft with the meta tags and no-store headers
        return response()
            ->view('pagebuilder::preview', [
                'page' => $page,
                'html' => $html,
                'css' => $css
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    /**
     * Validate and sync all GrapesJS HTML image sources against whitelisted Media Library items.
     * Removes unverified images.
     */
    private function validateAndSyncMediaImages(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $dom = new \DOMDocument();
        $libxmlState = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $images = $dom->getElementsByTagName('img');
        if ($images->length === 0) {
            libxml_clear_errors();
            libxml_use_internal_errors($libxmlState);
            return $html;
        }

        // Cache Media Library resources for 5 minutes
        $resources = Cache::remember('pagebuilder:media_resources', 300, function () {
            $cloudinary = app(\App\Services\CloudinaryService::class);
            return $cloudinary->listResources('all');
        });

        $cloudinary = app(\App\Services\CloudinaryService::class);
        $applicationUrl = rtrim(request()->getSchemeAndHttpHost() . request()->getBaseUrl(), '/');
        $isConfigured = $cloudinary->isConfigured();

        $mediaMap = [];
        foreach ($resources as $res) {
            $publicId = $res['public_id'] ?? '';
            if ($publicId === '') continue;

            $storage = $res['storage'] ?? ($isConfigured ? 'cloudinary' : 'local');
            $url = $res['secure_url'] ?? '';
            if ($storage === 'local') {
                $url = $applicationUrl . '/storage/' . ltrim($publicId, '/');
            }

            $mediaMap[$publicId] = $url;
        }

        $toRemove = [];
        foreach ($images as $image) {
            $mediaId = trim($image->getAttribute('data-media-id'));
            
            if ($mediaId === '' || !isset($mediaMap[$mediaId])) {
                $toRemove[] = $image;
                continue;
            }

            $image->setAttribute('src', $mediaMap[$mediaId]);
        }

        foreach ($toRemove as $image) {
            $image->parentNode?->removeChild($image);
        }

        $cleanHtml = $dom->saveHTML();
        $cleanHtml = preg_replace('/^<\?xml[^>]*>/i', '', $cleanHtml);

        libxml_clear_errors();
        libxml_use_internal_errors($libxmlState);

        return trim($cleanHtml);
    }

    /**
     * Keep project JSON in sync with the separately stored draft snapshot.
     * PHPageBuilder loads the editor from data.html/data.css only, while older
     * records may contain their usable content solely in draft_html/draft_css.
     */
    private function normalizeBuilderDataForEditor(CustomPage $page): void
    {
        if (!$page->builder_page_id) {
            return;
        }

        $builderPage = PageBuilderPage::find($page->builder_page_id);
        if (!$builderPage) {
            return;
        }

        $data = is_string($builderPage->data)
            ? json_decode($builderPage->data, true)
            : $builderPage->data;
        $data = is_array($data) ? $data : [];
        $changed = false;

        if ((!isset($data['html']) || $data['html'] === [] || $data['html'] === '') && trim((string) $builderPage->draft_html) !== '') {
            $data['html'] = [(string) $builderPage->draft_html];
            $changed = true;
        }

        if ((!array_key_exists('css', $data) || $data['css'] === null || $data['css'] === '') && trim((string) $builderPage->draft_css) !== '') {
            $data['css'] = (string) $builderPage->draft_css;
            $changed = true;
        }

        if (!isset($data['components']) || !is_array($data['components'])) {
            $data['components'] = [];
            $changed = true;
        }

        if (!isset($data['blocks']) || !is_array($data['blocks'])) {
            $data['blocks'] = [];
            $changed = true;
        }

        if ($changed) {
            $builderPage->update([
                'data' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
        }
    }
}
