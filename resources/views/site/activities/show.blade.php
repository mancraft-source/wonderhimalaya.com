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
        <section class="container-page grid gap-12 py-20 lg:grid-cols-12">
            <div class="lg:col-span-4"><p class="eyebrow">About {{ strtolower($activity->name) }}</p></div>
            <div class="prose prose-lg prose-wh lg:col-span-8">{!! $activity->description !!}</div>
        </section>
    @endif

    <section class="container-page py-20">
        <p class="text-sm text-ink-soft"><span class="font-display text-2xl text-juniper-800">{{ $trips->count() }}</span> {{ str('journey')->plural($trips->count()) }}</p>
        <div class="mt-10 grid gap-x-8 gap-y-14 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($trips as $trip)
                <x-site.trip-card :trip="$trip" data-reveal />
            @endforeach
        </div>
    </section>

    @include('site.partials.tailor-made-band')
</x-layouts.site>
