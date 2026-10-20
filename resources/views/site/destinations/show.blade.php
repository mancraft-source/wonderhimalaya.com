@php
    $crumbs = array_values(array_filter([
        ['label' => 'Destinations', 'url' => route('trips.index')],
        $destination->parent ? ['label' => $destination->parent->name, 'url' => url($destination->parent->publicPath())] : null,
        ['label' => $destination->name],
    ]));
    $intro = $destination->intro ?: str(strip_tags((string) $destination->description))->limit(240)->toString();
@endphp
<x-layouts.site :seo="$seo" :transparent="(bool) $heroMedia">
    <x-site.page-hero :media="$heroMedia" :eyebrow="$destination->tagline ?? ($destination->parent ? $destination->parent->name : 'Destination')" :title="e($destination->name)" :intro="$intro" :crumbs="$crumbs" size="lg">
        <div class="mt-8 flex flex-wrap gap-3">
            <span class="chip-light">{{ $trips->count() }} {{ str('journey')->plural($trips->count()) }}</span>
            @foreach ($destination->highlights ?? [] as $h)<span class="chip-light">{{ $h }}</span>@endforeach
        </div>
    </x-site.page-hero>

    @if ($destination->description && strlen(strip_tags($destination->description)) > strlen($intro) + 20)
        <section class="container-page grid gap-12 py-20 lg:grid-cols-12">
            <div class="lg:col-span-4"><p class="eyebrow">About {{ str_replace(' Region', '', $destination->name) }}</p></div>
            <div class="prose prose-lg prose-wh lg:col-span-8">{!! $destination->description !!}</div>
        </section>
    @endif

    @if ($children->isNotEmpty())
        <section class="container-page pt-16">
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

    <section class="container-page py-20">
        <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
            <x-site.section-heading eyebrow="Journeys" title="Ways to experience {{ str_replace(' Region', '', $destination->name) }}" />
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
