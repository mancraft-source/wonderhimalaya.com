@php
    $profile = \App\Support\AltitudeProfile::make($altitudes);
    $gallery = $trip->getMedia('gallery');
    $map = $trip->getFirstMedia('map');
    $price = $trip->displayPrice();
    $groupDepartures = $trip->upcomingDepartures->filter->isBookable();
    $joinsDaily = $trip->is_fixed_departure && $trip->departsDaily() && $groupDepartures->isEmpty();
    $hasGroup = $groupDepartures->isNotEmpty() || $joinsDaily;
    $groupPrice = $pricing->first()['from'] ?? $price;
    $facts = $trip->facts ?? [];
    $country = $trip->destination?->parent?->name ?? $trip->destination?->name;
    $start = $trip->start_point ?: ($facts['Arrival City'] ?? null);
    $end = $trip->end_point ?: ($facts['Departure City'] ?? null);
    $factRows = array_values(array_filter([
        ['Country', $country, 'globe-asia-australia'],
        ['Duration', $trip->duration_days ? $trip->duration_days.' days'.($trip->duration_nights ? ' / '.$trip->duration_nights.' nights' : '') : null, 'clock'],
        ['Difficulty', $trip->difficulty?->name, 'bolt'],
        ['Activity', $trip->activities->pluck('name')->implode(', ') ?: null, 'flag'],
        ['Max altitude', $trip->max_altitude_m ? number_format($trip->max_altitude_m).' m / '.number_format($trip->max_altitude_m * 3.28084).' ft' : null, 'arrow-trending-up'],
        ['Best season', $trip->best_season ?: ($facts['Best Season'] ?? null), 'sun'],
        ['Accommodation', $trip->accommodation ?: ($facts['Accommodation'] ?? null), 'home-modern'],
        ['Meals', $trip->meals ?: ($facts['Meals'] ?? null), 'cake'],
        ['Group size', $trip->group_min || $trip->group_max ? ($trip->group_min ?: 1).'–'.($trip->group_max ?: 12).' people' : null, 'user-group'],
        ['Guide language', $facts['Language'] ?? null, 'language'],
        ['Start / end', $start ? $start.($end && $end !== $start ? ' → '.$end : ($end ? ' (return)' : '')) : null, 'map-pin'],
    ], fn ($f) => filled($f[1])));
    $crumbs = array_values(array_filter([
        ['label' => 'Journeys', 'url' => route('trips.index')],
        $trip->destination?->parent ? ['label' => $trip->destination->parent->name, 'url' => url($trip->destination->parent->publicPath())] : null,
        $trip->destination ? ['label' => $trip->destination->name, 'url' => url($trip->destination->publicPath())] : null,
        ['label' => $trip->title],
    ]));
    $videos = collect($trip->videos ?? [])->map(function ($v) {
        $url = (string) (is_array($v) ? ($v['url'] ?? reset($v)) : $v);
        if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/|live/))([\w-]{11})~', $url, $m)) {
            return ['embed' => 'https://www.youtube-nocookie.com/embed/'.$m[1].'?autoplay=1&rel=0', 'thumb' => 'https://i.ytimg.com/vi/'.$m[1].'/hqdefault.jpg'];
        }
        if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $m)) {
            return ['embed' => 'https://player.vimeo.com/video/'.$m[1].'?autoplay=1', 'thumb' => null];
        }

        return null;
    })->filter()->values();
    $wa = preg_replace('/\D/', '', (string) \App\Models\Setting::get('company.whatsapp'));
    $phone = (string) \App\Models\Setting::get('company.phone');
    $email = (string) \App\Models\Setting::get('company.email');
    $sections = array_filter([
        'overview' => 'Overview',
        'itinerary' => $trip->days->isNotEmpty() ? 'Itinerary' : null,
        'departures' => 'Dates & prices',
        'includes' => ($includes || $excludes) ? 'Includes' : null,
        'map' => ($profile || $map) ? 'Map & elevation' : null,
        'packing' => $packing ? 'What to pack' : null,
        'video' => $videos->isNotEmpty() ? 'Video' : null,
        'faqs' => $trip->faqs->isNotEmpty() ? 'FAQs' : null,
        'reviews' => 'Reviews',
        'enquire' => 'Book / enquire',
    ]);
