<div class="cta-block py-5" style="
    background: {{ !empty($settings['bg_image_url']) ? 'linear-gradient(rgba(20, 57, 68, 0.88), rgba(20, 57, 68, 0.88)), url(' . $settings['bg_image_url'] . ') no-repeat center center' : ($settings['bg_color'] ?? '#143944') }};
    background-size: cover;
    color: #fff;
    text-align: center;
">
    <div class="container py-4" style="max-width: 800px;">
        @if(!empty($settings['title']))
            <h2 class="mb-3 font-display" style="font-size: clamp(1.8rem, 3vw, 2.5rem); font-weight: 700; color: #fff; line-height: 1.3;">
                {{ $settings['title'] }}
            </h2>
        @endif

        @if(!empty($settings['description']))
            <p class="mb-4" style="font-size: 1.15rem; color: rgba(255, 255, 255, 0.9); max-width: 600px; margin-inline: auto; line-height: 1.6;">
                {{ $settings['description'] }}
            </p>
        @endif

        @if(!empty($settings['button_url']) && !empty($settings['button_label']))
            <a href="{{ $settings['button_url'] }}" class="button" style="
                background: var(--brand-200, #ff750f);
                color: #fff;
                border: 0;
                font-weight: 700;
                padding: 0.8rem 2rem;
                border-radius: 99px;
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                text-decoration: none;
                transition: transform 0.2s ease, opacity 0.2s ease;
            " onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                {{ $settings['button_label'] }}
                <i class="fa-solid fa-arrow-right" style="font-size: 0.9rem;"></i>
            </a>
        @endif
    </div>
</div>
