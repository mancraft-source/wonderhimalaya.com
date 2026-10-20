@php
    $profile = \App\Support\AltitudeProfile::make($altitudes);
    $gallery = $trip->getMedia('gallery');
    $map = $trip->getFirstMedia('map');
    $price = $trip->displayPrice();
    $groupDepartures = $trip->upcomingDepartures->filter->isBookable();
    $facts = array_filter([
        ['Duration', $trip->duration_days ? $trip->duration_days.' days'.($trip->duration_nights ? ' / '.$trip->duration_nights.' nights' : '') : null, 'clock'],
        ['Max altitude', $trip->max_altitude_m ? number_format($trip->max_altitude_m).' m' : null, 'arrow-trending-up'],
        ['Grade', $trip->difficulty?->name, 'bolt'],
        ['Group size', $trip->group_min || $trip->group_max ? trim(($trip->group_min ?: 1).'–'.($trip->group_max ?: '12')).' people' : null, 'user-group'],
        ['Best season', $trip->best_season, 'sun'],
        ['Accommodation', $trip->accommodation, 'home-modern'],
        ['Meals', $trip->meals, 'cake'],
        ['Start / end', $trip->start_point ? $trip->start_point.($trip->end_point ? ' – '.$trip->end_point : '') : null, 'map-pin'],
    ], fn ($f) => filled($f[1]));
    $crumbs = array_values(array_filter([
        ['label' => 'Journeys', 'url' => route('trips.index')],
        $trip->destination?->parent ? ['label' => $trip->destination->parent->name, 'url' => url($trip->destination->parent->publicPath())] : null,
        $trip->destination ? ['label' => $trip->destination->name, 'url' => url($trip->destination->publicPath())] : null,
        ['label' => $trip->title],
    ]));
