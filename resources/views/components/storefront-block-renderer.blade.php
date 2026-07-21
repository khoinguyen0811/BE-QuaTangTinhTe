@props(['layout', 'page', 'preview' => false])

@foreach(($layout['blocks'] ?? []) as $block)
    @if($block['enabled'] ?? true)
        <div 
            class="{{ $preview ? 'builder-block-wrapper' : '' }}" 
            @if($preview) data-preview-block-id="{{ $block['id'] }}" @endif
        >
            @php
                try {
            @endphp
            @switch($block['type'])
                @case('rich_text')
                    @include('storefront.custom-pages.blocks.rich-text', ['settings' => $block['settings'] ?? []])
                    @break
                @case('faq')
                    @include('storefront.custom-pages.blocks.faq', ['settings' => $block['settings'] ?? []])
                    @break
                @case('contact_form')
                    @include('storefront.custom-pages.blocks.contact-form', ['settings' => $block['settings'] ?? [], 'preview' => $preview])
                    @break
                @case('feature_columns')
                    @include('storefront.custom-pages.blocks.feature-columns', ['settings' => $block['settings'] ?? []])
                    @break
                @case('image_text')
                    @include('storefront.custom-pages.blocks.image-text', ['settings' => $block['settings'] ?? []])
                    @break
                @case('cta')
                    @include('storefront.custom-pages.blocks.cta', ['settings' => $block['settings'] ?? []])
                    @break
                @case('spacer_divider')
                    @include('storefront.custom-pages.blocks.spacer-divider', ['settings' => $block['settings'] ?? []])
                    @break
            @endswitch
            @php
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("Error rendering block {$block['id']} of type {$block['type']}: " . $e->getMessage());
                }
            @endphp
        </div>
    @endif
@endforeach
