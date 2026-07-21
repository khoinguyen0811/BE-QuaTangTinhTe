<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const POST_SLUGS = [
        'cach-chon-dang-pha-le-3d-phu-hop-tung-dip-tang',
        'huong-dan-chuan-bi-anh-de-khac-3d-ro-net-nhat',
        'goi-y-thong-diep-khac-pha-le-cho-cac-dip-dac-biet',
        'xu-huong-qua-tang-pha-le-3d-duoc-yeu-thich-2026',
        'qua-pha-le-sinh-nhat-chon-mau-theo-nguoi-nhan',
        'qua-ky-niem-pha-le-3d-luu-giu-khoanh-khac',
    ];

    private const CATEGORY_SLUGS = [
        'huong-dan-chon-qua',
        'ca-nhan-hoa-3d',
        'qua-tang-sinh-nhat',
        'qua-tang-ky-niem',
        'qua-tang-doanh-nghiep',
        'tin-tuc-su-kien',
    ];

    public function up(): void
    {
        $now = now();
        $categories = [
            ['slug' => 'huong-dan-chon-qua', 'name' => 'Hướng dẫn chọn quà', 'description' => 'Kinh nghiệm chọn quà pha lê phù hợp người nhận và dịp tặng.'],
            ['slug' => 'ca-nhan-hoa-3d', 'name' => 'Cá nhân hóa 3D', 'description' => 'Chuẩn bị ảnh, nội dung và bố cục khắc pha lê 3D.'],
            ['slug' => 'qua-tang-sinh-nhat', 'name' => 'Quà tặng sinh nhật', 'description' => 'Gợi ý quà sinh nhật pha lê theo độ tuổi và mối quan hệ.'],
            ['slug' => 'qua-tang-ky-niem', 'name' => 'Quà tặng kỷ niệm', 'description' => 'Lưu giữ ngày cưới, ngày yêu và những cột mốc đáng nhớ.'],
            ['slug' => 'qua-tang-doanh-nghiep', 'name' => 'Quà tặng doanh nghiệp', 'description' => 'Quà tri ân, vinh danh và quà tặng đối tác bằng pha lê.'],
            ['slug' => 'tin-tuc-su-kien', 'name' => 'Tin tức & sự kiện', 'description' => 'Xu hướng quà tặng và hoạt động mới từ Quà Tặng Tinh Tế.'],
        ];

        foreach ($categories as $index => $category) {
            DB::table('post_categories')->updateOrInsert(
                ['slug' => $category['slug']],
                [
                    'parent_id' => null,
                    'name' => $this->localized($category['name']),
                    'description' => $this->localized($category['description']),
                    'is_active' => true,
                    'sort_order' => ($index + 1) * 10,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        $categoryIds = DB::table('post_categories')
            ->whereIn('slug', self::CATEGORY_SLUGS)
            ->pluck('id', 'slug');

        $posts = [
            [
                'category' => 'huong-dan-chon-qua',
                'slug' => self::POST_SLUGS[0],
                'title' => 'Cách chọn dáng pha lê 3D phù hợp từng dịp tặng',
                'summary' => 'Gợi ý chọn hình trái tim, khối chữ nhật, đa giác hoặc giọt nước theo người nhận và ý nghĩa món quà.',
                'image' => '/public/images/slider_1.png',
                'days_ago' => 2,
                'content' => <<<'HTML'
<p>Mỗi dáng pha lê tạo ra một cảm nhận khác nhau. Chọn đúng hình khối giúp ảnh chân dung, lời chúc và ánh sáng từ đế LED kết hợp tự nhiên hơn.</p>
<h2>Chọn hình khối theo thông điệp</h2>
<ul><li>Trái tim phù hợp quà tình yêu, ngày cưới và kỷ niệm.</li><li>Khối chữ nhật tạo cảm giác trang trọng, phù hợp gia đình và doanh nghiệp.</li><li>Đa giác giúp ảnh chân dung nổi bật nhờ nhiều mặt bắt sáng.</li><li>Giọt nước mềm mại, phù hợp quà sinh nhật và quà dành cho mẹ.</li></ul>
<h2>Lưu ý về kích thước</h2>
<p>Khối lớn hiển thị tốt ảnh nhiều người và nội dung dài. Với một chân dung, khối vừa thường cân đối hơn, dễ trưng bày và tối ưu ngân sách.</p>
HTML,
            ],
            [
                'category' => 'ca-nhan-hoa-3d',
                'slug' => self::POST_SLUGS[1],
                'title' => 'Hướng dẫn chuẩn bị ảnh để khắc 3D rõ nét nhất',
                'summary' => 'Những lưu ý quan trọng khi chọn ảnh chân dung để sản phẩm pha lê đạt chiều sâu và độ nhận diện tốt.',
                'image' => '/public/images/imgtext_1_videoimage.png',
                'days_ago' => 4,
                'content' => <<<'HTML'
<p>Ảnh đầu vào quyết định phần lớn chất lượng dựng chân dung 3D. Bạn không cần ảnh chụp chuyên nghiệp, nhưng khuôn mặt phải rõ và đủ sáng.</p>
<h2>Tiêu chí của một ảnh phù hợp</h2>
<ul><li>Khuôn mặt không bị che bởi tóc, kính tối hoặc vật thể.</li><li>Ảnh có độ phân giải tốt, không chụp lại từ màn hình.</li><li>Ánh sáng đều, hạn chế vùng cháy sáng và bóng tối quá mạnh.</li><li>Góc chụp chính diện hoặc nghiêng nhẹ giúp dựng khối tự nhiên.</li></ul>
<p>Đội ngũ thiết kế sẽ kiểm tra ảnh và gửi mẫu bố cục trước khi khắc để bạn duyệt.</p>
HTML,
            ],
            [
                'category' => 'qua-tang-ky-niem',
                'slug' => self::POST_SLUGS[2],
                'title' => 'Gợi ý thông điệp khắc pha lê cho các dịp đặc biệt',
                'summary' => 'Tổng hợp những câu chúc ngắn gọn, giàu ý nghĩa cho sinh nhật, kỷ niệm, khai trương và tri ân.',
                'image' => '/public/images/season_coll_6_img.png',
                'days_ago' => 6,
                'content' => <<<'HTML'
<p>Một lời nhắn ngắn thường đẹp hơn khi khắc trên pha lê. Hãy ưu tiên tên người nhận, ngày kỷ niệm và một câu chúc có ý nghĩa riêng.</p>
<h2>Công thức nội dung dễ áp dụng</h2>
<p><strong>Tên người nhận + lời chúc chính + ngày đáng nhớ.</strong> Cấu trúc này đủ thông tin nhưng vẫn giữ được khoảng thở cho bố cục ảnh.</p>
<h2>Một vài gợi ý</h2>
<ul><li>“Cảm ơn vì đã luôn ở bên anh.”</li><li>“Gia đình là nơi bình yên nhất.”</li><li>“Chúc mừng cột mốc mới rực rỡ và thành công.”</li></ul>
HTML,
            ],
            [
                'category' => 'tin-tuc-su-kien',
                'slug' => self::POST_SLUGS[3],
                'title' => 'Xu hướng quà tặng pha lê 3D được yêu thích năm 2026',
                'summary' => 'Những mẫu quà cá nhân hóa đang được lựa chọn nhiều nhờ thiết kế gọn, giàu cảm xúc và phù hợp nhiều dịp.',
                'image' => '/public/images/slider_3.png',
                'days_ago' => 8,
                'content' => <<<'HTML'
<p>Quà tặng năm 2026 tập trung vào trải nghiệm cá nhân và câu chuyện phía sau món quà. Pha lê khắc 3D đáp ứng tốt xu hướng này nhờ khả năng lưu giữ ảnh, chữ và ánh sáng trong cùng một sản phẩm.</p>
<h2>Ba lựa chọn nổi bật</h2>
<ul><li>Chân dung gia đình kết hợp đế LED ánh sáng ấm.</li><li>Ảnh đôi kèm ngày kỷ niệm và lời nhắn riêng.</li><li>Logo doanh nghiệp kết hợp thông điệp vinh danh.</li></ul>
HTML,
            ],
            [
                'category' => 'qua-tang-sinh-nhat',
                'slug' => self::POST_SLUGS[4],
                'title' => 'Quà pha lê sinh nhật: chọn mẫu theo người nhận',
                'summary' => 'Cách lựa chọn hình dáng, kích thước và nội dung quà sinh nhật cho cha mẹ, người yêu, bạn bè hoặc đồng nghiệp.',
                'image' => '/public/images/season_coll_1_img.png',
                'days_ago' => 10,
                'content' => <<<'HTML'
<p>Quà sinh nhật trở nên đáng nhớ khi thể hiện đúng mối quan hệ giữa người tặng và người nhận. Một bức ảnh quen thuộc cùng lời chúc riêng thường có giá trị cảm xúc lâu dài.</p>
<h2>Gợi ý theo người nhận</h2>
<ul><li>Cha mẹ: ảnh gia đình, lời cảm ơn và lời chúc sức khỏe.</li><li>Người yêu: ảnh đôi, ngày bắt đầu và một lời nhắn ngắn.</li><li>Đồng nghiệp: thiết kế tối giản, lịch sự, dễ đặt trên bàn làm việc.</li></ul>
HTML,
            ],
            [
                'category' => 'qua-tang-ky-niem',
                'slug' => self::POST_SLUGS[5],
                'title' => 'Quà kỷ niệm pha lê 3D: lưu giữ khoảnh khắc',
                'summary' => 'Biến ảnh cưới, ảnh đôi hoặc khoảnh khắc gia đình thành món quà có thể trưng bày bền lâu.',
                'image' => '/public/images/season_coll_2_img.png',
                'days_ago' => 12,
                'content' => <<<'HTML'
<p>Ngày kỷ niệm là dịp nhìn lại một hành trình chung. Pha lê trong suốt giúp hình ảnh nổi bật nhưng vẫn giữ cảm giác trang nhã khi đặt trong phòng ngủ, phòng khách hoặc bàn làm việc.</p>
<h2>Chuẩn bị nội dung trước khi đặt</h2>
<p>Hãy chọn một ảnh đại diện cho cột mốc, xác định ngày tháng cần khắc và viết lời nhắn trong khoảng một đến hai câu. Thiết kế gọn sẽ dễ đọc và bền đẹp theo thời gian.</p>
HTML,
            ],
        ];

        foreach ($posts as $post) {
            DB::table('posts')->updateOrInsert(
                ['slug' => $post['slug']],
                [
                    'category_id' => $categoryIds[$post['category']] ?? null,
                    'title' => $this->localized($post['title']),
                    'summary' => $this->localized($post['summary']),
                    'content' => $this->localized($post['content']),
                    'image_url' => $post['image'],
                    'is_active' => true,
                    'seo_title' => $this->localized($post['title']),
                    'seo_description' => $this->localized($post['summary']),
                    'seo_keys' => 'quà tặng pha lê, pha lê khắc 3D, quà tặng cá nhân hóa',
                    'published_at' => $now->copy()->subDays($post['days_ago']),
                    'updated_at' => $now,
                    'created_at' => $now->copy()->subDays($post['days_ago']),
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('posts')->whereIn('slug', self::POST_SLUGS)->delete();
        DB::table('post_categories')->whereIn('slug', self::CATEGORY_SLUGS)->delete();
    }

    private function localized(string $value): string
    {
        return json_encode(['vi' => $value], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
};