@endphp
<x-layouts.site :seo="$seo" transparent>
    {{-- ============================== HERO ============================== --}}
    <section class="relative isolate flex h-[86svh] min-h-[36rem] max-h-[56rem] flex-col justify-end overflow-hidden bg-juniper-950 text-white">
        <div class="absolute inset-0 -z-10" data-parallax>
            <x-site.picture :model="$trip" collection="hero" sizes="100vw" eager class="h-full w-full animate-slow-zoom" />
        </div>
        <div class="scrim-hero absolute inset-0 -z-10"></div>
        <div class="container-page pt-36 pb-12 lg:pb-16">
            <x-site.breadcrumbs :items="$crumbs" light />
            <div class="mt-6 flex flex-wrap gap-2">
                @if ($trip->is_fixed_departure)<span class="chip-light">Fixed group departure</span>@endif
                @if ($trip->is_top_selling)<span class="chip-light !bg-saffron-500 !text-ink">Top seller</span>@endif
                @foreach ($trip->activities as $a)<a href="{{ url($a->publicPath()) }}" class="chip-light hover:bg-white/25">{{ $a->name }}</a>@endforeach
            </div>
            <h1 class="mt-5 max-w-5xl font-display text-display-lg text-white">{{ $trip->title }}</h1>
            @if ($trip->subtitle || $trip->excerpt)
                <p class="mt-5 max-w-2xl text-lg text-white/85">{{ $trip->subtitle ?: str($trip->excerpt)->limit(200) }}</p>
            @endif
            <div class="mt-8 flex flex-wrap items-center gap-4">
                <a href="#enquire" class="btn-primary">{{ $groupDepartures->isNotEmpty() ? 'Check dates & book' : 'Request a quote' }} <x-heroicon-m-arrow-down class="size-4" /></a>
                @if ($gallery->isNotEmpty())
                    <button type="button" class="btn-ghost-light" x-data x-on:click="$dispatch('open-gallery', { i: 0 })"><x-heroicon-o-photo class="size-4" /> {{ $gallery->count() }} photos</button>
                @endif
                @if ($price)
                    <p class="text-white/80">from <span class="font-display text-3xl text-white">US${{ number_format($price) }}</span> per person</p>
                @endif
            </div>
        </div>
    </section>

    {{-- ============================== FACTS ============================== --}}
    @if ($facts)
        <section class="border-b border-stone-300/60 bg-stone-50">
            <dl class="container-page no-scrollbar flex gap-10 overflow-x-auto py-7 lg:flex-wrap lg:justify-between lg:gap-x-8 lg:gap-y-6 lg:overflow-visible">
                @foreach ($facts as [$label, $value, $icon])
                    <div class="flex min-w-max items-start gap-3 lg:min-w-0">
                        <x-dynamic-component :component="'heroicon-o-'.$icon" class="mt-0.5 size-5 shrink-0 text-terracotta-500" />
                        <div>
                            <dt class="text-[0.7rem] font-semibold uppercase tracking-[0.18em] text-stone-500">{{ $label }}</dt>
                            <dd class="mt-1 font-medium text-juniper-800">{{ $value }}</dd>
                        </div>
                    </div>
                @endforeach
            </dl>
        </section>
    @endif

    {{-- ============================== SUB NAV ============================== --}}
    @php
        $sections = array_filter([
            'overview' => 'Overview',
            'itinerary' => $trip->days->isNotEmpty() ? 'Itinerary' : null,
            'departures' => 'Dates & prices',
            'includes' => ($trip->includes || $trip->excludes) ? "What's included" : null,
            'faqs' => $trip->faqs->isNotEmpty() ? 'FAQs' : null,
            'enquire' => 'Enquire',
        ]);
    @endphp
    <nav class="sticky top-20 z-30 border-b border-stone-300/60 bg-stone-100/95 backdrop-blur-xl lg:top-24" aria-label="On this page"
         x-data="{ active: 'overview' }"
         x-init="const els = [...document.querySelectorAll('[data-section]')]; const o = new IntersectionObserver(es => es.forEach(e => e.isIntersecting && (active = e.target.id)), { rootMargin: '-40% 0px -55% 0px' }); els.forEach(el => o.observe(el))">
        <div class="container-page no-scrollbar flex gap-8 overflow-x-auto">
            @foreach ($sections as $id => $label)
                <a href="#{{ $id }}" class="relative shrink-0 py-4 text-sm font-semibold text-stone-500 transition hover:text-juniper-800"
                   x-bind:class="active === '{{ $id }}' && '!text-juniper-800'">
                    {{ $label }}
                    <span class="absolute inset-x-0 -bottom-px h-0.5 origin-left scale-x-0 bg-terracotta-500 transition-transform" x-bind:class="active === '{{ $id }}' && '!scale-x-100'"></span>
                </a>
            @endforeach
        </div>
    </nav>

    <div class="container-page grid gap-16 py-16 lg:grid-cols-12 lg:py-24">
        <div class="min-w-0 lg:col-span-8">
            {{-- ============================== OVERVIEW ============================== --}}
            <section id="overview" data-section class="scroll-mt-44">
                @if ($trip->highlights)
                    <p class="eyebrow">Highlights</p>
                    <ul class="mt-6 grid gap-x-10 gap-y-4 sm:grid-cols-2">
                        @foreach ($trip->highlights as $highlight)
                            <li class="flex gap-3">
                                <svg viewBox="0 0 20 20" class="mt-1 size-4 shrink-0 text-terracotta-500" aria-hidden="true"><path fill="currentColor" d="M10 1 12.2 7.8 19 10l-6.8 2.2L10 19l-2.2-6.8L1 10l6.8-2.2Z"/></svg>
                                <span>{{ is_array($highlight) ? ($highlight['text'] ?? $highlight['highlight'] ?? implode(' ', $highlight)) : $highlight }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($trip->overview)
                    <div @class(['prose prose-lg prose-wh', 'mt-14' => $trip->highlights]) x-data="{ more: false }">
                        <div class="relative overflow-hidden transition-[max-height] duration-700" x-bind:style="more ? '' : 'max-height: 34rem'">
                            {!! $trip->overview !!}
                            <div class="pointer-events-none absolute inset-x-0 bottom-0 h-40 bg-gradient-to-t from-stone-100" x-show="!more"></div>
                        </div>
                        <button type="button" class="link-arrow not-prose mt-6" x-on:click="more = !more" x-text="more ? 'Show less' : 'Read the full overview'"></button>
                    </div>
                @endif
            </section>

            {{-- ============================== ITINERARY ============================== --}}
            @if ($trip->days->isNotEmpty())
                <section id="itinerary" data-section class="mt-24 scroll-mt-44" x-data="{ open: [1], all: false }">
                    <div class="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <p class="eyebrow">Day by day</p>
                            <h2 class="mt-4 font-display text-display-sm">The itinerary</h2>
                        </div>
                        <button type="button" class="text-sm font-semibold text-juniper-800 underline underline-offset-4"
                                x-on:click="all = !all; open = all ? @js($trip->days->pluck('day_number')) : []" x-text="all ? 'Collapse all days' : 'Expand all days'"></button>
                    </div>

                    @if ($profile)
                        <figure class="mt-10 rounded-[1.75rem] bg-juniper-900 p-6 text-stone-200 sm:p-8">
                            <figcaption class="flex items-baseline justify-between text-sm">
                                <span class="font-semibold text-stone-100">Altitude profile</span>
                                <span class="data text-saffron-300">high point {{ number_format($profile['max']) }} m</span>
                            </figcaption>
                            <svg viewBox="0 0 {{ \App\Support\AltitudeProfile::W }} {{ \App\Support\AltitudeProfile::H }}" class="mt-6 w-full overflow-visible" role="img" aria-label="Altitude by day, peaking at {{ number_format($profile['max']) }} metres">
                                <defs><linearGradient id="alt-fill" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#d9a441" stop-opacity=".45"/><stop offset="1" stop-color="#d9a441" stop-opacity="0"/></linearGradient></defs>
                                @foreach ($profile['ticks'] as $tick)
                                    <line x1="0" x2="{{ \App\Support\AltitudeProfile::W }}" y1="{{ $tick['y'] }}" y2="{{ $tick['y'] }}" stroke="white" stroke-opacity=".08" />
                                    <text x="0" y="{{ $tick['y'] - 5 }}" fill="white" fill-opacity=".4" font-size="13" font-family="var(--font-mono)">{{ $tick['label'] }}</text>
                                @endforeach
                                <path d="{{ $profile['area'] }}" fill="url(#alt-fill)" />
                                <path d="{{ $profile['line'] }}" fill="none" stroke="#ecc77e" stroke-width="2.5" stroke-linecap="round" pathLength="1" stroke-dasharray="1" stroke-dashoffset="1" class="[animation:draw_2.4s_var(--ease-mountain)_forwards]" />
                                @foreach ($profile['dots'] as $dot)
                                    <g class="group/dot">
                                        <circle cx="{{ $dot['x'] }}" cy="{{ $dot['y'] }}" r="5" fill="#162b26" stroke="#ecc77e" stroke-width="2" />
                                        <circle cx="{{ $dot['x'] }}" cy="{{ $dot['y'] }}" r="16" fill="transparent"><title>Day {{ $dot['day'] }} · {{ number_format($dot['alt']) }} m — {{ strip_tags($dot['title']) }}</title></circle>
                                        @if ($dot['alt'] === $profile['max'])
                                            <text x="{{ $dot['x'] }}" y="{{ $dot['y'] - 14 }}" text-anchor="middle" fill="#ecc77e" font-size="14" font-family="var(--font-mono)">▲ {{ number_format($dot['alt']) }}</text>
                                        @endif
                                    </g>
                                @endforeach
                            </svg>
                            <div class="mt-3 flex justify-between data text-stone-400"><span>Day {{ $profile['dots'][0]['day'] }}</span><span>Day {{ end($profile['dots'])['day'] }}</span></div>
                        </figure>
                        @push('head')<style>@keyframes draw { to { stroke-dashoffset: 0; } }</style>@endpush
                    @endif

                    <ol class="relative mt-10">
                        <span class="absolute top-4 bottom-4 left-[1.18rem] w-px border-l border-dashed border-terracotta-500/50" aria-hidden="true"></span>
                        @foreach ($trip->days as $day)
                            <li class="relative pl-14" wire:key="day-{{ $day->id }}">
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
                                            <div class="flex flex-wrap gap-2">
                                                @if ($day->altitude_m)<span class="chip"><x-heroicon-o-arrow-trending-up class="size-3.5" /> <span class="data">{{ number_format($day->altitude_m) }} m</span></span>@endif
                                                @if ($day->distance_km)<span class="chip"><x-heroicon-o-map class="size-3.5" /> <span class="data">{{ rtrim(rtrim((string) $day->distance_km, '0'), '.') }} km</span></span>@endif
                                                @if ($day->duration)<span class="chip"><x-heroicon-o-clock class="size-3.5" /> {{ $day->duration }}</span>@endif
                                                @if ($day->meals)<span class="chip"><x-heroicon-o-cake class="size-3.5" /> {{ collect($day->meals)->map(fn ($m) => ucfirst($m))->implode(', ') }}</span>@endif
                                                @if ($day->accommodation)<span class="chip"><x-heroicon-o-home-modern class="size-3.5" /> {{ $day->accommodation }}</span>@endif
                                            </div>
                                            @if ($day->description)
                                                <div class="prose prose-wh mt-5">{!! $day->description !!}</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ol>

                    @if ($map)
                        <figure class="mt-14 overflow-hidden rounded-[1.75rem] bg-white ring-1 ring-stone-300/60">
                            <x-site.picture :media="$map" :alt="'Route map — '.$trip->title" sizes="(min-width: 1024px) 66vw, 100vw" img-class="h-auto w-full" />
                            <figcaption class="px-6 py-4 text-sm text-ink-soft">Route map — {{ $trip->title }}</figcaption>
                        </figure>
                    @endif
                </section>
            @endif

            {{-- ============================== DEPARTURES & PRICES ============================== --}}
            <section id="departures" data-section class="mt-24 scroll-mt-44" x-data="{ tab: '{{ $groupDepartures->isNotEmpty() ? 'group' : 'private' }}' }">
                <p class="eyebrow">Departures & availability</p>
                <h2 class="mt-4 font-display text-display-sm">Dates & prices</h2>

                <div class="mt-8 inline-flex rounded-full bg-stone-200/70 p-1 text-sm font-semibold" role="tablist">
                    <button type="button" role="tab" class="rounded-full px-5 py-2.5 transition" x-bind:class="tab === 'group' ? 'bg-juniper-800 text-stone-100' : 'text-juniper-800'" x-on:click="tab = 'group'">Group departures</button>
                    <button type="button" role="tab" class="rounded-full px-5 py-2.5 transition" x-bind:class="tab === 'private' ? 'bg-juniper-800 text-stone-100' : 'text-juniper-800'" x-on:click="tab = 'private'">Private trip</button>
                </div>

                <div x-show="tab === 'group'" class="mt-8">
                    @if ($groupDepartures->isEmpty())
                        <div class="rounded-[1.75rem] bg-stone-200/50 p-8">
                            <p class="font-display text-2xl text-juniper-800">No fixed dates published yet.</p>
                            <p class="mt-2 text-ink-soft">We run this journey privately on any date you choose — and can often match you with other travellers. Ask us about upcoming groups.</p>
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
                                        <tr>
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
                    @if ($trip->prices->isNotEmpty())
                        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach ($trip->prices->groupBy(fn ($p) => $p->tier?->name ?? 'Price') as $tierName => $rows)
                                <div class="rounded-[1.75rem] bg-stone-50 p-6 ring-1 ring-stone-300/60">
                                    <p class="font-display text-2xl text-juniper-800">{{ $tierName }}</p>
                                    <dl class="mt-4 divide-y divide-stone-300/60 text-sm">
                                        @foreach ($rows as $row)
                                            <div class="flex items-baseline justify-between py-2.5">
                                                <dt class="text-ink-soft">{{ $row->label ?: ($row->pax_max ? $row->pax_min.'–'.$row->pax_max.' people' : $row->pax_min.'+ people') }}</dt>
                                                <dd class="font-display text-lg text-juniper-800">US${{ number_format($row->effectivePrice()) }}</dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="rounded-[1.75rem] bg-stone-200/50 p-8">
                            <p class="font-display text-2xl text-juniper-800">{{ $price ? 'From US$'.number_format($price).' per person' : 'Priced to your group' }}</p>
                            <p class="mt-2 text-ink-soft">Private trips run on any date, with your own guide and porter team. The per-person price falls as your group grows, and every element — lodges, pace, extra days — can be adjusted.</p>
                        </div>
                    @endif
                    <button type="button" class="btn-dark mt-6" x-on:click="$dispatch('pick-departure', { id: 0 })">Request a private-trip quote</button>
                </div>
            </section>

            {{-- ============================== INCLUDES ============================== --}}
            @if ($trip->includes || $trip->excludes)
                <section id="includes" data-section class="mt-24 scroll-mt-44">
                    <p class="eyebrow">The fine print, made clear</p>
                    <h2 class="mt-4 font-display text-display-sm">What's included</h2>
                    <div class="mt-10 grid gap-8 md:grid-cols-2">
                        @foreach ([['Included', $trip->includes, 'check', 'text-juniper-500'], ['Not included', $trip->excludes, 'minus', 'text-terracotta-500']] as [$title, $items, $icon, $color])
                            @if ($items)
                                <div class="rounded-[1.75rem] bg-stone-50 p-7 ring-1 ring-stone-300/60">
                                    <h3 class="font-display text-2xl">{{ $title }}</h3>
                                    <ul class="mt-5 space-y-3">
                                        @foreach ($items as $item)
                                            <li class="flex gap-3 text-[0.95rem]"><x-dynamic-component :component="'heroicon-m-'.$icon" class="mt-1 size-4 shrink-0 {{ $color }}" /><span>{{ is_array($item) ? implode(' ', $item) : strip_tags($item) }}</span></li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- ============================== GALLERY ============================== --}}
            @if ($gallery->isNotEmpty())
                <section class="mt-24" x-data="{ open: false, i: 0, n: {{ $gallery->count() }} }" x-on:open-gallery.window="open = true; i = $event.detail.i" x-on:keydown.window="if (open) { if ($event.key === 'ArrowRight') i = (i + 1) % n; if ($event.key === 'ArrowLeft') i = (i - 1 + n) % n; if ($event.key === 'Escape') open = false }">
                    <p class="eyebrow">Gallery</p>
                    <div class="mt-8 grid auto-rows-[10rem] grid-cols-2 gap-3 sm:auto-rows-[13rem] md:grid-cols-4">
                        @foreach ($gallery->take(7) as $k => $media)
                            <button type="button" x-on:click="open = true; i = {{ $k }}" @class(['group relative overflow-hidden rounded-2xl', 'col-span-2 row-span-2' => $k === 0, 'md:col-span-2' => $k === 3])>
                                <x-site.picture :media="$media" sizes="(min-width: 768px) 25vw, 50vw" class="h-full w-full transition duration-700 group-hover:scale-105" />
                                @if ($loop->last && $gallery->count() > 7)<span class="absolute inset-0 flex items-center justify-center bg-juniper-950/60 font-display text-2xl text-white">+{{ $gallery->count() - 7 }}</span>@endif
                            </button>
                        @endforeach
                    </div>
                    <template x-teleport="body">
                        <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-[90] flex items-center justify-center bg-juniper-950/95 p-4" role="dialog" aria-modal="true" aria-label="Photo gallery">
                            <button type="button" class="absolute top-5 right-5 flex size-12 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20" x-on:click="open = false" aria-label="Close"><x-heroicon-o-x-mark class="size-6" /></button>
                            <button type="button" class="absolute left-4 flex size-12 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20" x-on:click="i = (i - 1 + n) % n" aria-label="Previous"><x-heroicon-m-arrow-left class="size-5" /></button>
                            @foreach ($gallery as $k => $media)
                                <figure x-show="i === {{ $k }}" class="max-h-full max-w-6xl">
                                    <img src="{{ $media->hasGeneratedConversion('wide') ? $media->getUrl('wide') : $media->getUrl() }}" alt="{{ $media->getCustomProperty('alt') }}" loading="lazy" class="max-h-[85vh] w-auto rounded-2xl object-contain">
                                    @if ($media->getCustomProperty('alt'))<figcaption class="mt-3 text-center text-sm text-stone-300">{{ $media->getCustomProperty('alt') }}</figcaption>@endif
                                </figure>
                            @endforeach
                            <button type="button" class="absolute right-4 flex size-12 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20" x-on:click="i = (i + 1) % n" aria-label="Next"><x-heroicon-m-arrow-right class="size-5" /></button>
                        </div>
                    </template>
                </section>
            @endif

            {{-- ============================== FAQS ============================== --}}
            @if ($trip->faqs->isNotEmpty())
                <section id="faqs" data-section class="mt-24 scroll-mt-44">
                    <p class="eyebrow">Good to know</p>
                    <h2 class="mt-4 font-display text-display-sm">Questions travellers ask</h2>
                    <div class="mt-8 divide-y divide-stone-300/70 border-y border-stone-300/70">
                        @foreach ($trip->faqs as $faq)
                            <details class="group py-2" @if ($loop->first) open @endif>
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-6 py-4 font-display text-xl text-juniper-800 [&::-webkit-details-marker]:hidden">
                                    {{ $faq->question }}
                                    <x-heroicon-m-plus class="size-5 shrink-0 text-terracotta-500 transition group-open:rotate-45" />
                                </summary>
                                <div class="prose prose-wh pb-6">{!! $faq->answer !!}</div>
                            </details>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- ============================== REVIEWS ============================== --}}
            @if ($trip->testimonials->isNotEmpty())
                <section class="mt-24">
                    <p class="eyebrow">Traveller stories</p>
                    <div class="mt-8 grid gap-6 md:grid-cols-2">
                        @foreach ($trip->testimonials->take(4) as $t)
                            <figure class="rounded-[1.75rem] bg-stone-50 p-7 ring-1 ring-stone-300/60">
                                <div class="flex gap-0.5 text-saffron-500">@for ($i = 0; $i < $t->rating; $i++)<x-heroicon-s-star class="size-4" />@endfor</div>
                                <blockquote class="mt-4 text-ink-soft">“{{ str($t->body)->limit(320) }}”</blockquote>
                                <figcaption class="mt-5 text-sm font-semibold text-juniper-800">{{ $t->author_name }}<span class="font-normal text-stone-500"> · {{ $t->author_country ?? ucfirst($t->source) }}</span></figcaption>
                            </figure>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- ============================== ENQUIRE ============================== --}}
            <section class="mt-24 scroll-mt-44" data-section id="enquire">
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
            <div class="space-y-6 lg:sticky lg:top-44">
                <div class="overflow-hidden rounded-[2rem] bg-juniper-800 text-stone-100">
                    <div class="p-8">
                        <p class="text-sm text-stone-300">{{ $price ? 'From' : 'Pricing' }}</p>
                        @if ($price)
                            <p class="mt-1 font-display text-5xl text-white">US${{ number_format($price) }}</p>
                            <p class="text-sm text-stone-300">per person · {{ $trip->durationLabel() }}</p>
                            @if ($trip->sale_price && $trip->price_from && $trip->sale_price < $trip->price_from)
                                <p class="mt-3 inline-flex rounded-full bg-saffron-500 px-3 py-1 text-xs font-semibold text-ink">Save US${{ number_format($trip->price_from - $trip->sale_price) }} — was US${{ number_format((float) $trip->price_from) }}</p>
                            @endif
                        @else
                            <p class="mt-1 font-display text-3xl text-white">Tailored to your group</p>
                        @endif
                        <div class="mt-8 grid gap-3">
                            <a href="#enquire" class="btn-primary">{{ $groupDepartures->isNotEmpty() ? 'Check dates & book' : 'Request a quote' }}</a>
                            @php $wa = preg_replace('/\D/', '', (string) \App\Models\Setting::get('company.whatsapp')); @endphp
                            @if ($wa)
                                <a href="https://wa.me/{{ $wa }}?text={{ rawurlencode('Namaste! I have a question about '.$trip->title.' ('.$trip->url().')') }}" target="_blank" rel="noopener" class="btn-ghost-light">Ask on WhatsApp</a>
                            @endif
                        </div>
                    </div>
                    <ul class="space-y-3 border-t border-white/10 bg-juniper-900/60 p-8 text-sm text-stone-300">
                        <li class="flex gap-3"><x-heroicon-o-shield-check class="size-5 shrink-0 text-saffron-300" /> Secure your place with a {{ $booking['deposit_percent'] ?? 20 }}% deposit</li>
                        <li class="flex gap-3"><x-heroicon-o-arrow-path class="size-5 shrink-0 text-saffron-300" /> <a class="underline-offset-2 hover:underline" href="{{ url('/risk-free-booking') }}">Risk-free booking & flexible changes</a></li>
                        <li class="flex gap-3"><x-heroicon-o-user-group class="size-5 shrink-0 text-saffron-300" /> Licensed local guides, insured porters</li>
                        <li class="flex gap-3"><x-heroicon-o-chat-bubble-left-right class="size-5 shrink-0 text-saffron-300" /> Personal reply within 24 hours</li>
                    </ul>
                </div>
                @if ($trip->trip_code)
                    <p class="px-2 text-xs text-stone-500">Trip code <span class="data">{{ $trip->trip_code }}</span></p>
                @endif
            </div>
        </aside>
    </div>

    {{-- ============================== RELATED ============================== --}}
    @if ($related->isNotEmpty())
        <section class="bg-stone-200/50 py-24">
            <div class="container-page">
                <x-site.section-heading eyebrow="You might also love" title="More journeys {{ $trip->destination ? 'in '.str_replace(' Region', '', $trip->destination->name) : 'like this' }}" />
                <div class="mt-14 grid gap-x-8 gap-y-14 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($related as $r)
                        <x-site.trip-card :trip="$r" data-reveal />
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
        <a href="#enquire" class="btn-primary !py-3">Enquire now</a>
    </div>
</x-layouts.site>
