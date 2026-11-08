@props(['seo', 'company' => [], 'social' => []])
@php
    // Paginated archives are self-canonical (page 2 is not a duplicate of page 1).
    $page = (int) request()->query('page', 1);
    $canonical = $seo->canonical ?: url()->current().($page > 1 ? '?page='.$page : '');
    $settings = \App\Support\Seo\SeoSettings::all();
    $image = $seo->image ?: \App\Support\Seo\SeoSettings::defaultImageUrl() ?: asset('images/brand/logo-badge.png');
    $org = [
        '@context' => 'https://schema.org',
        '@type' => $settings['organization_type'] ?: 'TravelAgency',
        '@id' => url('/').'#organization',
        'name' => \App\Support\Seo\SeoSettings::siteName(),
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
<meta name="robots" content="{{ $seo->robots() }}">
@foreach (['google' => 'google-site-verification', 'bing' => 'msvalidate.01', 'yandex' => 'yandex-verification', 'pinterest' => 'p:domain_verify', 'facebook' => 'facebook-domain-verification'] as $engine => $metaName)
@if (filled($settings['verification'][$engine] ?? null))<meta name="{{ $metaName }}" content="{{ $settings['verification'][$engine] }}">@endif
@endforeach
<meta property="og:locale" content="en_US">
<meta property="og:site_name" content="{{ \App\Support\Seo\SeoSettings::siteName() }}">
<meta property="og:type" content="{{ $seo->type }}">
<meta property="og:title" content="{{ $seo->ogTitle ?? $seo->fullTitle() }}">
@if ($seo->ogDescription ?? $seo->description)<meta property="og:description" content="{{ $seo->ogDescription ?? $seo->description }}">@endif
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:image" content="{{ $image }}">
<meta name="twitter:card" content="summary_large_image">
@if (filled($settings['twitter_handle']))<meta name="twitter:site" content="{{ $settings['twitter_handle'] }}">@endif
<script type="application/ld+json">{!! json_encode($org, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@if (request()->routeIs('home'))
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'WebSite', '@id' => url('/').'#website', 'url' => url('/'), 'name' => \App\Support\Seo\SeoSettings::siteName(), 'publisher' => ['@id' => url('/').'#organization'], 'potentialAction' => ['@type' => 'SearchAction', 'target' => ['@type' => 'EntryPoint', 'urlTemplate' => route('search').'?q={search_term_string}'], 'query-input' => 'required name=search_term_string']], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endif
@foreach ($seo->schema as $schema)
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org'] + $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endforeach
