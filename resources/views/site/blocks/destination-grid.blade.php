@php
    $ids = $data['destination_ids'] ?? [];
    $items = \App\Models\Destination::with('media')->withCount('trips')->when($ids, fn ($q) => $q->whereIn('id', $ids), fn ($q) => $q->whereNotNull('parent_id'))->orderBy('sort')->get()->where('trips_count', '>', 0);
@endphp
@if ($items->isNotEmpty())
    <section @class(['py-10' => $narrow, 'container-page py-16 lg:py-24' => ! $narrow])>
        @if (! empty($data['heading']))<x-site.section-heading :title="e($data['heading'])" />@endif
        <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($items as $d)
                <a href="{{ url($d->publicPath()) }}" class="group relative flex aspect-[4/5] flex-col justify-end overflow-hidden rounded-[1.75rem] p-6 text-white" data-reveal>
                    <x-site.picture :model="$d" collection="hero" sizes="25vw" class="absolute inset-0 h-full w-full transition duration-1000 group-hover:scale-105" />
                    <div class="scrim-bottom absolute inset-0"></div>
                    <span class="relative font-display text-3xl">{{ $d->name }}</span>
                    <span class="relative text-sm text-white/80">{{ $d->trips_count }} journeys</span>
                </a>
            @endforeach
        </div>
    </section>
@endif
