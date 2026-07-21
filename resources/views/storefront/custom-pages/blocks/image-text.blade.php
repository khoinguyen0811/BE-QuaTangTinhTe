<div class="image-text-block py-5">
    <div class="container">
        <div class="row align-items-center" style="
            display: flex;
            flex-wrap: wrap;
            gap: 3rem;
            flex-direction: {{ ($settings['image_position'] ?? 'left') === 'right' ? 'row-reverse' : 'row' }};
        ">
            <!-- Image Column -->
            <div class="col-lg-6" style="flex: 1; min-width: 300px;">
                <div class="image-wrap" style="
                    border-radius: var(--radius, 12px);
                    overflow: hidden;
                    border: 1px solid var(--line, #e2e8f0);
                    background: #f8fafc;
                ">
                    @if(!empty($settings['image_url']))
                        <img src="{{ $settings['image_url'] }}" alt="{{ $settings['image_alt'] ?? 'Hình ảnh giới thiệu' }}" style="
                            width: 100%;
                            height: 100%;
                            object-fit: cover;
                            display: block;
                            max-height: 480px;
                        ">
                    @else
                        <!-- Premium placeholder -->
                        <div style="height: 350px; display: flex; align-items: center; justify-content: center; background: #e2e8f0; color: #94a3b8;">
                            <i class="fa-regular fa-image" style="font-size: 3rem;"></i>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Content Column -->
            <div class="col-lg-6" style="flex: 1; min-width: 300px;">
                @if(!empty($settings['title']))
                    <h2 class="mb-4 font-display" style="font-size: 2.2rem; font-weight: 700; color: var(--brand-900);">
                        {{ $settings['title'] }}
                    </h2>
                @endif

                <div class="text-content text-muted mb-4" style="line-height: 1.8; font-size: 1.05rem; color: var(--ink);">
                    {!! $settings['content'] ?? '' !!}
                </div>

                @if(!empty($settings['button_url']) && !empty($settings['button_label']))
                    <a href="{{ $settings['button_url'] }}" class="button button-primary" style="display: inline-flex; align-items: center; gap: 0.5rem;">
                        {{ $settings['button_label'] }}
                        <i class="fa-solid fa-chevron-right" style="font-size: 0.8rem;"></i>
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
