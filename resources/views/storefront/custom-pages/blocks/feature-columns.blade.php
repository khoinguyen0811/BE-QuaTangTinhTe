<div class="features-block py-5">
    <div class="container">
        @if(!empty($settings['title']) || !empty($settings['description']))
            <div class="text-center mb-5">
                @if(!empty($settings['title']))
                    <h2 class="font-display" style="font-size: 2rem; font-weight: 700; color: var(--brand-900);">
                        {{ $settings['title'] }}
                    </h2>
                @endif
                @if(!empty($settings['description']))
                    <p class="text-muted mt-2" style="font-size: 1rem;">
                        {{ $settings['description'] }}
                    </p>
                @endif
            </div>
        @endif

        <div class="row g-4" style="
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
        ">
            @foreach(($settings['items'] ?? []) as $item)
                <div class="feature-card-item border rounded p-4 text-center" style="
                    background: var(--surface, #fff);
                    border: 1px solid var(--line, #e2e8f0);
                    border-radius: var(--radius, 8px);
                    transition: transform 0.2s ease, box-shadow 0.2s ease;
                ">
                    @if(!empty($item['image_url']))
                        <div class="mb-3 d-inline-block">
                            <img src="{{ $item['image_url'] }}" alt="{{ $item['title'] ?? '' }}" class="img-fluid rounded" style="max-height: 80px; object-fit: contain;">
                        </div>
                    @elseif(!empty($item['icon']))
                        <div class="icon-wrap mb-3 d-inline-flex align-items-center justify-content-center" style="
                            width: 60px;
                            height: 60px;
                            background: var(--surface-soft, #f8fafc);
                            color: var(--brand-700, #3b92ab);
                            border-radius: 50%;
                            font-size: 1.5rem;
                        ">
                            @if(str_starts_with($item['icon'], 'solar:'))
                                <iconify-icon icon="{{ $item['icon'] }}"></iconify-icon>
                            @else
                                <i class="{{ $item['icon'] }}"></i>
                            @endif
                        </div>
                    @endif

                    @if(!empty($item['title']))
                        <h3 class="mb-2 font-display" style="font-size: 1.25rem; font-weight: 600; color: var(--brand-900);">
                            {{ $item['title'] }}
                        </h3>
                    @endif

                    @if(!empty($item['description']))
                        <p class="text-muted mb-3" style="font-size: 0.95rem; line-height: 1.6;">
                            {{ $item['description'] }}
                        </p>
                    @endif

                    @if(!empty($item['link_url']) && !empty($item['link_label']))
                        <a href="{{ $item['link_url'] }}" class="fw-semibold text-decoration-none" style="color: var(--brand-700, #3b92ab); font-size: 0.9rem;">
                            {{ $item['link_label'] }} <i class="fa-solid fa-arrow-right ms-1" style="font-size: 0.8rem;"></i>
                        </a>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>

<style>
    .feature-card-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 24px rgba(20, 57, 68, 0.05);
        border-color: var(--brand-700, #3b92ab) !important;
    }
</style>
