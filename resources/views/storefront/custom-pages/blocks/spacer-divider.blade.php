<div class="spacer-divider-block" style="
    height: {{ $settings['height'] ?? '30px' }};
    display: flex;
    align-items: center;
    justify-content: center;
">
    @if($settings['show_line'] ?? false)
        <hr style="
            width: 100%;
            border: 0;
            border-top: 1px solid {{ $settings['line_color'] ?? '#e2e8f0' }};
            margin: 0;
        ">
    @endif
</div>
