<?php

namespace App\Services;

use App\Models\PageLayout;
use App\Models\ProjectSetting;
use Illuminate\Support\Facades\Schema;

class HomeLayoutService
{
    public const PAGE_KEY = 'home';

    public const SCHEMA_VERSION = 1;

    public function findOrCreate(): ?PageLayout
    {
        if (! Schema::hasTable('page_layouts')) {
            return null;
        }

        $layout = PageLayout::query()->where('page_key', self::PAGE_KEY)->first();
        if ($layout) {
            return $layout;
        }

        $content = $this->layoutFromLegacySettings();

        return PageLayout::query()->create([
            'page_key' => self::PAGE_KEY,
            'schema_version' => self::SCHEMA_VERSION,
            'draft_content' => $content,
            'published_content' => $content,
        ]);
    }

    public function publishedContent(): array
    {
        $layout = $this->findOrCreate();

        return $this->normalize($layout?->published_content ?: $this->defaultLayout());
    }

    public const CUSTOM_TYPES_WHITELIST = [
        'custom_video',
        'custom_image_banner',
        'custom_text_block',
    ];

    public function normalize(array $layout): array
    {
        $defaults = $this->defaultLayout();
        $defaultById = collect($defaults['sections'])->keyBy('id');
        $seen = [];
        $sections = [];

        foreach (($layout['sections'] ?? []) as $input) {
            if (! is_array($input)) {
                continue;
            }

            $id = trim((string) ($input['id'] ?? ''));
            if ($id === '' || isset($seen[$id])) {
                continue;
            }

            $type = trim((string) ($input['type'] ?? ''));

            if ($defaultById->has($id)) {
                $sections[] = $this->mergeKnown($defaultById->get($id), $input);
                $seen[$id] = true;
            } elseif (in_array($type, self::CUSTOM_TYPES_WHITELIST, true)) {
                $sections[] = [
                    'id' => $id,
                    'type' => $type,
                    'name' => mb_substr(trim((string) ($input['name'] ?? 'Khối tùy chỉnh')), 0, 100),
                    'enabled' => filter_var($input['enabled'] ?? true, FILTER_VALIDATE_BOOL),
                    'props' => $this->normalizeCustomBlock($type, $input['props'] ?? []),
                ];
                $seen[$id] = true;
            }
        }

        foreach ($defaults['sections'] as $section) {
            if (! isset($seen[$section['id']])) {
                $sections[] = $section;
            }
        }

        return [
            'schema_version' => self::SCHEMA_VERSION,
            'sections' => array_values($sections),
        ];
    }

