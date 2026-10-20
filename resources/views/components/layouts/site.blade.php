{{--
    Public site layout.
    Props: seo (App\Support\Seo) | title + description (simple pages), transparent (header over hero).
--}}
@props(['seo' => null, 'title' => null, 'description' => null, 'transparent' => false])
@php
    $seo ??= \App\Support\Seo::make($title, $description);
@endphp
<!DOCTYPE html>
<html lang="en" class="scroll-pt-24">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#1f3a33">
    <x-site.seo-head :seo="$seo" :company="$company" :social="$social" />
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles(['nonce' => \Illuminate\Support\Facades\Vite::cspNonce()])
    @stack('head')
    @if (! empty($analytics['gtm_id']) && app()->isProduction())
        <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','{{ $analytics['gtm_id'] }}');</script>
    @endif
</head>
<body class="min-h-dvh bg-stone-100 text-ink">
    <a href="#main" class="sr-only z-[100] rounded-full bg-juniper-800 px-5 py-3 text-white focus:not-sr-only focus:fixed focus:top-4 focus:left-4">Skip to content</a>

    @include('partials.header', ['transparent' => $transparent])

    <main id="main" @class(['pt-20 lg:pt-24' => ! $transparent])>
        {{ $slot }}
    </main>

    @include('partials.footer')
    @include('partials.whatsapp')
    @livewireScripts(['nonce' => \Illuminate\Support\Facades\Vite::cspNonce()])
    @stack('scripts')
</body>
</html>