@endphp
<x-layouts.site :seo="$seo" transparent>
    {{-- ============================== HERO ============================== --}}
    <section class="relative isolate flex h-[86svh] min-h-[38rem] max-h-[58rem] flex-col justify-end overflow-hidden bg-juniper-950 text-white">
        <div class="absolute inset-0 -z-10" data-parallax>
            <x-site.picture :model="$trip" collection="hero" sizes="100vw" eager class="h-full w-full animate-slow-zoom" />
        </div>
        <div class="scrim-hero absolute inset-0 -z-10"></div>
        <div class="hero-copy container-page pt-36 pb-12 lg:pb-16">
            <x-site.breadcrumbs :items="$crumbs" light />
            <div class="mt-6 flex flex-wrap gap-2">
                @if ($trip->is_fixed_departure)<span class="chip-light">Fixed group departure</span>@endif
                @if ($trip->is_top_selling)<span class="chip-light !bg-saffron-500 !text-ink">Top seller</span>@endif
                @foreach ($trip->activities as $a)<a href="{{ url($a->publicPath()) }}" class="chip-light hover:bg-white/25">{{ $a->name }}</a>@endforeach
            </div>
            <h1 class="mt-5 max-w-5xl font-display text-display-lg text-white">{{ $trip->title }}@if ($trip->duration_days) <span class="font-light text-white/70">– {{ $trip->durationLabel() }}</span>@endif</h1>

            {{-- Social proof --}}
            <div class="mt-5 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-white/85">
                <a href="{{ route('reviews') }}" class="flex items-center gap-2 hover:text-white">
                    <span class="flex gap-0.5 text-saffron-300">@for ($i = 0; $i < 5; $i++)<x-heroicon-s-star class="size-4" />@endfor</span>
                    {{ $reviews['google_label'] ?? 'Excellent' }} · {{ $reviews['google_count'] ?? 78 }} Google reviews
                </a>
                <span class="flex items-center gap-2"><x-heroicon-s-trophy class="size-4 text-saffron-300" /> Tripadvisor {{ $reviews['tripadvisor_award'] ?? "Travellers' Choice" }}</span>
            </div>

            <div class="mt-8 flex flex-wrap items-center gap-4">
                <a href="#departures" class="btn-primary">{{ $hasGroup ? 'Check dates & book' : 'Request a quote' }} <x-heroicon-m-arrow-down class="size-4" /></a>
                @if ($gallery->isNotEmpty())
                    <button type="button" class="btn-ghost-light" x-data x-on:click="$dispatch('open-gallery', { i: 0 })"><x-heroicon-o-photo class="size-4" /> See all {{ $gallery->count() + 1 }} photos</button>
                @endif
                <a href="{{ route('trips.pdf', $trip->slug) }}" class="btn-ghost-light"><x-heroicon-o-arrow-down-tray class="size-4" /> Itinerary PDF</a>
            </div>
        </div>
    </section>

    {{-- ============================== TRIP FACTS ============================== --}}
    @if ($factRows)
        <section class="border-b border-stone-300/60 bg-stone-50">
            <dl class="container-page grid grid-cols-2 gap-x-6 gap-y-6 py-8 sm:grid-cols-3 lg:grid-cols-6" data-stagger>
                @foreach (array_slice($factRows, 0, 12) as [$label, $value, $icon])
                    <div class="flex items-start gap-3">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-stone-200/70 text-terracotta-500"><x-dynamic-component :component="'heroicon-o-'.$icon" class="size-5" /></span>
                        <div class="min-w-0">
                            <dt class="text-[0.66rem] font-semibold uppercase tracking-[0.18em] text-stone-500">{{ $label }}</dt>
                            <dd class="mt-0.5 text-[0.95rem] leading-snug font-medium text-juniper-800">{{ $value }}</dd>
                        </div>
                    </div>
                @endforeach
            </dl>
        </section>
    @endif

    {{-- ============================== SUB NAV ============================== --}}
    <nav class="sticky top-20 z-30 border-b border-stone-300/60 bg-stone-100/95 backdrop-blur-xl lg:top-24" aria-label="On this page"
         x-data="{ active: 'overview' }"
         x-init="const els = [...document.querySelectorAll('[data-section]')]; const o = new IntersectionObserver(es => es.forEach(e => e.isIntersecting && (active = e.target.id)), { rootMargin: '-40% 0px -55% 0px' }); els.forEach(el => o.observe(el))">
        <div class="container-page no-scrollbar flex gap-7 overflow-x-auto">
            @foreach ($sections as $id => $label)
                <a href="#{{ $id }}" class="relative shrink-0 py-4 text-sm font-semibold whitespace-nowrap text-stone-500 transition hover:text-juniper-800"
                   x-bind:class="active === '{{ $id }}' && '!text-juniper-800'">
                    {{ $label }}
                    <span class="absolute inset-x-0 -bottom-px h-0.5 origin-left scale-x-0 bg-terracotta-500 transition-transform" x-bind:class="active === '{{ $id }}' && '!scale-x-100'"></span>
                </a>
            @endforeach
        </div>
    </nav>

    <div class="container-page grid gap-16 py-16 lg:grid-cols-12 lg:py-20">
        <div class="min-w-0 space-y-24 lg:col-span-8">
            {{-- ============================== OVERVIEW & HIGHLIGHTS ============================== --}}
            <section id="overview" data-section class="scroll-mt-44">
                @if ($trip->highlights)
                    <div class="rounded-[2rem] bg-juniper-800 p-8 text-stone-100 sm:p-10">
                        <p class="eyebrow eyebrow-light">Trip highlights</p>
                        <ul class="mt-6 grid gap-x-10 gap-y-4 sm:grid-cols-2">
                            @foreach ($trip->highlights as $highlight)
                                <li class="flex gap-3">
                                    <svg viewBox="0 0 20 20" class="mt-1 size-4 shrink-0 text-saffron-300" aria-hidden="true"><path fill="currentColor" d="M10 1 12.2 7.8 19 10l-6.8 2.2L10 19l-2.2-6.8L1 10l6.8-2.2Z"/></svg>
                                    <span class="text-stone-200">{{ is_array($highlight) ? ($highlight['text'] ?? $highlight['highlight'] ?? implode(' ', $highlight)) : $highlight }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($trip->overview)
                    <div @class(['mt-14' => $trip->highlights])>
                        <p class="eyebrow">Overview</p>
                        <h2 class="mt-4 font-display text-display-sm">About this journey</h2>
                        <div class="prose prose-lg prose-wh mt-6" x-data="{ more: false }">
                            <div class="relative overflow-hidden transition-[max-height] duration-700" x-bind:style="more ? '' : 'max-height: 26rem'">
                                {!! $trip->overview !!}
                                <div class="pointer-events-none absolute inset-x-0 bottom-0 h-40 bg-gradient-to-t from-stone-100" x-show="!more"></div>
                            </div>
                            <button type="button" class="link-arrow not-prose mt-6" x-on:click="more = !more" x-text="more ? 'Show less' : 'Read the full overview'"></button>
                        </div>
                    </div>
                @endif
            </section>

            {{-- ============================== ITINERARY ============================== --}}
            @if ($trip->days->isNotEmpty())
                <section id="itinerary" data-section class="scroll-mt-44" x-data="{ view: 'glance', open: [1], all: false }">
                    <div class="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <p class="eyebrow">Day by day</p>
                            <h2 class="mt-4 font-display text-display-sm">Itinerary</h2>
                        </div>
                        <div class="inline-flex rounded-full bg-stone-200/70 p-1 text-sm font-semibold" role="tablist">
                            <button type="button" role="tab" class="rounded-full px-4 py-2 transition" x-bind:class="view === 'glance' ? 'bg-juniper-800 text-stone-100' : 'text-juniper-800'" x-on:click="view = 'glance'">At a glance</button>
                            <button type="button" role="tab" class="rounded-full px-4 py-2 transition" x-bind:class="view === 'detail' ? 'bg-juniper-800 text-stone-100' : 'text-juniper-800'" x-on:click="view = 'detail'">Detailed</button>
                        </div>
                    </div>

                    {{-- Short itinerary --}}
                    <ol x-show="view === 'glance'" class="mt-8 overflow-hidden rounded-[1.75rem] bg-stone-50 ring-1 ring-stone-300/60">
                        @foreach ($trip->days as $day)
                            <li class="flex items-start gap-5 border-b border-stone-300/50 px-6 py-4 last:border-b-0">
                                <span class="data mt-0.5 w-14 shrink-0 text-terracotta-500">Day {{ str_pad($day->day_number, 2, '0', STR_PAD_LEFT) }}</span>
                                <span class="flex-1 text-[0.95rem] text-ink">{{ strip_tags($day->title) }}</span>
                                @if ($day->altitude_m)<span class="data hidden shrink-0 text-stone-500 sm:block">{{ number_format($day->altitude_m) }} m</span>@endif
                            </li>
                        @endforeach
                    </ol>

                    {{-- Detailed itinerary --}}
                    <div x-show="view === 'detail'" x-cloak class="mt-8">
                        <div class="mb-4 flex justify-end">
                            <button type="button" class="text-sm font-semibold text-juniper-800 underline underline-offset-4"
                                    x-on:click="all = !all; open = all ? @js($trip->days->pluck('day_number')) : []" x-text="all ? 'Collapse all' : 'Expand all'"></button>
                        </div>
                        <ol class="relative">
                            <span class="absolute top-4 bottom-4 left-[1.18rem] w-px border-l border-dashed border-terracotta-500/50" aria-hidden="true"></span>
                            @foreach ($trip->days as $day)
                                <li class="relative pl-14">
                                    <span class="absolute top-4 left-0 flex size-10 items-center justify-center rounded-full bg-stone-100 font-display text-lg text-juniper-800 ring-1 ring-terracotta-500/40"
                                          x-bind:class="open.includes({{ $day->day_number }}) && '!bg-juniper-800 !text-stone-100 !ring-juniper-800'">{{ $day->day_number }}</span>
                                    <div class="border-b border-stone-300/70">
                                        <button type="button" class="flex w-full items-start justify-between gap-6 py-5 text-left"
                                                x-on:click="open.includes({{ $day->day_number }}) ? open = open.filter(d => d !== {{ $day->day_number }}) : open.push({{ $day->day_number }})"
                                                x-bind:aria-expanded="open.includes({{ $day->day_number }})">
                                            <span>
                                                <span class="block text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">Day {{ $day->day_number }}</span>
                                                <span class="mt-1 block font-display text-xl leading-snug text-juniper-800">{{ strip_tags($day->title) }}</span>
                                            </span>
                                            <x-heroicon-m-chevron-down class="mt-6 size-5 shrink-0 text-stone-400 transition" x-bind:class="open.includes({{ $day->day_number }}) && 'rotate-180'" />
                                        </button>
                                        <div x-show="open.includes({{ $day->day_number }})" x-collapse @unless ($loop->first) x-cloak @endunless>
                                            <div class="pb-8">
                                                <dl class="grid gap-3 rounded-2xl bg-stone-200/50 p-4 text-sm sm:grid-cols-2">
                                                    @if ($day->altitude_m)<div class="flex gap-2"><x-heroicon-o-arrow-trending-up class="size-4 shrink-0 text-terracotta-500" /><dt class="text-stone-500">Max. altitude</dt><dd class="data ml-auto text-juniper-800 sm:ml-0">{{ number_format($day->altitude_m) }} m / {{ number_format($day->altitude_m * 3.28084) }} ft</dd></div>@endif
                                                    @if ($day->distance_km)<div class="flex gap-2"><x-heroicon-o-map class="size-4 shrink-0 text-terracotta-500" /><dt class="text-stone-500">Distance</dt><dd class="data ml-auto text-juniper-800 sm:ml-0">{{ rtrim(rtrim((string) $day->distance_km, '0'), '.') }} km</dd></div>@endif
                                                    @if ($day->durationLabel())<div class="flex gap-2"><x-heroicon-o-clock class="size-4 shrink-0 text-terracotta-500" /><dt class="text-stone-500">Duration</dt><dd class="ml-auto text-juniper-800 sm:ml-0">{{ $day->durationLabel() }}</dd></div>@endif
                                                    @if ($day->accommodation)<div class="flex gap-2"><x-heroicon-o-home-modern class="size-4 shrink-0 text-terracotta-500" /><dt class="text-stone-500">Overnight</dt><dd class="ml-auto text-juniper-800 sm:ml-0">{{ $day->accommodation }}</dd></div>@endif
                                                    @if ($day->meals)<div class="flex gap-2"><x-heroicon-o-cake class="size-4 shrink-0 text-terracotta-500" /><dt class="text-stone-500">Meals</dt><dd class="ml-auto text-juniper-800 sm:ml-0">{{ collect($day->meals)->map(fn ($m) => ucfirst($m))->implode(', ') }}</dd></div>@endif
                                                </dl>
                                                @if ($day->description)
                                                    <div class="prose prose-wh mt-5">{!! $day->description !!}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                    <p class="mt-6 rounded-2xl border border-saffron-500/40 bg-saffron-300/20 p-4 text-sm text-ink-soft"><strong class="text-juniper-800">Note:</strong> {{ $trip->itinerary_note ?: $tripContent['itinerary_note'] }}</p>
                </section>
            @endif

            {{-- ============================== DEPARTURES & AVAILABILITY ============================== --}}
            <section id="departures" data-section class="scroll-mt-44" x-data="{ tab: '{{ $hasGroup ? 'group' : 'private' }}' }">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="eyebrow">Departures & availability</p>
                        <h2 class="mt-4 font-display text-display-sm">Dates & prices</h2>
                    </div>
                    @if ($hasGroup)
                        <div class="inline-flex rounded-full bg-stone-200/70 p-1 text-sm font-semibold" role="tablist">
                            <button type="button" role="tab" class="rounded-full px-5 py-2.5 transition" x-bind:class="tab === 'group' ? 'bg-juniper-800 text-stone-100' : 'text-juniper-800'" x-on:click="tab = 'group'">Group departures</button>
                            <button type="button" role="tab" class="rounded-full px-5 py-2.5 transition" x-bind:class="tab === 'private' ? 'bg-juniper-800 text-stone-100' : 'text-juniper-800'" x-on:click="tab = 'private'">Private trip</button>
                        </div>
                    @endif
                </div>

                <div x-show="tab === 'group'" @unless ($hasGroup) x-cloak @endunless class="mt-8">
                    @if ($joinsDaily)
                        <div class="overflow-hidden rounded-[1.75rem] bg-juniper-800 text-stone-100" x-data="{ date: '{{ $trip->earliestStartDate()->toDateString() }}' }">
                            <div class="grid gap-8 p-7 sm:p-9 md:grid-cols-[1fr_auto] md:items-end">
                                <div>
                                    <p class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.18em] text-saffron-300"><x-heroicon-o-calendar-days class="size-4" /> Departs every day</p>
                                    <p class="mt-3 font-display text-3xl text-white">Join a small group on the date that suits you</p>
                                    <p class="mt-3 max-w-xl text-stone-300">Choose any start date from {{ $trip->earliestStartDate()->format('j M Y') }} — bookings close {{ $trip->departure_cutoff_days }} days before departure.{{ $trip->departure_capacity ? ' Groups of up to '.$trip->departure_capacity.' travellers.' : '' }} No payment is taken now.</p>
                                </div>
                                @if ($groupPrice)
                                    <p class="text-right"><span class="block text-sm text-stone-400">per person from</span><span class="font-display text-4xl text-white">US${{ number_format($groupPrice) }}</span></p>
                                @endif
                            </div>
                            <div class="flex flex-col gap-3 border-t border-white/10 bg-juniper-900/50 p-5 sm:flex-row sm:items-center sm:px-9">
                                <label for="daily-start" class="text-sm text-stone-300">Start date</label>
                                <input id="daily-start" type="date" x-model="date" min="{{ $trip->earliestStartDate()->toDateString() }}" class="rounded-xl border-0 bg-white px-4 py-2.5 text-ink focus:ring-2 focus:ring-saffron-300">
                                <span class="text-sm text-stone-300" x-show="date" x-text="'Returns ' + new Date(new Date(date).getTime() + {{ max(0, ($trip->duration_days ?? 1) - 1) }} * 864e5).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })"></span>
                                <button type="button" class="btn-primary sm:ml-auto" x-on:click="$dispatch('pick-date', { date })">Join this date</button>
                            </div>
                        </div>
                    @elseif ($groupDepartures->isNotEmpty())
                    <p class="mb-5 text-ink-soft">Join a small group on a fixed date. Choose a departure to request your place — no payment is taken now.</p>
                    @endif
                    @if ($joinsDaily)
                    @elseif ($groupDepartures->isEmpty())
                        <div class="rounded-[1.75rem] bg-stone-200/50 p-8">
                            <p class="font-display text-2xl text-juniper-800">No fixed dates published yet.</p>
                            <p class="mt-2 text-ink-soft">We run this journey privately on any date you choose — and can often match you with other travellers.</p>
                            <button type="button" class="btn-dark mt-6" x-on:click="tab = 'private'; $dispatch('pick-departure', { id: 0 })">Choose my own dates</button>
                        </div>
                    @else
                        <div class="overflow-hidden rounded-[1.75rem] ring-1 ring-stone-300/60">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-stone-200/60 text-xs uppercase tracking-[0.14em] text-stone-500">
                                    <tr><th class="px-5 py-4 font-semibold">Departure</th><th class="hidden px-5 py-4 font-semibold sm:table-cell">Returns</th><th class="px-5 py-4 font-semibold">Availability</th><th class="px-5 py-4 text-right font-semibold">Per person</th><th class="px-5 py-4"><span class="sr-only">Book</span></th></tr>
                                </thead>
                                <tbody class="divide-y divide-stone-300/60 bg-stone-50">
                                    @foreach ($groupDepartures as $d)
                                        <tr class="transition hover:bg-white">
                                            <td class="px-5 py-4"><span class="font-semibold text-juniper-800">{{ $d->start_date->format('D, j M Y') }}</span>@if ($d->tier)<span class="block text-xs text-stone-500">{{ $d->tier->name }}</span>@endif</td>
                                            <td class="hidden px-5 py-4 text-ink-soft sm:table-cell">{{ ($d->end_date ?? ($trip->duration_days ? $d->start_date->copy()->addDays($trip->duration_days - 1) : null))?->format('j M Y') ?? '—' }}</td>
                                            <td class="px-5 py-4">
                                                <span @class(['inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold',
                                                    'bg-juniper-100 text-juniper-800' => $d->status === \App\Enums\DepartureStatus::Guaranteed,
                                                    'bg-saffron-300/50 text-ink' => $d->status === \App\Enums\DepartureStatus::Limited || $d->seatsLeft() <= 3,
                                                    'bg-stone-200 text-ink-soft' => ! in_array($d->status, [\App\Enums\DepartureStatus::Guaranteed, \App\Enums\DepartureStatus::Limited]) && $d->seatsLeft() > 3])>
                                                    <span class="size-1.5 rounded-full bg-current"></span>{{ $d->status->getLabel() }} · {{ $d->seatsLeft() }} left
                                                </span>
                                            </td>
                                            <td class="px-5 py-4 text-right">
                                                @if ($d->sale_price && $d->price && $d->sale_price < $d->price)<s class="block text-xs text-stone-400">US${{ number_format((float) $d->price) }}</s>@endif
                                                <span class="font-display text-xl text-juniper-800">{{ $d->effectivePrice() ? 'US$'.number_format($d->effectivePrice()) : 'On request' }}</span>
                                            </td>
                                            <td class="px-5 py-4 text-right"><button type="button" class="btn-primary !px-4 !py-2 !text-xs" x-on:click="$dispatch('pick-departure', { id: {{ $d->id }} })">Join</button></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div x-show="tab === 'private'" x-cloak class="mt-8">
                    <p class="mb-5 text-ink-soft">Your own guide and porter team, on the dates you choose. Every element — lodges, pace, extra days — can be tailored.</p>
                    @if ($pricing->isNotEmpty())
                        <div @class(['grid gap-4', 'sm:grid-cols-2' => $pricing->count() >= 2, 'xl:grid-cols-3' => $pricing->count() >= 3])>
                            @foreach ($pricing as $i => $pkg)
                                @php $hl = $pricing->count() === 1 || ($pricing->count() >= 3 && $i === 1); @endphp
                                <div @class(['relative flex flex-col self-start rounded-[1.75rem] p-7 ring-1', 'bg-juniper-800 text-stone-100 ring-juniper-800' => $hl, 'bg-stone-50 ring-stone-300/60' => ! $hl])>
                                    <p @class(['font-display text-2xl', 'text-white' => $hl])>{{ $pkg['name'] }}</p>
                                    <p class="mt-4 text-sm opacity-70">from</p>
                                    <p><span class="font-display text-4xl">US${{ number_format($pkg['from']) }}</span> <span class="text-sm opacity-70">/ adult</span></p>
                                    @if ($pkg['child'])<p class="mt-1 text-sm opacity-80">Child: US${{ number_format($pkg['child']) }}</p>@endif
                                    @if ($pkg['min'] > 1)<p class="mt-1 text-sm opacity-70">Minimum {{ $pkg['min'] }} travellers</p>@endif
                                    @if ($pkg['description'])
                                        <details class="group/pkg mt-5 border-t pt-4 {{ $hl ? 'border-white/15' : 'border-stone-300/60' }}">
                                            <summary class="flex cursor-pointer list-none items-center justify-between text-sm font-semibold [&::-webkit-details-marker]:hidden">What this package includes <x-heroicon-m-chevron-down class="size-4 transition group-open/pkg:rotate-180" /></summary>
                                            <div @class(['prose prose-sm mt-3 max-w-none [&_ul]:pl-4 [&_li]:my-0.5', 'prose-invert' => $hl, 'prose-wh' => ! $hl])>{!! $pkg['description'] !!}</div>
                                        </details>
                                    @endif
                                    @if (count($pkg['bands']) > 1)
                                        <table class="mt-5 w-full text-sm">
                                            <thead><tr @class(['text-left text-[0.65rem] uppercase tracking-[0.14em]', 'text-stone-400' => $hl, 'text-stone-500' => ! $hl])><th class="pb-2 font-semibold">No. of people</th><th class="pb-2 text-right font-semibold">Per person</th></tr></thead>
                                            <tbody>
                                                @foreach ($pkg['bands'] as $band)
                                                    <tr @class(['border-t', 'border-white/10' => $hl, 'border-stone-300/60' => ! $hl])>
                                                        <td class="py-2">{{ $band['label'] }}</td>
                                                        <td class="py-2 text-right">@if ($band['was'])<s class="mr-1 text-xs opacity-60">US${{ number_format($band['was']) }}</s>@endif<span class="font-semibold">US${{ number_format($band['price']) }}</span></td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @endif
                                    <button type="button" @class(['btn mt-auto w-full !py-3 [margin-top:max(1.5rem,auto)]', 'bg-white text-juniper-900 hover:bg-saffron-300' => $hl, 'bg-juniper-800 text-white hover:bg-juniper-950' => ! $hl]) x-on:click="$dispatch('prefill-enquiry', { adults: 2, tier: @js($pkg['tier_id']) })">Request this package</button>
                                </div>
                            @endforeach
                        </div>
                        <p class="mt-4 flex items-center gap-2 text-sm text-stone-500"><x-heroicon-o-user-group class="size-4" /> {{ $tripContent['group_discount_note'] }}</p>
                    @else
                        <div class="rounded-[1.75rem] bg-stone-200/50 p-8">
                            <p class="font-display text-2xl text-juniper-800">{{ $price ? 'From US$'.number_format($price).' per person' : 'Priced to your group' }}</p>
                            <button type="button" class="btn-dark mt-6" x-on:click="$dispatch('pick-departure', { id: 0 })">Request a private-trip quote</button>
                        </div>
                    @endif
                </div>
            </section>

            {{-- ============================== INCLUDES / EXCLUDES ============================== --}}
            @if ($includes || $excludes)
                <section id="includes" data-section class="scroll-mt-44">
                    <p class="eyebrow">The fine print, made clear</p>
                    <h2 class="mt-4 font-display text-display-sm">Price includes & excludes</h2>
                    <div class="mt-10 grid gap-6 md:grid-cols-2">
                        @foreach ([['Price includes', $includes, 'check', 'text-juniper-500', 'bg-juniper-50'], ['Price excludes', $excludes, 'x-mark', 'text-terracotta-500', 'bg-terracotta-500/10']] as [$title, $groups, $mark, $color, $bubble])
                            @if ($groups)
                                <div class="rounded-[1.75rem] bg-stone-50 p-7 ring-1 ring-stone-300/60">
                                    <h3 class="flex items-center gap-3 font-display text-2xl"><span class="flex size-9 items-center justify-center rounded-full {{ $bubble }}"><x-dynamic-component :component="'heroicon-m-'.$mark" class="size-5 {{ $color }}" /></span>{{ $title }}</h3>
                                    <div class="mt-6 space-y-6">
                                        @foreach ($groups as $group)
                                            <div>
                                                <p class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.16em] text-stone-500"><x-dynamic-component :component="'heroicon-o-'.$group['icon']" class="size-4" /> {{ $group['title'] }}</p>
                                                <ul class="mt-2.5 space-y-2">
                                                    @foreach ($group['items'] as $item)
                                                        <li class="flex gap-3 text-[0.95rem]"><x-dynamic-component :component="'heroicon-m-'.$mark" class="mt-1 size-4 shrink-0 {{ $color }}" /><span>{{ $item }}</span></li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- ============================== MAP & ELEVATION ============================== --}}
            @if ($profile || $map)
                <section id="map" data-section class="scroll-mt-44" x-data="{ unit: 'm' }">
                    <div class="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <p class="eyebrow">Route</p>
                            <h2 class="mt-4 font-display text-display-sm">Trip map & elevation</h2>
                        </div>
                        <div class="flex items-center gap-3">
                            @if ($profile)
                                <div class="inline-flex rounded-full bg-stone-200/70 p-1 text-xs font-semibold" role="group" aria-label="Units">
                                    <button type="button" class="rounded-full px-3 py-1.5" x-bind:class="unit === 'm' ? 'bg-juniper-800 text-white' : 'text-juniper-800'" x-on:click="unit = 'm'">Metres</button>
                                    <button type="button" class="rounded-full px-3 py-1.5" x-bind:class="unit === 'ft' ? 'bg-juniper-800 text-white' : 'text-juniper-800'" x-on:click="unit = 'ft'">Feet</button>
                                </div>
                            @endif
                            @if ($map)<a href="{{ $map->getUrl() }}" download class="btn-outline !px-4 !py-2 !text-xs"><x-heroicon-o-arrow-down-tray class="size-4" /> Download map</a>@endif
                        </div>
                    </div>

                    @if ($profile)
                        <figure class="mt-8 rounded-[1.75rem] bg-juniper-900 p-6 text-stone-200 sm:p-8">
                            <figcaption class="flex items-baseline justify-between text-sm">
                                <span class="font-semibold text-stone-100">Altitude profile</span>
                                <span class="data text-saffron-300">high point <span x-text="unit === 'm' ? @js(number_format($profile['max']).' m') : @js(number_format($profile['max'] * 3.28084).' ft')">{{ number_format($profile['max']) }} m</span></span>
                            </figcaption>
                            <svg viewBox="0 0 {{ \App\Support\AltitudeProfile::W }} {{ \App\Support\AltitudeProfile::H }}" class="mt-6 w-full overflow-visible" role="img" aria-label="Altitude by day, peaking at {{ number_format($profile['max']) }} metres">
                                <defs><linearGradient id="alt-fill" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#d9a441" stop-opacity=".45"/><stop offset="1" stop-color="#d9a441" stop-opacity="0"/></linearGradient></defs>
                                @foreach ($profile['ticks'] as $tick)
                                    <line x1="0" x2="{{ \App\Support\AltitudeProfile::W }}" y1="{{ $tick['y'] }}" y2="{{ $tick['y'] }}" stroke="white" stroke-opacity=".08" />
                                    <text x="0" y="{{ $tick['y'] - 5 }}" fill="white" fill-opacity=".45" font-size="13" font-family="var(--font-mono)" x-text="unit === 'm' ? @js($tick['label']) : @js($tick['label_ft'])">{{ $tick['label'] }}</text>
                                @endforeach
                                <path d="{{ $profile['area'] }}" fill="url(#alt-fill)" />
                                <path d="{{ $profile['line'] }}" fill="none" stroke="#ecc77e" stroke-width="2.5" stroke-linecap="round" pathLength="1" stroke-dasharray="1" stroke-dashoffset="1" class="[animation:draw_2.4s_var(--ease-mountain)_forwards]" />
                                @foreach ($profile['dots'] as $dot)
                                    <g>
                                        <circle cx="{{ $dot['x'] }}" cy="{{ $dot['y'] }}" r="5" fill="#162b26" stroke="#ecc77e" stroke-width="2" />
                                        <circle cx="{{ $dot['x'] }}" cy="{{ $dot['y'] }}" r="16" fill="transparent"><title>Day {{ $dot['day'] }} · {{ number_format($dot['alt']) }} m — {{ strip_tags($dot['title']) }}</title></circle>
                                        @if ($dot['alt'] === $profile['max'])
                                            <text x="{{ $dot['x'] }}" y="{{ $dot['y'] - 14 }}" text-anchor="middle" fill="#ecc77e" font-size="14" font-family="var(--font-mono)">▲</text>
                                        @endif
                                    </g>
                                @endforeach
                            </svg>
                            <div class="mt-3 flex justify-between data text-stone-400"><span>Day {{ $profile['dots'][0]['day'] }}</span><span>Day {{ end($profile['dots'])['day'] }}</span></div>
                        </figure>
                        @push('head')<style>@keyframes draw { to { stroke-dashoffset: 0; } }</style>@endpush
                    @endif

                    @if ($map)
                        <figure class="mt-6 overflow-hidden rounded-[1.75rem] bg-white ring-1 ring-stone-300/60">
                            <x-site.picture :media="$map" :alt="'Route map — '.$trip->title" sizes="(min-width: 1024px) 66vw, 100vw" img-class="h-auto w-full" />
                        </figure>
                    @endif
                </section>
            @endif

            {{-- ============================== GALLERY ============================== --}}
            @if ($gallery->isNotEmpty())
                @php $photos = collect([$trip->getFirstMedia('hero')])->filter()->merge($gallery)->values(); @endphp
                <section x-data="{ open: false, i: 0, n: {{ $photos->count() }} }" x-on:open-gallery.window="open = true; i = $event.detail.i" x-on:keydown.window="if (open) { if ($event.key === 'ArrowRight') i = (i + 1) % n; if ($event.key === 'ArrowLeft') i = (i - 1 + n) % n; if ($event.key === 'Escape') open = false }">
                    <p class="eyebrow">Gallery</p>
                    @php $mosaic = $photos->count() >= 5; @endphp
                    <div @class(['mt-8 grid gap-3', 'auto-rows-[10rem] grid-cols-2 sm:auto-rows-[13rem] md:grid-cols-4' => $mosaic, 'grid-cols-2 sm:grid-cols-3' => ! $mosaic])>
                        @foreach ($photos->take(7) as $k => $media)
                            <button type="button" x-on:click="open = true; i = {{ $k }}" @class(['group relative overflow-hidden rounded-2xl', 'col-span-2 row-span-2' => $mosaic && $k === 0, 'md:col-span-2' => $mosaic && $k === 3, 'aspect-[4/3]' => ! $mosaic, 'col-span-2 sm:col-span-1' => ! $mosaic && $k === 0 && $photos->count() === 3])>
                                <x-site.picture :media="$media" sizes="(min-width: 768px) 25vw, 50vw" class="h-full w-full transition duration-700 group-hover:scale-105" />
                                @if ($loop->last && $photos->count() > 7)<span class="absolute inset-0 flex items-center justify-center bg-juniper-950/60 font-display text-2xl text-white">+{{ $photos->count() - 7 }}</span>@endif
                            </button>
                        @endforeach
                    </div>
                    <template x-teleport="body">
                        <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-[90] flex items-center justify-center bg-juniper-950/95 p-4" role="dialog" aria-modal="true" aria-label="Photo gallery">
                            <button type="button" class="absolute top-5 right-5 flex size-12 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20" x-on:click="open = false" aria-label="Close"><x-heroicon-o-x-mark class="size-6" /></button>
                            <button type="button" class="absolute left-4 flex size-12 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20" x-on:click="i = (i - 1 + n) % n" aria-label="Previous"><x-heroicon-m-arrow-left class="size-5" /></button>
                            @foreach ($photos as $k => $media)
                                <figure x-show="i === {{ $k }}" class="max-h-full max-w-6xl">
                                    <img src="{{ $media->hasGeneratedConversion('wide') ? $media->getUrl('wide') : $media->getUrl() }}" alt="{{ $media->getCustomProperty('alt') }}" loading="lazy" class="max-h-[85vh] w-auto rounded-2xl object-contain">
                                </figure>
                            @endforeach
                            <span class="data absolute bottom-6 left-1/2 -translate-x-1/2 text-stone-300" x-text="(i + 1) + ' / ' + n"></span>
                            <button type="button" class="absolute right-4 flex size-12 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20" x-on:click="i = (i + 1) % n" aria-label="Next"><x-heroicon-m-arrow-right class="size-5" /></button>
                        </div>
                    </template>
                </section>
            @endif

            {{-- ============================== WHAT TO PACK ============================== --}}
            @if ($packing)
                <section id="packing" data-section class="scroll-mt-44" x-data="{ open: 0 }">
                    <div class="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <p class="eyebrow">Gear & equipment</p>
                            <h2 class="mt-4 font-display text-display-sm">What to pack</h2>
                        </div>
                        <a href="{{ url('/travel-guide/equipment-checklist') }}" class="link-arrow">Full equipment guide <x-heroicon-m-arrow-right class="size-4" /></a>
                    </div>
                    {{-- Two independent stacks (odd/even), so opening a card never leaves a gap beside it. --}}
                    <div class="mt-8 grid items-start gap-3 md:grid-cols-2">
                        @foreach (collect($packing)->keys()->partition(fn ($i) => $i % 2 === 0) as $column)
                            <div class="grid gap-3">
                                @foreach ($column as $i)
                                    @php ['group' => $group, 'items' => $items] = $packing[$i]; @endphp
                                    <div class="rounded-[1.5rem] bg-stone-50 ring-1 ring-stone-300/60 transition hover:ring-juniper-800/25" x-bind:class="open === {{ $i }} && '!ring-terracotta-500/40 shadow-[0_18px_40px_-28px_rgba(15,29,25,0.5)]'">
                                        <button type="button" class="flex w-full items-center justify-between gap-4 px-6 py-5 text-left" x-on:click="open = open === {{ $i }} ? -1 : {{ $i }}" x-bind:aria-expanded="open === {{ $i }}">
                                            <span class="font-display text-xl text-juniper-800">{{ $group }}</span>
                                            <span class="flex items-center gap-3"><span class="data text-stone-400">{{ count($items) }}</span><x-heroicon-m-chevron-down class="size-5 text-stone-400 transition duration-500" x-bind:class="open === {{ $i }} && 'rotate-180'" /></span>
                                        </button>
                                        <ul x-show="open === {{ $i }}" x-collapse @unless ($i === 0) x-cloak @endunless class="space-y-2 px-6 pb-6">
                                            @foreach ($items as $item)
                                                <li class="flex gap-3 text-[0.95rem] text-ink-soft"><span class="mt-2 size-1.5 shrink-0 rounded-full bg-terracotta-500"></span>{{ $item }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                    @if ($tripContent['packing_note'])<p class="mt-4 text-sm text-stone-500">{{ $tripContent['packing_note'] }}</p>@endif
                </section>
            @endif

            {{-- ============================== VIDEO ============================== --}}
            @if ($videos->isNotEmpty())
                <section id="video" data-section class="scroll-mt-44">
                    <p class="eyebrow">Trip video</p>
                    <h2 class="mt-4 font-display text-display-sm">See the trail</h2>
                    <div class="mt-8 grid gap-4 @if ($videos->count() > 1) sm:grid-cols-2 @endif">
                        @foreach ($videos as $video)
                            <div class="relative aspect-video overflow-hidden rounded-[1.75rem] bg-juniper-950" x-data="{ play: false }">
                                <template x-if="play"><iframe class="absolute inset-0 h-full w-full" src="{{ $video['embed'] }}" title="{{ $trip->title }} video" allow="autoplay; encrypted-media; picture-in-picture; fullscreen" allowfullscreen></iframe></template>
                                <button type="button" x-show="!play" x-on:click="play = true" class="group absolute inset-0" aria-label="Play video">
                                    @if ($video['thumb'])<img src="{{ $video['thumb'] }}" alt="" loading="lazy" class="h-full w-full object-cover opacity-80 transition group-hover:opacity-100">@else<x-site.picture :model="$trip" collection="hero" class="h-full w-full opacity-60" />@endif
                                    <span class="absolute inset-0 m-auto flex size-20 items-center justify-center rounded-full bg-terracotta-500 text-white shadow-2xl transition group-hover:scale-110"><x-heroicon-s-play class="ml-1 size-8" /></span>
                                </button>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- ============================== FAQS ============================== --}}
            @if ($trip->faqs->isNotEmpty())
                <section id="faqs" data-section class="scroll-mt-44" x-data="{ all: false }">
                    <div class="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <p class="eyebrow">Good to know</p>
                            <h2 class="mt-4 font-display text-display-sm">FAQs for {{ $trip->title }}</h2>
                        </div>
                        <button type="button" class="text-sm font-semibold text-juniper-800 underline underline-offset-4"
                                x-on:click="all = !all; $root.querySelectorAll('details').forEach(d => d.open = all)" x-text="all ? 'Collapse all' : 'Expand all'"></button>
                    </div>
                    <div class="mt-8 divide-y divide-stone-300/70 border-y border-stone-300/70">
                        @foreach ($trip->faqs as $faq)
                            <x-site.faq-item :question="$faq->question" :number="$loop->iteration" :open="$loop->first" data-reveal>{!! $faq->answer !!}</x-site.faq-item>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- ============================== REVIEWS ============================== --}}
            <section id="reviews" data-section class="scroll-mt-44">
                @php $avg = $tripReviews->isNotEmpty() ? round($tripReviews->avg('rating'), 1) : null; @endphp
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="eyebrow">Traveller reviews</p>
                        <h2 class="mt-4 font-display text-display-sm">{{ $tripReviews->isNotEmpty() ? 'Reviews of this trip' : 'What our travellers say' }}</h2>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        @if ($avg)
                            <span class="flex items-center gap-2 rounded-full bg-juniper-800 py-2 pr-5 pl-3 text-sm text-white">
                                <span class="flex gap-0.5 text-saffron-300">@for ($i = 1; $i <= 5; $i++)<x-heroicon-s-star @class(['size-4', 'opacity-30' => $i > round($avg)]) />@endfor</span>
                                <span class="font-semibold">{{ number_format($avg, 1) }}</span> · {{ $tripReviews->count() }} {{ str('review')->plural($tripReviews->count()) }}
                            </span>
                        @endif
                        <a href="{{ route('reviews') }}" class="flex items-center gap-2 rounded-full bg-stone-50 py-2 pr-5 pl-3 text-sm ring-1 ring-stone-300/60 hover:ring-juniper-500">
                            <x-heroicon-s-star class="size-4 text-saffron-500" /><span><span class="font-semibold text-juniper-800">{{ $reviews['google_label'] ?? 'Excellent' }}</span> · {{ $reviews['google_count'] ?? 78 }} Google reviews</span>
                        </a>
                    </div>
                </div>

                @foreach ([[$tripReviews->take(6), true], [$otherReviews, false]] as [$list, $isTrip])
                    @if ($list->isNotEmpty())
                        @unless ($isTrip || $tripReviews->isEmpty())<p class="mt-10 text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">More from our travellers</p>@endunless
                        <div class="mt-6 grid gap-5 md:grid-cols-2">
                            @foreach ($list as $t)
                                <figure class="flex flex-col rounded-[1.75rem] bg-stone-50 p-7 ring-1 ring-stone-300/60" x-data="{ more: false }">
                                    <div class="flex items-center justify-between">
                                        <div class="flex gap-0.5 text-saffron-500" aria-label="{{ $t->rating }} out of 5">@for ($i = 0; $i < $t->rating; $i++)<x-heroicon-s-star class="size-4" />@endfor</div>
                                        <span class="text-[0.65rem] uppercase tracking-[0.16em] text-stone-400">{{ $t->source === 'website' ? 'Verified traveller' : $t->source }}</span>
                                    </div>
                                    @if ($t->title)<p class="mt-4 font-display text-xl text-juniper-800">{{ $t->title }}</p>@endif
                                    <blockquote class="mt-3 text-ink-soft" x-bind:class="! more && 'line-clamp-5'">“{{ $t->body }}”</blockquote>
                                    @if (strlen($t->body) > 280)<button type="button" class="mt-2 self-start text-xs font-semibold text-terracotta-500" x-on:click="more = ! more" x-text="more ? 'Show less' : 'Read more'"></button>@endif
                                    <figcaption class="mt-auto flex items-center gap-3 pt-5">
                                        <span class="flex size-9 items-center justify-center rounded-full bg-juniper-800 font-display text-sm text-stone-100">{{ str($t->author_name)->substr(0, 1)->upper() }}</span>
                                        <span class="text-sm font-semibold text-juniper-800">{{ $t->author_name }}<span class="block text-xs font-normal text-stone-500">{{ collect([$t->author_country, $t->travelled_on ? 'Travelled '.$t->travelled_on->format('M Y') : $t->reviewed_at?->format('M Y')])->filter()->implode(' · ') }}</span></span>
                                    </figcaption>
                                </figure>
                            @endforeach
                        </div>
                    @endif
                @endforeach

                <div class="mt-8"><livewire:trip-review-form :trip="$trip" /></div>
            </section>

            {{-- ============================== WHY BOOK WITH US ============================== --}}
            <section class="overflow-hidden rounded-[2rem] bg-stone-200/60">
                <div class="grid gap-px bg-stone-300/50 sm:grid-cols-2">
                    @foreach (collect($tripContent['trust'])->map(fn ($t) => [$t['icon'] ?? 'check-badge', $t['title'] ?? '', $t['text'] ?? '']) as [$icon, $title, $text])
                        <div class="flex gap-4 bg-stone-100 p-7">
                            <x-dynamic-component :component="'heroicon-o-'.$icon" class="size-7 shrink-0 text-terracotta-500" />
                            <div><h3 class="font-display text-xl">{{ $title }}</h3><p class="mt-1 text-sm text-ink-soft">{{ $text }}</p></div>
                        </div>
                    @endforeach
                </div>
                <div class="flex flex-wrap items-center justify-between gap-6 bg-stone-100 px-7 py-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">Registered & affiliated</p>
                    <div class="flex flex-wrap items-center gap-4">
                        @foreach (['aff-nepal-government.png' => 'Government of Nepal', 'aff-ntb.jpg' => 'Nepal Tourism Board', 'aff-taan.png' => 'TAAN', 'aff-nma.png' => 'NMA', 'aff-keep.webp' => 'KEEP'] as $file => $label)
                            <img src="{{ asset('images/brand/'.$file) }}" alt="{{ $label }}" title="{{ $label }}" class="h-11 w-auto object-contain" loading="lazy">
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- ============================== ENQUIRE ============================== --}}
            <section class="scroll-mt-44" data-section id="enquire">
                <p class="eyebrow">Plan this journey</p>
                <h2 class="mt-4 font-display text-display-sm">Reserve your place, or make it your own</h2>
                <p class="mt-4 max-w-2xl text-ink-soft">No payment is taken now. A specialist confirms availability and sends a detailed quote; you secure your place with a {{ $booking['deposit_percent'] ?? 20 }}% deposit only when you're happy.</p>
                <div class="mt-10">
                    <livewire:trip-enquiry :trip="$trip" :departure="request()->integer('departure') ?: null" />
                </div>
            </section>
        </div>

        {{-- ============================== SIDEBAR ============================== --}}
        <aside class="lg:col-span-4">
            <div class="space-y-5 lg:sticky lg:top-40">
                <div class="overflow-hidden rounded-[2rem] bg-juniper-800 text-stone-100 shadow-[0_30px_60px_-30px_rgba(15,29,25,0.7)]">
                    <div class="p-7">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm text-stone-300">{{ $price ? 'From' : 'Pricing' }}</p>
                                @if ($price)
                                    <p class="mt-1 font-display text-5xl leading-none text-white">US${{ number_format($price) }}</p>
                                    <p class="mt-1 text-sm text-stone-300">per person</p>
                                @else
                                    <p class="mt-1 font-display text-3xl text-white">Tailored to your group</p>
                                @endif
                            </div>
                            @if ($trip->sale_price && $trip->price_from && $trip->sale_price < $trip->price_from)
                                <span class="rounded-full bg-saffron-500 px-3 py-1 text-xs font-bold text-ink">Save US${{ number_format($trip->price_from - $trip->sale_price) }}</span>
                            @endif
                        </div>

                        @if ($pricing->isNotEmpty())
                            <div class="mt-6 rounded-2xl bg-juniper-950/40 p-5"
                                 x-data="{
                                    pkgs: @js($pricing->map(fn ($p) => ['key' => $p['key'], 'name' => $p['name'], 'tier' => $p['tier_id'], 'bands' => $p['bands']])->values()),
                                    pkg: 0, pax: 2,
                                    get bands() { return this.pkgs[this.pkg]?.bands ?? [] },
                                    get min() { return this.bands.length ? this.bands[0].min : 1 },
                                    get band() { return this.bands.find(b => this.pax >= b.min && (b.max === null || this.pax <= b.max)) ?? (this.pax < this.min ? this.bands[0] : this.bands[this.bands.length - 1]) },
                                    get pp() { return this.band ? this.band.price : null },
                                    money(v) { return 'US$' + Math.round(v).toLocaleString('en-US') },
                                 }">
                                <p class="text-[0.65rem] font-semibold uppercase tracking-[0.18em] text-saffron-300">Group price calculator</p>
                                <template x-if="pkgs.length > 1">
                                    <div class="mt-3 flex flex-wrap gap-1.5">
                                        <template x-for="(p, i) in pkgs" :key="p.key">
                                            <button type="button" x-on:click="pkg = i" class="rounded-full px-3 py-1.5 text-xs font-semibold ring-1 transition"
                                                    x-bind:class="pkg === i ? 'bg-white text-juniper-900 ring-white' : 'text-stone-200 ring-white/25 hover:ring-white/60'" x-text="p.name"></button>
                                        </template>
                                    </div>
                                </template>
                                <div class="mt-4 flex items-center justify-between gap-3">
                                    <span class="text-sm text-stone-300">Travellers</span>
                                    <div class="flex items-center gap-1 rounded-full bg-white/10 p-1">
                                        <button type="button" class="flex size-8 items-center justify-center rounded-full hover:bg-white/15" x-on:click="pax = Math.max(1, pax - 1)" aria-label="Fewer travellers"><x-heroicon-m-minus class="size-4" /></button>
                                        <span class="w-8 text-center font-display text-xl text-white" x-text="pax" aria-live="polite"></span>
                                        <button type="button" class="flex size-8 items-center justify-center rounded-full hover:bg-white/15" x-on:click="pax = Math.min(30, pax + 1)" aria-label="More travellers"><x-heroicon-m-plus class="size-4" /></button>
                                    </div>
                                </div>
                                <p x-show="pax < min" x-cloak class="mt-2 text-xs text-saffron-300">Minimum <span x-text="min"></span> travellers for this package — solo? Ask us about joining a group.</p>
                                <div class="mt-4 flex items-end justify-between border-t border-white/10 pt-4">
                                    <div><p class="text-xs text-stone-400">Per person</p><p class="font-display text-2xl text-white" x-text="pp ? money(pp) : 'On request'"></p></div>
                                    <div class="text-right"><p class="text-xs text-stone-400">Total</p><p class="font-display text-2xl text-saffron-300" x-text="pp ? money(pp * Math.max(pax, min)) : '—'"></p></div>
                                </div>
                                <template x-if="bands.length > 1">
                                    <table class="mt-4 w-full text-xs">
                                        <thead><tr class="text-left text-stone-400"><th class="pb-1.5 font-semibold">No. of people</th><th class="pb-1.5 text-right font-semibold">Price per person</th></tr></thead>
                                        <tbody>
                                            <template x-for="b in bands" :key="b.min">
                                                <tr class="border-t border-white/10" x-bind:class="band === b && 'text-saffron-300'">
                                                    <td class="py-1.5" x-text="b.label"></td><td class="py-1.5 text-right font-semibold" x-text="money(b.price)"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </template>
                                <button type="button" class="btn mt-4 w-full bg-white !py-2.5 text-xs text-juniper-900 hover:bg-saffron-300" x-on:click="$dispatch('prefill-enquiry', { adults: pax, tier: pkgs[pkg]?.tier })">Request a quote for <span x-text="pax"></span></button>
                            </div>
                        @endif

                        <div class="mt-6 grid gap-2.5">
                            <a href="#departures" class="btn-primary">{{ $hasGroup ? 'Book now' : 'Request a quote' }}</a>
                            <div class="grid grid-cols-2 gap-2.5">
                                <a href="#departures" class="btn-ghost-light !px-3 !py-3 !text-xs">Check availability</a>
                                <a href="#enquire" class="btn-ghost-light !px-3 !py-3 !text-xs" x-data x-on:click="$dispatch('pick-departure', { id: 0 })">Enquire now</a>
                            </div>
                        </div>
                    </div>
                    <ul class="space-y-3 border-t border-white/10 bg-juniper-900/60 px-7 py-6 text-sm text-stone-300">
                        <li class="flex gap-3"><x-heroicon-o-shield-check class="size-5 shrink-0 text-saffron-300" /> {{ $booking['deposit_percent'] ?? 20 }}% deposit secures your place</li>
                        <li class="flex gap-3"><x-heroicon-o-arrow-path class="size-5 shrink-0 text-saffron-300" /> <a class="underline-offset-2 hover:underline" href="{{ url('/risk-free-booking') }}">Risk-free booking</a></li>
                        <li class="flex gap-3"><x-heroicon-o-chat-bubble-left-right class="size-5 shrink-0 text-saffron-300" /> Personal reply within 24 hours</li>
                    </ul>
                </div>

                {{-- Need help? --}}
                <div class="rounded-[2rem] bg-stone-50 p-7 ring-1 ring-stone-300/60">
                    <p class="font-display text-xl text-juniper-800">Need help booking?</p>
                    <p class="mt-1 text-sm text-ink-soft">Talk to a trip specialist in Kathmandu.</p>
                    <div class="mt-5 space-y-2.5 text-sm">
                        @if ($wa)<a href="https://wa.me/{{ $wa }}?text={{ rawurlencode('Namaste! I have a question about '.$trip->title.' ('.$trip->url().')') }}" target="_blank" rel="noopener" class="flex items-center gap-3 rounded-xl bg-[#25D366]/10 px-4 py-3 font-semibold text-juniper-800 hover:bg-[#25D366]/20"><x-heroicon-o-chat-bubble-oval-left-ellipsis class="size-5 text-[#1a9e4b]" /> WhatsApp us</a>@endif
                        @if ($phone)<a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="flex items-center gap-3 rounded-xl bg-stone-200/60 px-4 py-3 font-semibold text-juniper-800 hover:bg-stone-200"><x-heroicon-o-phone class="size-5 text-terracotta-500" /> {{ $phone }}</a>@endif
                        @if ($email)<a href="mailto:{{ $email }}?subject={{ rawurlencode('Enquiry: '.$trip->title) }}" class="flex items-center gap-3 rounded-xl bg-stone-200/60 px-4 py-3 font-semibold text-juniper-800 hover:bg-stone-200"><x-heroicon-o-envelope class="size-5 text-terracotta-500" /> {{ $email }}</a>@endif
                        <a href="{{ route('trips.pdf', $trip->slug) }}" class="flex items-center gap-3 rounded-xl bg-stone-200/60 px-4 py-3 font-semibold text-juniper-800 hover:bg-stone-200"><x-heroicon-o-arrow-down-tray class="size-5 text-terracotta-500" /> Download itinerary (PDF)</a>
                    </div>
                    @if ($trip->trip_code)<p class="mt-5 text-xs text-stone-500">Trip code <span class="data">{{ $trip->trip_code }}</span></p>@endif
                </div>
            </div>
        </aside>
    </div>

    {{-- ============================== SIMILAR TRIPS ============================== --}}
    @if ($related->isNotEmpty())
        <section class="bg-stone-200/50 py-24">
            <div class="container-page">
                <x-site.section-heading eyebrow="Similar trips" title="More journeys {{ $trip->destination ? 'in '.str_replace(' Region', '', $trip->destination->name) : 'like this' }}" />
                <div class="mt-14 grid gap-x-8 gap-y-14 sm:grid-cols-2 lg:grid-cols-3" data-stagger>
                    @foreach ($related as $r)
                        <x-site.trip-card :trip="$r" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Mobile sticky CTA --}}
    <div class="fixed inset-x-0 bottom-0 z-30 flex items-center justify-between gap-4 border-t border-stone-300/70 bg-stone-100/95 px-5 py-3 backdrop-blur-xl lg:hidden">
        <div>
            @if ($price)<p class="text-xs text-stone-500">from</p><p class="font-display text-xl leading-none text-juniper-800">US${{ number_format($price) }}</p>@else<p class="font-display text-lg text-juniper-800">{{ $trip->durationLabel() }}</p>@endif
        </div>
        <div class="flex gap-2">
            @if ($wa)<a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener" class="btn-outline !px-4 !py-3" aria-label="WhatsApp"><x-heroicon-o-chat-bubble-oval-left-ellipsis class="size-5" /></a>@endif
            <a href="#departures" class="btn-primary !py-3">Book / enquire</a>
        </div>
    </div>
</x-layouts.site>
