<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @php
            $isIndexable = request()->routeIs('home', 'privacy', 'terms', 'security-page');
            $seoTitle = config('seo.title');
            $seoDescription = config('seo.description');
            $seoImage = config('seo.image');
            $canonicalUrl = rtrim(config('app.url'), '/') . (request()->path() === '/' ? '/' : '/' . request()->path());
        @endphp

        <title inertia>{{ $seoTitle }}</title>
        <meta name="description" content="{{ $seoDescription }}">
        <meta name="robots" content="{{ $isIndexable ? 'index, follow' : 'noindex, nofollow' }}">
        @if ($isIndexable)
            <link rel="canonical" href="{{ $canonicalUrl }}">
        @endif

        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ config('app.name', 'Vouch') }}">
        <meta property="og:locale" content="en_US">
        <meta property="og:title" content="{{ $seoTitle }}">
        <meta property="og:description" content="{{ $seoDescription }}">
        <meta property="og:image" content="{{ $seoImage }}">
        <meta property="og:image:width" content="1731">
        <meta property="og:image:height" content="909">
        @if ($isIndexable)
            <meta property="og:url" content="{{ $canonicalUrl }}">
        @endif

        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $seoTitle }}">
        <meta name="twitter:description" content="{{ $seoDescription }}">
        <meta name="twitter:image" content="{{ $seoImage }}">
        @if (config('seo.twitter_site'))
            <meta name="twitter:site" content="{{ config('seo.twitter_site') }}">
        @endif

        <!-- Fonts -->
        <link rel="icon" type="image/png" sizes="32x32" href="{{ Vite::asset('resources/images/favicon-32.png') }}">
        <link rel="icon" type="image/png" sizes="64x64" href="{{ Vite::asset('resources/images/favicon-64.png') }}">
        <link rel="manifest" href="/manifest.json">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Dancing+Script:wght@600&family=Pacifico&family=Great+Vibes&family=Architects+Daughter&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @viteReactRefresh
        @vite(['resources/js/app.tsx', "resources/js/Pages/{$page['component']}.tsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased" data-theme="vouch">
        @inertia
    </body>
</html>
