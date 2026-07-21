<div class="contact-block py-5">
    <div class="container contact-layout">
        <!-- Contact Information Column -->
        <div class="contact-info-card">
            @if(!empty($settings['title']))
                <h2>{{ $settings['title'] }}</h2>
            @endif
            @if(!empty($settings['description']))
                <p class="mb-4">{{ $settings['description'] }}</p>
            @endif

            <div class="contact-list">
                @if(!empty($settings['address']))
                    <div class="contact-item-row">
                        <div class="icon-box">
                            <i class="fa-solid fa-location-dot"></i>
                        </div>
                        <div class="item-details">
                            <strong>Địa chỉ</strong>
                            <span>{{ $settings['address'] }}</span>
                        </div>
                    </div>
                @endif

                @if(($settings['show_phone'] ?? true) && !empty($settings['phone']))
                    <div class="contact-item-row">
                        <div class="icon-box">
                            <i class="fa-solid fa-phone"></i>
                        </div>
                        <div class="item-details">
                            <strong>Điện thoại</strong>
                            <a href="tel:{{ $settings['phone'] }}">{{ $settings['phone'] }}</a>
                        </div>
                    </div>
                @endif

                @if(($settings['show_email'] ?? true) && !empty($settings['email']))
                    <div class="contact-item-row">
                        <div class="icon-box">
                            <i class="fa-solid fa-envelope"></i>
                        </div>
                        <div class="item-details">
                            <strong>Địa chỉ Email</strong>
                            <a href="mailto:{{ $settings['email'] }}">{{ $settings['email'] }}</a>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Contact Form Column -->
        <div class="contact-form-card" id="form-container-block">
            <h2>Gửi tin nhắn liên hệ</h2>
            <form class="form-grid" onsubmit="submitBlockContactForm(event, '{{ $settings['phone'] ?? '0983833830' }}')">
                <div class="field">
                    <label for="contact-block-name">Họ và tên của bạn *</label>
                    <input id="contact-block-name" type="text" required placeholder="Nhập họ và tên đầy đủ" />
                </div>

                <div class="field">
                    <label for="contact-block-phone">Số điện thoại *</label>
                    <input id="contact-block-phone" type="tel" required placeholder="Nhập số điện thoại" />
                </div>

                <div class="field">
                    <label for="contact-block-email">Địa chỉ Email</label>
                    <input id="contact-block-email" type="email" placeholder="Nhập email (không bắt buộc)" />
                </div>

                <div class="field">
                    <label for="contact-block-subject">Bạn muốn tư vấn về *</label>
                    <select id="contact-block-subject" required>
                        <option value="Tư vấn đặt quà tặng pha lê 3D">Tư vấn đặt quà tặng pha lê 3D</option>
                        <option value="Yêu cầu thiết kế mẫu 3D miễn phí">Yêu cầu thiết kế mẫu 3D miễn phí</option>
                        <option value="Báo giá đơn hàng số lượng lớn">Báo giá đơn hàng số lượng lớn</option>
                        <option value="Hợp tác kinh doanh / Ý kiến đóng góp">Hợp tác kinh doanh / Ý kiến đóng góp</option>
                    </select>
                </div>

                <div class="field">
                    <label for="contact-block-message">Lời nhắn chi tiết *</label>
                    <textarea id="contact-block-message" required rows="4" placeholder="Nhập nội dung yêu cầu chi tiết của bạn..."></textarea>
                </div>

                <button class="button button-primary" type="submit" style="width:100%; justify-content:center;">
                    <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                    Gửi yêu cầu liên hệ
                </button>
            </form>
        </div>
    </div>

    @if(!empty($settings['map_embed_url']))
        <div class="container map-section mt-5">
            <div class="map-container">
                <iframe
                    src="{{ $settings['map_embed_url'] }}"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    style="border:0;"
                ></iframe>
            </div>
        </div>
    @endif
</div>

<script>
    if (typeof window.submitBlockContactForm === 'undefined') {
        window.submitBlockContactForm = function(event, phone) {
            event.preventDefault();
            
            @if($preview)
                alert('Chế độ xem trước: Đã chặn chuyển hướng và Zalo API.');
                return;
            @endif

            const name = document.getElementById("contact-block-name").value.trim();
            const contactPhone = document.getElementById("contact-block-phone").value.trim();
            const email = document.getElementById("contact-block-email").value.trim();
            const subject = document.getElementById("contact-block-subject").value;
            const message = document.getElementById("contact-block-message").value.trim();
            
            const zaloText = `=== YÊU CẦU LIÊN HỆ ===\nHọ tên: ${name}\nSĐT: ${contactPhone}\nEmail: ${email || "Không cung cấp"}\nChủ đề: ${subject}\nNội dung: ${message}\n======================`;
            
            const copySuccess = () => {
                const container = document.getElementById("form-container-block");
                if (container) {
                    container.innerHTML = `
                        <div class="success-animation-card" style="text-align: center; padding: 2rem;">
                            <i class="fa-solid fa-circle-check" style="font-size: 3rem; color: var(--success, #28a745); margin-bottom: 1rem;"></i>
                            <h3>Cảm ơn ${name}!</h3>
                            <p style="margin-bottom: 1.5rem;">
                                Thông tin liên hệ của bạn đã được sao chép vào clipboard.<br>
                                Bạn có thể tiếp tục trò chuyện trực tiếp và gửi thiết kế qua Zalo để chúng tôi hỗ trợ nhanh nhất.
                            </p>
                            <a class="button button-primary" href="https://zalo.me/${phone.replace(/[^0-9]/g, '')}" target="_blank" style="margin-inline:auto; display: inline-flex; align-items: center; gap: 0.5rem;">
                                <i class="fa-solid fa-comment-dots"></i>
                                Liên hệ qua Zalo ngay
                            </a>
                        </div>
                    `;
                }
            };

            navigator.clipboard.writeText(zaloText).then(copySuccess).catch(copySuccess);
        };
    }
</script>
