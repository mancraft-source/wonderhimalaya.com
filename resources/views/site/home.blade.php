<x-layouts.site :seo="$seo" transparent>
    {{-- ============================== HERO ============================== --}}
    <section class="relative isolate flex min-h-[100svh] flex-col justify-end overflow-hidden bg-juniper-950 text-white"
             x-data="{ i: 0, n: {{ max(1, $heroTrips->count()) }} }"
             x-init="n > 1 && setInterval(() => i = (i + 1) % n, 7000)">
        <div class="absolute inset-0 -z-10" data-parallax>
            @forelse ($heroTrips as $k => $trip)
                <div class="absolute inset-0 transition-opacity duration-[2s]" x-bind:class="i === {{ $k }} ? 'opacity-100' : 'opacity-0'" @if ($k > 0) x-cloak @endif>
                    <x-site.picture :model="$trip" collection="hero" sizes="100vw" :eager="$k === 0" class="h-full w-full animate-slow-zoom" />
                </div>
            @empty
                <x-site.picture class="h-full w-full" alt="The Himalaya" />
            @endforelse
        </div>
        <div class="scrim-hero absolute inset-0 -z-10"></div>

        <div class="container-page pt-40 pb-14 lg:pb-20">
            <div class="max-w-4xl">
                <p class="eyebrow eyebrow-light animate-reveal">Nepal · Tibet · Bhutan</p>
                <h1 class="mt-6 font-display text-display-xl text-white animate-reveal [animation-delay:120ms]">
                    Walk among <em class="font-light text-saffron-300">giants.</em>
                </h1>
                <p class="mt-6 max-w-xl text-lg text-white/85 animate-reveal [animation-delay:240ms]">
                    Treks, climbs and quiet journeys through the high Himalaya — designed and guided by a small Kathmandu team who call these mountains home.
                </p>
            </div>

            <form action="{{ route('trips.index') }}" method="get" class="mt-12 grid max-w-4xl gap-2 rounded-[2rem] bg-white/95 p-2 text-ink shadow-2xl backdrop-blur md:grid-cols-[1fr_1fr_auto] animate-reveal [animation-delay:360ms]" role="search">
                <label class="flex flex-col rounded-[1.6rem] px-5 py-3 transition hover:bg-stone-100">
                    <span class="text-[0.7rem] font-semibold uppercase tracking-[0.18em] text-stone-500">Where</span>
                    <select name="destination" class="-ml-1 mt-0.5 border-0 bg-transparent p-0 pr-8 font-display text-lg text-juniper-800 focus:ring-0">
                        <option value="">Anywhere in the Himalaya</option>
                        @foreach ($nav['destinations'] as $country)
                            <option value="{{ basename($country['url']) }}">{{ $country['name'] }}</option>
                            @foreach ($country['children'] as $region)<option value="{{ basename($region['url']) }}">&nbsp;&nbsp;{{ $region['name'] }}</option>@endforeach
                        @endforeach
                    </select>
                </label>
                <label class="flex flex-col rounded-[1.6rem] px-5 py-3 transition hover:bg-stone-100 md:border-l md:border-stone-200">
                    <span class="text-[0.7rem] font-semibold uppercase tracking-[0.18em] text-stone-500">How</span>
                    <select name="activity" class="-ml-1 mt-0.5 border-0 bg-transparent p-0 pr-8 font-display text-lg text-juniper-800 focus:ring-0">
                        <option value="">Any style of journey</option>
                        @foreach ($nav['activities'] as $activity)<option value="{{ basename($activity['url']) }}">{{ $activity['name'] }}</option>@endforeach
                    </select>
                </label>
                <button type="submit" class="btn-primary !rounded-[1.6rem] !px-8"><x-heroicon-m-magnifying-glass class="size-5" /> Find journeys</button>
            </form>

            <div class="mt-10 flex flex-wrap items-center gap-x-8 gap-y-3 text-sm text-white/80">
                <span class="flex items-center gap-2"><x-heroicon-s-trophy class="size-5 text-saffron-300" /> Tripadvisor {{ $reviews['tripadvisor_award'] ?? "Travellers' Choice" }}</span>
                <span class="flex items-center gap-2"><x-heroicon-s-star class="size-5 text-saffron-300" /> {{ $reviews['google_label'] ?? 'Excellent' }} · {{ $reviews['google_count'] ?? 78 }} Google reviews</span>
                <span class="flex items-center gap-2"><x-heroicon-s-check-badge class="size-5 text-saffron-300" /> Locally owned in Kathmandu</span>
            </div>
        </div>
    </section>

    {{-- ============================== WELCOME ============================== --}}
    <section class="grain relative overflow-hidden py-24 lg:py-36">
        <div class="topo pointer-events-none absolute -top-40 -right-40 size-[60rem] opacity-70" aria-hidden="true"></div>
        <div class="container-page relative grid gap-16 lg:grid-cols-12">
            <div class="lg:col-span-7" data-reveal>
                <p class="eyebrow">Welcome to Wonder Himalaya</p>
                <p class="mt-8 font-display text-display-md leading-[1.12] text-juniper-800">
                    We're a small team in Thamel, Kathmandu, and we've spent years doing one thing — taking people into the mountains and bringing them back with <em class="text-terracotta-500">stories they'll tell for the rest of their lives.</em>
                </p>
            </div>
            <div class="flex flex-col justify-end lg:col-span-4 lg:col-start-9" data-reveal>
                <p class="text-lg text-ink-soft">From the teahouses of the Everest region to the monastery corridors of Lhasa, from the rhododendron forests of the Annapurna Circuit to the fortress towns of Bhutan — this part of the world is what we know, and sharing it is what we do.</p>
                <a href="{{ url('/about-us') }}" class="link-arrow mt-8">Our story <x-heroicon-m-arrow-right class="size-4" /></a>
            </div>
        </div>

        @if ($stats)
            <dl class="container-page relative mt-20 grid grid-cols-2 gap-px overflow-hidden rounded-[2rem] bg-stone-300/60 ring-1 ring-stone-300/60 lg:grid-cols-4" data-reveal>
                @foreach ($stats as $stat)
                    <div class="bg-stone-100 p-8 lg:p-10">
                        <dt class="text-sm text-ink-soft">{{ $stat['label'] }}</dt>
                        <dd class="mt-3 font-display text-5xl text-juniper-800 lg:text-6xl">{{ $stat['value'] }}</dd>
                    </div>
                @endforeach
            </dl>
        @endif
    </section>

    {{-- ============================== MOODS ============================== --}}
    @php
        $moods = [
            ['guided-trek', 'Classic trails', 'Tea-house treks to the great base camps', 'from-juniper-700 to-juniper-950'],
            ['peak-climbing', 'Summits', 'Island, Mera, Lobuche & trekking peaks', 'from-glacier-500 to-juniper-900'],
            ['heli-tour', 'Above it all', 'Helicopter journeys to the high valleys', 'from-terracotta-500 to-juniper-900'],
            ['city-tour', 'Living culture', 'Temples, durbar squares & festivals', 'from-saffron-500 to-terracotta-600'],
            ['jungle-safari', 'Into the wild', 'Rhinos & tigers in Chitwan and Bardiya', 'from-juniper-500 to-juniper-950'],
            ['paragliding', 'Pure adrenaline', 'Soar over Phewa Lake & Pokhara', 'from-glacier-300 to-glacier-500'],
        ];
        $activityUrls = collect($nav['activities'])->keyBy(fn ($a) => basename($a['url']));
    @endphp
    <section class="pb-24 lg:pb-32">
        <div class="container-page flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
            <x-site.section-heading eyebrow="Travel by feeling" title="How do you want the mountains to <em>feel</em>?" />
            <a href="{{ route('trips.index') }}" class="link-arrow shrink-0" data-reveal>Every journey <x-heroicon-m-arrow-right class="size-4" /></a>
        </div>
        <div class="no-scrollbar mt-12 flex snap-x snap-mandatory gap-5 overflow-x-auto px-5 pb-4 sm:px-8 lg:px-[max(3rem,calc((100vw-90rem)/2+3rem))]">
            @foreach ($moods as [$slug, $title, $line, $gradient])
                @continue(! $activityUrls->has($slug))
                <a href="{{ $activityUrls[$slug]['url'] }}" class="group relative flex aspect-[3/4] w-[78vw] shrink-0 snap-start flex-col justify-end overflow-hidden rounded-[2rem] bg-gradient-to-br {{ $gradient }} p-7 text-white sm:w-[22rem]" data-reveal>
                    <div class="topo absolute inset-0 opacity-30 invert transition duration-1000 group-hover:scale-110"></div>
                    <span class="absolute top-6 right-6 data rounded-full bg-white/15 px-3 py-1 backdrop-blur">{{ $activityUrls[$slug]['count'] }} {{ str('trip')->plural($activityUrls[$slug]['count']) }}</span>
                    <h3 class="relative font-display text-4xl text-white">{{ $title }}</h3>
                    <p class="relative mt-2 text-white/80">{{ $line }}</p>
                    <span class="relative mt-6 inline-flex items-center gap-2 text-sm font-semibold">Explore <x-heroicon-m-arrow-right class="size-4 transition group-hover:translate-x-1" /></span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- ============================== SIGNATURE JOURNEYS ============================== --}}
    @if ($signature->isNotEmpty())
        <section class="bg-stone-200/50 py-24 lg:py-32">
            <div class="container-page">
                <x-site.section-heading eyebrow="Signature journeys" title="The trails our travellers love most" intro="Annapurna Base Camp, the Annapurna Circuit, Mardi Himal and Everest Base Camp — four classics we have walked hundreds of times, and still can't get enough of." />
                <div class="mt-16 grid gap-x-8 gap-y-14 md:grid-cols-2 lg:grid-cols-12">
                    @foreach ($signature->values() as $k => $trip)
                        <x-site.trip-card :trip="$trip" :size="in_array($k, [0, 3]) ? 'lg' : 'md'" class="{{ in_array($k, [0, 3]) ? 'lg:col-span-7' : 'lg:col-span-5 lg:pt-24' }}" data-reveal />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ============================== TRIP OF THE MONTH ============================== --}}
    @php $featuredStory = $tripOfMonth ?? $topSeller; @endphp
    @if ($featuredStory)
        <section class="relative isolate overflow-hidden bg-juniper-900 text-stone-100">
            <div class="grid lg:grid-cols-2">
                <div class="relative min-h-[28rem] lg:min-h-[44rem]">
                    <x-site.picture :model="$featuredStory" collection="hero" sizes="(min-width: 1024px) 50vw, 100vw" class="absolute inset-0 h-full w-full" />
                </div>
                <div class="relative flex flex-col justify-center px-6 py-20 sm:px-12 lg:px-20">
                    <div class="topo pointer-events-none absolute inset-0 opacity-30 invert"></div>
                    <div class="relative max-w-xl" data-reveal>
                        <p class="eyebrow eyebrow-light">{{ $tripOfMonth ? 'Trip of the month' : 'Our top-selling trek' }}</p>
                        <h2 class="mt-6 font-display text-display-md text-stone-100">{{ $featuredStory->title }}</h2>
                        <p class="mt-6 text-lg text-stone-300">{{ $featuredStory->excerpt }}</p>
                        <dl class="mt-10 grid grid-cols-3 gap-6 border-y border-white/10 py-6">
                            <div><dt class="text-xs uppercase tracking-[0.18em] text-stone-400">Duration</dt><dd class="mt-1 font-display text-2xl">{{ $featuredStory->duration_days ?? '—' }} days</dd></div>
                            <div><dt class="text-xs uppercase tracking-[0.18em] text-stone-400">Max altitude</dt><dd class="mt-1 font-display text-2xl">{{ $featuredStory->max_altitude_m ? number_format($featuredStory->max_altitude_m).' m' : '—' }}</dd></div>
                            <div><dt class="text-xs uppercase tracking-[0.18em] text-stone-400">Grade</dt><dd class="mt-1 font-display text-2xl">{{ $featuredStory->difficulty?->name ?? '—' }}</dd></div>
                        </dl>
                        <div class="mt-10 flex flex-wrap gap-3">
                            <a href="{{ url($featuredStory->publicPath()) }}" class="btn-primary">Discover the journey <x-heroicon-m-arrow-right class="size-4" /></a>
                            @if ($featuredStory->displayPrice())<span class="self-center text-stone-300">from <span class="font-display text-2xl text-white">US${{ number_format($featuredStory->displayPrice()) }}</span> pp</span>@endif
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- ============================== REGIONS ============================== --}}
    @if ($regions->isNotEmpty())
        <section class="py-24 lg:py-36" x-data="{ r: 0 }">
            <div class="container-page grid gap-14 lg:grid-cols-12">
                <div class="lg:col-span-5">
                    <x-site.section-heading eyebrow="Choose your mountains" title="Nepal, region by region" intro="Each valley has its own character — Sherpa villages and glaciers, rhododendron forests, Tibetan-Buddhist hidden valleys. Hover a region to explore." />
                    <ul class="mt-12 divide-y divide-stone-300/70 border-y border-stone-300/70">
                        @foreach ($regions as $k => $region)
                            <li>
                                <a href="{{ url($region->publicPath()) }}" class="group flex items-center justify-between gap-6 py-5"
                                   x-on:mouseenter="r = {{ $k }}" x-on:focus="r = {{ $k }}">
                                    <span class="flex items-baseline gap-4">
                                        <span class="data text-stone-400" x-bind:class="r === {{ $k }} && '!text-terracotta-500'">{{ str_pad($k + 1, 2, '0', STR_PAD_LEFT) }}</span>
                                        <span class="font-display text-3xl text-juniper-800 transition group-hover:translate-x-1 lg:text-4xl">{{ str_replace(' Region', '', $region->name) }}</span>
                                    </span>
                                    <span class="flex items-center gap-3 text-sm text-ink-soft">{{ $region->trips_count }} {{ str('journey')->plural($region->trips_count) }}
                                        <x-heroicon-m-arrow-up-right class="size-4 text-stone-400 transition group-hover:text-terracotta-500" /></span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="relative lg:col-span-7">
                    @foreach ($regions as $k => $region)
                        @php $media = $region->getFirstMedia('hero') ?? $region->trips->first(fn ($t) => $t->hasMedia('hero'))?->getFirstMedia('hero'); @endphp
                        <div class="lg:sticky lg:top-32" x-show="r === {{ $k }}" @if ($k > 0) x-cloak @endif
                             x-transition:enter="transition duration-700 ease-out" x-transition:enter-start="opacity-0 scale-[0.98]" x-transition:enter-end="opacity-100 scale-100">
                            <div class="relative aspect-[4/5] overflow-hidden img-organic sm:aspect-[5/4] lg:aspect-[4/5]">
                                <x-site.picture :media="$media" :alt="$region->name" sizes="(min-width: 1024px) 55vw, 100vw" class="h-full w-full" />
                                <div class="scrim-bottom absolute inset-0"></div>
                                <div class="absolute inset-x-0 bottom-0 p-8 text-white sm:p-10">
                                    @if ($region->tagline)<p class="eyebrow eyebrow-light">{{ $region->tagline }}</p>@endif
                                    <p class="mt-4 max-w-lg text-white/90">{{ str($region->intro ?? strip_tags((string) $region->description))->limit(220) }}</p>
                                    @if ($region->highlights)
                                        <ul class="mt-5 flex flex-wrap gap-2">
                                            @foreach ($region->highlights as $h)<li class="chip-light">{{ $h }}</li>@endforeach
                                        </ul>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            @php $abroad = $countries->where('slug', '!=', 'nepal')->where('trips_count', '>', 0); @endphp
            @if ($abroad->isNotEmpty())
                <div @class(['container-page mt-20 grid gap-5', 'md:grid-cols-2' => $abroad->count() === 2, 'md:grid-cols-3' => $abroad->count() >= 3])>
                    @foreach ($abroad as $country)
                        <a href="{{ url($country->publicPath()) }}" class="group flex items-center justify-between rounded-[1.75rem] bg-stone-200/60 p-7 transition hover:bg-juniper-800 hover:text-stone-100" data-reveal>
                            <span>
                                <span class="block text-xs font-semibold uppercase tracking-[0.18em] text-stone-500 group-hover:text-saffron-300">Beyond Nepal</span>
                                <span class="mt-2 block font-display text-3xl text-juniper-800 group-hover:text-stone-100">{{ $country->name }}</span>
                                <span class="mt-1 block text-sm text-ink-soft group-hover:text-stone-300">{{ $country->tagline ?? $country->trips_count.' journeys' }}</span>
                            </span>
                            <x-heroicon-m-arrow-up-right class="size-6 shrink-0 transition group-hover:translate-x-1 group-hover:-translate-y-1" />
                        </a>
                    @endforeach
                </div>
            @endif
        </section>
    @endif

    {{-- ============================== GROUP DEPARTURES ============================== --}}
    @if ($departures->isNotEmpty() || $fixedTrips->isNotEmpty())
        <section class="bg-stone-200/50 py-24 lg:py-32">
            <div class="container-page">
                <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                    <x-site.section-heading eyebrow="Fixed group departures" title="Travel solo, trek together" intro="Join a small, guaranteed group on set dates — shared costs, instant camaraderie and the same expert guiding as our private trips." />
                    <a href="{{ route('departures') }}" class="btn-dark shrink-0" data-reveal>All departure dates</a>
                </div>

                @if ($departures->isNotEmpty())
                    <div class="mt-14 divide-y divide-stone-300/70 overflow-hidden rounded-[2rem] bg-stone-50 ring-1 ring-stone-300/60" data-reveal>
                        @foreach ($departures as $d)
                            <a href="{{ url($d->trip->publicPath()) }}#departures" class="group grid items-center gap-4 p-5 transition hover:bg-white sm:grid-cols-[7rem_1fr_auto_auto] sm:gap-8 sm:p-6">
                                <div class="flex items-baseline gap-2 sm:block">
                                    <p class="font-display text-4xl leading-none text-juniper-800">{{ $d->start_date->format('d') }}</p>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">{{ $d->start_date->format('M Y') }}</p>
                                </div>
                                <div>
                                    <p class="font-display text-xl text-juniper-800 group-hover:underline">{{ $d->trip->title }}</p>
                                    <p class="text-sm text-ink-soft">{{ $d->trip->destination?->name }} · {{ $d->trip->durationLabel() }}</p>
                                </div>
                                <span class="chip w-max">{{ $d->seatsLeft() }} seats left</span>
                                <span class="font-display text-2xl text-juniper-800">{{ $d->effectivePrice() ? 'US$'.number_format($d->effectivePrice()) : 'On request' }}</span>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="mt-14 grid gap-x-8 gap-y-14 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($fixedTrips->take(3) as $trip)
                            <x-site.trip-card :trip="$trip" data-reveal />
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- ============================== WHY US ============================== --}}
    <section class="relative overflow-hidden py-24 lg:py-36">
        <div class="container-page grid gap-16 lg:grid-cols-12">
            <div class="lg:col-span-5">
                <x-site.section-heading eyebrow="Why Wonder Himalaya" title="We are the trusted partner in the Himalaya" />
                <div class="mt-10 flex items-center gap-5 rounded-[1.75rem] bg-stone-200/60 p-6" data-reveal>
                    <div class="flex size-16 shrink-0 items-center justify-center rounded-full bg-[#34E0A1]/90">
                        <svg viewBox="0 0 24 24" class="size-9" aria-hidden="true"><circle cx="7" cy="13" r="3.2" fill="none" stroke="#000" stroke-width="1.6"/><circle cx="17" cy="13" r="3.2" fill="none" stroke="#000" stroke-width="1.6"/><circle cx="7" cy="13" r="1" /><circle cx="17" cy="13" r="1" /><path d="M3 9.5h3.2C8 8 10 7.3 12 7.3S16 8 17.8 9.5H21l-1.4 1.6" fill="none" stroke="#000" stroke-width="1.6"/></svg>
                    </div>
                    <p class="text-ink-soft"><span class="block font-display text-xl text-juniper-800">Tripadvisor Travellers' Choice</span>Recognised by travellers, journey after journey.</p>
                </div>
                <a href="{{ url('/why-wonder-himalaya') }}" class="link-arrow mt-10" data-reveal>Why travel with us <x-heroicon-m-arrow-right class="size-4" /></a>
            </div>
            <div class="grid gap-x-10 gap-y-14 sm:grid-cols-2 lg:col-span-7">
                @foreach ([
                    ['Guided by experts', 'Licensed, first-aid trained local guides who grew up on these trails — and who know exactly where the hidden gems are.', 'M4 34 16 14l8 12 6-8 14 16Z'],
                    ['Sustainable travel', 'Fair wages and insurance for porters, local lodges and suppliers, and a firm leave-no-trace ethic on every trek.', 'M24 40c-8-4-14-11-14-20 7 0 12 3 14 9 2-6 7-9 14-9 0 9-6 16-14 20Z'],
                    ['Tailored to you', 'Every itinerary can be adjusted — extra acclimatisation days, a lodge upgrade, a helicopter return or a festival detour.', 'M8 24h32M24 8v32M13 13l22 22M35 13 13 35'],
                    ['Unrivalled hospitality', 'From airport pick-up to farewell dinner in Thamel, our team looks after you as family — 24/7 while you travel.', 'M10 22c0-8 6-12 14-12s14 4 14 12-6 16-14 18c-8-2-14-10-14-18Z'],
                ] as [$title, $text, $path])
                    <div data-reveal>
                        <svg viewBox="0 0 48 48" class="size-14 text-terracotta-500" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round" aria-hidden="true"><path d="{{ $path }}" /></svg>
                        <h3 class="mt-6 font-display text-2xl">{{ $title }}</h3>
                        <p class="mt-3 text-ink-soft">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============================== REVIEWS ============================== --}}
    @if ($testimonials->isNotEmpty())
        <section class="overflow-hidden bg-juniper-800 py-24 text-stone-100 lg:py-32" x-data="{ s: 0, n: {{ $testimonials->count() }} }">
            <div class="container-page">
                <div class="flex flex-col gap-8 md:flex-row md:items-end md:justify-between">
                    <x-site.section-heading light eyebrow="In their words" title="‘Memorable’, ‘exciting’, ‘unforgettable’" />
                    <div class="flex items-center gap-3" data-reveal>
                        <p class="mr-4 text-sm text-stone-300"><span class="font-display text-3xl text-white">{{ $reviews['google_label'] ?? 'Excellent' }}</span><br>{{ $reviews['google_count'] ?? 78 }} reviews on Google</p>
                        <button type="button" class="flex size-12 items-center justify-center rounded-full border border-white/25 hover:bg-white hover:text-juniper-900" x-on:click="s = (s - 1 + n) % n" aria-label="Previous review"><x-heroicon-m-arrow-left class="size-5" /></button>
                        <button type="button" class="flex size-12 items-center justify-center rounded-full border border-white/25 hover:bg-white hover:text-juniper-900" x-on:click="s = (s + 1) % n" aria-label="Next review"><x-heroicon-m-arrow-right class="size-5" /></button>
                    </div>
                </div>
                <div class="relative mt-16 min-h-[22rem] sm:min-h-[18rem]">
                    @foreach ($testimonials as $k => $t)
                        <figure class="absolute inset-0 grid gap-10 lg:grid-cols-12" x-show="s === {{ $k }}" @if ($k > 0) x-cloak @endif
                                x-transition:enter="transition duration-700 ease-out" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
                            <blockquote class="font-display text-2xl leading-snug text-white sm:text-3xl lg:col-span-9 lg:text-[2.4rem]">
                                <span class="text-saffron-300">“</span>{{ str($t->body)->limit(420) }}<span class="text-saffron-300">”</span>
                            </blockquote>
                            <figcaption class="lg:col-span-3 lg:border-l lg:border-white/15 lg:pl-8">
                                <div class="flex gap-0.5 text-saffron-300" aria-label="{{ $t->rating }} out of 5 stars">
                                    @for ($i = 0; $i < $t->rating; $i++)<x-heroicon-s-star class="size-5" />@endfor
                                </div>
                                <p class="mt-4 font-semibold text-white">{{ $t->author_name }}</p>
                                <p class="text-sm text-stone-300">{{ $t->author_country ?? ucfirst($t->source).' review' }}</p>
                            </figcaption>
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ============================== JOURNAL ============================== --}}
    @if ($posts->isNotEmpty())
        <section class="py-24 lg:py-32">
            <div class="container-page">
                <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                    <x-site.section-heading eyebrow="The Journal" title="Notes from the trail" />
                    <a href="{{ route('blog.index') }}" class="link-arrow shrink-0" data-reveal>All stories <x-heroicon-m-arrow-right class="size-4" /></a>
                </div>
                <div class="mt-14 grid gap-10 md:grid-cols-3">
                    @foreach ($posts as $post)
                        <x-site.post-card :post="$post" data-reveal />
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layouts.site>
