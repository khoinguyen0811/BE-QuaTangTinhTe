<?php

namespace App\Services;

use App\Models\Post;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Str;

class PostSeoAnalyzer
{
    public const MIN_CONTENT_WORDS = 800;

    /**
     * Analyze the editorial and technical SEO requirements used by the publish gate.
     */
    public function analyze(
        array $data,
        ?Post $post = null,
        ?string $locale = null,
        bool $hasFeaturedImage = false,
        ?int $featuredImageWidth = null,
        ?int $featuredImageHeight = null,
    ): array {
        $locale ??= app()->getLocale() ?: 'vi';
        $title = trim((string) ($data['title'] ?? ''));
        $slug = Str::slug((string) ($data['slug'] ?? $title));
        $summary = trim((string) ($data['summary'] ?? ''));
        $seoTitle = trim((string) ($data['seo_title'] ?? ''));
        $seoDescription = trim((string) ($data['seo_description'] ?? ''));
        $focusKeyword = trim((string) ($data['seo_keys'] ?? ''));
        $content = (string) ($data['content'] ?? '');

        $keywordNormalized = $this->normalize($focusKeyword);
        $keywordWords = $this->wordCount($keywordNormalized);
        $contentStats = $this->inspectContent($content);
        $slugParts = array_values(array_filter(explode('-', $slug)));
        $normalizedSlug = str_replace('-', ' ', $slug);
        $keywordOccurrences = $keywordNormalized === ''
            ? 0
            : substr_count(' '.$contentStats['normalized_text'].' ', ' '.$keywordNormalized.' ');
        $keywordDensity = $contentStats['word_count'] > 0
            ? round(($keywordOccurrences * max(1, $keywordWords) / $contentStats['word_count']) * 100, 2)
            : 0.0;

        [$uniqueTitle, $uniqueSeoTitle] = $this->uniqueTitles($title, $seoTitle, $post, $locale);

        $rules = [
            $this->rule(
                'focus_keyword',
                'Một cụm từ khóa trọng tâm, không phải danh sách từ khóa',
                $keywordWords >= 2 && $keywordWords <= 6 && mb_strlen($focusKeyword) >= 8
                    && mb_strlen($focusKeyword) <= 60 && ! preg_match('/[,;|]/u', $focusKeyword),
                "Hiện có {$keywordWords} từ; yêu cầu 2–6 từ, dài 8–60 ký tự và không ngăn cách bằng dấu phẩy.",
                5
            ),
            $this->rule(
                'category',
                'Bài viết thuộc một chuyên mục rõ ràng',
                ! empty($data['category_id']),
                'Chọn chuyên mục để Google và người đọc hiểu ngữ cảnh chủ đề.',
                4
            ),
            $this->rule(
                'title_length',
                'Tiêu đề H1 mô tả rõ nội dung',
                mb_strlen($title) >= 35 && mb_strlen($title) <= 75,
                'Độ dài hiện tại: '.mb_strlen($title).' ký tự; chuẩn biên tập nghiêm ngặt là 35–75.',
                5
            ),
            $this->rule(
                'title_keyword',
                'Tiêu đề H1 chứa tự nhiên cụm từ khóa trọng tâm',
                $keywordNormalized !== '' && str_contains($this->normalize($title), $keywordNormalized),
                'Dùng cụm từ khóa đúng ngữ cảnh, không lặp hoặc nhồi từ khóa.',
                5
            ),
            $this->rule(
                'unique_title',
                'Tiêu đề H1 không trùng bài viết khác',
                $title !== '' && $uniqueTitle,
                'Mỗi bài cần một tiêu đề phân biệt để tránh cạnh tranh và tín hiệu trùng lặp nội bộ.',
                4
            ),
            $this->rule(
                'seo_title_length',
                'Tiêu đề SEO hoàn chỉnh và súc tích',
                mb_strlen($seoTitle) >= 35 && mb_strlen($seoTitle) <= 65,
                'Độ dài hiện tại: '.mb_strlen($seoTitle).' ký tự; yêu cầu 35–65 và đây là title cuối cùng hiển thị ra trang.',
                5
            ),
            $this->rule(
                'seo_title_keyword',
                'Tiêu đề SEO chứa cụm từ khóa trọng tâm',
                $keywordNormalized !== '' && str_contains($this->normalize($seoTitle), $keywordNormalized),
                'Chỉ cần xuất hiện một lần và phải đọc tự nhiên.',
                4
            ),
            $this->rule(
                'unique_seo_title',
                'Tiêu đề SEO không trùng bài viết khác',
                $seoTitle !== '' && $uniqueSeoTitle,
                'Title trùng nhau làm công cụ tìm kiếm khó phân biệt mục đích của từng trang.',
                3
            ),
            $this->rule(
                'slug_quality',
                'Slug ngắn, dễ đọc và chỉ dùng ký tự an toàn',
                $slug !== '' && mb_strlen($slug) <= 75 && count($slugParts) >= 3 && count($slugParts) <= 10
                    && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug),
                'Slug hiện có '.count($slugParts).' thành phần/'.mb_strlen($slug).' ký tự; yêu cầu 3–10 thành phần và tối đa 75 ký tự.',
                4
            ),
            $this->rule(
                'slug_keyword',
                'Slug chứa cụm từ khóa trọng tâm',
                $keywordNormalized !== '' && str_contains($normalizedSlug, $keywordNormalized),
                'Slug phải phản ánh đúng chủ đề chính, không thêm từ dư thừa.',
                4
            ),
            $this->rule(
                'summary',
                'Tóm tắt trả lời trực tiếp nội dung chính',
                mb_strlen($summary) >= 120 && mb_strlen($summary) <= 300
                    && $keywordNormalized !== '' && str_contains($this->normalize($summary), $keywordNormalized),
                'Độ dài hiện tại: '.mb_strlen($summary).' ký tự; yêu cầu 120–300 và có cụm từ khóa tự nhiên.',
                6
            ),
            $this->rule(
                'meta_description',
                'Meta description riêng, rõ lợi ích và đúng chủ đề',
                mb_strlen($seoDescription) >= 120 && mb_strlen($seoDescription) <= 160
                    && $keywordNormalized !== '' && str_contains($this->normalize($seoDescription), $keywordNormalized),
                'Độ dài hiện tại: '.mb_strlen($seoDescription).' ký tự; yêu cầu 120–160 và có cụm từ khóa tự nhiên.',
                6
            ),
            $this->rule(
                'content_depth',
                'Nội dung có độ sâu biên tập',
                $contentStats['word_count'] >= self::MIN_CONTENT_WORDS && $contentStats['paragraph_count'] >= 6,
                "Hiện có {$contentStats['word_count']} từ/{$contentStats['paragraph_count']} đoạn; yêu cầu tối thiểu ".self::MIN_CONTENT_WORDS.' từ và 6 đoạn có nghĩa.',
                8
            ),
            $this->rule(
                'intro_keyword',
                'Đoạn mở đầu xác nhận ngay chủ đề',
                $keywordNormalized !== '' && str_contains($contentStats['first_paragraph'], $keywordNormalized),
                'Đưa cụm từ khóa vào đoạn đầu theo cách tự nhiên để người đọc biết bài viết trả lời vấn đề gì.',
                4
            ),
            $this->rule(
                'heading_structure',
                'Cấu trúc heading logic, không chèn H1 trong nội dung',
                $contentStats['h2_count'] >= 3 && $contentStats['heading_order_valid'],
                "Hiện có {$contentStats['h2_count']} H2; yêu cầu ít nhất 3 H2, H3 chỉ được đứng sau H2 và không có H1 trong nội dung.",
                6
            ),
            $this->rule(
                'scannable_content',
                'Có danh sách giúp đọc và trích xuất ý chính',
                $contentStats['list_count'] >= 1,
                'Thêm ít nhất một danh sách có thứ tự hoặc không thứ tự với nội dung thực sự hữu ích.',
                3
            ),
            $this->rule(
                'internal_links',
                'Có ít nhất hai liên kết nội bộ theo ngữ cảnh',
                $contentStats['internal_link_count'] >= 2,
                "Hiện có {$contentStats['internal_link_count']} liên kết nội bộ; yêu cầu ít nhất 2 liên kết <a href> tới nội dung liên quan.",
                5
            ),
            $this->rule(
                'external_source',
                'Có nguồn tham khảo bên ngoài',
                $contentStats['external_link_count'] >= 1,
                'Thêm ít nhất một liên kết nguồn đáng tin cậy cho các nhận định hoặc số liệu có thể kiểm chứng.',
                4
            ),
            $this->rule(
                'descriptive_links',
                'Mọi liên kết đều có anchor text mô tả',
                $contentStats['link_count'] > 0 && $contentStats['bad_anchor_count'] === 0,
                "Có {$contentStats['bad_anchor_count']} liên kết dùng chữ mơ hồ, URL thô hoặc thiếu nội dung; hãy mô tả rõ trang đích.",
                3
            ),
            $this->rule(
                'featured_image',
                'Có ảnh đại diện lớn phục vụ Search và Discover',
                $hasFeaturedImage && ($featuredImageWidth === null || $featuredImageWidth === 0 || $featuredImageWidth >= 1200)
                    && ($featuredImageHeight === null || $featuredImageHeight === 0 || $featuredImageHeight >= 630),
                $hasFeaturedImage
                    ? 'Ảnh mới phải tối thiểu 1200×630 px; ảnh đã lưu trước đó được giữ nguyên.'
                    : 'Bắt buộc có ảnh đại diện; ảnh tải mới phải tối thiểu 1200×630 px.',
                5
            ),
            $this->rule(
                'image_alts',
                'Tất cả ảnh trong nội dung có alt mô tả',
                $contentStats['image_without_alt_count'] === 0,
                "Có {$contentStats['image_without_alt_count']} ảnh thiếu alt. Alt phải mô tả đúng nội dung ảnh, không nhồi từ khóa.",
                3
            ),
            $this->rule(
                'no_keyword_stuffing',
                'Không nhồi lặp cụm từ khóa',
                $keywordNormalized !== '' && $keywordOccurrences >= 1 && $keywordDensity <= 2.5,
                "Cụm từ khóa xuất hiện {$keywordOccurrences} lần, mật độ quy đổi {$keywordDensity}%; yêu cầu có trong nội dung và không vượt 2,5%.",
                4
            ),
        ];

