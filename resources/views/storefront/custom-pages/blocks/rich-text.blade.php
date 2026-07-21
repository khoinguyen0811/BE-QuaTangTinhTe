<div class="rich-text-block py-5">
    <div class="container" style="
        @if(($settings['width'] ?? 'normal') === 'normal') max-width: 800px; @elseif(($settings['width'] ?? 'normal') === 'wide') max-width: 1100px; @else max-width: 100%; @endif
        text-align: {{ $settings['align'] ?? 'left' }};
    ">
        @if(!empty($settings['title']))
            <h2 class="mb-4 text-dark font-display" style="font-size: 2rem; font-weight: 700; color: var(--brand-900);">
                {{ $settings['title'] }}
            </h2>
        @endif
        
        <div class="rich-text-content entry-content text-muted custom-page-rich-text" style="line-height: 1.8; font-size: 1.05rem; color: var(--ink);">
            {!! $settings['content'] ?? '' !!}
        </div>
    </div>
</div>
