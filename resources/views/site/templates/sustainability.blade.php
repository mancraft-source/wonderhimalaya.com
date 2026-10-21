{{-- Sustainable tourism — designed template. Commitments from the client's existing page. --}}
@php
    $up = fn (string $p) => asset('storage/wp-content/uploads/'.$p);
    $c = \App\Support\Content::page($page);
    $pillars = collect($c['pillars'])->map(fn ($r) => [$r['icon'] ?? 'check-badge', $r['title'] ?? '', $r['text'] ?? ''])->all();
@endphp
<x-layouts.site :seo="$seo" transparent>
    <section class="relative isolate flex h-[72svh] min-h-[34rem] max-h-[48rem] flex-col justify-end overflow-hidden bg-juniper-950 text-white">
        <div class="absolute inset-0 -z-10" data-parallax><img src="{{ \App\Support\Content::image($c['hero_image'], \App\Support\BrandImage::url('rara-lake-trek-wonder-himalaya-scaled-1.png', $up('2026/01/annapurna-region-mardi-himal-wonder-himalaya.jpg'))) }}" alt="" class="h-full w-full animate-slow-zoom object-cover" fetchpriority="high"></div>
        <div class="scrim-hero absolute inset-0 -z-10"></div>
        <div class="container-page pt-36 pb-16 lg:pb-24">
            <x-site.breadcrumbs :items="[['label' => 'About', 'url' => url('/about-us')], ['label' => 'Sustainable tourism']]" light />
            <p class="eyebrow eyebrow-light mt-8">{{ $c['hero_eyebrow'] }}</p>
            <h1 class="mt-5 max-w-4xl font-display text-display-lg text-white">{{ $c['hero_title'] }} <em class="text-saffron-300">{{ $c['hero_title_em'] }}</em></h1>
        </div>
    </section>

    <section class="grain relative overflow-hidden py-24 lg:py-32">
        <div class="topo pointer-events-none absolute inset-0 opacity-60"></div>
        <div class="container-page relative grid gap-12 lg:grid-cols-12">
            <div class="lg:col-span-5" data-reveal>
                <p class="eyebrow">{{ $c['intro_eyebrow'] }}</p>
                <h2 class="mt-5 font-display text-display-md">{{ $c['intro_title'] }}</h2>
            </div>
            <div class="prose prose-lg prose-wh text-ink-soft lg:col-span-6 lg:col-start-7" data-reveal>{!! $c['intro_body'] !!}</div>
        </div>
    </section>

    <section class="container-page pb-24">
        <div class="grid gap-6 md:grid-cols-2">
            @foreach ($pillars as $i => [$icon, $title, $text])
                <div class="card-surface flex gap-6 p-8 lg:p-10" data-reveal>
                    <span class="flex size-14 shrink-0 items-center justify-center rounded-full bg-juniper-800 text-saffron-300"><x-dynamic-component :component="'heroicon-o-'.$icon" class="size-6" /></span>
                    <div>
                        <h3 class="font-display text-2xl">{{ $title }}</h3>
                        <p class="mt-3 text-ink-soft">{{ $text }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    @if (! empty($c['closing']))
        <section class="container-narrow pb-20 text-center"><p class="font-display text-display-sm leading-snug text-juniper-800" data-reveal>{{ $c['closing'] }}</p></section>
    @endif

    <section class="bg-juniper-800 py-20 text-stone-100">
        <div class="container-page grid items-center gap-10 lg:grid-cols-12">
            <div class="flex justify-center lg:col-span-2"><img src="{{ asset('images/brand/aff-keep.webp') }}" alt="Kathmandu Environmental Education Project (KEEP)" class="h-28 w-28 rounded-3xl bg-white object-contain p-3" loading="lazy"></div>
            <div class="lg:col-span-7">
                <h2 class="font-display text-3xl text-white">{{ $c['tips_title'] }}</h2>
                <ul class="mt-6 grid gap-3 text-stone-300 sm:grid-cols-2">
                    @foreach ($c['tips'] as $tip)
                        <li class="flex gap-3"><x-heroicon-m-check class="mt-1 size-4 shrink-0 text-saffron-300" /> {{ $tip }}</li>
                    @endforeach
                </ul>
            </div>
            <div class="lg:col-span-3 lg:text-right"><a href="{{ route('tailor-made') }}" class="btn-primary">{{ $c['cta_label'] }}</a></div>
        </div>
    </section>
</x-layouts.site>
