@php
    $limit = (int) ($data['limit'] ?? 6) ?: 6;
    $q = \App\Models\Trip::published()->with(['destination', 'difficulty', 'media']);
    match ($data['source'] ?? 'featured') {
        'popular' => $q->where('is_popular', true),
        'top_selling' => $q->where('is_top_selling', true),
        'fixed_departure' => $q->where('is_fixed_departure', true),
        'destination' => $q->whereHas('destinations', fn ($d) => $d->whereKey($data['destination_id'] ?? 0)),
        'activity' => $q->whereHas('activities', fn ($a) => $a->whereKey($data['activity_id'] ?? 0)),
        'manual' => $q->whereIn('id', $data['trip_ids'] ?? [])->orderByRaw('1'),
        default => $q->where('is_featured', true),
    };
    $trips = $q->orderBy('sort')->limit($limit)->get();
    if (($data['source'] ?? null) === 'manual') { $ids = array_map('intval', $data['trip_ids'] ?? []); $trips = $trips->sortBy(fn ($t) => array_search($t->id, $ids))->values(); }
@endphp
@if ($trips->isNotEmpty())
    <section @class(['py-10' => $narrow, 'container-page py-16 lg:py-24' => ! $narrow])>
        @if (! empty($data['heading']))<x-site.section-heading :eyebrow="$data['eyebrow'] ?? null" :title="e($data['heading'])" :intro="$data['intro'] ?? null" />@endif
        <div @class(['mt-12 grid gap-x-8 gap-y-14 sm:grid-cols-2', 'xl:grid-cols-3' => ! $narrow])>
            @foreach ($trips as $trip)<x-site.trip-card :trip="$trip" data-reveal />@endforeach
        </div>
    </section>
@endif
