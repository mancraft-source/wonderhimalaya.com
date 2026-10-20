@props(['seo', 'company' => [], 'social' => []])
@php
    $canonical = $seo->canonical ?: url()->current();
    $image = $seo->image ?: asset('images/brand/logo-badge.png');
    $org = [
        '@context' => 'https://schema.org',
        '@type' => 'TravelAgency',
        '@id' => url('/').'#organization',
        'name' => config('app.name'),
        'url' => url('/'),
        'logo' => asset('images/brand/logo-badge.png'),
        'image' => asset('images/brand/logo-badge.png'),
        'telephone' => data_get($company ?? [], 'phone', '+977-01-5928066'),
        'email' => data_get($company ?? [], 'email', 'info@wonderhimalaya.com'),
        'address' => ['@type' => 'PostalAddress', 'streetAddress' => 'Thamel', 'addressLocality' => 'Kathmandu', 'addressCountry' => 'NP'],
        'sameAs' => array_values(array_filter($social ?? [])),
    ];
@endphp
<title>{{ $seo->fullTitle() }}</title>
@if ($seo->description)<meta name="description" content="{{ $seo->description }}">@endif
<link rel="canonical" href="{{ $canonical }}">
@if ($seo->noindex || ! app()->isProduction())<meta name="robots" content="noindex, nofollow">@else<meta name="robots" content="index, follow, max-image-preview:large">@endif
<meta property="og:site_name" content="{{ config('app.name') }}">
<meta property="og:type" content="{{ $seo->type }}">
<meta property="og:title" content="{{ $seo->title ?? config('app.name') }}">
@if ($seo->description)<meta property="og:description" content="{{ $seo->description }}">@endif
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:image" content="{{ $image }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:site" content="@WonderH90546">
<script type="application/ld+json">{!! json_encode($org, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@foreach ($seo->schema as $schema)
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org'] + $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endforeach
