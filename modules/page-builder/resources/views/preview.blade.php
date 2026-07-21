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
    <!-- Render raw visual editor HTML draft -->
    {!! $html !!}
</body>
</html>
