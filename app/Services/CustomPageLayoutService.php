<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CustomPageLayoutService
{
    public const SCHEMA_VERSION = 1;

    public const ALLOWED_BLOCK_TYPES = [
        'rich_text',
        'faq',
        'contact_form',
        'feature_columns',
        'image_text',
        'cta',
        'spacer_divider',
    ];

    /**
     * Normalize and validate a draft or published layout JSON.
     */
    public function normalizeAndValidate(array $layout): array
    {
        // 1. Check raw limits (Raw JSON size of payload)
        $encoded = json_encode($layout, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded === false || strlen($encoded) > 1024 * 1024) { // 1MB limit
            throw ValidationException::withMessages([
                'layout' => 'Dữ liệu bố cục vượt quá giới hạn 1 MB.',
            ]);
        }

        $schemaVersion = (int) ($layout['schema_version'] ?? self::SCHEMA_VERSION);
        $blocks = $layout['blocks'] ?? [];

        if (!is_array($blocks)) {
            throw ValidationException::withMessages([
                'layout' => 'Danh sách block không hợp lệ.',
            ]);
        }

        if (count($blocks) > 30) {
            throw ValidationException::withMessages([
                'layout' => 'Không được vượt quá 30 block trên một trang.',
            ]);
        }

        $normalizedBlocks = [];
        $seenIds = [];

        foreach ($blocks as $index => $block) {
            if (!is_array($block)) {
                continue;
            }

            $id = trim((string) ($block['id'] ?? ''));
            if ($id === '') {
                $id = (string) Str::uuid();
            }

            if (in_array($id, $seenIds, true)) {
                throw ValidationException::withMessages([
                    "layout.blocks.{$index}.id" => "ID của block '{$id}' bị trùng lặp.",
                ]);
            }
            $seenIds[] = $id;

            $type = trim((string) ($block['type'] ?? ''));
            if (!in_array($type, self::ALLOWED_BLOCK_TYPES, true)) {
                throw ValidationException::withMessages([
                    "layout.blocks.{$index}.type" => "Loại block '{$type}' không hợp lệ hoặc không được hỗ trợ.",
                ]);
            }

            $version = (int) ($block['version'] ?? 1);
            $enabled = filter_var($block['enabled'] ?? true, FILTER_VALIDATE_BOOL);
            $settings = $block['settings'] ?? [];
            if (!is_array($settings)) {
                $settings = [];
            }

            // Run migration if block version is older
            if ($version < self::SCHEMA_VERSION) {
                $settings = $this->migrateBlock($type, $version, $settings);
                $version = self::SCHEMA_VERSION;
            }

            // Normalize block settings
            $normalizedSettings = $this->normalizeBlockSettings($type, $settings, $index);

            $normalizedBlocks[] = [
                'id' => $id,
                'type' => $type,
                'version' => $version,
                'enabled' => $enabled,
                'settings' => $normalizedSettings,
            ];
        }

        $finalLayout = [
            'schema_version' => self::SCHEMA_VERSION,
            'blocks' => $normalizedBlocks,
        ];

        // Double check post-normalization 1MB size limit
        $finalEncoded = json_encode($finalLayout, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($finalEncoded === false || strlen($finalEncoded) > 1024 * 1024) {
            throw ValidationException::withMessages([
                'layout' => 'Dữ liệu bố cục sau chuẩn hóa vượt quá giới hạn 1 MB.',
            ]);
        }

        return $finalLayout;
    }

    /**
     * Migrate block settings layout schema.
     */
    public function migrateBlock(string $type, int $fromVersion, array $settings): array
    {
        $currentSettings = $settings;
        // Placeholder for future schema migrations
        return $currentSettings;
    }

    /**
     * Normalize block settings based on block type.
     */
    private function normalizeBlockSettings(string $type, array $settings, int $index): array
    {
        $normalized = [];

        switch ($type) {
            case 'rich_text':
                $normalized['title'] = mb_substr(trim((string) ($settings['title'] ?? '')), 0, 200);
                $content = (string) ($settings['content'] ?? '');
                
                // Validate rich text length
                if (strlen($content) > 50000) {
                    throw ValidationException::withMessages([
                        "layout.blocks.{$index}.settings.content" => "Nội dung văn bản Rich Text không được vượt quá 50.000 ký tự.",
                    ]);
                }

                // Clean content HTML
                $cleaned = $this->sanitizeHtml($content);
                if ($content !== '' && $cleaned === '') {
                    throw ValidationException::withMessages([
                        "layout.blocks.{$index}.settings.content" => "Nội dung văn bản Rich Text không hợp lệ hoặc chứa mã độc hại bị cấm.",
                    ]);
                }

                $normalized['content'] = $this->validateAndSyncMediaImages($cleaned, $index);
                $align = $settings['align'] ?? 'left';
                $normalized['align'] = in_array($align, ['left', 'center', 'right', 'justify'], true) ? $align : 'left';
                $width = $settings['width'] ?? 'normal';
                $normalized['width'] = in_array($width, ['normal', 'wide', 'full'], true) ? $width : 'normal';
                break;

            case 'faq':
                $normalized['title'] = mb_substr(trim((string) ($settings['title'] ?? '')), 0, 200);
                $normalized['description'] = mb_substr(trim((string) ($settings['description'] ?? '')), 0, 500);
                
                $items = $settings['items'] ?? [];
                if (!is_array($items)) {
                    $items = [];
                }
                
                if (count($items) > 15) {
                    throw ValidationException::withMessages([
                        "layout.blocks.{$index}.settings.items" => "Block FAQ không được có quá 15 câu hỏi.",
                    ]);
                }

                $normalizedItems = [];
                foreach ($items as $itemIndex => $item) {
                    if (!is_array($item)) continue;
                    $normalizedItems[] = [
                        'question' => mb_substr(trim((string) ($item['question'] ?? '')), 0, 250),
                        'answer' => $this->sanitizeHtml((string) ($item['answer'] ?? '')),
                    ];
                }
                $normalized['items'] = $normalizedItems;
                $normalized['first_open'] = filter_var($settings['first_open'] ?? false, FILTER_VALIDATE_BOOL);
                break;

            case 'contact_form':
                $normalized['title'] = mb_substr(trim((string) ($settings['title'] ?? '')), 0, 200);
                $normalized['description'] = mb_substr(trim((string) ($settings['description'] ?? '')), 0, 500);
                $formType = $settings['form_type'] ?? 'general';
                $normalized['form_type'] = in_array($formType, ['general', 'consultation', 'feedback'], true) ? $formType : 'general';
                
                // recipient_group_id validation
                $groupId = $settings['recipient_group_id'] ?? null;
                if ($groupId !== null) {
                    if (!is_numeric($groupId) || (int)$groupId <= 0) {
                        throw ValidationException::withMessages([
                            "layout.blocks.{$index}.settings.recipient_group_id" => "Nhóm nhận tin nhắn phải là một số nguyên dương hợp lệ.",
                        ]);
                    }
                    $groupId = (int) $groupId;
                    $tableExists = Schema::hasTable('notification_recipient_groups');
                    if ($tableExists) {
                        $groupExists = DB::table('notification_recipient_groups')
                            ->where('id', $groupId)
                            ->where('is_active', true)
                            ->exists();
                        if (!$groupExists) {
                            throw ValidationException::withMessages([
                                "layout.blocks.{$index}.settings.recipient_group_id" => "Nhóm nhận tin nhắn không tồn tại hoặc đã bị vô hiệu hóa.",
                            ]);
                        }
                    }
                }
                $normalized['recipient_group_id'] = $groupId;
                
                $normalized['address'] = mb_substr(trim((string) ($settings['address'] ?? '')), 0, 300);
                $normalized['phone'] = mb_substr(trim((string) ($settings['phone'] ?? '')), 0, 50);
                $normalized['email'] = mb_substr(trim((string) ($settings['email'] ?? '')), 0, 100);
                
                // Whitelist Google Maps embed URL
                $mapUrl = trim((string) ($settings['map_embed_url'] ?? ''));
                if ($mapUrl !== '') {
                    if (!str_starts_with($mapUrl, 'https://www.google.com/maps/embed') && !str_starts_with($mapUrl, 'https://maps.google.com/')) {
                        throw ValidationException::withMessages([
                            "layout.blocks.{$index}.settings.map_embed_url" => "Đường dẫn nhúng bản đồ không hợp lệ. Chỉ chấp nhận liên kết từ Google Maps.",
                        ]);
                    }
                }
                $normalized['map_embed_url'] = $mapUrl;
                
                $normalized['show_phone'] = filter_var($settings['show_phone'] ?? true, FILTER_VALIDATE_BOOL);
                $normalized['show_email'] = filter_var($settings['show_email'] ?? true, FILTER_VALIDATE_BOOL);
                break;

            case 'feature_columns':
                $normalized['title'] = mb_substr(trim((string) ($settings['title'] ?? '')), 0, 200);
                $normalized['description'] = mb_substr(trim((string) ($settings['description'] ?? '')), 0, 500);
                $colsCount = (int) ($settings['columns_count'] ?? 3);
                $normalized['columns_count'] = in_array($colsCount, [2, 3, 4], true) ? $colsCount : 3;
                
                $items = $settings['items'] ?? [];
                if (!is_array($items)) {
                    $items = [];
                }

                if (count($items) > 8) {
                    throw ValidationException::withMessages([
                        "layout.blocks.{$index}.settings.items" => "Feature Columns không được có quá 8 items.",
                    ]);
                }

                $normalizedItems = [];
                foreach ($items as $item) {
                    if (!is_array($item)) continue;
                    
                    $linkUrl = trim((string) ($item['link_url'] ?? ''));
                    if (str_starts_with($linkUrl, 'javascript:')) {
                        $linkUrl = '';
                    }

                    $normalizedItems[] = [
                        'title' => mb_substr(trim((string) ($item['title'] ?? '')), 0, 150),
                        'description' => mb_substr(trim((string) ($item['description'] ?? '')), 0, 500),
                        'icon' => mb_substr(trim((string) ($item['icon'] ?? 'fa-gem')), 0, 50),
                        'image_url' => mb_substr(trim((string) ($item['image_url'] ?? '')), 0, 1000),
                        'link_label' => mb_substr(trim((string) ($item['link_label'] ?? '')), 0, 50),
                        'link_url' => $linkUrl,
                    ];
                }
                $normalized['items'] = $normalizedItems;
                break;

            case 'image_text':
                $normalized['title'] = mb_substr(trim((string) ($settings['title'] ?? '')), 0, 200);
                
                $content = (string) ($settings['content'] ?? '');
                $cleaned = $this->sanitizeHtml($content);
                $normalized['content'] = $this->validateAndSyncMediaImages($cleaned, $index);

                $normalized['image_url'] = mb_substr(trim((string) ($settings['image_url'] ?? '')), 0, 1000);
                $normalized['image_alt'] = mb_substr(trim((string) ($settings['image_alt'] ?? '')), 0, 200);
                $imgPos = $settings['image_position'] ?? 'left';
                $normalized['image_position'] = in_array($imgPos, ['left', 'right'], true) ? $imgPos : 'left';
                
                $btnUrl = trim((string) ($settings['button_url'] ?? ''));
                if (str_starts_with($btnUrl, 'javascript:')) {
                    $btnUrl = '';
                }
                $normalized['button_label'] = mb_substr(trim((string) ($settings['button_label'] ?? '')), 0, 50);
                $normalized['button_url'] = $btnUrl;
                break;

            case 'cta':
                $normalized['title'] = mb_substr(trim((string) ($settings['title'] ?? '')), 0, 200);
                $normalized['description'] = mb_substr(trim((string) ($settings['description'] ?? '')), 0, 500);
                
                $btnUrl = trim((string) ($settings['button_url'] ?? ''));
                if (str_starts_with($btnUrl, 'javascript:')) {
                    $btnUrl = '';
                }
                $normalized['button_label'] = mb_substr(trim((string) ($settings['button_label'] ?? '')), 0, 50);
                $normalized['button_url'] = $btnUrl;
                
                $normalized['bg_image_url'] = mb_substr(trim((string) ($settings['bg_image_url'] ?? '')), 0, 1000);
                $normalized['bg_color'] = mb_substr(trim((string) ($settings['bg_color'] ?? '')), 0, 30);
                break;

            case 'spacer_divider':
                $normalized['height'] = mb_substr(trim((string) ($settings['height'] ?? '30px')), 0, 20);
                $normalized['show_line'] = filter_var($settings['show_line'] ?? false, FILTER_VALIDATE_BOOL);
                $normalized['line_color'] = mb_substr(trim((string) ($settings['line_color'] ?? '')), 0, 30);
                break;
        }

        return $normalized;
    }

    /**
     * Sanitizer whitelist HTML cleaner logic.
     */
    public function sanitizeHtml(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $allowedTags = ['p', 'br', 'strong', 'em', 'u', 'span', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li', 'a', 'img', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'div', 'blockquote', 'pre', 'code'];
        
        $allowedAttributes = [
            'a' => ['href', 'title', 'target', 'rel'],
            'img' => ['src', 'alt', 'title', 'width', 'height', 'style', 'data-media-id'],
            'span' => ['style'],
            'p' => ['style', 'class', 'data-text-align'],
            'h1' => ['data-text-align'],
            'h2' => ['data-text-align'],
            'h3' => ['data-text-align'],
            'h4' => ['data-text-align'],
            'h5' => ['data-text-align'],
            'h6' => ['data-text-align'],
            'div' => ['style', 'class'],
            'td' => ['colspan', 'rowspan', 'style'],
            'th' => ['colspan', 'rowspan', 'style'],
        ];

        $libxmlState = libxml_use_internal_errors(true);

        $dom = new \DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $xpath = new \DOMXPath($dom);
        $elements = $xpath->query('//*');

        $toRemove = [];
        foreach ($elements as $element) {
            $tagName = strtolower($element->tagName);
            
            if (!in_array($tagName, $allowedTags, true)) {
                $toRemove[] = $element;
                continue;
            }

            if ($element->hasAttributes()) {
                $attrsToRemove = [];
                foreach ($element->attributes as $attr) {
                    $attrName = strtolower($attr->name);
                    
                    $allowedAttrsForTag = $allowedAttributes[$tagName] ?? [];
                    if (!in_array($attrName, $allowedAttrsForTag, true)) {
                        $attrsToRemove[] = $attr->name;
                        continue;
                    }

                    if ($attrName === 'href' || $attrName === 'src') {
                        $value = trim($attr->value);
                        if (preg_match('/^(?:javascript|data|vbscript):/i', $value)) {
                            $attrsToRemove[] = $attr->name;
                        }
                    }

                    if ($attrName === 'data-text-align') {
                        $val = trim($attr->value);
                        if (!in_array($val, ['left', 'center', 'right', 'justify'], true)) {
                            $attrsToRemove[] = $attr->name;
                        }
                    }
                }

                foreach ($attrsToRemove as $attrName) {
                    $element->removeAttribute($attrName);
                }
            }
        }

        foreach ($toRemove as $element) {
            $parent = $element->parentNode;
            if ($parent) {
                while ($element->firstChild) {
                    $parent->insertBefore($element->firstChild, $element);
                }
                $parent->removeChild($element);
            }
        }

        $cleanHtml = $dom->saveHTML();
        $cleanHtml = preg_replace('/^<\?xml[^>]*>/i', '', $cleanHtml);

        libxml_clear_errors();
        libxml_use_internal_errors($libxmlState);

        return trim($cleanHtml);
    }

    /**
     * Validate all image data-media-id attributes against the Media Library (Cloudinary/local files).
     * Replaces the image src with the official URL from the Media Library.
     */
    private function validateAndSyncMediaImages(string $html, int $index): string
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

        // Fetch all resources dynamically from Cloudinary/Local storage via CloudinaryService
        $cloudinary = app(\App\Services\CloudinaryService::class);
        $resources = $cloudinary->listResources('all');
        $applicationUrl = rtrim(request()->getSchemeAndHttpHost() . request()->getBaseUrl(), '/');
        $isConfigured = $cloudinary->isConfigured();

        // Build a map of public_id => url
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
            
            // Strip images without data-media-id to enforce only Media Library images are used
            if ($mediaId === '') {
                $toRemove[] = $image;
                continue;
            }

            if (!isset($mediaMap[$mediaId])) {
                throw ValidationException::withMessages([
                    "layout.blocks.{$index}.settings.content" => "Nội dung chứa hình ảnh không hợp lệ hoặc không tồn tại trong Thư viện ảnh: {$mediaId}.",
                ]);
            }

            // Sync src with the official URL
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
}
