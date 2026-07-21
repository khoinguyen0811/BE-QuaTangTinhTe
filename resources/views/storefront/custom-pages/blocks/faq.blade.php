<div class="faq-block py-5" style="background: var(--surface-soft, #f8fafc);">
    <div class="container" style="max-width: 850px;">
        @if(!empty($settings['title']) || !empty($settings['description']))
            <div class="text-center mb-5">
                @if(!empty($settings['title']))
                    <h2 class="font-display" style="font-size: 2rem; font-weight: 700; color: var(--brand-900); margin-bottom: 0.5rem;">
                        {{ $settings['title'] }}
                    </h2>
                @endif
                @if(!empty($settings['description']))
                    <p class="text-muted" style="font-size: 1rem;">
                        {{ $settings['description'] }}
                    </p>
                @endif
            </div>
        @endif

        <div class="faq-accordion">
            @foreach(($settings['items'] ?? []) as $index => $item)
                @if(!empty($item['question']))
                    <details class="faq-item mb-3" style="
                        background: #fff;
                        border: 1px solid var(--line, #e2e8f0);
                        border-radius: var(--radius, 8px);
                        overflow: hidden;
                        transition: border-color 0.2s ease;
                    " @if($index === 0 && ($settings['first_open'] ?? false)) open @endif>
                        <summary style="
                            padding: 1.25rem 1.5rem;
                            font-weight: 600;
                            color: var(--brand-900, #143944);
                            cursor: pointer;
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            list-style: none;
                            user-select: none;
                        ">
                            <span>{{ $item['question'] }}</span>
                            <i class="fa-solid fa-chevron-down faq-icon" style="
                                font-size: 0.85rem;
                                transition: transform 0.2s ease;
                                color: var(--brand-700);
                            "></i>
                        </summary>
                        <div style="
                            padding: 0 1.5rem 1.25rem;
                            color: var(--ink, #334155);
                            line-height: 1.7;
                            font-size: 0.98rem;
                            border-top: 1px solid var(--line, #f1f5f9);
                            background: #fafbfd;
                        ">
                            {!! $item['answer'] ?? '' !!}
                        </div>
                    </details>
                @endif
            @endforeach
        </div>
    </div>
</div>

<style>
    details.faq-item summary::-webkit-details-marker {
        display: none;
    }
    details.faq-item[open] {
        border-color: var(--brand-700, #3b92ab) !important;
        box-shadow: 0 4px 12px rgba(20, 57, 68, 0.03);
    }
    details.faq-item[open] summary .faq-icon {
        transform: rotate(180deg);
    }
</style>
