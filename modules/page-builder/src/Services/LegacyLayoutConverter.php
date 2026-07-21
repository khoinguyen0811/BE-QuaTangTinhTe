<?php

namespace HansSchouten\LaravelPageBuilder\Services;

class LegacyLayoutConverter
{
    public static function convert(array $legacyLayout): array
    {
        $blocks = $legacyLayout['blocks'] ?? [];
        $htmlParts = [];
        $blocksData = [];

        foreach ($blocks as $block) {
            if (!($block['enabled'] ?? true)) {
                continue;
            }

            $id = $block['id'] ?? null;
            if (!$id || !str_starts_with($id, 'ID')) {
                $id = 'ID' . strtoupper(substr(md5(uniqid() . rand()), 0, 14));
            }
            $type = $block['type'] ?? '';
            $settings = $block['settings'] ?? [];

            switch ($type) {
                case 'rich_text':
                    $slug = 'text-content';
                    $align = $settings['align'] ?? 'left';
                    $width = $settings['width'] ?? 'normal';
                    $maxWidth = '800px';
                    if ($width === 'wide') {
                        $maxWidth = '1100px';
                    } elseif ($width === 'full') {
                        $maxWidth = '100%';
                    }
                    
                    $titleHtml = '';
                    if (!empty($settings['title'])) {
                        $titleHtml = '<h2 class="mb-4 text-dark font-display" style="font-size: 2rem; font-weight: 700; color: var(--brand-900);">' . e($settings['title']) . '</h2>';
                    }

                    $blockHtml = '<div class="rich-text-block py-5"><div class="container" style="max-width: ' . $maxWidth . '; text-align: ' . $align . ';">' . $titleHtml . '<div class="rich-text-content entry-content text-muted custom-page-rich-text" style="line-height: 1.8; font-size: 1.05rem; color: var(--ink);">' . ($settings['content'] ?? '') . '</div></div></div>';
                    
                    $htmlParts[] = '[block slug="' . $slug . '" id="' . $id . '"]';
                    $blocksData[$id] = [
                        'settings' => [],
                        'blocks' => [],
                        'html' => $blockHtml,
                        'is_html' => true,
                    ];
                    break;

                case 'image_text':
                    $slug = 'image-text';
                    $imgPos = $settings['image_position'] ?? 'left';
                    $dir = ($imgPos === 'right') ? 'row-reverse' : 'row';
                    
                    $imgHtml = '';
                    if (!empty($settings['image_url'])) {
                        $imgHtml = '<img src="' . e($settings['image_url']) . '" alt="' . e($settings['image_alt'] ?? 'Hình ảnh giới thiệu') . '" style="width: 100%; height: 100%; object-fit: cover; display: block; max-height: 480px;">';
                    } else {
                        $imgHtml = '<div style="height: 350px; display: flex; align-items: center; justify-content: center; background: #e2e8f0; color: #94a3b8;"><i class="fa-regular fa-image" style="font-size: 3rem;"></i></div>';
                    }

                    $titleHtml = '';
                    if (!empty($settings['title'])) {
                        $titleHtml = '<h2 class="mb-4 font-display" style="font-size: 2.2rem; font-weight: 700; color: var(--brand-900);">' . e($settings['title']) . '</h2>';
                    }

                    $btnHtml = '';
                    if (!empty($settings['button_url']) && !empty($settings['button_label'])) {
                        $btnHtml = '<a href="' . e($settings['button_url']) . '" class="button button-primary" style="display: inline-flex; align-items: center; gap: 0.5rem;">' . e($settings['button_label']) . '<i class="fa-solid fa-chevron-right" style="font-size: 0.8rem;"></i></a>';
                    }

                    $blockHtml = '<div class="image-text-block py-5"><div class="container"><div class="row align-items-center" style="display: flex; flex-wrap: wrap; gap: 3rem; flex-direction: ' . $dir . ';"><div class="col-lg-6" style="flex: 1; min-width: 300px;"><div class="image-wrap" style="border-radius: var(--radius, 12px); overflow: hidden; border: 1px solid var(--line, #e2e8f0); background: #f8fafc;">' . $imgHtml . '</div></div><div class="col-lg-6" style="flex: 1; min-width: 300px;">' . $titleHtml . '<div class="text-content text-muted mb-4" style="line-height: 1.8; font-size: 1.05rem; color: var(--ink);">' . ($settings['content'] ?? '') . '</div>' . $btnHtml . '</div></div></div></div>';

                    $htmlParts[] = '[block slug="' . $slug . '" id="' . $id . '"]';
                    $blocksData[$id] = [
                        'settings' => [],
                        'blocks' => [],
                        'html' => $blockHtml,
                        'is_html' => true,
                    ];
                    break;

                case 'feature_columns':
                    $slug = 'why-us';
                    $titleHtml = '';
                    if (!empty($settings['title']) || !empty($settings['description'])) {
                        $t = !empty($settings['title']) ? '<h2 class="font-display" style="font-size: 2rem; font-weight: 700; color: var(--brand-900);">' . e($settings['title']) . '</h2>' : '';
                        $d = !empty($settings['description']) ? '<p class="text-muted mt-2" style="font-size: 1rem;">' . e($settings['description']) . '</p>' : '';
                        $titleHtml = '<div class="text-center mb-5">' . $t . $d . '</div>';
                    }

                    $itemsHtml = '';
                    foreach (($settings['items'] ?? []) as $item) {
                        $itemImg = '';
                        if (!empty($item['image_url'])) {
                            $itemImg = '<div class="mb-3 d-inline-block"><img src="' . e($item['image_url']) . '" alt="' . e($item['title'] ?? '') . '" class="img-fluid rounded" style="max-height: 80px; object-fit: contain;"></div>';
                        } elseif (!empty($item['icon'])) {
                            $itemImg = '<div class="icon-wrap mb-3 d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px; background: var(--surface-soft, #f8fafc); color: var(--brand-700, #3b92ab); border-radius: 50%; font-size: 1.5rem;"><i class="' . e($item['icon']) . '"></i></div>';
                        }

                        $itemTitle = !empty($item['title']) ? '<h3 class="mb-2 font-display" style="font-size: 1.25rem; font-weight: 600; color: var(--brand-900);">' . e($item['title']) . '</h3>' : '';
                        $itemDesc = !empty($item['description']) ? '<p class="text-muted mb-3" style="font-size: 0.95rem; line-height: 1.6;">' . e($item['description']) . '</p>' : '';
                        
                        $itemLink = '';
                        if (!empty($item['link_url']) && !empty($item['link_label'])) {
                            $itemLink = '<a href="' . e($item['link_url']) . '" class="fw-semibold text-decoration-none" style="color: var(--brand-700, #3b92ab); font-size: 0.9rem;">' . e($item['link_label']) . ' <i class="fa-solid fa-arrow-right ms-1" style="font-size: 0.8rem;"></i></a>';
                        }

                        $itemsHtml .= '<div class="feature-card-item border rounded p-4 text-center" style="background: var(--surface, #fff); border: 1px solid var(--line, #e2e8f0); border-radius: var(--radius, 8px); transition: transform 0.2s ease, box-shadow 0.2s ease;">' . $itemImg . $itemTitle . $itemDesc . $itemLink . '</div>';
                    }

                    $blockHtml = '<div class="features-block py-5"><div class="container">' . $titleHtml . '<div class="row g-4" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem;">' . $itemsHtml . '</div></div></div>';

                    $htmlParts[] = '[block slug="' . $slug . '" id="' . $id . '"]';
                    $blocksData[$id] = [
                        'settings' => [],
                        'blocks' => [],
                        'html' => $blockHtml,
                        'is_html' => true,
                    ];
                    break;

                case 'cta':
                    $slug = 'cta-contact';
                    $bg = !empty($settings['bg_image_url']) ? 'linear-gradient(rgba(20, 57, 68, 0.88), rgba(20, 57, 68, 0.88)), url(' . $settings['bg_image_url'] . ') no-repeat center center' : ($settings['bg_color'] ?? '#143944');
                    
                    $titleHtml = '';
                    if (!empty($settings['title'])) {
                        $titleHtml = '<h2 class="mb-3 font-display" style="font-size: clamp(1.8rem, 3vw, 2.5rem); font-weight: 700; color: #fff; line-height: 1.3;">' . e($settings['title']) . '</h2>';
                    }

                    $descHtml = '';
                    if (!empty($settings['description'])) {
                        $descHtml = '<p class="mb-4" style="font-size: 1.15rem; color: rgba(255, 255, 255, 0.9); max-width: 600px; margin-inline: auto; line-height: 1.6;">' . e($settings['description']) . '</p>';
                    }

                    $btnHtml = '';
                    if (!empty($settings['button_url']) && !empty($settings['button_label'])) {
                        $btnHtml = '<a href="' . e($settings['button_url']) . '" class="button" style="background: var(--brand-200, #ff750f); color: #fff; border: 0; font-weight: 700; padding: 0.8rem 2rem; border-radius: 99px; display: inline-flex; align-items: center; gap: 0.5rem; text-decoration: none; transition: transform 0.2s ease, opacity 0.2s ease;">' . e($settings['button_label']) . '<i class="fa-solid fa-arrow-right" style="font-size: 0.9rem;"></i></a>';
                    }

                    $blockHtml = '<div class="cta-block py-5" style="background: ' . $bg . '; background-size: cover; color: #fff; text-align: center;"><div class="container py-4" style="max-width: 800px;">' . $titleHtml . $descHtml . $btnHtml . '</div></div>';

                    $htmlParts[] = '[block slug="' . $slug . '" id="' . $id . '"]';
                    $blocksData[$id] = [
                        'settings' => [],
                        'blocks' => [],
                        'html' => $blockHtml,
                        'is_html' => true,
                    ];
                    break;

                case 'contact_form':
                    $slug = 'contact-section';
                    
                    $titleHtml = !empty($settings['title']) ? '<h2>' . e($settings['title']) . '</h2>' : '';
                    $descHtml = !empty($settings['description']) ? '<p class="mb-4">' . e($settings['description']) . '</p>' : '';
                    
                    $addressHtml = !empty($settings['address']) ? '<div class="contact-item-row"><div class="icon-box"><i class="fa-solid fa-location-dot"></i></div><div class="item-details"><strong>Địa chỉ</strong><span>' . e($settings['address']) . '</span></div></div>' : '';
                    $phoneHtml = (($settings['show_phone'] ?? true) && !empty($settings['phone'])) ? '<div class="contact-item-row"><div class="icon-box"><i class="fa-solid fa-phone"></i></div><div class="item-details"><strong>Điện thoại</strong><a href="tel:' . e($settings['phone']) . '">' . e($settings['phone']) . '</a></div></div>' : '';
                    $emailHtml = (($settings['show_email'] ?? true) && !empty($settings['email'])) ? '<div class="contact-item-row"><div class="icon-box"><i class="fa-solid fa-envelope"></i></div><div class="item-details"><strong>Địa chỉ Email</strong><a href="mailto:' . e($settings['email']) . '">' . e($settings['email']) . '</a></div></div>' : '';

                    $mapHtml = '';
                    if (!empty($settings['map_embed_url'])) {
                        $mapHtml = '<div class="container map-section mt-5"><div class="map-container"><iframe src="' . e($settings['map_embed_url']) . '" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade" style="border:0;"></iframe></div></div>';
                    }

                    $blockHtml = '<div class="contact-block py-5"><div class="container contact-layout"><div class="contact-info-card">' . $titleHtml . $descHtml . '<div class="contact-list">' . $addressHtml . $phoneHtml . $emailHtml . '</div></div><div class="contact-form-card" id="form-container-block"><h2>Gửi tin nhắn liên hệ</h2><form class="form-grid"><div class="field"><label>Họ và tên của bạn *</label><input type="text" required placeholder="Nhập họ và tên đầy đủ" /></div><div class="field"><label>Số điện thoại *</label><input type="tel" required placeholder="Nhập số điện thoại" /></div><div class="field"><label>Địa chỉ Email</label><input type="email" placeholder="Nhập email (không bắt buộc)" /></div><div class="field"><label>Bạn muốn tư vấn về *</label><select required><option>Tư vấn đặt quà tặng pha lê 3D</option><option>Yêu cầu thiết kế mẫu 3D miễn phí</option></select></div><div class="field"><label>Lời nhắn chi tiết *</label><textarea required rows="4" placeholder="Nhập nội dung yêu cầu chi tiết của bạn..."></textarea></div><button class="button button-primary" type="submit" style="width:100%; justify-content:center;"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Gửi yêu cầu liên hệ</button></form></div></div>' . $mapHtml . '</div>';

                    $htmlParts[] = '[block slug="' . $slug . '" id="' . $id . '"]';
                    $blocksData[$id] = [
                        'settings' => [],
                        'blocks' => [],
                        'html' => $blockHtml,
                        'is_html' => true,
                    ];
                    break;
                
                case 'faq':
                    $slug = 'faq';
                    $titleHtml = !empty($settings['title']) ? '<h2 class="font-display" style="font-size: 2rem; font-weight: 700; color: var(--brand-900);">' . e($settings['title']) . '</h2>' : '';
                    $descHtml = !empty($settings['description']) ? '<p class="text-muted mt-2" style="font-size: 1rem;">' . e($settings['description']) . '</p>' : '';
                    
                    $faqsHtml = '';
                    foreach (($settings['items'] ?? []) as $i => $item) {
                        $faqsHtml .= '<div style="margin-bottom: 12px; border-bottom: 1px solid #eee; padding-bottom: 12px;"><h4 style="font-weight:600; font-size:1.1rem; color:#333; margin-bottom:8px;">' . e($item['question'] ?? '') . '</h4><div style="color:#666; font-size:0.95rem; line-height:1.6;">' . ($item['answer'] ?? '') . '</div></div>';
                    }

                    $blockHtml = '<section style="padding: 50px 20px;"><div style="max-width: 800px; margin:0 auto;"><div style="text-align:center; margin-bottom: 40px;">' . $titleHtml . $descHtml . '</div><div>' . $faqsHtml . '</div></div></section>';

                    $htmlParts[] = '[block slug="' . $slug . '" id="' . $id . '"]';
                    $blocksData[$id] = [
                        'settings' => [],
                        'blocks' => [],
                        'html' => $blockHtml,
                        'is_html' => true,
                    ];
                    break;
                
                case 'spacer_divider':
                    $slug = 'divider';
                    $blockHtml = '<div style="padding: 20px 0;"><hr style="border: 0; border-top: 1px solid #eee; margin: 0;"></div>';
                    $htmlParts[] = '[block slug="' . $slug . '" id="' . $id . '"]';
                    $blocksData[$id] = [
                        'settings' => [],
                        'blocks' => [],
                        'html' => $blockHtml,
                        'is_html' => true,
                    ];
                    break;
            }
        }

        $htmlString = implode("\n", $htmlParts);
        $projectData = [
            'html' => [ $htmlString ],
            'css' => '',
            'components' => [],
            'blocks' => [
                'vi' => $blocksData,
                'en' => $blocksData
            ]
        ];

        return [
            'html' => $htmlString,
            'data' => json_encode($projectData),
        ];
    }
}
