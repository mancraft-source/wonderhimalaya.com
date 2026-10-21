{{-- Travel guide hub — designed template listing all guide pages. --}}
@php
    $up = fn (string $p) => asset('storage/wp-content/uploads/'.$p);
    $icons = ['nepal-visa' => 'identification', 'equipment-checklist' => 'briefcase', 'nepal-at-glance' => 'globe-asia-australia', 'flight-delayed-or-cancellation' => 'paper-airplane', 'best-trekking-seasons' => 'sun', 'trip-grade' => 'chart-bar', 'travel-insurance' => 'shield-check', 'insurance' => 'shield-check', 'recommended-medical-kit' => 'heart', 'recommended-flights' => 'globe-alt'];
    $c = \App\Support\Content::page($page);
    $guides = \App\Models\TeamMember::where('is_published', true)->where('is_guide', true)->with('media')->orderBy('sort')->limit(6)->get();
@endphp
<x-layouts.site :seo="$seo" transparent>
    <section class="relative isolate flex h-[64svh] min-h-[32rem] max-h-[44rem] flex-col justify-end overflow-hidden bg-juniper-950 text-white">
        <div class="absolute inset-0 -z-10" data-parallax><img src="{{ \App\Support\Content::image($c['hero_image'], \App\Support\BrandImage::url('langtang-lerung-rest-point-scaled.jpeg', $up('2026/03/IMG_0743-scaled.jpeg'))) }}" alt="" class="h-full w-full animate-slow-zoom object-cover" fetchpriority="high"></div>
        <div class="scrim-hero absolute inset-0 -z-10"></div>
        <div class="hero-copy container-page pt-36 pb-16 lg:pb-20">
            <x-site.breadcrumbs :items="[['label' => 'Travel guide']]" light />
            <p class="eyebrow eyebrow-light mt-8">{{ $c['hero_eyebrow'] }}</p>
            <h1 class="mt-5 max-w-4xl font-display text-display-lg text-white">{{ $c['hero_title'] }} <em class="text-saffron-300">{{ $c['hero_title_em'] }}</em></h1>
            <p class="hero-glass mt-6 max-w-2xl text-lg text-white/90">{{ $c['hero_subtitle'] }}</p>
        </div>
    </section>

    <section class="container-page py-20 lg:py-28">
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($siblings as $i => $g)
                <a href="{{ url($g->publicPath()) }}" class="card-surface group flex flex-col p-8 transition hover:-translate-y-1 hover:ring-juniper-500" data-reveal>
                    <div class="flex items-start justify-between">
                        <span class="flex size-12 items-center justify-center rounded-full bg-stone-200/80 text-juniper-800 transition group-hover:bg-juniper-800 group-hover:text-saffron-300"><x-dynamic-component :component="'heroicon-o-'.($icons[$g->slug] ?? 'book-open')" class="size-5" /></span>
                        <span class="data text-stone-400">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                    </div>
                    <h2 class="mt-8 font-display text-2xl text-juniper-800">{{ $g->title }}</h2>
                    <span class="mt-auto inline-flex items-center gap-2 pt-6 text-sm font-semibold text-terracotta-500">Read the guide <x-heroicon-m-arrow-right class="size-4 transition group-hover:translate-x-1" /></span>
                </a>
            @endforeach
        </div>
    </section>

    @if ($guides->isNotEmpty())
        <section class="bg-stone-200/50 py-24">
            <div class="container-page">
                <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                    <x-site.section-heading :eyebrow="$c['guides_eyebrow']" :title="e($c['guides_title'])" :intro="$c['guides_intro']" />
                    <a href="{{ route('team.index') }}" class="link-arrow shrink-0">All guides <x-heroicon-m-arrow-right class="size-4" /></a>
                </div>
                <div class="mt-12 grid grid-cols-2 gap-6 md:grid-cols-3 lg:grid-cols-6">
                    @foreach ($guides as $m)
                        <a href="{{ url($m->publicPath()) }}" class="group" data-reveal>
                            <div class="aspect-[4/5] overflow-hidden rounded-[1.5rem] bg-stone-200"><x-site.picture :model="$m" collection="photo" sizes="16vw" class="h-full w-full transition duration-700 group-hover:scale-[1.03]" /></div>
                            <p class="mt-4 font-display text-lg leading-tight text-juniper-800">{{ $m->name }}</p>
                            <p class="text-xs text-ink-soft">{{ $m->role }}</p>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @include('site.partials.tailor-made-band')
</x-layouts.site>
