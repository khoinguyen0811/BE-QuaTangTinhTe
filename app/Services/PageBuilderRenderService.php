<?php

namespace App\Services;

use App\Models\CustomPage;

class PageBuilderRenderService
{
    /**
     * Render the published or draft visual page builder page HTML with XSS sanitization.
     *
     * @param CustomPage $page
     * @param bool $preview
     * @return string
     */
    public function render(CustomPage $page, bool $preview = false): string
    {
        if ($preview && $page->builder_page_id) {
            $builderPage = \HansSchouten\LaravelPageBuilder\Models\PageBuilderPage::find($page->builder_page_id);
            if ($builderPage) {
                $html = $builderPage->draft_html ?? '';
                $css = $builderPage->draft_css ?? '';
                $projectData = $builderPage->data;
            } else {
                $html = '';
                $css = '';
                $projectData = null;
            }
        } else {
            $layoutPublished = $page->layout_published;
            
            $published = null;
            if (is_array($layoutPublished)) {
                $published = $layoutPublished;
            } elseif (is_string($layoutPublished)) {
                $published = json_decode($layoutPublished, true);
            }

            if (!$published || empty($published['html'])) {
                return '';
            }

            $html = $published['html'];
            $css = $published['css'] ?? '';
            $projectData = $published['data'] ?? null;
        }

        // 0. Resolve [block slug="..." id="..."] shortcodes into rendered HTML
        $html = $this->resolvePageBuilderShortcodes($html, $page, $projectData);

        // 1. Sanitize HTML to prevent XSS
        $cleanHtml = $this->sanitizeXss($html);

        // 2. Sanitize CSS styles
        $cleanCss = $this->sanitizeCss($css);

        // 3. Wrap HTML with sanitized CSS styles
        $styleBlock = '';
        if ($cleanCss !== '') {
            $styleBlock = "<style>\n" . $cleanCss . "\n</style>\n";
        }

        return $styleBlock . $cleanHtml;
    }

    /**
     * Resolve PHPageBuilder [block slug="..." id="..."] shortcodes into actual HTML.
     *
     * Dynamic PHP blocks must be rendered through PHPageBuilder so their saved
     * settings and live catalog data are applied. The older HTML-only resolver is
     * retained as a fallback for dangling legacy records.
     */
    protected function resolvePageBuilderShortcodes(string $html, CustomPage $page, $projectData = null): string
    {
        if (strpos($html, '[block ') === false) {
            return $html;
        }

        $builderPage = $page->builder_page_id
            ? \HansSchouten\LaravelPageBuilder\Models\PageBuilderPage::find($page->builder_page_id)
            : null;
        if (!$builderPage) {
            return $this->resolveBlockShortcodes($html, $page);
        }

        $data = is_string($projectData) ? json_decode($projectData, true) : $projectData;
        if (!is_array($data)) {
            $data = is_string($builderPage->data) ? json_decode($builderPage->data, true) : $builderPage->data;
        }
        $data = is_array($data) ? $data : [];
        $allLanguageBlocks = $data['blocks'] ?? [];
        $locale = app()->getLocale();
        $blocksData = $allLanguageBlocks[$locale]
            ?? $allLanguageBlocks['vi']
            ?? (is_array($allLanguageBlocks) ? (reset($allLanguageBlocks) ?: []) : []);

        app()->make('phpPageBuilder');
        $repositoryPage = (new \PHPageBuilder\Repositories\PageRepository)->findWithId($builderPage->id);
        if (!$repositoryPage) {
            return $this->resolveBlockShortcodes($html, $page);
        }

        $theme = phpb_instance('theme', [phpb_config('theme'), phpb_config('theme.active_theme')]);
        $renderer = phpb_instance(\PHPageBuilder\Modules\GrapesJS\PageRenderer::class, [$theme, $repositoryPage, false]);
        $renderer->setLanguage($locale);

        return $renderer->parseShortcodes($html, is_array($blocksData) ? $blocksData : []);
    }

