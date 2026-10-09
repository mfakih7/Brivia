@props([
    'title' => null,
    'description' => null,
    'ogImage' => null,
    'preview' => false,
    'canonical' => null,
    'noindex' => false,
])
@php
    $site = \App\Support\PublicSite::settings();
    $legal = \App\Support\PublicSite::legalLinks();
    $fullTitle = $title ? $title.' — '.($site->business_name ?: 'BRIVIA') : ($site->default_meta_title ?: 'BRIVIA');
    $metaDescription = $description ?: $site->default_meta_description;
    $canonicalUrl = $canonical ?? url()->current();
    $indexable = ! $preview && ! $noindex && \App\Support\PublicSite::indexable();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $fullTitle }}</title>
    @if ($metaDescription)<meta name="description" content="{{ $metaDescription }}">@endif
    <meta name="robots" content="{{ $indexable ? 'index, follow' : 'noindex, nofollow' }}">
    @unless ($preview)<link rel="canonical" href="{{ $canonicalUrl }}">@endunless
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $site->business_name ?: 'BRIVIA' }}">
    <meta property="og:title" content="{{ $fullTitle }}">
    @if ($metaDescription)<meta property="og:description" content="{{ $metaDescription }}">@endif
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ $ogImage ? url($ogImage) : asset('images/brand/brivia-mark-192.png') }}">
    <meta name="twitter:card" content="{{ $ogImage ? 'summary_large_image' : 'summary' }}">
    <meta name="theme-color" content="#061528">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/brand/apple-touch-icon.png') }}">
    {{-- Preload the faces used above the fold to limit font-swap layout shift. --}}
    @foreach (['inter/files/inter-latin-400-normal', 'manrope/files/manrope-latin-700-normal', 'manrope/files/manrope-latin-800-normal'] as $font)
        <link rel="preload" as="font" type="font/woff2" href="{{ \Illuminate\Support\Facades\Vite::asset('node_modules/@fontsource/'.$font.'.woff2') }}" crossorigin>
    @endforeach
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if ($indexable)
        <script type="application/ld+json" nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">{!! json_encode(array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $site->business_name ?: 'BRIVIA',
            'url' => url('/'),
            'logo' => asset('images/brand/brivia-mark-192.png'),
            'email' => $site->email,
            'telephone' => $site->phone,
            'sameAs' => array_values($site->filledSocialLinks()) ?: null,
        ]), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
    @endif
    {{ $head ?? '' }}
</head>
<body class="public flex min-h-screen flex-col bg-surface text-ink">
    <a class="skip-link" href="#main">Skip to content</a>
    @if ($preview)
        <div class="bg-amber-100 px-4 py-2.5 text-center text-sm font-semibold text-amber-900" role="status">Private preview of saved content. This page is not public.</div>
    @endif
    <x-public.header />
    <main id="main" class="flex-1" tabindex="-1">
        {{ $slot }}
    </main>
    <x-public.footer :site="$site" :legal="$legal" />
</body>
</html>
