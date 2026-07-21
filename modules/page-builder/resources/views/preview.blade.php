<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview: {{ $page->title }}</title>
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Inline page CSS -->
    <style>
        {!! $css !!}
    </style>
</head>
<body>
    @if(trim($html) !== '')
        <!-- Render raw visual editor HTML draft -->
        {!! $html !!}
    @else
        <div style="display:flex; align-items:center; justify-content:center; min-height:100vh; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background:#f8f9fa; color:#6c757d;">
            <div style="text-align:center; max-width:500px; padding:40px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" viewBox="0 0 16 16" style="opacity:0.4; margin-bottom:16px;">
                    <path d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/>
                    <path d="M6.854 4.646a.5.5 0 0 1 0 .708L4.207 8l2.647 2.646a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 0 1 .708 0zm2.292 0a.5.5 0 0 0 0 .708L11.793 8l-2.647 2.646a.5.5 0 0 0 .708.708l3-3a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708 0z"/>
                </svg>
                <h2 style="font-size:1.4rem; font-weight:600; color:#495057; margin-bottom:12px;">Chưa có nội dung</h2>
                <p style="font-size:0.95rem; line-height:1.6;">
                    Trang "<strong>{{ $page->title }}</strong>" chưa được thiết kế nội dung.
                    <br>Hãy bấm <strong>"Thiết kế"</strong> để mở trình kéo thả GrapesJS và tạo nội dung cho trang.
                </p>
            </div>
        </div>
    @endif
</body>
</html>
