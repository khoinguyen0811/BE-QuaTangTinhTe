<!DOCTYPE html>
<html lang="<?= phpb_config('general.language') ?>">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $page->getTranslation('title') ?? 'Page' ?></title>
    <link rel="icon" href="/public/logo_title.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/index.css">
</head>
<body>
    <!-- Website Announcement Bar -->
    <div class="announcement" role="status" style="pointer-events:none;">
        Miễn phí dựng mẫu 3D trước khi khắc - tư vấn nhanh qua hotline 0983 833 830
    </div>

    <!-- Website Header/Navbar -->
    <header class="site-header" style="pointer-events:none;">
        <div class="container nav-shell">
            <a class="brand-link" href="/" aria-label="Trang chủ">
                <img class="brand-logo" src="/public/logo.png" alt="Quà Tặng Tinh Tế">
            </a>
            <nav class="desktop-nav" aria-label="Menu chính">
                <a href="/">Trang chủ</a>
                <a href="/about.html">Giới thiệu</a>
                <a href="/collection.html">Bộ sưu tập</a>
                <a href="/contact.html">Liên hệ</a>
            </nav>
            <div class="header-actions">
                <label class="search-field">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <input type="search" placeholder="Tìm mẫu pha lê..." disabled>
                </label>
                <a class="icon-button" href="#" aria-label="Giỏ hàng">
                    <i class="fa-solid fa-bag-shopping" aria-hidden="true"></i>
                    <span class="cart-badge">0</span>
                </a>
                <a class="icon-button" href="#" aria-label="Tài khoản">
                    <i class="fa-solid fa-user" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </header>

    <!-- Page Builder Content Area -->
    <main style="min-height:50vh;">
        <?= $body ?>
    </main>

    <!-- Website Footer -->
    <footer class="site-footer" style="pointer-events:none;">
        <div class="container footer-grid">
            <section>
                <h3 style="margin:0 0 1.25rem;color:#fff;font-size:0.95rem;text-transform:uppercase;font-weight:700;">Về chúng tôi</h3>
                <div style="display:grid;gap:0.75rem;color:rgba(255,255,255,0.72);font-size:0.88rem;line-height:1.6;">
                    <div style="display:flex;gap:0.5rem;">
                        <i class="fa-solid fa-location-dot" style="margin-top:0.3rem;flex-shrink:0;color:#fff;"></i>
                        <span><strong>Trụ sở:</strong> 41 Nguyễn Bỉnh Khiêm, P. Đa Kao, Quận 1, TP.HCM<br><strong>Xưởng sản xuất:</strong> Lê Văn Lương, Nhà Bè, TP.HCM</span>
                    </div>
                    <div style="display:flex;gap:0.5rem;">
                        <i class="fa-solid fa-clock" style="margin-top:0.3rem;flex-shrink:0;color:#fff;"></i>
                        <div><strong style="color:#fff;">Thời gian làm việc:</strong><br><span style="font-size:0.85rem;">Thứ 2 đến Thứ 7 (8h - 17h30)<br>- Hotline: <span style="color:#3b92ab;font-weight:700;">0983.83.38.30</span></span></div>
                    </div>
                </div>
            </section>
            <section>
                <h3 style="margin:0 0 1.25rem;color:#fff;font-size:0.95rem;text-transform:uppercase;font-weight:700;">Chính sách</h3>
                <ul style="display:grid;gap:0.65rem;padding:0;margin:0;list-style:none;">
                    <li><span style="color:rgba(255,255,255,0.72);font-size:0.88rem;">Chính sách mua hàng</span></li>
                    <li><span style="color:rgba(255,255,255,0.72);font-size:0.88rem;">Chính sách giao hàng</span></li>
                    <li><span style="color:rgba(255,255,255,0.72);font-size:0.88rem;">Chính sách thanh toán</span></li>
                    <li><span style="color:rgba(255,255,255,0.72);font-size:0.88rem;">Chính sách đổi trả</span></li>
                </ul>
            </section>
            <section>
                <h3 style="margin:0 0 1.25rem;color:#fff;font-size:0.95rem;text-transform:uppercase;font-weight:700;">Liên kết trang</h3>
                <ul style="display:grid;gap:0.65rem;padding:0;margin:0;list-style:none;">
                    <li><span style="color:rgba(255,255,255,0.72);font-size:0.88rem;">Trang chủ</span></li>
                    <li><span style="color:rgba(255,255,255,0.72);font-size:0.88rem;">Sản phẩm</span></li>
                    <li><span style="color:rgba(255,255,255,0.72);font-size:0.88rem;">Giới thiệu</span></li>
                    <li><span style="color:rgba(255,255,255,0.72);font-size:0.88rem;">Liên hệ</span></li>
                </ul>
            </section>
            <section>
                <h3 style="margin:0 0 1.25rem;color:#fff;font-size:0.95rem;text-transform:uppercase;font-weight:700;">Đăng ký nhận tin</h3>
                <p style="font-size:0.88rem;margin:0 0 1rem;opacity:0.85;color:rgba(255,255,255,0.72);">Đăng ký nhận thông tin khuyến mãi và bộ sưu tập pha lê mới nhất.</p>
            </section>
        </div>
        <div class="container footer-bottom" style="font-size:0.82rem;color:rgba(255,255,255,0.55);border-top:1px solid rgba(255,255,255,0.1);padding:1.2rem 0;">
            <span>© Bản quyền thuộc về Quà Tặng Tinh Tế</span>
        </div>
    </footer>
</body>
</html>
