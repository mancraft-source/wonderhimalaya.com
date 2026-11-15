{{--
    Public site layout.
    Props: seo (App\Support\Seo) | title + description (simple pages), transparent (header over hero).
--}}
@props(['seo' => null, 'title' => null, 'description' => null, 'transparent' => false])
@php
    $seo ??= \App\Support\Seo::make($title, $description);
    // Signed draft preview: never index it, and say clearly that it is not live.
    $previewing = \App\Support\Preview::active();
    if ($previewing) {
        $seo->noindex = true;
        $seo->nofollow = true;
    }
@endphp
<!DOCTYPE html>
<html lang="en" class="scroll-pt-24">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#1f3a33">
    <x-site.seo-head :seo="$seo" :company="$company" :social="$social" />
    <link rel="icon" href="{{ asset('favicon.png') }}" type="image/png" sizes="32x32">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles(['nonce' => \Illuminate\Support\Facades\Vite::cspNonce()])
    @stack('head')
    @if (! empty($analytics['gtm_id']) && app()->isProduction())
        <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','{{ $analytics['gtm_id'] }}');</script>
    @endif
</head>
<body @class(['min-h-dvh bg-stone-100 text-ink', 'pt-10 [&_[data-site-header]]:top-10' => $previewing])>
    @if ($previewing)
        @php $previewExpires = \App\Support\Preview::expiresAt(); @endphp
        <div role="status" data-preview-bar class="fixed inset-x-0 top-0 z-[60] flex h-10 items-center justify-center gap-3 border-b border-saffron-300/40 bg-juniper-800 px-4 text-sm text-stone-100">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-saffron-300 px-2.5 py-0.5 text-xs font-semibold tracking-wide text-juniper-900 uppercase">
                <x-heroicon-m-eye class="size-3.5" /> Preview
            </span>
            <span class="font-medium">Preview — not published yet</span>
            @if ($previewExpires)
                <span class="hidden text-stone-100/70 sm:inline">· Link valid until {{ $previewExpires->format('j M Y, H:i') }}</span>
            @endif
        </div>
    @endif
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
