@props([
    'title' => null,
    'description' => null,
    'canonical' => null,
    'image' => null,
    'type' => 'website',
])

@php
    $seo = config('marketing.seo');
    $appName = config('app.name', 'ExpenseHub');
    $pageTitle = $title ?? $seo['title'];
    $pageDescription = $description ?? $seo['description'];
    $canonicalUrl = $canonical ?? url()->current();
    $ogImage = $image ?? ($seo['og_image'] ? url($seo['og_image']) : null);
    $siteUrl = config('app.url');
@endphp

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>{{ $pageTitle }}</title>
<meta name="description" content="{{ $pageDescription }}">
@if(! empty($seo['keywords']))
    <meta name="keywords" content="{{ $seo['keywords'] }}">
@endif
@if(! empty($seo['author']))
    <meta name="author" content="{{ $seo['author'] }}">
@endif
<meta name="robots" content="index, follow">
<meta name="theme-color" content="#10b981">
<link rel="canonical" href="{{ $canonicalUrl }}">

{{-- Open Graph --}}
<meta property="og:type" content="{{ $type }}">
<meta property="og:site_name" content="{{ $appName }}">
<meta property="og:title" content="{{ $pageTitle }}">
<meta property="og:description" content="{{ $pageDescription }}">
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta property="og:locale" content="{{ str_replace('_', '-', app()->getLocale()) }}">
@if($ogImage)
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:alt" content="{{ $appName }} — expense management dashboard">
@endif

{{-- Twitter Card --}}
<meta name="twitter:card" content="{{ $ogImage ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $pageTitle }}">
<meta name="twitter:description" content="{{ $pageDescription }}">
@if($ogImage)
    <meta name="twitter:image" content="{{ $ogImage }}">
@endif
@if(! empty($seo['twitter_handle']))
    <meta name="twitter:site" content="{{ $seo['twitter_handle'] }}">
@endif

<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'Organization',
            'name' => $appName,
            'url' => $siteUrl,
            'description' => $pageDescription,
        ],
        [
            '@type' => 'SoftwareApplication',
            'name' => $appName,
            'applicationCategory' => 'BusinessApplication',
            'operatingSystem' => 'Web',
            'url' => $siteUrl,
            'description' => $pageDescription,
            'offers' => [
                '@type' => 'Offer',
                'price' => '0',
                'priceCurrency' => 'INR',
                'description' => '14-day free trial, then plans from Free tier',
            ],
        ],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
</script>
