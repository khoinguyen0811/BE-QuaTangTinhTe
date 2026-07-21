<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class SeoDiscoveryController extends Controller
{
    public function sitemap(Request $request): Response
    {
        $siteUrl = $this->siteUrl($request);
        $today = now()->toDateString();
        $entries = [
            ['/', $today, 'daily', '1.0'],
            ['/collection', $today, 'daily', '0.9'],
            ['/posts', $today, 'weekly', '0.8'],
            ['/about', $today, 'monthly', '0.6'],
            ['/contact', $today, 'monthly', '0.5'],
            ['/policies/purchase', $today, 'monthly', '0.4'],
            ['/policies/shipping', $today, 'monthly', '0.4'],
            ['/policies/payment', $today, 'monthly', '0.4'],
            ['/policies/return', $today, 'monthly', '0.4'],
            ['/policies/refund', $today, 'monthly', '0.4'],
            ['/policies/privacy', $today, 'monthly', '0.4'],
        ];

        Category::query()
            ->where('is_active', true)
            ->whereNotNull('slug')
            ->orderBy('id')
            ->get(['slug', 'updated_at'])
            ->each(function (Category $category) use (&$entries): void {
                $entries[] = [
                    '/collection?category='.rawurlencode($category->slug),
                    optional($category->updated_at)->toDateString(),
                    'daily',
                    '0.85',
                ];
            });

        Product::query()
            ->where('is_active', true)
            ->whereNotNull('slug')
            ->where(function ($query): void {
                $query->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->orderBy('id')
            ->get(['slug', 'updated_at'])
            ->each(function (Product $product) use (&$entries): void {
                $entries[] = [
                    '/collection/'.rawurlencode($product->slug).'/',
                    optional($product->updated_at)->toDateString(),
                    'weekly',
                    '0.8',
                ];
            });

        $this->publishedPosts(['slug', 'published_at', 'updated_at'])
            ->each(function (Post $post) use (&$entries): void {
                $entries[] = [
                    '/posts/'.rawurlencode($post->slug).'/',
                    optional($post->updated_at ?: $post->published_at)->toDateString(),
                    'weekly',
                    '0.75',
                ];
            });

        \App\Models\CustomPage::query()
            ->published()
            ->orderBy('id')
            ->get(['slug', 'updated_at'])
            ->each(function (\App\Models\CustomPage $page) use (&$entries): void {
                $entries[] = [
                    '/pages/'.rawurlencode($page->slug),
                    optional($page->updated_at)->toDateString(),
                    'monthly',
                    '0.6',
                ];
            });

        $urls = collect($entries)
            ->unique(fn (array $entry): string => $entry[0])
            ->map(function (array $entry) use ($siteUrl, $today): string {
                [$path, $lastmod, $changefreq, $priority] = $entry;

                return implode("\n", [
                    '  <url>',
                    '    <loc>'.$this->xml($siteUrl.$path).'</loc>',
                    '    <lastmod>'.$this->xml($lastmod ?: $today).'</lastmod>',
                    '    <changefreq>'.$changefreq.'</changefreq>',
                    '    <priority>'.$priority.'</priority>',
                    '  </url>',
                ]);
            })
            ->implode("\n");

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n"
            .$urls."\n"
            .'</urlset>'."\n";

        return $this->plainResponse($xml, 'application/xml; charset=UTF-8');
    }

    public function llms(Request $request): Response
    {
        return $this->plainResponse($this->llmsDocument($request, false), 'text/plain; charset=UTF-8');
    }

    public function llmsFull(Request $request): Response
    {
        return $this->plainResponse($this->llmsDocument($request, true), 'text/plain; charset=UTF-8');
    }

    private function llmsDocument(Request $request, bool $includeContent): string
    {
        $siteUrl = $this->siteUrl($request);
        $posts = $this->publishedPosts();
        $lines = [
            '# Quà Tặng Tinh Tế',
            '',
            '> Website tư vấn và cung cấp quà tặng pha lê khắc 3D, cá nhân hóa theo người nhận và dịp tặng.',
            '',
            '## Bài viết chuyên môn',
            '',
        ];

        foreach ($posts as $post) {
            $title = $this->markdownText($this->localizedField($post, 'title', 'Bài viết'));
            $summary = $this->markdownText(
                $this->localizedField($post, 'summary') ?: $this->localizedField($post, 'seo_description')
            );
            $lines[] = sprintf('- [%s](%s/posts/%s/): %s', $title, $siteUrl, rawurlencode($post->slug), $summary);
        }

        $lines = array_merge($lines, [
            '',
            '## Tệp khám phá',
            '',
            '- [Sitemap]('.$siteUrl.'/sitemap.xml)',
            '- [Danh sách bài viết]('.$siteUrl.'/posts)',
            '- [Giới thiệu đơn vị biên soạn]('.$siteUrl.'/about)',
            '',
        ]);

        if ($includeContent) {
            foreach ($posts as $post) {
                $lines = array_merge($lines, [
                    '## '.$this->markdownText($this->localizedField($post, 'title', 'Bài viết')),
                    '',
                    'URL: '.$siteUrl.'/posts/'.rawurlencode($post->slug).'/',
                    'Chuyên mục: '.$this->markdownText($post->category
                        ? $this->localizedField($post->category, 'name', 'Bài viết')
                        : 'Bài viết'),
                    'Cập nhật: '.optional($post->updated_at ?: $post->published_at)->toIso8601String(),
                    '',
                    $this->markdownText($this->localizedField($post, 'content') ?: $this->localizedField($post, 'summary')),
                    '',
                ]);
            }
        }

        return implode("\n", $lines)."\n";
    }

    private function publishedPosts(array $columns = ['*'])
    {
        $query = Post::query()
            ->where('is_active', true)
            ->whereNotNull('slug')
            ->where(function ($query): void {
                $query->whereNull('published_at')->orWhere('published_at', '<=', now());
            });

        if ($columns === ['*']) {
            $query->with('category');
        }

        return $query
            ->orderByRaw('COALESCE(published_at, created_at) desc')
            ->get($columns);
    }

    private function localizedField(Model $model, string $field, string $fallback = ''): string
    {
        $locales = array_values(array_unique([app()->getLocale(), 'vi', 'en']));
        if (method_exists($model, 'getTranslations')) {
            $translations = $model->getTranslations($field);
            foreach ($locales as $locale) {
                $value = $translations[$locale] ?? null;
                if (is_string($value) && trim($value) !== '') {
                    return trim($value);
                }
            }
        }

        $value = $model->{$field} ?? null;

        return is_string($value) && trim($value) !== '' ? trim($value) : $fallback;
    }

    private function siteUrl(Request $request): string
    {
        return rtrim($request->getSchemeAndHttpHost(), '/');
    }

    private function markdownText(?string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', strip_tags((string) $value)) ?? '');
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function plainResponse(string $content, string $contentType): Response
    {
        return response($content, 200, [
            'Content-Type' => $contentType,
            'Cache-Control' => 'public, max-age=300, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