        $totalWeight = array_sum(array_column($rules, 'weight'));
        $passedWeight = array_sum(array_map(
            fn (array $rule): int => $rule['passed'] ? $rule['weight'] : 0,
            $rules
        ));
        $failed = array_values(array_filter($rules, fn (array $rule): bool => ! $rule['passed']));

        return [
            'score' => $totalWeight > 0 ? (int) round(($passedWeight / $totalWeight) * 100) : 0,
            'ready_to_publish' => $failed === [],
            'rules' => $rules,
            'failed_rules' => $failed,
            'metrics' => [
                'word_count' => $contentStats['word_count'],
                'paragraph_count' => $contentStats['paragraph_count'],
                'h2_count' => $contentStats['h2_count'],
                'internal_link_count' => $contentStats['internal_link_count'],
                'external_link_count' => $contentStats['external_link_count'],
                'keyword_density' => $keywordDensity,
            ],
        ];
    }

    private function rule(string $key, string $label, bool $passed, string $detail, int $weight): array
    {
        return compact('key', 'label', 'passed', 'detail', 'weight');
    }

    private function uniqueTitles(string $title, string $seoTitle, ?Post $post, string $locale): array
    {
        $normalizedTitle = $this->normalize($title);
        $normalizedSeoTitle = $this->normalize($seoTitle);
        $posts = Post::query()
            ->when($post, fn ($query) => $query->whereKeyNot($post->getKey()))
            ->get(['id', 'title', 'seo_title']);

        $titleExists = $posts->contains(function (Post $candidate) use ($locale, $normalizedTitle): bool {
            if ($normalizedTitle === '') {
                return false;
            }

            return $this->normalize((string) $candidate->getTranslation('title', $locale, false)) === $normalizedTitle;
        });
        $seoTitleExists = $posts->contains(function (Post $candidate) use ($locale, $normalizedSeoTitle): bool {
            if ($normalizedSeoTitle === '') {
                return false;
            }

            return $this->normalize((string) $candidate->getTranslation('seo_title', $locale, false)) === $normalizedSeoTitle;
        });

        return [! $titleExists, ! $seoTitleExists];
    }

    private function inspectContent(string $html): array
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<?xml encoding="UTF-8"><!doctype html><html><body>'.$html.'</body></html>',
            LIBXML_NOERROR | LIBXML_NOWARNING
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        foreach (['script', 'style', 'iframe', 'object', 'embed'] as $tag) {
            $nodes = [];
            foreach ($dom->getElementsByTagName($tag) as $node) {
                $nodes[] = $node;
            }
            foreach ($nodes as $node) {
                $node->parentNode?->removeChild($node);
            }
        }

        $body = $dom->getElementsByTagName('body')->item(0);
        $text = trim(preg_replace('/\s+/u', ' ', (string) ($body?->textContent ?? strip_tags($html))) ?? '');
        $normalizedText = $this->normalize($text);

        $paragraphs = [];
        foreach ($dom->getElementsByTagName('p') as $paragraph) {
            $paragraphText = $this->normalize((string) $paragraph->textContent);
            if ($this->wordCount($paragraphText) >= 5) {
                $paragraphs[] = $paragraphText;
            }
        }

        $headingOrderValid = true;
        $seenH2 = false;
        $h2Count = 0;
        $headings = (new DOMXPath($dom))->query('//h1|//h2|//h3|//h4|//h5|//h6') ?: [];
        foreach ($headings as $heading) {
            $level = (int) substr($heading->tagName, 1);
            if ($level === 1 || trim((string) $heading->textContent) === '') {
                $headingOrderValid = false;
            }
            if ($level === 2) {
                $seenH2 = true;
                $h2Count++;
            }
            if ($level >= 3 && ! $seenH2) {
                $headingOrderValid = false;
            }
        }

        $internalLinks = 0;
        $externalLinks = 0;
        $badAnchors = 0;
        $linkCount = 0;
        $siteHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        $genericAnchors = ['xem them', 'tai day', 'bam vao day', 'click here', 'read more', 'link'];
        foreach ($dom->getElementsByTagName('a') as $link) {
            $href = trim((string) $link->getAttribute('href'));
            if ($href === '' || str_starts_with($href, '#') || str_starts_with(strtolower($href), 'javascript:')) {
                continue;
            }

            $linkCount++;
            $host = strtolower((string) parse_url($href, PHP_URL_HOST));
            if ($host === '' || ($siteHost !== '' && $host === $siteHost)) {
                $internalLinks++;
            } elseif (preg_match('/^https?:\/\//i', $href)) {
                $externalLinks++;
            }

            $anchor = $this->normalize((string) $link->textContent);
            if ($anchor === '' || mb_strlen($anchor) < 4 || in_array($anchor, $genericAnchors, true)
                || preg_match('/^https?:\/\//i', trim((string) $link->textContent))) {
                $badAnchors++;
            }
        }

        $imagesWithoutAlt = 0;
        foreach ($dom->getElementsByTagName('img') as $image) {
            if (trim((string) $image->getAttribute('alt')) === '') {
                $imagesWithoutAlt++;
            }
        }

        return [
            'normalized_text' => $normalizedText,
            'word_count' => $this->wordCount($normalizedText),
            'paragraph_count' => count($paragraphs),
            'first_paragraph' => $paragraphs[0] ?? implode(' ', array_slice(explode(' ', $normalizedText), 0, 120)),
            'h2_count' => $h2Count,
            'heading_order_valid' => $headingOrderValid,
            'list_count' => $dom->getElementsByTagName('ul')->length + $dom->getElementsByTagName('ol')->length,
            'link_count' => $linkCount,
            'internal_link_count' => $internalLinks,
            'external_link_count' => $externalLinks,
            'bad_anchor_count' => $badAnchors,
            'image_without_alt_count' => $imagesWithoutAlt,
        ];
    }

    private function normalize(string $value): string
    {
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return Str::of($value)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->toString();
    }

    private function wordCount(string $value): int
    {
        if (trim($value) === '') {
            return 0;
        }

        return count(preg_split('/\s+/u', trim($value), -1, PREG_SPLIT_NO_EMPTY) ?: []);
    }
}
