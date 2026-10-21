{{-- Editorial page header. With media → full-bleed image hero (use with transparent layout); without → paper header. --}}
@props(['title', 'eyebrow' => null, 'intro' => null, 'media' => null, 'model' => null, 'crumbs' => [], 'size' => 'md'])
@php $hasImage = $media || $model?->hasMedia('hero'); @endphp
@if ($hasImage)
    <section @class(['relative isolate flex flex-col justify-end overflow-hidden bg-juniper-950 text-white', 'h-[72svh] min-h-[34rem] max-h-[48rem]' => $size === 'lg', 'h-[58svh] min-h-[30rem] max-h-[40rem]' => $size !== 'lg'])>
        <div class="absolute inset-0 -z-10" data-parallax>
            <x-site.picture :media="$media" :model="$model" collection="hero" :alt="$title" sizes="100vw" eager class="h-full w-full animate-slow-zoom" />
        </div>
        <div class="scrim-hero absolute inset-0 -z-10"></div>
        <div class="container-page pt-36 pb-14 lg:pb-20">
            @if ($crumbs)<x-site.breadcrumbs :items="$crumbs" light />@endif
            @if ($eyebrow)<p class="eyebrow eyebrow-light mt-6">{{ $eyebrow }}</p>@endif
            <h1 class="mt-5 max-w-5xl font-display text-display-lg text-white">{!! $title !!}</h1>
            @if ($intro)<p class="mt-6 line-clamp-4 max-w-2xl text-lg text-white/85 sm:line-clamp-none">{{ $intro }}</p>@endif
            {{ $slot }}
        </div>
    </section>
@else
    <section class="grain relative overflow-hidden border-b border-stone-300/60">
        <div class="topo pointer-events-none absolute -top-24 -right-24 size-[50rem] opacity-80" aria-hidden="true"></div>
        <div class="container-page relative pt-14 pb-16 lg:pt-20 lg:pb-24">
            @if ($crumbs)<x-site.breadcrumbs :items="$crumbs" />@endif
            @if ($eyebrow)<p class="eyebrow mt-8">{{ $eyebrow }}</p>@endif
            <h1 class="mt-5 max-w-5xl font-display text-display-lg">{!! $title !!}</h1>
            @if ($intro)<p class="mt-6 max-w-2xl text-lg text-ink-soft">{{ $intro }}</p>@endif
            {{ $slot }}
        </div>
    </section>
@endif
