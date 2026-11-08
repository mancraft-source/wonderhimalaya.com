<x-layouts.site :seo="$seo" :transparent="(bool) $heroMedia">
    <x-site.page-hero :media="$heroMedia" eyebrow="Travel style" :title="e($activity->name)" :intro="$activity->tagline ?: str(html_entity_decode(strip_tags((string) $activity->description), ENT_QUOTES | ENT_HTML5))->squish()->limit(220)->toString()"
        :crumbs="[['label' => 'Journeys', 'url' => route('trips.index')], ['label' => $activity->name]]" size="lg">
        @if ($regions->isNotEmpty())
            <div class="mt-8 flex flex-wrap gap-2">
                @foreach ($regions as $r)<a href="{{ url($r->publicPath()) }}" class="chip-light hover:bg-white/25">{{ $r->name }}</a>@endforeach
            </div>
        @endif
    </x-site.page-hero>

    @if ($activity->description && strlen(strip_tags($activity->description)) > 240)
        <x-site.about-section :eyebrow="'About '.strtolower($activity->name)" :html="$activity->description">
            <div class="rounded-[1.75rem] bg-juniper-900 p-7 text-stone-100 shadow-[0_24px_60px_-30px_rgba(15,29,25,0.6)]">
                @if ($trips->isNotEmpty())<p class="flex items-baseline gap-3"><span class="font-display text-5xl text-saffron-300">{{ $trips->count() }}</span><span class="text-sm text-stone-100/75">{{ str('journey')->plural($trips->count()) }} to choose from</span></p>@else<p class="font-display text-2xl text-stone-100">Journeys here are designed to order</p>@endif
                @if ($regions->isNotEmpty())
                    <p class="mt-6 text-xs font-semibold tracking-[0.18em] text-stone-100/60 uppercase">Where</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($regions as $r)<a href="{{ url($r->publicPath()) }}" class="rounded-full bg-white/10 px-3 py-1.5 text-sm hover:bg-white/20">{{ str_replace(' Region', '', $r->name) }}</a>@endforeach
                    </div>
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

    <section id="journeys" class="container-page scroll-mt-28 py-16 lg:py-24">
        <x-site.section-heading eyebrow="Journeys" title="{{ $trips->count() }} {{ str('journey')->plural($trips->count()) }} for {{ strtolower($activity->name) }}" />
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
