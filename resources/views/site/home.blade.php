@php
    $headline = [['Walk', false], ['among', false], ['giants.', true]];
    $tickerItems = array_merge(
        collect($regions)->map(fn ($r) => str_replace(' Region', '', $r->name))->all(),
        $countries->where('slug', '!=', 'nepal')->where('trips_count', '>', 0)->pluck('name')->all(),
    );
    $collage = $signature->take(3)->values();
@endphp
<x-layouts.site :seo="$seo" transparent>

    {{-- ============================== HERO ============================== --}}
    <section data-hero data-interval="7000" class="relative isolate flex h-[100svh] min-h-[42rem] max-h-[68rem] flex-col overflow-hidden bg-juniper-950 text-white">
        <div class="absolute inset-0 -z-10">
            @forelse ($heroSlides as $k => $slide)
                <div @class(['hero-slide absolute inset-0', 'is-active' => $k === 0])>
                    <x-site.picture :media="$slide['media']" :alt="$slide['title']" sizes="100vw" :eager="$k === 0" class="h-full w-full" />
                </div>
            @empty
                <x-site.picture class="h-full w-full" alt="The Himalaya" />
            @endforelse
        </div>
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_center,rgba(15,29,25,0.15)_0%,rgba(15,29,25,0.55)_75%)]"></div>
        <div class="scrim-hero absolute inset-0 -z-10"></div>

        <div class="container-page flex flex-1 flex-col items-center justify-center pt-28 text-center">
            <p class="eyebrow eyebrow-light animate-reveal justify-center">Nepal · Tibet · Bhutan</p>
            <h1 class="split mt-6 font-display text-[clamp(3.5rem,2rem+8vw,10rem)] leading-[0.92] font-light tracking-[-0.03em] text-white">
                @foreach ($headline as $i => [$word, $em])
                    <span class="w"><span style="--i: {{ $i }}" @class(['italic text-saffron-300' => $em])>{{ $word }}</span></span>
                @endforeach
            </h1>
            <p class="mt-7 max-w-xl text-lg text-white/85 animate-reveal [animation-delay:600ms] sm:text-xl">
                Treks, climbs and quiet journeys through the high Himalaya — designed and guided by a small Kathmandu team who call these mountains home.
            </p>

            <form action="{{ route('trips.index') }}" method="get" role="search"
                  class="mt-12 grid w-full max-w-3xl grid-cols-1 gap-1 rounded-[2rem] border border-white/20 bg-white/10 p-1.5 text-left shadow-[0_30px_80px_-20px_rgba(0,0,0,0.6)] backdrop-blur-xl animate-reveal [animation-delay:800ms] sm:grid-cols-[1fr_1fr_auto] sm:rounded-full">
                <label class="group flex flex-col rounded-[1.6rem] px-6 py-3 transition hover:bg-white/10 sm:rounded-full">
                    <span class="text-[0.65rem] font-semibold uppercase tracking-[0.22em] text-white/60">Where to</span>
                    <select name="destination" class="-ml-1 border-0 bg-transparent p-0 pr-8 font-display text-lg text-white focus:ring-0 [&>option]:text-ink">
                        <option value="">Anywhere in the Himalaya</option>
                        @foreach ($nav['destinations'] as $country)
                            <option value="{{ basename($country['url']) }}">{{ $country['name'] }}</option>
                            @foreach ($country['children'] as $region)<option value="{{ basename($region['url']) }}">&nbsp;&nbsp;{{ $region['name'] }}</option>@endforeach
                        @endforeach
                    </select>
                </label>
                <label class="group flex flex-col rounded-[1.6rem] px-6 py-3 transition hover:bg-white/10 sm:rounded-full sm:border-l sm:border-white/15">
                    <span class="text-[0.65rem] font-semibold uppercase tracking-[0.22em] text-white/60">Travel style</span>
                    <select name="activity" class="-ml-1 border-0 bg-transparent p-0 pr-8 font-display text-lg text-white focus:ring-0 [&>option]:text-ink">
                        <option value="">Any kind of journey</option>
                        @foreach ($nav['activities'] as $activity)<option value="{{ basename($activity['url']) }}">{{ $activity['name'] }}</option>@endforeach
                    </select>
                </label>
                <button type="submit" class="btn-primary !rounded-full !px-8 !py-4"><x-heroicon-m-magnifying-glass class="size-5" /> Explore</button>
            </form>
        </div>

        {{-- Slide captions + progress --}}
        <div class="container-page relative flex items-end justify-between gap-8 pb-8 lg:pb-10">
            <div class="min-w-0">
                @foreach ($heroSlides as $k => $slide)
                    <a href="{{ $slide['url'] }}" data-hero-caption @if ($k > 0) hidden @endif class="group block text-left">
                        <span class="block text-[0.65rem] font-semibold uppercase tracking-[0.22em] text-saffron-300">{{ $slide['eyebrow'] }}</span>
                        <span class="mt-1 flex items-center gap-2 font-display text-xl text-white">{{ $slide['title'] }} <x-heroicon-m-arrow-up-right class="size-4 transition group-hover:translate-x-0.5 group-hover:-translate-y-0.5" /></span>
                    </a>
                @endforeach
            </div>
            <a href="#intro" class="scroll-cue absolute left-1/2 hidden h-12 w-px -translate-x-1/2 overflow-hidden bg-white/20 md:block" aria-label="Scroll down"><span class="block h-1/2 w-px bg-white"></span></a>
            @if (count($heroSlides) > 1)
                <div class="flex shrink-0 gap-2" role="tablist" aria-label="Slides">
                    @foreach ($heroSlides as $k => $slide)
                        <button type="button" @class(['hero-progress h-[3px] w-10 overflow-hidden rounded-full bg-white/25 sm:w-16', 'is-active' => $k === 0]) aria-label="Show {{ $slide['title'] }}">
                            <span class="block h-full w-full rounded-full bg-white"></span>
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- ============================== TICKER ============================== --}}
    <div class="border-y border-white/5 bg-juniper-900 py-5 text-stone-200">
        <div class="marquee" style="--speed: 55s; --gap: 2.5rem">
            @for ($copy = 0; $copy < 2; $copy++)
                <div class="marquee__track items-center" @if ($copy) aria-hidden="true" @endif>
                    <span class="flex items-center gap-3 font-display text-2xl whitespace-nowrap text-white"><x-heroicon-s-trophy class="size-5 text-saffron-300" /> Tripadvisor {{ $reviews['tripadvisor_award'] ?? "Travellers' Choice" }}</span>
                    <span class="text-saffron-500">✦</span>
                    @foreach ($tickerItems as $item)
                        <span class="font-display text-2xl whitespace-nowrap italic">{{ $item }}</span>
                        <span class="text-saffron-500">✦</span>
                    @endforeach
                    <span class="flex items-center gap-3 font-display text-2xl whitespace-nowrap text-white"><x-heroicon-s-star class="size-5 text-saffron-300" /> {{ $reviews['google_label'] ?? 'Excellent' }} on Google · {{ $reviews['google_count'] ?? 78 }} reviews</span>
                    <span class="text-saffron-500">✦</span>
                    <span class="font-display text-2xl whitespace-nowrap">Locally owned in Thamel, Kathmandu</span>
                    <span class="text-saffron-500">✦</span>
                </div>
            @endfor
        </div>
    </div>

    {{-- ============================== INTRO + STATS ============================== --}}
    <section id="intro" class="grain relative scroll-mt-20 overflow-hidden py-24 lg:py-36">
        <div class="topo pointer-events-none absolute -top-40 -right-40 size-[60rem] opacity-60" aria-hidden="true"></div>
        <div class="container-page relative grid items-center gap-16 lg:grid-cols-12 lg:gap-10">
            {{-- Collage --}}
            <div class="relative mx-auto h-[30rem] w-full max-w-xl sm:h-[36rem] lg:col-span-6 lg:max-w-none">
                @if ($collage->count() >= 1)
                    <div class="absolute top-0 left-0 h-[78%] w-[62%] overflow-hidden rounded-[2rem] shadow-[0_40px_80px_-30px_rgba(15,29,25,0.5)]" data-clip>
                        <x-site.picture :model="$collage[0]" collection="hero" sizes="(min-width: 1024px) 30vw, 60vw" class="h-full w-full" />
                    </div>
                @endif
                @if ($collage->count() >= 2)
                    <div class="absolute right-0 bottom-0 h-[62%] w-[52%] overflow-hidden rounded-[2rem] border-[6px] border-stone-100 shadow-[0_40px_80px_-30px_rgba(15,29,25,0.55)]" data-parallax="-0.06">
                        <x-site.picture :model="$collage[1]" collection="hero" sizes="(min-width: 1024px) 25vw, 50vw" class="h-full w-full" />
                    </div>
                @endif
                {{-- Rotating badge --}}
                <div class="absolute top-[58%] left-[52%] flex size-32 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full bg-terracotta-500 text-white shadow-xl sm:size-36">
                    <svg viewBox="0 0 120 120" class="spin-slow absolute inset-0 h-full w-full" aria-hidden="true">
                        <defs><path id="badge-circle" d="M60 60m-46 0a46 46 0 1 1 92 0a46 46 0 1 1-92 0" /></defs>
                        <text font-size="10.5" letter-spacing="3.2" fill="currentColor" font-family="var(--font-inter)" font-weight="600"><textPath href="#badge-circle">WANDER ALWAYS · WONDER OFTEN · </textPath></text>
                    </svg>
                    <img src="{{ asset('images/brand/logo-badge-64.webp') }}" alt="" class="size-14 rounded-full bg-white p-0.5 sm:size-16">
                </div>
            </div>

            {{-- Copy --}}
            <div class="lg:col-span-5 lg:col-start-8" data-stagger>
                <p class="eyebrow">Welcome to Wonder Himalaya</p>
                <h2 class="mt-6 font-display text-display-md">A small Thamel team, a lifetime on these trails.</h2>
                <p class="mt-6 text-lg text-ink-soft">For years we have done one thing — taking people into the mountains and bringing them back with stories they'll tell for the rest of their lives.</p>
                <p class="mt-4 text-ink-soft">From the teahouses of the Everest region to the monastery corridors of Lhasa, from the rhododendron forests of the Annapurna Circuit to the fortress towns of Bhutan — this part of the world is what we know, and sharing it is what we do.</p>
                <div class="mt-10 flex flex-wrap items-center gap-6">
                    <a href="{{ url('/about-us') }}" class="btn-dark">Our story <x-heroicon-m-arrow-right class="size-4" /></a>
                    <a href="{{ route('team.index') }}" class="link-arrow">Meet the team</a>
                </div>
            </div>
        </div>

        @if ($stats)
            <dl class="container-page relative mt-24 grid grid-cols-2 gap-y-12 border-t border-stone-300/70 pt-14 lg:grid-cols-4" data-stagger>
                @foreach ($stats as $stat)
                    <div class="px-2 lg:border-l lg:border-stone-300/70 lg:px-8 lg:first:border-l-0 lg:first:pl-0">
                        <dd class="font-display text-[clamp(3rem,2rem+3vw,5rem)] leading-none text-juniper-800" data-count="{{ $stat['value'] }}">{{ $stat['value'] }}</dd>
                        <dt class="mt-3 text-sm font-medium uppercase tracking-[0.16em] text-stone-500">{{ $stat['label'] }}</dt>
                    </div>
                @endforeach
            </dl>
        @endif
    </section>

    {{-- ============================== TRAVEL BY FEELING ============================== --}}
    @if ($moods)
        <section class="pb-24 lg:pb-36">
            <div class="container-page flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                <x-site.section-heading eyebrow="Travel by feeling" title="How do you want the mountains to <em class='text-terracotta-500'>feel</em>?" />
                <a href="{{ route('trips.index') }}" class="link-arrow shrink-0" data-reveal>Every journey <x-heroicon-m-arrow-right class="size-4" /></a>
            </div>
            <div class="container-page mt-14">
                <div class="panels no-scrollbar -mx-5 flex snap-x snap-mandatory gap-4 overflow-x-auto px-5 pb-2 sm:-mx-8 sm:px-8 lg:mx-0 lg:overflow-visible lg:px-0 lg:pb-0" data-reveal>
                    @foreach ($moods as $k => $mood)
                        <a href="{{ $mood['url'] }}" class="group relative flex aspect-[3/4] w-[76vw] shrink-0 snap-start flex-col justify-end overflow-hidden rounded-[2rem] bg-juniper-900 text-white sm:w-[22rem] lg:aspect-auto lg:w-auto lg:shrink">
                            <x-site.picture :media="$mood['media']" :alt="$mood['title']" sizes="(min-width: 1024px) 40vw, 76vw" class="absolute inset-0 h-full w-full transition duration-[1.6s] ease-(--ease-mountain) group-hover:scale-105" />
                            <div class="absolute inset-0 bg-gradient-to-t from-juniper-950/90 via-juniper-950/25 to-juniper-950/10"></div>
                            <span class="data absolute top-5 right-5 rounded-full bg-white/15 px-3 py-1 backdrop-blur-md">{{ $mood['count'] }} {{ str('trip')->plural($mood['count']) }}</span>
                            {{-- Collapsed (desktop): vertical title --}}
                            <span class="panel-title-v absolute bottom-8 left-7 hidden rotate-180 font-display text-2xl whitespace-nowrap [writing-mode:vertical-rl] lg:block">{{ $mood['title'] }}</span>
                            <div class="panel-copy relative p-7 lg:p-9">
                                <span class="data text-saffron-300">0{{ $k + 1 }}</span>
                                <h3 class="mt-2 font-display text-4xl text-white lg:text-5xl">{{ $mood['title'] }}</h3>
                                <p class="mt-2 max-w-xs text-white/80">{{ $mood['line'] }}</p>
                                <span class="mt-6 inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-semibold text-juniper-900">Explore <x-heroicon-m-arrow-right class="size-4 transition group-hover:translate-x-1" /></span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ============================== SIGNATURE JOURNEYS (carousel) ============================== --}}
    @if ($signature->isNotEmpty())
        <section class="overflow-hidden bg-stone-200/50 py-24 lg:py-32" data-carousel>
            <div class="container-page flex flex-col gap-8 md:flex-row md:items-end md:justify-between">
                <x-site.section-heading eyebrow="Signature journeys" title="The trails our travellers love most" intro="Classics we've walked hundreds of times — and still can't get enough of." />
                <div class="flex shrink-0 items-center gap-3" data-reveal>
                    <button type="button" data-prev class="flex size-14 items-center justify-center rounded-full border border-juniper-800/20 text-juniper-800 transition hover:bg-juniper-800 hover:text-white disabled:opacity-30 disabled:hover:bg-transparent disabled:hover:text-juniper-800" aria-label="Previous journeys"><x-heroicon-m-arrow-left class="size-5" /></button>
                    <button type="button" data-next class="flex size-14 items-center justify-center rounded-full border border-juniper-800/20 text-juniper-800 transition hover:bg-juniper-800 hover:text-white disabled:opacity-30 disabled:hover:bg-transparent disabled:hover:text-juniper-800" aria-label="Next journeys"><x-heroicon-m-arrow-right class="size-5" /></button>
                </div>
            </div>

            <div data-track class="rail no-scrollbar mt-14 flex cursor-grab snap-x snap-mandatory gap-6 overflow-x-auto scroll-smooth pb-4 select-none active:cursor-grabbing" data-stagger>
                @foreach ($signature as $trip)
                    @php $price = $trip->displayPrice(); @endphp
                    <a href="{{ url($trip->publicPath()) }}" class="group relative flex aspect-[3/4] w-[80vw] shrink-0 snap-start flex-col justify-end overflow-hidden rounded-[2rem] bg-juniper-900 text-white sm:w-[24rem] lg:w-[26rem]" draggable="false">
                        <x-site.picture :model="$trip" collection="hero" sizes="(min-width: 1024px) 26rem, 80vw" class="pointer-events-none absolute inset-0 h-full w-full transition duration-[1.6s] ease-(--ease-mountain) group-hover:scale-[1.06]" />
                        <div class="absolute inset-0 bg-gradient-to-t from-juniper-950/95 via-juniper-950/30 to-transparent"></div>
                        <div class="absolute inset-x-0 top-0 flex items-start justify-between p-5">
                            <div class="flex flex-wrap gap-2">
                                @if ($trip->is_fixed_departure)<span class="chip-light">Group departure</span>@endif
                                @if ($trip->difficulty)<span class="chip-light">{{ $trip->difficulty->name }}</span>@endif
                            </div>
                            <span class="flex size-11 items-center justify-center rounded-full bg-white/15 backdrop-blur-md transition group-hover:bg-terracotta-500"><x-heroicon-m-arrow-up-right class="size-5" /></span>
                        </div>
                        <div class="relative p-7">
                            <p class="flex flex-wrap items-center gap-x-3 text-[0.7rem] font-semibold uppercase tracking-[0.18em] text-white/70">
                                @if ($trip->destination)<span>{{ str_replace(' Region', '', $trip->destination->name) }}</span>@endif
                                @if ($trip->duration_days)<span class="size-1 rounded-full bg-saffron-300"></span><span>{{ $trip->durationLabel() }}</span>@endif
                                @if ($trip->max_altitude_m)<span class="size-1 rounded-full bg-saffron-300"></span><span>{{ number_format($trip->max_altitude_m) }} m</span>@endif
                            </p>
                            <h3 class="mt-3 font-display text-3xl leading-[1.1] text-white">{{ $trip->title }}</h3>
                            <div class="mt-5 flex items-end justify-between border-t border-white/15 pt-4">
                                <span class="text-sm text-white/70">{{ $price ? 'From' : 'Price' }}</span>
                                <span class="font-display text-2xl">{{ $price ? 'US$'.number_format($price) : 'On request' }}</span>
                            </div>
                        </div>
                    </a>
                @endforeach
                <a href="{{ route('trips.index') }}" class="flex aspect-[3/4] w-[80vw] shrink-0 snap-start flex-col items-center justify-center gap-5 rounded-[2rem] border-2 border-dashed border-juniper-800/25 text-center text-juniper-800 transition hover:border-terracotta-500 hover:text-terracotta-500 sm:w-[24rem] lg:w-[26rem]">
                    <span class="flex size-20 items-center justify-center rounded-full bg-juniper-800 text-white"><x-heroicon-m-arrow-right class="size-7" /></span>
                    <span class="font-display text-3xl">All journeys</span>
                    <span class="text-sm text-ink-soft">{{ $nav['activities'] ? collect($nav['activities'])->sum('count') : '' }} ways into the mountains</span>
                </a>
            </div>

            <div class="container-page mt-10">
                <div class="h-px w-full overflow-hidden bg-juniper-800/15"><div data-progress class="h-full origin-left bg-juniper-800 transition-transform duration-300" style="transform: scaleX(0.1)"></div></div>
            </div>
        </section>
    @endif

    {{-- ============================== FEATURE TRIP (immersive) ============================== --}}
    @if ($featureTrip)
        <section class="relative isolate flex min-h-[44rem] items-end overflow-hidden bg-juniper-950 py-16 text-white lg:min-h-[52rem] lg:items-center lg:py-24">
            <div class="absolute inset-0 -z-10" data-parallax="0.12">
                <x-site.picture :model="$featureTrip" collection="hero" sizes="100vw" class="h-full w-full" />
            </div>
            <div class="absolute inset-0 -z-10 bg-gradient-to-r from-juniper-950/85 via-juniper-950/40 to-transparent"></div>
            <div class="container-page">
                <div class="max-w-xl rounded-[2rem] border border-white/15 bg-juniper-950/40 p-8 backdrop-blur-xl sm:p-12" data-stagger>
                    <p class="eyebrow eyebrow-light">{{ $isTripOfMonth ? 'Trip of the month' : 'Our top-selling trek' }}</p>
                    <h2 class="mt-6 font-display text-display-md text-white">{{ $featureTrip->title }}</h2>
                    <p class="mt-5 text-white/80">{{ str($featureTrip->excerpt)->limit(220) }}</p>
                    <dl class="mt-8 grid grid-cols-3 gap-4 border-y border-white/15 py-6">
                        <div><dt class="text-[0.65rem] uppercase tracking-[0.2em] text-white/55">Days</dt><dd class="mt-1 font-display text-3xl">{{ $featureTrip->duration_days ?? '—' }}</dd></div>
                        <div><dt class="text-[0.65rem] uppercase tracking-[0.2em] text-white/55">High point</dt><dd class="mt-1 font-display text-3xl">{{ $featureTrip->max_altitude_m ? number_format($featureTrip->max_altitude_m) : '—' }}<span class="text-base"> m</span></dd></div>
                        <div><dt class="text-[0.65rem] uppercase tracking-[0.2em] text-white/55">Grade</dt><dd class="mt-1 font-display text-3xl">{{ $featureTrip->difficulty?->name ?? '—' }}</dd></div>
                    </dl>
                    <div class="mt-8 flex flex-wrap items-center gap-5">
                        <a href="{{ url($featureTrip->publicPath()) }}" class="btn-primary">Discover the journey <x-heroicon-m-arrow-right class="size-4" /></a>
                        @if ($featureTrip->displayPrice())<span class="text-white/75">from <span class="font-display text-2xl text-white">US${{ number_format($featureTrip->displayPrice()) }}</span></span>@endif
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- ============================== REGIONS ============================== --}}
    @if ($regions->isNotEmpty())
        <section class="py-24 lg:py-36" x-data="{ r: 0 }">
            <div class="container-page grid gap-14 lg:grid-cols-12 lg:gap-10">
                <div class="lg:col-span-5">
                    <x-site.section-heading eyebrow="Choose your mountains" title="Nepal, valley by valley" intro="Sherpa villages and glaciers, rhododendron forests, Tibetan-Buddhist hidden valleys — each region has its own soul." />
                    <ol class="mt-12 border-t border-stone-300/70">
                        @foreach ($regions as $k => $region)
                            <li class="border-b border-stone-300/70">
                                <a href="{{ url($region->publicPath()) }}" class="group flex items-center gap-6 py-5" x-on:mouseenter="r = {{ $k }}" x-on:focus="r = {{ $k }}">
                                    <span class="data w-8 text-stone-400 transition" x-bind:class="r === {{ $k }} && '!text-terracotta-500'">{{ str_pad($k + 1, 2, '0', STR_PAD_LEFT) }}</span>
                                    <span class="flex-1">
                                        <span class="block font-display text-3xl text-juniper-800 transition-transform duration-500 ease-(--ease-mountain) lg:text-[2.6rem]" x-bind:class="r === {{ $k }} && 'translate-x-2 italic'">{{ str_replace(' Region', '', $region->name) }}</span>
                                        <span class="grid transition-[grid-template-rows] duration-500 ease-(--ease-mountain)" x-bind:class="r === {{ $k }} ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'">
                                            <span class="overflow-hidden"><span class="block pt-1 text-sm text-ink-soft">{{ $region->tagline }}</span></span>
                                        </span>
                                    </span>
                                    <span class="text-sm text-ink-soft">{{ $region->trips_count }}</span>
                                    <span class="flex size-10 items-center justify-center rounded-full border border-stone-300 transition group-hover:border-terracotta-500 group-hover:bg-terracotta-500 group-hover:text-white"><x-heroicon-m-arrow-up-right class="size-4" /></span>
                                </a>
                            </li>
                        @endforeach
                    </ol>
                </div>
                <div class="relative hidden lg:col-span-6 lg:col-start-7 lg:block">
                    <div class="sticky top-32 aspect-[4/5] overflow-hidden img-organic bg-juniper-900">
                        @foreach ($regions as $k => $region)
                            @php $media = $regionMedia[$region->id] ?? null; @endphp
                            <div class="absolute inset-0 transition-[opacity,clip-path] duration-[1.1s] ease-(--ease-mountain)"
                                 x-bind:style="r === {{ $k }} ? 'opacity:1; clip-path: inset(0 0 0 0)' : 'opacity:0; clip-path: inset(0 0 100% 0)'" @if ($k > 0) style="opacity:0" @endif>
                                <x-site.picture :media="$media" :alt="$region->name" sizes="45vw" class="h-full w-full" />
                                <div class="scrim-bottom absolute inset-0"></div>
                                <div class="absolute inset-x-0 bottom-0 p-10 text-white">
                                    <p class="max-w-md text-white/90">{{ str($region->intro ?? strip_tags((string) $region->description))->limit(180) }}</p>
                                    @if ($region->highlights)
                                        <ul class="mt-5 flex flex-wrap gap-2">@foreach ($region->highlights as $h)<li class="chip-light">{{ $h }}</li>@endforeach</ul>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            @php $abroad = $countries->where('slug', '!=', 'nepal')->where('trips_count', '>', 0)->values(); @endphp
            @if ($abroad->isNotEmpty())
                <div class="container-page mt-20">
                    <p class="eyebrow">Beyond Nepal</p>
                    <div @class(['mt-6 grid gap-4', 'md:grid-cols-2' => $abroad->count() === 2, 'md:grid-cols-3' => $abroad->count() >= 3]) data-stagger>
                        @foreach ($abroad as $country)
                            @php $cm = $country->getFirstMedia('hero') ?? $country->trips()->published()->whereHas('media')->with('media')->first()?->getFirstMedia('hero'); @endphp
                            <a href="{{ url($country->publicPath()) }}" class="group relative flex h-56 items-end overflow-hidden rounded-[1.75rem] bg-juniper-900 p-7 text-white">
                                <x-site.picture :media="$cm" :alt="$country->name" sizes="33vw" class="absolute inset-0 h-full w-full transition duration-[1.4s] group-hover:scale-105" />
                                <div class="absolute inset-0 bg-gradient-to-t from-juniper-950/85 to-juniper-950/10"></div>
                                <span class="relative">
                                    <span class="block font-display text-4xl">{{ $country->name }}</span>
                                    <span class="mt-1 block text-sm text-white/75">{{ $country->tagline ?? $country->trips_count.' journeys' }}</span>
                                </span>
                                <x-heroicon-m-arrow-up-right class="relative ml-auto size-6 shrink-0 transition group-hover:translate-x-1 group-hover:-translate-y-1" />
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>
    @endif

    {{-- ============================== GROUP DEPARTURES ============================== --}}
    @if ($departures->isNotEmpty() || $fixedTrips->isNotEmpty())
        <section class="relative overflow-hidden bg-juniper-800 py-24 text-stone-100 lg:py-32">
            <div class="topo pointer-events-none absolute inset-0 opacity-20 invert"></div>
            <div class="container-page relative">
                <div class="flex flex-col gap-8 md:flex-row md:items-end md:justify-between">
                    <x-site.section-heading light eyebrow="Fixed group departures" title="Travel solo, <em class='text-saffron-300'>trek together</em>" intro="Small, guaranteed groups on set dates — shared costs and the same expert guiding as our private journeys." />
                    <a href="{{ route('departures') }}" class="btn-ghost-light shrink-0" data-reveal>All departure dates</a>
                </div>

                <div class="mt-14 grid gap-5 md:grid-cols-2 xl:grid-cols-3" data-stagger>
                    @if ($departures->isNotEmpty())
                        @foreach ($departures as $d)
                            <a href="{{ url($d->trip->publicPath()) }}?departure={{ $d->id }}#enquire" class="group relative flex overflow-hidden rounded-[1.75rem] bg-stone-100 text-ink transition duration-500 hover:-translate-y-1 hover:shadow-[0_30px_60px_-25px_rgba(0,0,0,0.6)]">
                                <div class="flex w-28 shrink-0 flex-col items-center justify-center border-r-2 border-dashed border-stone-300 bg-saffron-300/30 px-3 py-6 text-center">
                                    <span class="text-xs font-semibold uppercase tracking-[0.2em] text-terracotta-600">{{ $d->start_date->format('M') }}</span>
                                    <span class="font-display text-5xl leading-none text-juniper-800">{{ $d->start_date->format('d') }}</span>
                                    <span class="mt-1 text-xs text-stone-500">{{ $d->start_date->format('Y') }}</span>
                                </div>
                                <div class="flex min-w-0 flex-1 flex-col p-6">
                                    <p class="text-[0.7rem] font-semibold uppercase tracking-[0.16em] text-stone-500">{{ $d->trip->destination?->name }} · {{ $d->trip->durationLabel() }}</p>
                                    <h3 class="mt-2 font-display text-xl leading-snug text-juniper-800 group-hover:underline">{{ $d->trip->title }}</h3>
                                    <div class="mt-auto flex items-end justify-between pt-5">
                                        <span @class(['rounded-full px-2.5 py-1 text-xs font-semibold', 'bg-saffron-300/60 text-ink' => $d->seatsLeft() <= 3, 'bg-juniper-100 text-juniper-800' => $d->seatsLeft() > 3])>{{ $d->seatsLeft() }} seats left</span>
                                        <span class="font-display text-2xl text-juniper-800">{{ $d->effectivePrice() ? 'US$'.number_format($d->effectivePrice()) : 'On request' }}</span>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    @else
                        @foreach ($fixedTrips as $trip)<x-site.trip-card :trip="$trip" class="[&_h3]:!text-white [&_p]:!text-stone-300" />@endforeach
                    @endif
                </div>
            </div>
        </section>
    @endif

    {{-- ============================== WHY US (bento) ============================== --}}
    <section class="py-24 lg:py-36">
        <div class="container-page">
            <x-site.section-heading eyebrow="Why Wonder Himalaya" title="The trusted partner in the Himalaya" align="center" />
            <div class="mt-16 grid gap-4 md:grid-cols-2 lg:grid-cols-12 lg:grid-rows-2" data-stagger>
                <div class="relative flex min-h-[24rem] flex-col justify-end overflow-hidden rounded-[2rem] bg-juniper-900 p-8 text-white md:col-span-2 lg:col-span-6 lg:row-span-2 lg:p-12">
                    @if ($collage->count() >= 3)
                        <x-site.picture :model="$collage[2]" collection="hero" sizes="50vw" class="absolute inset-0 h-full w-full opacity-70" />
                    @endif
                    <div class="absolute inset-0 bg-gradient-to-t from-juniper-950 via-juniper-950/40 to-transparent"></div>
                    <div class="relative">
                        <span class="inline-flex items-center gap-2 rounded-full bg-[#34E0A1] px-3 py-1.5 text-xs font-bold text-ink"><x-heroicon-s-trophy class="size-4" /> Tripadvisor</span>
                        <p class="mt-5 font-display text-4xl leading-tight lg:text-5xl">{{ $reviews['tripadvisor_award'] ?? "Travellers' Choice" }}</p>
                        <p class="mt-3 max-w-md text-white/80">Officially recognised by travellers — a true reflection of the confidence people place in us, journey after journey.</p>
                    </div>
                </div>
                @foreach ([
                    ['Guided by experts', 'Licensed, first-aid trained local guides who grew up on these trails.', 'user-group', 'bg-stone-200/70'],
                    ['Sustainable travel', 'Local guides and lodges, fair treatment of porters, and leave-no-trace on every trek.', 'globe-asia-australia', 'bg-saffron-300/35'],
                    ['Tailored to you', 'Extra acclimatisation, lodge upgrades, a heli return — every itinerary bends to you.', 'adjustments-horizontal', 'bg-glacier-100'],
                    ['24/7 hospitality', 'From airport pick-up to the farewell dinner, our Thamel office is always on call.', 'heart', 'bg-terracotta-500 text-white'],
                ] as [$title, $text, $icon, $tone])
                    <div class="flex min-h-[15rem] flex-col rounded-[2rem] p-8 lg:col-span-3 {{ $tone }}">
                        <x-dynamic-component :component="'heroicon-o-'.$icon" class="size-9 {{ str_contains($tone, 'text-white') ? 'text-white' : 'text-terracotta-500' }}" />
                        <h3 class="mt-auto pt-10 font-display text-2xl {{ str_contains($tone, 'text-white') ? '!text-white' : '' }}">{{ $title }}</h3>
                        <p class="mt-2 {{ str_contains($tone, 'text-white') ? 'text-white/85' : 'text-ink-soft' }}">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============================== REVIEWS (double marquee) ============================== --}}
    @if ($testimonials->isNotEmpty())
        @php $rows = $testimonials->count() >= 6 ? $testimonials->split(2) : collect([$testimonials]); @endphp
        <section class="overflow-hidden bg-stone-200/50 py-24 lg:py-32">
            <div class="container-page flex flex-col gap-8 md:flex-row md:items-end md:justify-between">
                <x-site.section-heading eyebrow="In their words" title="‘Memorable’, ‘exciting’, <em class='text-terracotta-500'>‘unforgettable’</em>" />
                <a href="{{ route('reviews') }}" class="flex items-center gap-4 rounded-full bg-stone-50 py-2 pr-6 pl-2 ring-1 ring-stone-300/60 transition hover:ring-juniper-500" data-reveal>
                    <span class="flex size-11 items-center justify-center rounded-full bg-white shadow-sm">
                        <svg viewBox="0 0 24 24" class="size-6" aria-hidden="true"><path fill="#4285F4" d="M23.5 12.3c0-.8-.1-1.6-.2-2.3H12v4.4h6.5a5.6 5.6 0 0 1-2.4 3.6v3h3.9c2.2-2.1 3.5-5.1 3.5-8.7z"/><path fill="#34A853" d="M12 24c3.2 0 6-1.1 7.9-2.9l-3.9-3c-1.1.7-2.4 1.2-4 1.2-3.1 0-5.7-2.1-6.6-4.9H1.4v3.1A12 12 0 0 0 12 24z"/><path fill="#FBBC05" d="M5.4 14.4a7.2 7.2 0 0 1 0-4.7V6.6h-4a12 12 0 0 0 0 10.8z"/><path fill="#EA4335" d="M12 4.8c1.8 0 3.3.6 4.6 1.8l3.4-3.4A12 12 0 0 0 1.4 6.6l4 3.1C6.3 6.9 8.9 4.8 12 4.8z"/></svg>
                    </span>
                    <span><span class="block font-display text-xl leading-none text-juniper-800">{{ $reviews['google_label'] ?? 'Excellent' }}</span><span class="text-xs text-ink-soft">{{ $reviews['google_count'] ?? 78 }} Google reviews · read all</span></span>
                </a>
            </div>
            <div class="mt-14 space-y-5">
                @foreach ($rows as $r => $row)
                    <div @class(['marquee', 'marquee--reverse' => $r === 1]) style="--speed: {{ 70 + $r * 15 }}s; --gap: 1.25rem">
                        @for ($copy = 0; $copy < 2; $copy++)
                            <div class="marquee__track" @if ($copy) aria-hidden="true" @endif>
                                @foreach ($row as $t)
                                    <figure class="flex w-[22rem] shrink-0 flex-col rounded-[1.75rem] bg-stone-50 p-7 ring-1 ring-stone-300/60 sm:w-[26rem]">
                                        <div class="flex gap-0.5 text-saffron-500">@for ($i = 0; $i < $t->rating; $i++)<x-heroicon-s-star class="size-4" />@endfor</div>
                                        <blockquote class="mt-4 line-clamp-5 text-ink-soft">“{{ $t->body }}”</blockquote>
                                        <figcaption class="mt-auto flex items-center gap-3 pt-6">
                                            <span class="flex size-10 items-center justify-center rounded-full bg-juniper-800 font-display text-stone-100">{{ str($t->author_name)->substr(0, 1)->upper() }}</span>
                                            <span><span class="block text-sm font-semibold text-juniper-800">{{ $t->author_name }}</span><span class="block text-xs text-stone-500">{{ $t->author_country ?? ucfirst($t->source).' review' }}</span></span>
                                        </figcaption>
                                    </figure>
                                @endforeach
                            </div>
                        @endfor
                    </div>
                @endforeach
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
                <div class="mt-14 grid gap-10 lg:grid-cols-12">
                    <x-site.post-card :post="$posts[0]" large class="lg:col-span-7" data-reveal />
                    <div class="grid gap-10 sm:grid-cols-2 lg:col-span-5 lg:grid-cols-1" data-stagger>
                        @foreach ($posts->slice(1) as $post)
                            <article class="group grid grid-cols-[8rem_1fr] items-center gap-5 sm:grid-cols-1 lg:grid-cols-[11rem_1fr]">
                                <a href="{{ url($post->publicPath()) }}" class="block aspect-square overflow-hidden rounded-[1.5rem] sm:aspect-[4/3] lg:aspect-square">
                                    <x-site.picture :model="$post" collection="hero" sizes="12rem" class="h-full w-full transition duration-[1.4s] group-hover:scale-105" />
                                </a>
                                <div>
                                    @if ($post->category)<p class="text-[0.7rem] font-semibold uppercase tracking-[0.18em] text-terracotta-500">{{ $post->category->name }}</p>@endif
                                    <h3 class="mt-2 font-display text-xl leading-snug text-juniper-800"><a href="{{ url($post->publicPath()) }}" class="group-hover:underline">{{ $post->title }}</a></h3>
                                    @if ($post->reading_minutes)<p class="mt-2 text-sm text-stone-500">{{ $post->reading_minutes }} min read</p>@endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif
</x-layouts.site>
