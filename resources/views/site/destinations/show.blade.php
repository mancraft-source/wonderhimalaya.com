@php
    $crumbs = array_values(array_filter([
        ['label' => 'Destinations', 'url' => route('trips.index')],
        $destination->parent ? ['label' => $destination->parent->name, 'url' => url($destination->parent->publicPath())] : null,
        ['label' => $destination->name],
    ]));
    $shortName = str_replace(' Region', '', $destination->name);
    $intro = str(html_entity_decode($destination->intro ?: strip_tags((string) $destination->description), ENT_QUOTES | ENT_HTML5))->squish()->limit(230)->toString();
@endphp
<x-layouts.site :seo="$seo" :transparent="(bool) $heroMedia">
    <x-site.page-hero :media="$heroMedia" :eyebrow="$destination->tagline ?? ($destination->parent ? $destination->parent->name : 'Destination')" :title="e($destination->name)" :intro="$intro" :crumbs="$crumbs" size="lg">
        <div class="mt-8 flex flex-wrap gap-3">
            <span class="chip-light">{{ $trips->count() }} {{ str('journey')->plural($trips->count()) }}</span>
            @foreach ($destination->highlights ?? [] as $h)<span class="chip-light">{{ $h }}</span>@endforeach
        </div>
    </x-site.page-hero>

    @if ($destination->description && strlen(strip_tags($destination->description)) > 260)
        <x-site.about-section :eyebrow="'About '.$shortName" :html="$destination->description">
            <div class="rounded-[1.75rem] bg-juniper-900 p-7 text-stone-100 shadow-[0_24px_60px_-30px_rgba(15,29,25,0.6)]">
                @if ($trips->isNotEmpty())<p class="flex items-baseline gap-3"><span class="font-display text-5xl text-saffron-300">{{ $trips->count() }}</span><span class="text-sm text-stone-100/75">{{ str('journey')->plural($trips->count()) }} in {{ $shortName }}</span></p>@else<p class="font-display text-2xl text-stone-100">Journeys here are designed to order</p>@endif
                @if ($destination->highlights)
                    <p class="mt-6 text-xs font-semibold tracking-[0.18em] text-stone-100/60 uppercase">Highlights</p>
                    <ul class="mt-3 space-y-2">
                        @foreach ($destination->highlights as $h)
                            <li class="flex items-center gap-2.5"><svg viewBox="0 0 16 12" class="w-3.5 flex-none text-saffron-300" aria-hidden="true"><path d="M1 11 6 3l2.5 3.5L10.5 1 15 11Z" fill="currentColor" /></svg>{{ $h }}</li>
                        @endforeach
                    </ul>
                @endif
                <div class="mt-7 flex flex-col gap-3">
                    @if ($trips->isNotEmpty())
                        <a href="#journeys" class="btn-primary justify-center">See the journeys</a>
                        <a href="{{ route('tailor-made') }}" class="inline-flex items-center justify-center gap-2 text-sm font-semibold text-stone-100/85 hover:text-white">Or plan a tailor-made trip <x-heroicon-m-arrow-right class="size-4" /></a>
                    @else
                        <a href="{{ route('tailor-made') }}" class="btn-primary justify-center">Plan a tailor-made trip</a>
                    @endif
                </div>
            </div>
        </x-site.about-section>
    @endif

    @if ($children->isNotEmpty())
        <section class="container-page pt-16 lg:pt-20">
            <x-site.section-heading eyebrow="Regions" title="Where in {{ $destination->name }}?" />
            <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($children as $child)
                    <a href="{{ url($child->publicPath()) }}" class="group relative flex aspect-[4/5] flex-col justify-end overflow-hidden rounded-[1.75rem] p-6 text-white" data-reveal>
                        <x-site.picture :model="$child" collection="hero" sizes="(min-width: 1024px) 25vw, 50vw" class="absolute inset-0 h-full w-full transition duration-1000 group-hover:scale-105" />
                        <div class="scrim-bottom absolute inset-0"></div>
                        <span class="relative font-display text-3xl">{{ str_replace(' Region', '', $child->name) }}</span>
                        <span class="relative mt-1 text-sm text-white/80">{{ $child->trips_count }} {{ str('journey')->plural($child->trips_count) }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <section id="journeys" class="container-page scroll-mt-28 py-16 lg:py-24">
        <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
            <x-site.section-heading eyebrow="Journeys" title="Ways to experience {{ $shortName }}" />
            <a href="{{ route('trips.index', ['destination' => $destination->slug]) }}" class="link-arrow shrink-0" data-reveal>Filter these journeys <x-heroicon-m-arrow-right class="size-4" /></a>
        </div>
        <div class="mt-14 grid gap-x-8 gap-y-14 sm:grid-cols-2 xl:grid-cols-3">
            @forelse ($trips as $trip)
                <x-site.trip-card :trip="$trip" data-reveal />
            @empty
                <p class="col-span-full text-ink-soft">New journeys here are coming soon — <a class="link-arrow" href="{{ route('tailor-made') }}">ask us to design one</a>.</p>
            @endforelse
        </div>
    </section>

    @include('site.partials.tailor-made-band')
</x-layouts.site>