    protected function resolveBlockShortcodes(string $html, CustomPage $page): string
    {
        // Quick check — if no shortcodes, return as-is
        if (strpos($html, '[block ') === false) {
            return $html;
        }

        // Load blocks data from PageBuilderPage JSON
        $blocksData = [];
        if ($page->builder_page_id) {
            $builderPage = \HansSchouten\LaravelPageBuilder\Models\PageBuilderPage::find($page->builder_page_id);
            if ($builderPage) {
                if ($builderPage->data) {
                    $data = is_string($builderPage->data)
                        ? json_decode($builderPage->data, true)
                        : $builderPage->data;

                    if (is_array($data)) {
                        // blocks data is keyed by language code, then by block instance ID
                        $blocks = $data['blocks'] ?? [];
                        // Flatten all language variants — prefer the first available language
                        foreach ($blocks as $languageBlocks) {
                            if (is_array($languageBlocks)) {
                                $blocksData = array_merge($blocksData, $languageBlocks);
                            }
                        }
                    }
                }
            }
        }

        // Theme blocks directory for fallback
        $themeBlocksDir = base_path('themes/quatangtinhte/blocks');

        // Replace all [block slug="..." id="..."] shortcodes
        $html = preg_replace_callback(
            '/\[block\s+slug="([^"]+)"\s+id="([^"]+)"\]/',
            function ($matches) use ($blocksData, $themeBlocksDir) {
                $slug = $matches[1];
                $id = $matches[2];

                // Strategy 1: Use user-edited HTML from blocks data
                if (isset($blocksData[$id]) && !empty($blocksData[$id]['html'])) {
                    $blockHtml = $blocksData[$id]['html'];
                    // Recursively resolve nested shortcodes
                    if (strpos($blockHtml, '[block ') !== false) {
                        $blockHtml = preg_replace_callback(
                            '/\[block\s+slug="([^"]+)"\s+id="([^"]+)"\]/',
                            function ($m) use ($blocksData, $themeBlocksDir) {
                                $s = $m[1];
                                $i = $m[2];
                                if (isset($blocksData[$i]['html'])) {
                                    return $blocksData[$i]['html'];
                                }
                                $viewFile = $themeBlocksDir . '/' . $s . '/view.html';
                                return file_exists($viewFile) ? file_get_contents($viewFile) : '';
                            },
                            $blockHtml
                        );
                    }
                    return $blockHtml;
                }

                // Strategy 2: Fallback to theme block view.html
                $viewFile = $themeBlocksDir . '/' . $slug . '/view.html';
                if (file_exists($viewFile)) {
                    return file_get_contents($viewFile);
                }

                // If no block found, output nothing
                return '';
            },
            $html
        );

        return $html;
    }

    /**
     * Robust HTML XSS Sanitizer for page builder layouts.
     * Strips blacklist tags, php/blade, event handlers, and dangerous URL protocols.
     *
     * @param string $html
     * @return string
     */
    public function sanitizeXss(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        // 1. Strip PHP and Blade tags to prevent execution
        $html = preg_replace('/<\?php.*?\?>/is', '', $html);
        $html = preg_replace('/\{\{.*?\}\}/is', '', $html);
        $html = preg_replace('/\{!!.*?!!\}/is', '', $html);

        $libxmlState = libxml_use_internal_errors(true);

        $dom = new \DOMDocument();
        // Force loading with UTF-8 encoding
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $xpath = new \DOMXPath($dom);

        // 2. Remove blacklisted elements
        $blacklist = ['script', 'object', 'embed', 'form', 'input', 'button', 'textarea'];
        foreach ($blacklist as $tag) {
            $elements = $xpath->query("//{$tag}");
            foreach ($elements as $el) {
                $el->parentNode?->removeChild($el);
            }
        }

        // 3. Scan all elements for dangerous attributes (like onload, onclick) or javascript: protocols
        $allElements = $xpath->query('//*');
        foreach ($allElements as $element) {
            if ($element->hasAttributes()) {
                $attrsToRemove = [];
                foreach ($element->attributes as $attr) {
                    $name = strtolower($attr->name);
                    $value = trim($attr->value);

                    // Strip inline script attributes starting with "on"
                    if (str_starts_with($name, 'on')) {
                        $attrsToRemove[] = $attr->name;
                        continue;
                    }

                    // Strip dangerous protocols
                    if (in_array($name, ['href', 'src', 'action'], true)) {
                        if (preg_match('/^(?:javascript|data|vbscript):/i', $value)) {
                            $attrsToRemove[] = $attr->name;
                        }
                    }
                }

                foreach ($attrsToRemove as $attrName) {
                    $element->removeAttribute($attrName);
                }
            }
        }

        $cleanHtml = $dom->saveHTML();
        // Remove XML prefix added by loadHTML
        $cleanHtml = preg_replace('/^<\?xml[^>]*>/i', '', $cleanHtml);

        libxml_clear_errors();
        libxml_use_internal_errors($libxmlState);

        return trim($cleanHtml);
    }

    /**
     * Clean and sanitize CSS content.
     * Removes @import rules, expressions, browser hacks, php/blade, and dangerous protocols in url().
     *
     * @param string $css
     * @return string
     */
    public function sanitizeCss(string $css): string
    {
        if (trim($css) === '') {
            return '';
        }

        // 1. Remove PHP and Blade template syntax markers
        $css = preg_replace('/<\?php.*?\?>/is', '', $css);
        $css = preg_replace('/\{\{.*?\}\}/is', '', $css);
        $css = preg_replace('/\{!!.*?!!\}/is', '', $css);

        // 2. Remove @import rules to prevent external stylesheet loading
        $css = preg_replace('/@import\s+[^;]+;/i', '', $css);

        // 3. Remove browser hacks and dangerous CSS functions
        $css = preg_replace('/expression\s*\(.*?\)/is', '', $css);
        $css = preg_replace('/behavior\s*:[^;}]*/is', '', $css);
        $css = preg_replace('/-moz-binding\s*:[^;}]*/is', '', $css);

        // 4. Sanitize protocols inside url() declarations
        $css = preg_replace_callback('/url\s*\(\s*([\'"]?)(.*?)\1\s*\)/is', function ($matches) {
            $url = trim($matches[2]);
            if (preg_match('/^(?:javascript|data|vbscript):/i', $url)) {
                return 'url("")';
            }
            return $matches[0];
        }, $css);

        return strip_tags($css);
    }
}
