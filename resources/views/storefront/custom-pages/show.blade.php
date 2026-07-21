<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $page->seo_title ?: $page->title }} - Quà Tặng Tinh Tế</title>
    <meta name="description" content="{{ $page->seo_description ?: 'Quà Tặng Tinh Tế - Hệ thống quà tặng pha lê cao cấp cá nhân hóa.' }}" />
    
    @if($preview)
        <meta name="robots" content="noindex, nofollow" />
    @else
        <meta name="robots" content="index, follow" />
        <link rel="canonical" href="{{ url('/pages/' . $page->slug) }}" />
    @endif

    <meta property="og:title" content="{{ $page->seo_title ?: $page->title }}" />
    <meta property="og:description" content="{{ $page->seo_description ?: 'Quà Tặng Tinh Tế - Quà tặng pha lê cao cấp.' }}" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="{{ url('/pages/' . $page->slug) }}" />
    <meta property="og:image" content="{{ $page->seo_image ?: '/public/images/slider_3.png' }}" />

    <link rel="icon" href="/public/logo_title.png" type="image/png" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="/index.css" />

    <style>
        .site-main {
            min-height: 50vh;
            padding-bottom: 4rem;
        }

        /* Premium highlight for active preview selection */
        .preview-block-selected {
            outline: 3px solid #0d6efd !important;
            outline-offset: -3px;
            position: relative;
        }
        .preview-block-selected::after {
            content: "Đang sửa";
            position: absolute;
            top: 4px;
            right: 4px;
            background: #0d6efd;
            color: #fff;
            font-size: 11px;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 3px;
            z-index: 9999;
            pointer-events: none;
        }
        .builder-block-wrapper {
            cursor: pointer;
            transition: box-shadow 0.2s ease;
        }
        .builder-block-wrapper:hover {
            box-shadow: inset 0 0 0 2px rgba(13, 110, 253, 0.4);
        }

        /* Custom page rich-text styling (Tiptap support) */
        .custom-page-rich-text {
            overflow-wrap: anywhere;
        }
        .custom-page-rich-text table {
            width: 100%;
            border-collapse: collapse;
            margin: 1.5rem 0;
        }
        .custom-page-rich-text th,
        .custom-page-rich-text td {
            padding: 0.75rem;
            border: 1px solid #cbd5e1;
            vertical-align: top;
        }
        .custom-page-rich-text th {
            background-color: #f8fafc;
            font-weight: 700;
        }
        .custom-page-rich-text [data-text-align="left"] { text-align: left; }
        .custom-page-rich-text [data-text-align="center"] { text-align: center; }
        .custom-page-rich-text [data-text-align="right"] { text-align: right; }
        .custom-page-rich-text [data-text-align="justify"] { text-align: justify; }
        
        .custom-page-rich-text ul,
        .custom-page-rich-text ol {
            margin-bottom: 1rem;
            padding-left: 2rem;
        }
        .custom-page-rich-text ul { list-style-type: disc; }
        .custom-page-rich-text ol { list-style-type: decimal; }
        .custom-page-rich-text blockquote {
            border-left: 4px solid #cbd5e1;
            padding-left: 1rem;
            color: #64748b;
            font-style: italic;
            margin: 1.5rem 0;
        }

        @media (max-width: 767px) {
            .custom-page-rich-text {
                overflow-x: auto;
            }
            .custom-page-rich-text table {
                min-width: 640px;
            }
        }
    </style>
