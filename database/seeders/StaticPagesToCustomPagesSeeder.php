<?php

namespace Database\Seeders;

use App\Models\CustomPage;
use App\Services\CustomPageLayoutService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class StaticPagesToCustomPagesSeeder extends Seeder
{
    /**
     * Idempotent seeder: firstOrCreate by slug.
     * - Never overwrites existing pages (admin may have edited them).
     * - Skips soft-deleted slugs and logs a warning.
     * - All layouts pass through CustomPageLayoutService::normalizeAndValidate().
     */
    public function run(): void
    {
        $layoutService = app(CustomPageLayoutService::class);

        $pages = $this->getPageDefinitions();

        foreach ($pages as $definition) {
            $slug = $definition['slug'];

            // Check for soft-deleted page with matching slug prefix
            $softDeletedExists = CustomPage::onlyTrashed()
                ->where('slug', 'LIKE', $slug . '%')
                ->exists();

            if ($softDeletedExists) {
                Log::warning("StaticPagesSeeder: Skipped '{$slug}' — a soft-deleted page with this slug exists.", [
                    'slug' => $slug,
                ]);
                $this->command?->warn("Skipped '{$slug}' — soft-deleted page exists.");
                continue;
            }

            // Normalize layout through service
            $rawLayout = $definition['layout'];
            try {
                $normalizedLayout = $layoutService->normalizeAndValidate($rawLayout);
            } catch (\Throwable $e) {
                Log::error("StaticPagesSeeder: Layout validation failed for '{$slug}'.", [
                    'slug' => $slug,
                    'error' => $e->getMessage(),
                ]);
                $this->command?->error("Layout validation FAILED for '{$slug}': {$e->getMessage()}");
                continue;
            }

            $page = CustomPage::query()->firstOrCreate(
                ['slug' => $slug],
                [
                    'title' => $definition['title'],
                    'seo_title' => $definition['seo_title'] ?? null,
                    'seo_description' => $definition['seo_description'] ?? null,
                    'seo_image' => $definition['seo_image'] ?? null,
                    'layout_draft' => $normalizedLayout,
                    'layout_published' => $normalizedLayout,
                    'is_active' => true,
                    'published_at' => now(),
                    'lock_version' => 1,
                    'created_by' => null,
                    'updated_by' => null,
                ]
            );

            if ($page->wasRecentlyCreated) {
                Log::info("StaticPagesSeeder: Created page '{$slug}'.", ['page_id' => $page->id]);
                $this->command?->info("Created: {$definition['title']} (/{$slug})");
            } else {
                Log::info("StaticPagesSeeder: Skipped '{$slug}' — already exists.", ['page_id' => $page->id]);
                $this->command?->warn("Skipped: {$definition['title']} (/{$slug}) — already exists.");
            }
        }
    }

    /**
     * Return all page definitions with layout data.
     */
    private function getPageDefinitions(): array
    {
        return [
            $this->aboutPage(),
            $this->contactPage(),
            $this->policyPaymentPage(),
            $this->policyPrivacyPage(),
            $this->policyPurchasePage(),
            $this->policyRefundPage(),
            $this->policyReturnPage(),
            $this->policyShippingPage(),
        ];
    }

    // ───────────────────────────────────────────────
    // Trang Giới thiệu
    // ───────────────────────────────────────────────
    private function aboutPage(): array
    {
        return [
            'slug' => 'gioi-thieu',
            'title' => 'Giới thiệu',
            'seo_title' => 'Giới thiệu - Quà Tặng Tinh Tế',
            'seo_description' => 'Tìm hiểu về Quà Tặng Tinh Tế - thương hiệu quà tặng pha lê cá nhân hóa cao cấp trực thuộc Công Ty TNHH SX-TM-DV Pha Lê Việt hoạt động từ 1996.',
            'seo_image' => '/public/images/slider_3.png',
            'layout' => [
                'schema_version' => 1,
                'blocks' => [
                    // Block 1: Hero
                    [
                        'id' => 'about-hero',
                        'type' => 'rich_text',
                        'version' => 1,
                        'enabled' => true,
                        'settings' => [
                            'title' => '',
                            'content' => '<h1 style="text-align: center;">Về Chúng Tôi</h1><p style="text-align: center;">Hơn 30 năm chặng đường phát triển</p><p style="text-align: center;">Quà Tặng Tinh Tế - Nơi lưu giữ trọn vẹn những ký ức và tình cảm quý giá qua ánh sáng pha lê chân thực.</p>',
                            'align' => 'center',
                            'width' => 'normal',
                        ],
                    ],
                    // Block 2: Thư chào mừng
                    [
                        'id' => 'about-welcome',
                        'type' => 'rich_text',
                        'version' => 1,
                        'enabled' => true,
                        'settings' => [
                            'title' => '',
                            'content' => '<blockquote><p style="text-align: center;">"Kính chào quý khách!<br>Lời đầu tiên, chúng tôi chân thành cảm ơn quý khách đã sử dụng sản phẩm của chúng tôi hơn 30 năm qua (Từ 1996). Sự thành công của chúng tôi ngày hôm nay chính là nhờ vào sự tin tưởng của quý khách dành cho chúng tôi."</p></blockquote><p style="text-align: right;"><strong>— Đội ngũ Quà Tặng Tinh Tế</strong></p>',
                            'align' => 'center',
                            'width' => 'normal',
                        ],
                    ],
                    // Block 3: Câu chuyện thương hiệu
                    [
                        'id' => 'about-story',
                        'type' => 'image_text',
                        'version' => 1,
                        'enabled' => true,
                        'settings' => [
                            'title' => 'Sản xuất quà tặng pha lê cá nhân hóa cao cấp',
                            'content' => '<p><strong>Quà Tặng Tinh Tế</strong> là thương hiệu cung cấp quà tặng pha lê cá nhân hóa cao cấp trực thuộc <strong>Công Ty TNHH SX-TM-DV Pha Lê Việt</strong>. Chúng tôi hoạt động từ năm 1996, là công ty chuyên nhập khẩu, sản xuất trực tiếp các sản phẩm quà tặng cao cấp phục vụ các doanh nghiệp, tổ chức và cá nhân.</p><p>Chúng tôi tự hào là công ty đầu tiên tại TP.HCM sản xuất và cung cấp quà tặng pha lê. Với bề dày gần 30 năm kinh nghiệm, chúng tôi tự tin có thể đáp ứng mọi yêu cầu khắt khe nhất của quý khách từ các sản phẩm phục vụ hội nghị, tặng phẩm đối tác, họp mặt, kỷ niệm ngày thành lập đến quà tặng trang trí nội thất và tặng phẩm cá nhân độc đáo.</p>',
                            'image_url' => '/public/images/season_coll_1_img.png',
                            'image_alt' => 'Quà tặng pha lê cao cấp từ Quà Tặng Tinh Tế',
                            'image_position' => 'right',
                            'button_label' => 'Xem bộ sưu tập',
                            'button_url' => '/collection',
                        ],
                    ],
                    // Block 4: Giá trị cốt lõi (4 cột)
                    [
                        'id' => 'about-mission',
                        'type' => 'feature_columns',
                        'version' => 1,
                        'enabled' => true,
                        'settings' => [
                            'title' => 'Sứ mệnh đối với khách hàng',
                            'description' => 'Giá trị cốt lõi',
                            'columns_count' => 4,
                            'items' => [
                                [
                                    'title' => 'Khách hàng là số 1',
                                    'description' => 'Luôn phấn đấu cải tiến công nghệ và kỹ thuật sản xuất để mang lại sự hài lòng tối đa cho khách hàng.',
                                    'icon' => 'fa-solid fa-users',
                                    'image_url' => '',
                                    'link_label' => '',
                                    'link_url' => '',
                                ],
                                [
                                    'title' => 'Chất lượng vượt trội',
                                    'description' => 'Sản xuất và cung cấp các dòng sản phẩm quà tặng pha lê với độ trong suốt K9 hoàn mỹ, chạm khắc sắc nét.',
                                    'icon' => 'fa-solid fa-award',
                                    'image_url' => '',
                                    'link_label' => '',
                                    'link_url' => '',
                                ],
                                [
                                    'title' => 'Thiết kế chuyên nghiệp',
                                    'description' => 'Hỗ trợ khách hàng chu đáo từ khâu lên ý tưởng, dựng mẫu 3D miễn phí cho tới khi hoàn thiện sản phẩm hoàn hảo.',
                                    'icon' => 'fa-solid fa-palette',
                                    'image_url' => '',
                                    'link_label' => '',
                                    'link_url' => '',
                                ],
                                [
                                    'title' => 'Luôn luôn đổi mới',
                                    'description' => 'Không ngừng nghiên cứu và thiết kế nhiều mẫu mã mới phù hợp xu hướng thời đại và yêu cầu cá nhân hóa cao.',
                                    'icon' => 'fa-solid fa-lightbulb',
                                    'image_url' => '',
                                    'link_label' => '',
                                    'link_url' => '',
                                ],
                            ],
                        ],
                    ],
                    // Block 5: Phương châm hoạt động (CTA)
                    [
                        'id' => 'about-motto',
                        'type' => 'cta',
                        'version' => 1,
                        'enabled' => true,
                        'settings' => [
                            'title' => 'Phương châm hoạt động',
                            'description' => 'Chất lượng phải được ưu tiên hàng đầu, không ngừng cải tiến công nghệ, tăng hiệu suất làm việc để có mức giá tốt nhất và chất lượng tối ưu nhất nhằm đem đến giá trị cao nhất cho khách hàng.',
                            'button_label' => 'Liên hệ tư vấn',
                            'button_url' => '/contact',
                            'bg_image_url' => '',
                            'bg_color' => '#143944',
                        ],
                    ],
                    // Block 6: Thông tin pháp nhân
                    [
                        'id' => 'about-legal',
                        'type' => 'rich_text',
                        'version' => 1,
                        'enabled' => true,
                        'settings' => [
                            'title' => 'Tư Cách Pháp Nhân & Thông Tin Doanh Nghiệp',
                            'content' => '<table><thead><tr><th>Thông tin</th><th>Chi tiết</th></tr></thead><tbody><tr><td><strong>Tên doanh nghiệp</strong></td><td>Công Ty TNHH SX - TM – DV Pha Lê Việt</td></tr><tr><td><strong>Tên giao dịch quốc tế</strong></td><td>Viet Crystal Manufacture Service Trading Company Limited</td></tr><tr><td><strong>Tên viết tắt</strong></td><td>Viet Crystal Co., Ltd</td></tr><tr><td><strong>Mã số thuế</strong></td><td>0304609065</td></tr><tr><td><strong>Vốn điều lệ</strong></td><td>9.000.000.000 đ</td></tr><tr><td><strong>Địa chỉ ĐKKD</strong></td><td>286/2 (Số cũ 95/B9) Nguyễn Oanh, Phường Gò Vấp, TP. HCM</td></tr><tr><td><strong>Địa chỉ giao dịch</strong></td><td>340/39 Quang Trung, P. Gò Vấp, TP. HCM</td></tr><tr><td><strong>Email nhận hóa đơn</strong></td><td>ketoan@phaleviet.vn</td></tr><tr><td><strong>Ngành nghề</strong></td><td>Nhập khẩu và sản xuất quà tặng pha lê mỹ nghệ</td></tr><tr><td><strong>Phạm vi hoạt động</strong></td><td>Trong và ngoài nước</td></tr></tbody></table>',
                            'align' => 'left',
                            'width' => 'wide',
                        ],
                    ],
                ],
            ],
        ];
    }

    // ───────────────────────────────────────────────
    // Trang Liên hệ
    // ───────────────────────────────────────────────
    private function contactPage(): array
    {
        return [
            'slug' => 'lien-he',
            'title' => 'Liên hệ',
            'seo_title' => 'Liên hệ - Quà Tặng Tinh Tế',
            'seo_description' => 'Liên hệ với Quà Tặng Tinh Tế để được tư vấn thiết kế và chế tác quà tặng pha lê 3Dcrystal cao cấp.',
            'seo_image' => '/public/images/about_bg.png',
            'layout' => [
                'schema_version' => 1,
                'blocks' => [
                    // Block 1: Hero
                    [
                        'id' => 'contact-hero',
                        'type' => 'rich_text',
                        'version' => 1,
                        'enabled' => true,
                        'settings' => [
                            'title' => '',
                            'content' => '<h1 style="text-align: center;">Liên Hệ Với Chúng Tôi</h1><p style="text-align: center;">Hãy liên lạc để được tư vấn thiết kế 3D miễn phí và nhận báo giá các mẫu pha lê cao cấp.</p>',
                            'align' => 'center',
                            'width' => 'normal',
                        ],
                    ],
                    // Block 2: Contact form + Info + Map
                    [
                        'id' => 'contact-form-main',
                        'type' => 'contact_form',
                        'version' => 1,
                        'enabled' => true,
                        'settings' => [
                            'title' => 'Thông tin liên hệ',
                            'description' => 'Gửi tin nhắn liên hệ, chúng tôi sẽ sớm phản hồi.',
                            'form_type' => 'consultation',
                            'recipient_group_id' => null,
                            'address' => 'Công Ty TNHH SX-TM-DV Pha Lê Việt - Showroom: 340/39 Quang Trung, P. Gò Vấp, TP. HCM - Xưởng SX: 72 Vườn Lài, P. An Phú Đông, TP. HCM',
                            'phone' => '0983833830',
                            'email' => 'quatangtinhte@phaleviet.vn',
                            'map_embed_url' => 'https://maps.google.com/maps?q=Qu%C3%A0%20t%E1%BB%AFng%20Minh%20Pha%20L%C3%AA%20-%20Showroom%20-%20X%C6%B0%E1%BB%9Fng%20SX%2C%2072%20V%C6%B0%E1%BB%9Dn%20L%C3%A0i%2C%20P%2C%20Q12%2C%20H%E1%BB%93%20Ch%C3%AD%20Minh%20700000%2C%20Vietnam&t=&z=16&ie=UTF8&iwloc=&output=embed',
                            'show_phone' => true,
                            'show_email' => true,
                        ],
                    ],
                ],
            ],
        ];
    }

    // ───────────────────────────────────────────────
    // 6 trang Chính sách
    // ───────────────────────────────────────────────

    private function policyPaymentPage(): array
    {
        return [
            'slug' => 'chinh-sach-thanh-toan',
            'title' => 'Chính sách thanh toán',
            'seo_title' => 'Chính sách thanh toán - Quà Tặng Tinh Tế',
            'seo_description' => 'Chính sách thanh toán khi đặt quà tặng pha lê khắc 3D tại Quà Tặng Tinh Tế.',
            'layout' => [
                'schema_version' => 1,
                'blocks' => [
                    [
                        'id' => 'policy-payment-content',
                        'type' => 'rich_text',
                        'version' => 1,
                        'enabled' => true,
                        'settings' => [
                            'title' => 'Chính sách thanh toán',
                            'content' => '<h2>1. Hình thức thanh toán</h2><ul><li>Thanh toán tiền mặt tại showroom khi nhận hàng.</li><li>Chuyển khoản ngân hàng theo thông tin được nhân viên xác nhận.</li><li>Thanh toán khi giao hàng nếu đơn hàng đủ điều kiện hỗ trợ.</li></ul><h2>2. Đặt cọc đơn cá nhân hóa</h2><ul><li>Sản phẩm khắc ảnh, tên, logo hoặc lời chúc riêng có thể cần đặt cọc.</li><li>Số tiền đặt cọc được trừ vào tổng giá trị đơn hàng.</li><li>Đơn hàng bắt đầu sản xuất sau khi hai bên xác nhận nội dung và thanh toán theo thỏa thuận.</li></ul><h2>3. Xác nhận thanh toán</h2><ul><li>Khách hàng vui lòng gửi ảnh giao dịch nếu thanh toán chuyển khoản.</li><li>Cửa hàng chỉ ghi nhận thanh toán sau khi nhận được xác nhận từ hệ thống/ngân hàng.</li></ul><p><strong>Lưu ý:</strong> Không chuyển khoản vào tài khoản không được nhân viên Quà Tặng Tinh Tế xác nhận.</p>',
                            'align' => 'left',
                            'width' => 'normal',
                        ],
                    ],
                ],
            ],
        ];
    }

    private function policyPrivacyPage(): array
    {
        return [
            'slug' => 'chinh-sach-bao-mat',
            'title' => 'Chính sách bảo mật',
            'seo_title' => 'Chính sách bảo mật - Quà Tặng Tinh Tế',
            'seo_description' => 'Chính sách bảo mật thông tin khách hàng tại Quà Tặng Tinh Tế.',
            'layout' => [
                'schema_version' => 1,
                'blocks' => [
                    [
                        'id' => 'policy-privacy-content',
                        'type' => 'rich_text',
                        'version' => 1,
                        'enabled' => true,
                        'settings' => [
                            'title' => 'Chính sách bảo mật',
                            'content' => '<h2>1. Thông tin được thu thập</h2><ul><li>Họ tên, số điện thoại, địa chỉ giao hàng và thông tin liên hệ.</li><li>Ảnh, logo, lời chúc hoặc nội dung khách hàng gửi để cá nhân hóa sản phẩm.</li><li>Thông tin thanh toán cần thiết để xác nhận đơn hàng.</li></ul><h2>2. Mục đích sử dụng</h2><ul><li>Tư vấn sản phẩm, xác nhận thiết kế và sản xuất đơn hàng.</li><li>Giao hàng, bảo hành, đổi trả hoặc hỗ trợ sau bán hàng.</li><li>Liên hệ khi cần xác nhận thêm thông tin liên quan đến đơn.</li></ul><h2>3. Cam kết bảo mật</h2><ul><li>Không bán hoặc chia sẻ thông tin cá nhân cho bên thứ ba không liên quan đến đơn hàng.</li><li>Chỉ cung cấp thông tin cần thiết cho đơn vị vận chuyển hoặc xử lý thanh toán khi cần.</li><li>Khách hàng có thể yêu cầu cập nhật hoặc xóa thông tin không còn cần thiết.</li></ul><p><strong>Lưu ý:</strong> Ảnh cá nhân dùng để khắc chỉ nên gửi qua các kênh liên hệ chính thức của Quà Tặng Tinh Tế.</p>',
                            'align' => 'left',
                            'width' => 'normal',
                        ],
                    ],
                ],
            ],
        ];
    }

    private function policyPurchasePage(): array
    {
        return [
            'slug' => 'chinh-sach-mua-hang',
            'title' => 'Chính sách mua hàng',
            'seo_title' => 'Chính sách mua hàng - Quà Tặng Tinh Tế',
            'seo_description' => 'Quy trình đặt mua pha lê khắc 3D cá nhân hóa tại Quà Tặng Tinh Tế.',
            'layout' => [
                'schema_version' => 1,
                'blocks' => [
                    [
                        'id' => 'policy-purchase-content',
                        'type' => 'rich_text',
                        'version' => 1,
                        'enabled' => true,
                        'settings' => [
                            'title' => 'Chính sách mua hàng',
                            'content' => '<h2>1. Tư vấn và chọn sản phẩm</h2><ul><li>Khách hàng chọn mẫu trên website hoặc gửi nhu cầu qua hotline/Zalo.</li><li>Đội ngũ tư vấn hỗ trợ chọn dáng khối, kích thước, chất liệu đế và hộp quà.</li><li>Giá bán được báo theo mẫu, kích thước, số lượng và yêu cầu cá nhân hóa.</li></ul><h2>2. Duyệt nội dung trước khi sản xuất</h2><ul><li>Khách hàng gửi ảnh, lời chúc, logo hoặc thông tin cần khắc.</li><li>Thiết kế được xác nhận trước khi tiến hành khắc 3D.</li><li>Nội dung đã duyệt là căn cứ để sản xuất và kiểm tra thành phẩm.</li></ul><h2>3. Xác nhận đơn hàng</h2><ul><li>Đơn hàng được xác nhận sau khi thống nhất mẫu, giá, thời gian hoàn thiện và phương thức thanh toán.</li><li>Với sản phẩm cá nhân hóa, cửa hàng có thể yêu cầu đặt cọc trước khi sản xuất.</li><li>Thời gian hoàn thiện phụ thuộc độ phức tạp của mẫu và số lượng đặt hàng.</li></ul><p><strong>Lưu ý:</strong> Nếu bạn cần đơn gấp, hãy liên hệ trực tiếp để được kiểm tra lịch sản xuất trước khi đặt hàng.</p>',
                            'align' => 'left',
                            'width' => 'normal',
                        ],
                    ],
                ],
            ],
        ];
    }

    private function policyRefundPage(): array
    {
        return [
            'slug' => 'chinh-sach-hoan-tien',
            'title' => 'Chính sách hoàn tiền',
            'seo_title' => 'Chính sách hoàn tiền - Quà Tặng Tinh Tế',
            'seo_description' => 'Việc hoàn tiền được xử lý minh bạch theo tình trạng đơn hàng và mức độ cá nhân hóa của sản phẩm.',
            'layout' => [
                'schema_version' => 1,
                'blocks' => [
                    [
                        'id' => 'policy-refund-content',
                        'type' => 'rich_text',
                        'version' => 1,
                        'enabled' => true,
                        'settings' => [
                            'title' => 'Chính sách hoàn tiền',
                            'content' => '<h2>1. Điều kiện hoàn tiền</h2><ul><li>Đơn hàng chưa đưa vào sản xuất và khách hàng yêu cầu hủy hợp lệ.</li><li>Cửa hàng không thể thực hiện đơn theo thời gian hoặc yêu cầu đã xác nhận.</li><li>Sản phẩm lỗi thuộc trách nhiệm sản xuất và không thể khắc phục bằng đổi mới/sửa chữa.</li></ul><h2>2. Chi phí không hoàn lại</h2><ul><li>Chi phí thiết kế/sản xuất đã phát sinh theo xác nhận của khách hàng.</li><li>Phí vận chuyển đã thực hiện bởi đơn vị giao hàng.</li><li>Giá trị cá nhân hóa đã hoàn tất đúng theo mẫu duyệt.</li></ul><h2>3. Thời gian xử lý</h2><ul><li>Yêu cầu hoàn tiền được kiểm tra sau khi có đủ thông tin đơn hàng.</li><li>Thời gian hoàn tiền phụ thuộc phương thức thanh toán ban đầu.</li><li>Cửa hàng sẽ thông báo trạng thái xử lý qua kênh khách hàng đã đặt hàng.</li></ul><p><strong>Lưu ý:</strong> Vui lòng giữ lại hóa đơn, xác nhận thanh toán và nội dung duyệt mẫu để quá trình xử lý nhanh hơn.</p>',
                            'align' => 'left',
                            'width' => 'normal',
                        ],
                    ],
                ],
            ],
        ];
    }

    private function policyReturnPage(): array
    {
        return [
            'slug' => 'chinh-sach-doi-tra',
            'title' => 'Chính sách đổi trả',
            'seo_title' => 'Chính sách đổi trả - Quà Tặng Tinh Tế',
            'seo_description' => 'Chính sách đổi trả sản phẩm pha lê khắc 3D cá nhân hóa tại Quà Tặng Tinh Tế.',
            'layout' => [
                'schema_version' => 1,
                'blocks' => [
                    [
                        'id' => 'policy-return-content',
                        'type' => 'rich_text',
                        'version' => 1,
                        'enabled' => true,
                        'settings' => [
                            'title' => 'Chính sách đổi trả',
                            'content' => '<h2>1. Trường hợp hỗ trợ đổi trả</h2><ul><li>Sản phẩm giao sai mẫu, sai kích thước hoặc sai nội dung so với xác nhận cuối cùng.</li><li>Sản phẩm bị lỗi kỹ thuật trong quá trình sản xuất.</li><li>Sản phẩm bị hư hỏng do vận chuyển và có hình ảnh hiện trạng khi nhận hàng.</li></ul><h2>2. Trường hợp không áp dụng</h2><ul><li>Sản phẩm đã sản xuất đúng theo nội dung khách hàng duyệt.</li><li>Khách thay đổi ý định sau khi sản phẩm cá nhân hóa đã hoàn thiện.</li><li>Sản phẩm hư hỏng do sử dụng, rơi vỡ hoặc bảo quản không đúng cách.</li></ul><h2>3. Cách gửi yêu cầu</h2><ul><li>Liên hệ trong vòng 24 giờ sau khi nhận hàng nếu có vấn đề.</li><li>Cung cấp mã đơn, ảnh/video sản phẩm và tình trạng bao bì.</li><li>Cửa hàng kiểm tra và phản hồi phương án xử lý phù hợp.</li></ul><p><strong>Lưu ý:</strong> Với đơn cá nhân hóa, hình ảnh duyệt mẫu là căn cứ quan trọng để xử lý đổi trả.</p>',
                            'align' => 'left',
                            'width' => 'normal',
                        ],
                    ],
                ],
            ],
        ];
    }

    private function policyShippingPage(): array
    {
        return [
            'slug' => 'chinh-sach-giao-hang',
            'title' => 'Chính sách giao hàng',
            'seo_title' => 'Chính sách giao hàng - Quà Tặng Tinh Tế',
            'seo_description' => 'Chính sách giao hàng của Quà Tặng Tinh Tế cho sản phẩm pha lê khắc 3D.',
            'layout' => [
                'schema_version' => 1,
                'blocks' => [
                    [
                        'id' => 'policy-shipping-content',
                        'type' => 'rich_text',
                        'version' => 1,
                        'enabled' => true,
                        'settings' => [
                            'title' => 'Chính sách giao hàng',
                            'content' => '<h2>1. Khu vực giao hàng</h2><ul><li>Hỗ trợ giao hàng tại TP.HCM và các tỉnh thành toàn quốc.</li><li>Khách hàng có thể nhận tại showroom hoặc yêu cầu giao tận nơi.</li><li>Phí giao hàng được thông báo khi xác nhận đơn.</li></ul><h2>2. Đóng gói sản phẩm</h2><ul><li>Pha lê được kiểm tra bề mặt, hình khắc và phụ kiện đi kèm trước khi đóng gói.</li><li>Đơn hàng được chèn lót phù hợp để hạn chế va đập trong quá trình vận chuyển.</li><li>Với đơn quà tặng, cửa hàng hỗ trợ tư vấn hộp quà theo nhu cầu.</li></ul><h2>3. Thời gian giao hàng</h2><ul><li>Thời gian giao phụ thuộc địa chỉ nhận hàng, thời điểm đặt và lịch sản xuất.</li><li>Đơn hàng cần giao gấp nên được xác nhận trực tiếp với nhân viên tư vấn.</li><li>Khi giao hàng, khách vui lòng kiểm tra tình trạng bên ngoài của kiện hàng.</li></ul><p><strong>Lưu ý:</strong> Nếu kiện hàng có dấu hiệu móp, vỡ hoặc ướt, hãy chụp ảnh hiện trạng và liên hệ ngay để được hỗ trợ.</p>',
                            'align' => 'left',
                            'width' => 'normal',
                        ],
                    ],
                ],
            ],
        ];
    }
}