    private function normalizeCustomBlock(string $type, array $props): array
    {
        $normalized = [];
        if ($type === 'custom_video') {
            $normalized['title'] = mb_substr(trim((string) ($props['title'] ?? '')), 0, 200);
            $normalized['description'] = mb_substr(trim((string) ($props['description'] ?? '')), 0, 1000);
            
            $rawUrl = trim((string) ($props['video_url'] ?? ''));
            $normalized['video_url'] = $this->parseSafeVideoUrl($rawUrl);
        } elseif ($type === 'custom_image_banner') {
            $normalized['image_url'] = mb_substr(trim((string) ($props['image_url'] ?? '')), 0, 1000);
            $normalized['link_url'] = mb_substr(trim((string) ($props['link_url'] ?? '')), 0, 1000);
            $normalized['alt_text'] = mb_substr(trim((string) ($props['alt_text'] ?? '')), 0, 200);
            $normalized['overlay_enabled'] = filter_var($props['overlay_enabled'] ?? false, FILTER_VALIDATE_BOOL);
            $normalized['height'] = mb_substr(trim((string) ($props['height'] ?? '400px')), 0, 50);
        } elseif ($type === 'custom_text_block') {
            $normalized['title'] = mb_substr(trim((string) ($props['title'] ?? '')), 0, 200);
            $normalized['align'] = in_array($props['align'] ?? 'center', ['left', 'center', 'right'], true) ? $props['align'] : 'center';
            
            $rawContent = (string) ($props['content'] ?? '');
            $cleaned = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $rawContent) ?? $rawContent;
            $normalized['content'] = trim(strip_tags($cleaned));
        }
        return $normalized;
    }

    private function parseSafeVideoUrl(string $url): string
    {
        if (empty($url)) {
            return '';
        }

        if (preg_match('%(?:youtube\.com/(?:[^/]+/.*|(?:v|e(?:mbed)?)|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match)) {
            $youtubeId = $match[1];
            return "https://www.youtube.com/embed/{$youtubeId}";
        }

        if (preg_match('%(?:vimeo\.com)/(?:video/)?([0-9]+)%i', $url, $match)) {
            $vimeoId = $match[1];
            return "https://player.vimeo.com/video/{$vimeoId}";
        }

        if (str_starts_with($url, 'https://www.youtube.com/embed/') || str_starts_with($url, 'https://player.vimeo.com/video/')) {
            return $url;
        }

        return '';
    }

    public function defaultLayout(): array
    {
        return [
            'schema_version' => self::SCHEMA_VERSION,
            'sections' => [
                [
                    'id' => 'hero',
                    'type' => 'hero',
                    'name' => 'Hero đầu trang',
                    'enabled' => true,
                    'variant' => 'cover',
                    'props' => [
                        'eyebrow' => 'Quà tặng cá nhân hóa bằng pha lê khắc 3D',
                        'title' => 'Giữ lại khoảnh khắc đẹp trong một khối pha lê trong suốt.',
                        'description' => 'Chọn mẫu pha lê, gửi ảnh chân dung và lời nhắn. Đội ngũ Mcrystal tư vấn bố cục, dựng mẫu 3D và hoàn thiện món quà sẵn sàng trao tặng.',
                        'desktop_image' => 'public/images/slider_1.png',
                        'mobile_image' => 'public/images/slider_1.png',
                        'image_alt' => 'Quà tặng pha lê khắc 3D cá nhân hóa',
                        'primary_label' => 'Xem sản phẩm',
                        'primary_href' => '/collection',
                        'secondary_label' => 'Chọn theo dịp tặng',
                        'secondary_href' => '#occasions',
                        'autoplay' => true,
                        'autoplay_interval' => 5000,
                        'transition_duration' => 600,
                        'pause_on_hover' => true,
                        'show_arrows' => true,
                        'show_dots' => true,
                        'overlay_enabled' => false,
                        'hide_text' => false,
                        'slides' => [
                            [
                                'desktop_image' => 'public/images/slider_1.png',
                                'mobile_image' => 'public/images/slider_1.png',
                                'alt_text' => 'Quà tặng pha lê khắc 3D cá nhân hóa',
                                'link_url' => '/collection',
                                'eyebrow' => 'Quà tặng cá nhân hóa bằng pha lê khắc 3D',
                                'title' => 'Giữ lại khoảnh khắc đẹp trong một khối pha lê trong suốt.',
                                'description' => 'Chọn mẫu pha lê, gửi ảnh chân dung và lời nhắn. Đội ngũ Mcrystal tư vấn bố cục, dựng mẫu 3D và hoàn thiện món quà sẵn sàng trao tặng.',
                                'primary_label' => 'Xem sản phẩm',
                                'primary_href' => '/collection',
                                'secondary_label' => 'Chọn theo dịp tặng',
                                'secondary_href' => '#occasions',
                            ]
                        ],
                        'metrics' => [
                            ['value' => '1996', 'label' => 'Kinh nghiệm pha lê'],
                            ['value' => '3D', 'label' => 'Duyệt mẫu trước khi khắc'],
                            ['value' => 'K9', 'label' => 'Pha lê trong và nặng tay'],
                        ],
                    ],
                ],
                [
                    'id' => 'wow_gift',
                    'type' => 'media_text',
                    'name' => 'Giới thiệu quà tặng',
                    'enabled' => true,
                    'variant' => 'text-left',
                    'props' => [
                        'eyebrow' => 'Hãy WOW người thân của bạn',
                        'title' => 'Một món quà tặng tinh tế, được giữ lại bằng ánh sáng.',
                        'description' => 'Pha lê khắc 3D phù hợp cho sinh nhật, kỷ niệm, tri ân cha mẹ, quà tặng người yêu hoặc đối tác. Mỗi sản phẩm được tư vấn theo ảnh, lời chúc, dáng khối và ngân sách thực tế.',
                        'image_url' => 'public/images/imgtext_1_videoimage.png',
                        'image_alt' => 'Bộ quà tặng pha lê khắc chân dung 3D',
                        'media_type' => 'video',
                        'video_url' => 'https://www.youtube.com/watch?v=8x87TxOHXmo',
                        'caption_eyebrow' => 'Pha lê cá nhân hóa',
                        'caption_title' => 'Ảnh rõ - chữ sâu - quà gọn',
                        'primary_label' => 'Khám phá ngay',
                        'primary_href' => '#collections',
                        'secondary_label' => 'Xem cách chế tác',
                    ],
                ],
                [
                    'id' => 'service_offer',
                    'type' => 'cta_strip',
                    'name' => 'Ưu đãi dịch vụ',
                    'enabled' => true,
                    'variant' => 'default',
                    'props' => [
                        'eyebrow' => 'Ưu đãi đặt quà',
                        'title' => 'Miễn phí thiết kế mẫu trước khi khắc',
                        'description' => 'Gửi ảnh và lời nhắn để được tư vấn nhanh qua hotline 0983 833 830.',
                        'button_label' => 'Liên hệ tư vấn',
                        'button_href' => '/contact',
                    ],
                ],
                [
                    'id' => 'occasions',
                    'type' => 'card_grid',
                    'name' => 'Bộ sưu tập theo dịp',
                    'enabled' => true,
                    'variant' => 'grid',
                    'props' => [
                        'eyebrow' => 'Khám phá bộ sưu tập theo dịp',
                        'title' => 'Chọn món quà theo người nhận và khoảnh khắc muốn giữ lại.',
                        'cards' => [
                            ['label' => 'Quà tặng sinh nhật', 'image_url' => 'public/images/season_coll_1_img.png', 'image_alt' => 'Quà tặng sinh nhật', 'href' => '/collection?q=sinh%20nhat'],
                            ['label' => 'Quà tặng tình yêu', 'image_url' => 'public/images/season_coll_2_img.png', 'image_alt' => 'Quà tặng tình yêu', 'href' => '/collection?q=tinh%20yeu'],
                            ['label' => 'Quà tặng người thân', 'image_url' => 'public/images/season_coll_3_img.png', 'image_alt' => 'Quà tặng người thân', 'href' => '/collection?q=nguoi%20than'],
                            ['label' => 'Quà tặng sếp', 'image_url' => 'public/images/season_coll_4_img.png', 'image_alt' => 'Quà tặng sếp', 'href' => '/collection?q=sep'],
                            ['label' => 'Quà tặng cha mẹ', 'image_url' => 'public/images/season_coll_5_img.png', 'image_alt' => 'Quà tặng cha mẹ', 'href' => '/collection?q=cha%20me'],
                            ['label' => 'Quà tặng đám cưới', 'image_url' => 'public/images/season_coll_6_img.png', 'image_alt' => 'Quà tặng đám cưới', 'href' => '/collection?q=dam%20cuoi'],
                            ['label' => 'Quà tặng bạn trai', 'image_url' => 'public/images/season_coll_7_img.png', 'image_alt' => 'Quà tặng bạn trai', 'href' => '/collection?q=ban%20trai'],
                            ['label' => 'Quà tặng bạn gái', 'image_url' => 'public/images/season_coll_8_img.png', 'image_alt' => 'Quà tặng bạn gái', 'href' => '/collection?q=ban%20gai'],
                        ],
                    ],
                ],
                [
                    'id' => 'collections',
                    'type' => 'product_collection',
                    'name' => 'Sản phẩm nổi bật',
                    'enabled' => true,
                    'variant' => 'two-groups',
                    'props' => [
                        'eyebrow' => 'Sản phẩm bán chạy',
                        'title' => 'Các mẫu pha lê đang được chọn nhiều',
                        'button_label' => 'Xem tất cả sản phẩm',
                        'button_href' => '/collection',
                        'product_source' => 'all',
                        'product_ids' => [],
                        'product_limit' => 8,
                        'category_id' => null,
                    ],
                ],
                [
                    'id' => 'brand_story',
                    'type' => 'media_text',
                    'name' => 'Câu chuyện thương hiệu',
                    'enabled' => true,
                    'variant' => 'image-left',
                    'props' => [
                        'eyebrow' => 'Thương hiệu 30 năm cung cấp pha lê',
                        'title' => '3Dcrystal - quà tặng pha lê dành cho những dịp đáng nhớ.',
                        'description' => "Mcrystal là đơn vị sản xuất và tư vấn quà tặng pha lê tại TP.HCM, tập trung vào sản phẩm cá nhân hóa, quà tặng gia đình, tri ân doanh nghiệp và các dịp quan trọng.\n\nBạn có thể đến showroom để xem trực tiếp độ trong của pha lê, kích thước khối, hộp quà và mẫu khắc thực tế trước khi quyết định.",
                        'image_url' => 'public/images/slider_3.png',
                        'image_alt' => 'Pha lê 3Dcrystal tại showroom Mcrystal',
                        'primary_label' => 'Tìm hiểu thêm',
                        'primary_href' => '/about',
                    ],
                ],
                [
                    'id' => 'testimonials',
                    'type' => 'testimonials',
                    'name' => 'Đánh giá khách hàng',
                    'enabled' => true,
                    'variant' => 'grid',
                    'props' => [
                        'eyebrow' => 'Khách hàng đã nói gì',
                        'title' => 'Những phản hồi sau khi nhận quà',
                        'items' => [
                            ['name' => 'Minh Tú', 'content' => 'Sản phẩm chất lượng, giá hợp lý, giao hàng nhanh. Kiểu dáng độc đáo, sang trọng và khắc nội dung để tặng rất hay.', 'avatar_url' => 'public/images/reviewer_minh_tu.png'],
                            ['name' => 'Ngọc Bích', 'content' => 'Tới cửa hàng mới thấy sản phẩm đẹp lung linh, nhân viên tư vấn nhiệt tình. Mình sẽ tiếp tục ủng hộ.', 'avatar_url' => 'public/images/reviewer_ngoc_bich.png'],
                            ['name' => 'Thu Hằng', 'content' => 'Cửa hàng trưng bày đẹp mắt, nhiều mẫu mã lạ và sang trọng. Nhân viên tư vấn kỹ nên chọn quà rất dễ.', 'avatar_url' => 'public/images/reviewer_thu_hang.png'],
                        ],
                    ],
                ],
                [
                    'id' => 'service_commitments',
                    'type' => 'icon_list',
                    'name' => 'Cam kết dịch vụ',
                    'enabled' => true,
                    'variant' => 'strip',
                    'props' => [
                        'items' => [
                            ['icon' => 'fa-pen-nib', 'label' => 'Khắc, in logo theo yêu cầu'],
                            ['icon' => 'fa-industry', 'label' => 'Sản xuất trực tiếp, kiểm soát chất lượng'],
                            ['icon' => 'fa-gem', 'label' => 'Cam kết pha lê chính hãng'],
                            ['icon' => 'fa-headset', 'label' => 'Tư vấn tận tình, miễn phí thiết kế'],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function layoutFromLegacySettings(): array
    {
        $layout = $this->defaultLayout();
        if (! Schema::hasTable('project_settings')) {
            return $layout;
        }

        $settings = ProjectSetting::query()->pluck('setting_value', 'setting_key')->all();
        $legacySections = is_array($settings['home_sections'] ?? null) ? $settings['home_sections'] : [];
        $heroBanners = is_array($settings['hero_banners'] ?? null) ? $settings['hero_banners'] : [];
        if (is_string($settings['hero_banners'] ?? null)) {
            $decoded = json_decode($settings['hero_banners'], true);
            if (is_array($decoded)) {
                $heroBanners = $decoded;
            }
        }
        $hero = is_array($heroBanners[0] ?? null) ? $heroBanners[0] : [];

        foreach ($layout['sections'] as &$section) {
            if ($section['id'] === 'hero' && $hero) {
                // Map slides list
                $mappedSlides = [];
                $rawSlides = $hero['slides'] ?? [];
                if (is_string($rawSlides)) {
                    $rawSlides = json_decode($rawSlides, true) ?: [];
                }
                if (is_array($rawSlides)) {
                    foreach ($rawSlides as $s) {
                        $desktop = '';
                        $mobile = '';
                        if (is_string($s)) {
                            $desktop = $s;
                            $mobile = $s;
                        } elseif (is_array($s)) {
                            $desktop = $s['img'] ?? $s['desktop_image'] ?? '';
                            $mobile = $s['mobile_img'] ?? $s['mobile_image'] ?? $desktop;
                        }
                        if (!empty($desktop)) {
                            $mappedSlides[] = [
                                'desktop_image' => $desktop,
                                'mobile_image' => $mobile,
                                'alt_text' => $s['alt_text'] ?? $hero['title'] ?? 'Banner',
                                'link_url' => $s['link_url'] ?? $hero['primary_href'] ?? '/collection',
                                'eyebrow' => $s['eyebrow'] ?? $hero['eyebrow'] ?? '',
                                'title' => $s['title'] ?? $hero['title'] ?? '',
                                'description' => $s['description'] ?? $hero['description'] ?? '',
                                'primary_label' => $s['primary_label'] ?? $hero['primary_label'] ?? 'Xem sản phẩm',
                                'primary_href' => $s['primary_href'] ?? $s['link_url'] ?? $hero['primary_href'] ?? '/collection',
                                'secondary_label' => $s['secondary_label'] ?? $hero['secondary_label'] ?? '',
                                'secondary_href' => $s['secondary_href'] ?? $hero['secondary_href'] ?? '',
                            ];
                        }
                    }
                }

                $section['props'] = $this->mergeKnown($section['props'], [
                    'eyebrow' => $hero['eyebrow'] ?? null,
                    'title' => $hero['title'] ?? null,
                    'description' => $hero['description'] ?? null,
                    'desktop_image' => $hero['img'] ?? ($hero['slides'][0] ?? null),
                    'mobile_image' => $hero['mobile_img'] ?? null,
                    'primary_label' => $hero['primary_label'] ?? null,
                    'primary_href' => $hero['primary_href'] ?? null,
                    'secondary_label' => $hero['secondary_label'] ?? null,
                    'secondary_href' => $hero['secondary_href'] ?? null,
                    'metrics' => $hero['metrics'] ?? null,
                    'slides' => !empty($mappedSlides) ? $mappedSlides : null,
                    'autoplay' => isset($hero['autoplay']) ? (bool)$hero['autoplay'] : null,
                    'autoplay_interval' => isset($hero['autoplay_interval']) ? (int)$hero['autoplay_interval'] : null,
                    'transition_duration' => isset($hero['transition_duration']) ? (int)$hero['transition_duration'] : null,
                    'pause_on_hover' => isset($hero['pause_on_hover']) ? (bool)$hero['pause_on_hover'] : null,
                    'show_arrows' => isset($hero['show_arrows']) ? (bool)$hero['show_arrows'] : null,
                    'show_dots' => isset($hero['show_dots']) ? (bool)$hero['show_dots'] : null,
                    'overlay_enabled' => isset($hero['overlay_enabled']) ? (bool)$hero['overlay_enabled'] : null,
                    'hide_text' => isset($hero['hide_text']) ? (bool)$hero['hide_text'] : null,
                ]);
            }

            $legacyKey = match ($section['id']) {
                'wow_gift' => 'wow_gift',
                'occasions' => 'occasion_stack',
                'brand_story' => 'brand_story_new',
                'testimonials' => 'process_steps',
                default => null,
            };

            if ($legacyKey && is_array($legacySections[$legacyKey] ?? null)) {
                $legacy = $legacySections[$legacyKey];
                if ($section['id'] === 'brand_story') {
                    $legacy = $legacy['featured'] ?? $legacy;
                } elseif ($section['id'] === 'testimonials' && isset($legacy['steps'])) {
                    $legacy['items'] = array_map(fn (array $item) => [
                        'name' => $item['title'] ?? '',
                        'content' => $item['desc'] ?? '',
                        'avatar_url' => $item['avatar'] ?? '',
                    ], array_filter($legacy['steps'], 'is_array'));
                } elseif ($section['id'] === 'occasions' && isset($legacy['cards'])) {
                    $legacy['cards'] = array_map(fn (array $item) => [
                        'label' => $item['label'] ?? '',
                        'image_url' => $item['img'] ?? '',
                        'image_alt' => $item['label'] ?? '',
                        'href' => $item['href'] ?? '#collections',
                    ], array_filter($legacy['cards'], 'is_array'));
                }
                $section['props'] = $this->mergeKnown($section['props'], $legacy);
            }
        }
        unset($section);

        return $this->normalize($layout);
    }

    private function mergeKnown(array $defaults, array $input): array
    {
        $result = [];

        foreach ($defaults as $key => $defaultValue) {
            if (! array_key_exists($key, $input) || $input[$key] === null) {
                $result[$key] = $defaultValue;
                continue;
            }

            $value = $input[$key];
            if ($defaultValue === null) {
                if ($value === null || $value === '') {
                    $result[$key] = null;
                } else {
                    $result[$key] = is_numeric($value) ? (int)$value : mb_substr(trim((string) $value), 0, 5000);
                }
                continue;
            }

            if (is_array($defaultValue)) {
                if (! is_array($value)) {
                    $result[$key] = $defaultValue;
                } elseif (array_is_list($defaultValue)) {
                    $template = is_array($defaultValue[0] ?? null) ? $defaultValue[0] : null;
                    $items = array_slice(array_values($value), 0, 20);
                    if ($key === 'product_ids') {
                        $items = array_map('intval', $items);
                    }
                    $result[$key] = $template
                        ? array_values(array_map(fn ($item) => is_array($item) ? $this->mergeKnown($template, $item) : $template, $items))
                        : $items;
                } else {
                    $result[$key] = $this->mergeKnown($defaultValue, $value);
                }
            } elseif (is_bool($defaultValue)) {
                $result[$key] = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $defaultValue;
            } elseif (is_int($defaultValue)) {
                $result[$key] = (int) $value;
            } else {
                $result[$key] = mb_substr(trim((string) $value), 0, 5000);
            }
        }

        return $result;
    }
}