</head>
<body>
    <a class="skip-link" href="#main-content">Bỏ qua để đến nội dung chính</a>

    <div class="announcement" role="status">
        Miễn phí dựng mẫu 3D trước khi khắc - tư vấn nhanh qua hotline 0983 833 830
    </div>

    <header class="site-header">
        <div class="container nav-shell">
            <a class="brand-link" href="/" aria-label="Trang chủ Quà Tặng Tinh Tế">
                <img class="brand-logo" src="/public/logo.png" alt="Quà Tặng Tinh Tế" />
            </a>

            <nav class="desktop-nav" aria-label="Menu chính">
                <a href="/">Trang chủ</a>
                <a href="/about.html">Giới thiệu</a>
                <a href="/collection.html">Bộ sưu tập</a>
                <a href="/contact.html">Liên hệ</a>
            </nav>

            <div class="header-actions">
                <label class="search-field" for="search-input">
                    <span class="sr-only">Tìm sản phẩm pha lê</span>
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <input id="search-input" type="search" placeholder="Tìm mẫu pha lê..." autocomplete="off" />
                </label>
                <a class="icon-button" href="/cart.html" aria-label="Xem giỏ hàng">
                    <i class="fa-solid fa-bag-shopping" aria-hidden="true"></i>
                    <span id="cart-badge" class="cart-badge">0</span>
                </a>
                <a class="icon-button" href="/login.html" data-auth-link aria-label="Đăng nhập tài khoản">
                    <i class="fa-solid fa-user" aria-hidden="true"></i>
                    <span class="sr-only" data-auth-label>Đăng nhập</span>
                </a>
                <button class="icon-button menu-toggle" id="mobile-menu-toggle" type="button" aria-expanded="false" aria-controls="mobile-nav">
                    <i class="fa-solid fa-bars" aria-hidden="true"></i>
                    <span class="sr-only">Menu di động</span>
                </button>
            </div>
        </div>

        <nav class="mobile-nav" id="mobile-nav" aria-label="Menu di động">
            <a href="/">Trang chủ</a>
            <a href="/about.html">Giới thiệu</a>
            <a href="/collection.html">Bộ sưu tập</a>
            <a href="/contact.html">Liên hệ</a>
        </nav>
    </header>

    <main id="main-content" class="site-main">
        @if(($page->builder_driver ?? 'legacy') === 'laravel-pagebuilder')
            <!-- Visual Page Builder Render -->
            {!! app(\App\Services\PageBuilderRenderService::class)->render($page, $preview ?? false) !!}
        @else
            <!-- Legacy Block Renderer -->
            <x-storefront-block-renderer :layout="$layout" :page="$page" :preview="$preview" />
        @endif
    </main>

    <footer class="site-footer">
        <div class="container footer-grid">
            <section aria-labelledby="footer-about">
                <h3 id="footer-about" style="margin: 0 0 1.25rem; color: #fff; font-size: 0.95rem; text-transform: uppercase; font-weight: 700;">Về chúng tôi</h3>
                <div style="display: grid; gap: 0.75rem; color: rgba(255,255,255,0.72); font-size: 0.88rem; line-height: 1.6;">
                    <div style="display: flex; gap: 0.5rem;">
                        <i class="fa-solid fa-location-dot" style="margin-top: 0.3rem; flex-shrink: 0; color: #fff;"></i>
                        <span>
                            <strong>Trụ sở:</strong> 41 Nguyễn Bỉnh Khiêm, P. Đa Kao, Quận 1, TP.HCM<br>
                            <strong>Xưởng sản xuất:</strong> Lê Văn Lương, Nhà Bè, TP.HCM
                        </span>
                    </div>
                    <div style="display: flex; gap: 0.5rem;">
                        <i class="fa-solid fa-clock" style="margin-top: 0.3rem; flex-shrink: 0; color: #fff;"></i>
                        <div>
                            <strong style="color: #fff;">Thời gian làm việc:</strong><br>
                            <span style="font-size: 0.85rem;">
                                Thứ 2 đến Thứ 7 (8h - 17h30)<br>
                                - Hotline: <a href="tel:0983833830" style="color: #3b92ab; font-weight: 700;">0983.83.38.30</a>
                            </span>
                        </div>
                    </div>
                </div>
            </section>
            <section aria-labelledby="footer-policies">
                <h3 id="footer-policies" style="margin: 0 0 1.25rem; color: #fff; font-size: 0.95rem; text-transform: uppercase; font-weight: 700;">Chính sách</h3>
                <ul style="display: grid; gap: 0.65rem; padding: 0; margin: 0; list-style: none;">
                    <li><a href="/policies/purchase" style="color: rgba(255,255,255,0.72); font-size: 0.88rem;">Chính sách mua hàng</a></li>
                    <li><a href="/policies/shipping" style="color: rgba(255,255,255,0.72); font-size: 0.88rem;">Chính sách giao hàng</a></li>
                    <li><a href="/policies/payment" style="color: rgba(255,255,255,0.72); font-size: 0.88rem;">Chính sách thanh toán</a></li>
                    <li><a href="/policies/return" style="color: rgba(255,255,255,0.72); font-size: 0.88rem;">Chính sách đổi trả</a></li>
                    <li><a href="/policies/privacy" style="color: rgba(255,255,255,0.72); font-size: 0.88rem;">Chính sách bảo mật</a></li>
                </ul>
            </section>
            <section aria-labelledby="footer-links">
                <h3 id="footer-links" style="margin: 0 0 1.25rem; color: #fff; font-size: 0.95rem; text-transform: uppercase; font-weight: 700;">Liên kết trang</h3>
                <ul style="display: grid; gap: 0.65rem; padding: 0; margin: 0; list-style: none; margin-bottom: 1.5rem;">
                    <li><a href="/" style="color: rgba(255,255,255,0.72); font-size: 0.88rem;">Trang chủ</a></li>
                    <li><a href="/collection.html" style="color: rgba(255,255,255,0.72); font-size: 0.88rem;">Sản phẩm</a></li>
                    <li><a href="/about.html" style="color: rgba(255,255,255,0.72); font-size: 0.88rem;">Giới thiệu</a></li>
                    <li><a href="/contact.html" style="color: rgba(255,255,255,0.72); font-size: 0.88rem;">Liên hệ</a></li>
                </ul>
            </section>
            <section aria-labelledby="footer-newsletter">
                <h3 id="footer-newsletter" style="margin: 0 0 1.25rem; color: #fff; font-size: 0.95rem; text-transform: uppercase; font-weight: 700;">Đăng ký nhận tin</h3>
                <p style="font-size: 0.88rem; margin: 0 0 1rem; opacity: 0.85; color: rgba(255,255,255,0.72);">Đăng ký nhận thông tin khuyến mãi và bộ sưu tập pha lê mới nhất.</p>
                <form onsubmit="event.preventDefault(); alert('Cảm ơn bạn đã đăng ký!'); this.reset();" style="display: flex; border-radius: 99px; background: #fff; overflow: hidden; padding: 2px; border: 1px solid rgba(255,255,255,0.15); max-width: 320px;">
                    <input type="email" required placeholder="Nhập địa chỉ email" style="flex: 1; border: 0; padding: 0.5rem 0.85rem; font-size: 0.85rem; outline: none; background: transparent; color: #1e293b;" />
                    <button type="submit" style="background: #143944; color: #fff; border: 0; border-radius: 99px; padding: 0.5rem 1rem; font-size: 0.82rem; font-weight: 700; cursor: pointer; white-space: nowrap; flex-shrink: 0;">Đăng ký</button>
                </form>
            </section>
        </div>
        <div class="container footer-bottom" style="font-size: 0.82rem; color: rgba(255,255,255,0.55); border-top: 1px solid rgba(255, 255, 255, 0.1); padding: 1.2rem 0;">
            <span>© Bản quyền thuộc về Quà Tặng Tinh Tế</span>
        </div>
    </footer>

    <script src="/static-client.js"></script>
    <script>
        // Update cart indicator
        function updateCartBadge() {
            try {
                const cart = JSON.parse(localStorage.getItem("cart") || "[]");
                const badge = document.getElementById("cart-badge");
                if (badge) {
                    badge.textContent = cart.reduce((sum, item) => sum + item.quantity, 0);
                }
            } catch(e) {}
        }
        updateCartBadge();

        // Mobile menu toggle
        const toggle = document.getElementById("mobile-menu-toggle");
        const nav = document.getElementById("mobile-nav");
        if (toggle && nav) {
            toggle.addEventListener("click", () => {
                const open = toggle.getAttribute("aria-expanded") === "true";
                toggle.setAttribute("aria-expanded", String(!open));
                nav.classList.toggle("is-open", !open);
            });
        }

        // Live preview message handler (Admin integration)
        @if($preview)
            window.addEventListener('message', (event) => {
                const data = event.data;
                if (!data || data.type !== 'sly_custom_page_builder_preview') return;
                
                // Real-time hot reload layout updates
                if (data.layout) {
                    // Quick reload layout state via POST/GET from database is avoided, but dynamic refresh is triggered
                    window.location.reload();
                }

                // Focus and outline scroll highlight
                if (data.selectedId) {
                    document.querySelectorAll('.builder-block-wrapper').forEach(el => {
                        el.classList.remove('preview-block-selected');
                    });
                    const selectedEl = document.querySelector(`[data-preview-block-id="${data.selectedId}"]`);
                    if (selectedEl) {
                        selectedEl.classList.add('preview-block-selected');
                        if (data.scrollToSelected) {
                            selectedEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    }
                }
            });

            // Dispatch focus event to parent editor when a block is clicked inside preview
            document.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('.builder-block-wrapper').forEach(wrapper => {
                    wrapper.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        const blockId = wrapper.dataset.previewBlockId;
                        if (blockId) {
                            window.parent.postMessage({
                                type: 'sly_custom_page_builder_select_block',
                                blockId: blockId
                            }, '*');
                        }
                    });
                });
            });
        @endif
    </script>
</body>
</html>
