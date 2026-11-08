{{-- Site header: transparent over heroes, solid on scroll (data-scrolled set by app.js). --}}
@php $phone = $company['phone'] ?? '+977-01-5928066'; @endphp
<header
    data-site-header
    x-data="{ open: false, mega: null }"
    x-on:keydown.escape.window="open = false; mega = null"
    @class([
        'group/header fixed inset-x-0 top-0 z-50 transition-all duration-500 ease-(--ease-mountain)',
        'data-[scrolled]:bg-stone-100/92 data-[scrolled]:backdrop-blur-xl' => $transparent,
        'bg-stone-100/92 backdrop-blur-xl' => ! $transparent,
    ])
    x-bind:class="(open || mega) && '!bg-stone-100'"
>
    <div class="container-page flex h-20 items-center justify-between gap-6 lg:h-24">
        <a href="{{ route('home') }}" class="relative z-10" aria-label="Wonder Himalaya — home">
            <x-site.logo :light="$transparent" />
        </a>

        @php
            $linkBase = 'relative inline-flex items-center gap-1 whitespace-nowrap py-2 text-[0.95rem] font-medium transition-colors after:absolute after:inset-x-0 after:-bottom-0.5 after:h-px after:origin-left after:scale-x-0 after:bg-terracotta-500 after:transition-transform after:duration-300 hover:after:scale-x-100';
            $linkColor = $transparent ? 'text-white group-data-[scrolled]/header:text-juniper-800' : 'text-juniper-800';
        @endphp

        <nav class="hidden items-center gap-7 lg:flex xl:gap-9" aria-label="Main">
            <button type="button" class="{{ $linkBase }} {{ $linkColor }}" x-bind:class="mega && '!text-juniper-800'" x-on:click="mega = mega === 'destinations' ? null : 'destinations'" x-bind:aria-expanded="mega === 'destinations'">
                Destinations <x-heroicon-m-chevron-down class="size-4 transition" x-bind:class="mega === 'destinations' && 'rotate-180'" />
            </button>
            <button type="button" class="{{ $linkBase }} {{ $linkColor }}" x-bind:class="mega && '!text-juniper-800'" x-on:click="mega = mega === 'journeys' ? null : 'journeys'" x-bind:aria-expanded="mega === 'journeys'">
                Journeys <x-heroicon-m-chevron-down class="size-4 transition" x-bind:class="mega === 'journeys' && 'rotate-180'" />
            </button>
            <a href="{{ route('departures') }}" class="{{ $linkBase }} {{ $linkColor }}" x-bind:class="mega && '!text-juniper-800'">Group departures</a>
            <button type="button" class="{{ $linkBase }} {{ $linkColor }}" x-bind:class="mega && '!text-juniper-800'" x-on:click="mega = mega === 'about' ? null : 'about'" x-bind:aria-expanded="mega === 'about'">
                Why us <x-heroicon-m-chevron-down class="size-4 transition" x-bind:class="mega === 'about' && 'rotate-180'" />
            </button>
            <a href="{{ route('blog.index') }}" class="{{ $linkBase }} {{ $linkColor }}" x-bind:class="mega && '!text-juniper-800'">Journal</a>
        </nav>

        <div class="flex items-center gap-3">
            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="hidden items-center gap-2 whitespace-nowrap text-sm font-medium 2xl:inline-flex {{ $linkColor }}" x-bind:class="mega && '!text-juniper-800'">
                <x-heroicon-o-phone class="size-4" /> {{ $phone }}
            </a>
            <a href="{{ route('search') }}" class="hidden size-10 items-center justify-center rounded-full transition hover:bg-black/5 lg:inline-flex {{ $linkColor }}" x-bind:class="mega && '!text-juniper-800'" aria-label="Search"><x-heroicon-o-magnifying-glass class="size-5" /></a>
            <a href="{{ route('tailor-made') }}" class="btn-primary hidden whitespace-nowrap !px-5 !py-3 sm:inline-flex">Plan your journey</a>
            <button type="button" class="relative z-10 inline-flex size-11 items-center justify-center rounded-full lg:hidden {{ $transparent ? 'text-white group-data-[scrolled]/header:text-juniper-800' : 'text-juniper-800' }}" x-bind:class="open && '!text-juniper-800'" x-on:click="open = !open" x-bind:aria-expanded="open" aria-controls="mobile-menu">
                <span class="sr-only">Menu</span>
                <x-heroicon-o-bars-2 class="size-7" x-show="!open" />
                <x-heroicon-o-x-mark class="size-7" x-show="open" x-cloak />
            </button>
        </div>
    </div>

    {{-- Desktop mega menus --}}
    <div class="hidden lg:block" x-show="mega" x-cloak x-on:click.outside="mega = null"
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="border-t border-stone-300/60 bg-stone-100 shadow-[0_30px_60px_-30px_rgba(15,29,25,0.35)]">
            <div class="container-page py-12">
                <div x-show="mega === 'destinations'" class="grid grid-cols-12 gap-10">
                    <div class="col-span-3">
                        <p class="eyebrow">Where to</p>
                        <p class="mt-4 font-display text-3xl text-juniper-800">Four kingdoms of the high Himalaya.</p>
                        <a href="{{ route('trips.index') }}" class="link-arrow mt-6">All journeys <x-heroicon-m-arrow-right class="size-4" /></a>
                    </div>
                    <div class="col-span-9 grid grid-cols-3 gap-x-10 gap-y-8">
                        @foreach ($nav['destinations'] as $country)
                            <div>
                                <a href="{{ $country['url'] }}" class="font-display text-xl text-juniper-800 hover:text-terracotta-500">{{ $country['name'] }}</a>
                                @if ($country['children'])
                                    <ul class="mt-3 space-y-2">
                                        @foreach ($country['children'] as $region)
                                            <li><a href="{{ $region['url'] }}" class="group flex items-baseline justify-between gap-3 text-[0.95rem] text-ink-soft hover:text-juniper-800">
                                                <span>{{ $region['name'] }}</span><span class="data text-stone-400 group-hover:text-terracotta-500">{{ $region['count'] }}</span>
                                            </a></li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <div x-show="mega === 'journeys'" class="grid grid-cols-12 gap-10">
                    <div class="col-span-3">
                        <p class="eyebrow">How you travel</p>
                        <p class="mt-4 font-display text-3xl text-juniper-800">From tea-house trails to summit pushes.</p>
                        <a href="{{ route('tailor-made') }}" class="link-arrow mt-6">Design a private trip <x-heroicon-m-arrow-right class="size-4" /></a>
                    </div>
                    <ul class="col-span-9 grid grid-cols-3 gap-x-10 gap-y-4">
                        @foreach ($nav['activities'] as $activity)
                            <li><a href="{{ $activity['url'] }}" class="group flex items-center justify-between border-b border-stone-300/70 py-3 text-lg text-juniper-800 hover:border-terracotta-500">
                                <span class="font-display">{{ $activity['name'] }}</span>
                                <x-heroicon-m-arrow-up-right class="size-4 text-stone-400 transition group-hover:translate-x-0.5 group-hover:-translate-y-0.5 group-hover:text-terracotta-500" />
                            </a></li>
                        @endforeach
                        <li><a href="{{ route('trips.index', ['tier' => 'luxury']) }}" class="group flex items-center justify-between border-b border-stone-300/70 py-3 text-lg text-juniper-800 hover:border-terracotta-500">
                            <span class="font-display italic">Luxury journeys</span>
                            <x-heroicon-m-arrow-up-right class="size-4 text-stone-400 group-hover:text-terracotta-500" />
                        </a></li>
                    </ul>
                </div>

                <div x-show="mega === 'about'" class="grid grid-cols-12 gap-10">
                    <div class="col-span-3">
                        <p class="eyebrow">Why Wonder Himalaya</p>
                        <p class="mt-4 font-display text-3xl text-juniper-800">A small Thamel team who know these trails by name.</p>
                    </div>
                    <div class="col-span-9 grid grid-cols-3 gap-10">
                        <ul class="space-y-3 text-[0.95rem]">
                            <li class="mb-1 text-xs font-semibold uppercase tracking-[0.2em] text-stone-500">Our story</li>
                            <li><a class="hover:text-terracotta-500" href="{{ url('/about-us') }}">About us</a></li>
                            <li><a class="hover:text-terracotta-500" href="{{ route('team.index') }}">Meet the team</a></li>
                            <li><a class="hover:text-terracotta-500" href="{{ url('/why-wonder-himalaya') }}">Why travel with us</a></li>
                            <li><a class="hover:text-terracotta-500" href="{{ url('/sustainable-tourism') }}">Sustainable tourism</a></li>
                            <li><a class="hover:text-terracotta-500" href="{{ route('reviews') }}">Traveller reviews</a></li>
                            <li><a class="hover:text-terracotta-500" href="{{ route('b2b') }}">For travel partners (B2B)</a></li>
                        </ul>
                        <ul class="col-span-2 grid grid-cols-2 gap-x-10 gap-y-3 text-[0.95rem]">
                            <li class="col-span-2 mb-1 text-xs font-semibold uppercase tracking-[0.2em] text-stone-500"><a href="{{ url('/travel-guide') }}" class="hover:text-terracotta-500">Travel guide →</a></li>
                            @foreach ($nav['guides'] as $guide)
                                <li><a class="hover:text-terracotta-500" href="{{ $guide['url'] }}">{{ $guide['name'] }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Mobile drawer --}}
    <div id="mobile-menu" class="fixed inset-0 top-20 z-40 overflow-y-auto bg-stone-100 lg:hidden" x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
        <nav class="container-page flex flex-col gap-2 py-8" aria-label="Mobile">
            <div x-data="{ s: null }" class="divide-y divide-stone-300/70">
                <div class="py-4">
                    <button type="button" class="flex w-full items-center justify-between font-display text-2xl text-juniper-800" x-on:click="s = s === 'd' ? null : 'd'">Destinations <x-heroicon-m-plus class="size-5" /></button>
                    <div x-show="s === 'd'" x-collapse class="mt-4 space-y-4">
                        @foreach ($nav['destinations'] as $country)
                            <div>
                                <a href="{{ $country['url'] }}" class="font-semibold text-juniper-800">{{ $country['name'] }}</a>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach ($country['children'] as $region)
                                        <a href="{{ $region['url'] }}" class="chip">{{ $region['name'] }}</a>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="py-4">
                    <button type="button" class="flex w-full items-center justify-between font-display text-2xl text-juniper-800" x-on:click="s = s === 'j' ? null : 'j'">Journeys <x-heroicon-m-plus class="size-5" /></button>
                    <div x-show="s === 'j'" x-collapse class="mt-4 flex flex-wrap gap-2">
                        @foreach ($nav['activities'] as $activity)
                            <a href="{{ $activity['url'] }}" class="chip">{{ $activity['name'] }}</a>
                        @endforeach
                    </div>
                </div>
                <a href="{{ route('departures') }}" class="block py-4 font-display text-2xl text-juniper-800">Group departures</a>
                <a href="{{ url('/about-us') }}" class="block py-4 font-display text-2xl text-juniper-800">About us</a>
                <a href="{{ route('blog.index') }}" class="block py-4 font-display text-2xl text-juniper-800">Journal</a>
                <a href="{{ route('contact') }}" class="block py-4 font-display text-2xl text-juniper-800">Contact</a>
            </div>
            <a href="{{ route('tailor-made') }}" class="btn-primary mt-6">Plan your journey</a>
            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="btn-outline">Call {{ $phone }}</a>
        </nav>
    </div>
    {{-- Ridge-line edge: the solid header ends in a mountain skyline; the terracotta line traces reading progress. --}}
    <svg viewBox="0 0 1600 12" preserveAspectRatio="none" aria-hidden="true" @class([
        'header-ridge pointer-events-none absolute inset-x-0 top-full h-3 w-full transition-opacity duration-500',
        'opacity-0 group-data-[scrolled]/header:opacity-100' => $transparent,
    ]) x-bind:class="(open || mega) && '!opacity-0'">
        <path d="M0,7.3 L16,7.9 L32,8.9 L48,8.5 L64,8.0 L80,8.1 L96,8.5 L112,7.8 L128,7.5 L144,6.5 L160,7.5 L176,7.6 L192,8.1 L208,8.6 L224,9.3 L240,10.9 L256,10.4 L272,9.6 L288,8.5 L304,5.9 L320,4.6 L336,3.9 L352,3.0 L368,2.6 L384,2.4 L400,1.0 L416,2.6 L432,3.5 L448,4.0 L464,4.0 L480,5.0 L496,4.4 L512,5.8 L528,5.3 L544,4.1 L560,3.1 L576,4.1 L592,3.5 L608,4.5 L624,5.3 L640,5.9 L656,7.0 L672,8.8 L688,9.7 L704,10.0 L720,10.7 L736,11 L752,9.6 L768,8.8 L784,8.9 L800,7.2 L816,6.9 L832,6.1 L848,5.6 L864,7.0 L880,5.7 L896,6.6 L912,6.6 L928,7.6 L944,6.7 L960,6.4 L976,6.3 L992,4.3 L1008,3.6 L1024,3.1 L1040,1 L1056,1 L1072,1 L1088,1.5 L1104,2.1 L1120,2.4 L1136,3.9 L1152,4.8 L1168,6.3 L1184,6.2 L1200,7.8 L1216,8.1 L1232,7.9 L1248,7.0 L1264,7.1 L1280,5.0 L1296,5.7 L1312,7.5 L1328,7.8 L1344,8.1 L1360,10.2 L1376,10.5 L1392,11 L1408,10.7 L1424,10.3 L1440,10.1 L1456,8.4 L1472,7.5 L1488,7.3 L1504,4.7 L1520,3.1 L1536,2.5 L1552,2.5 L1568,3.9 L1584,3.5 L1600,3.9 L1600,0 L0,0 Z" fill="#f4efe6" fill-opacity=".92" />
        <path d="M0,7.3 L16,7.9 L32,8.9 L48,8.5 L64,8.0 L80,8.1 L96,8.5 L112,7.8 L128,7.5 L144,6.5 L160,7.5 L176,7.6 L192,8.1 L208,8.6 L224,9.3 L240,10.9 L256,10.4 L272,9.6 L288,8.5 L304,5.9 L320,4.6 L336,3.9 L352,3.0 L368,2.6 L384,2.4 L400,1.0 L416,2.6 L432,3.5 L448,4.0 L464,4.0 L480,5.0 L496,4.4 L512,5.8 L528,5.3 L544,4.1 L560,3.1 L576,4.1 L592,3.5 L608,4.5 L624,5.3 L640,5.9 L656,7.0 L672,8.8 L688,9.7 L704,10.0 L720,10.7 L736,11 L752,9.6 L768,8.8 L784,8.9 L800,7.2 L816,6.9 L832,6.1 L848,5.6 L864,7.0 L880,5.7 L896,6.6 L912,6.6 L928,7.6 L944,6.7 L960,6.4 L976,6.3 L992,4.3 L1008,3.6 L1024,3.1 L1040,1 L1056,1 L1072,1 L1088,1.5 L1104,2.1 L1120,2.4 L1136,3.9 L1152,4.8 L1168,6.3 L1184,6.2 L1200,7.8 L1216,8.1 L1232,7.9 L1248,7.0 L1264,7.1 L1280,5.0 L1296,5.7 L1312,7.5 L1328,7.8 L1344,8.1 L1360,10.2 L1376,10.5 L1392,11 L1408,10.7 L1424,10.3 L1440,10.1 L1456,8.4 L1472,7.5 L1488,7.3 L1504,4.7 L1520,3.1 L1536,2.5 L1552,2.5 L1568,3.9 L1584,3.5 L1600,3.9" fill="none" stroke="#1f3a33" stroke-opacity=".12" vector-effect="non-scaling-stroke" />
        <path class="header-ridge__progress" d="M0,7.3 L16,7.9 L32,8.9 L48,8.5 L64,8.0 L80,8.1 L96,8.5 L112,7.8 L128,7.5 L144,6.5 L160,7.5 L176,7.6 L192,8.1 L208,8.6 L224,9.3 L240,10.9 L256,10.4 L272,9.6 L288,8.5 L304,5.9 L320,4.6 L336,3.9 L352,3.0 L368,2.6 L384,2.4 L400,1.0 L416,2.6 L432,3.5 L448,4.0 L464,4.0 L480,5.0 L496,4.4 L512,5.8 L528,5.3 L544,4.1 L560,3.1 L576,4.1 L592,3.5 L608,4.5 L624,5.3 L640,5.9 L656,7.0 L672,8.8 L688,9.7 L704,10.0 L720,10.7 L736,11 L752,9.6 L768,8.8 L784,8.9 L800,7.2 L816,6.9 L832,6.1 L848,5.6 L864,7.0 L880,5.7 L896,6.6 L912,6.6 L928,7.6 L944,6.7 L960,6.4 L976,6.3 L992,4.3 L1008,3.6 L1024,3.1 L1040,1 L1056,1 L1072,1 L1088,1.5 L1104,2.1 L1120,2.4 L1136,3.9 L1152,4.8 L1168,6.3 L1184,6.2 L1200,7.8 L1216,8.1 L1232,7.9 L1248,7.0 L1264,7.1 L1280,5.0 L1296,5.7 L1312,7.5 L1328,7.8 L1344,8.1 L1360,10.2 L1376,10.5 L1392,11 L1408,10.7 L1424,10.3 L1440,10.1 L1456,8.4 L1472,7.5 L1488,7.3 L1504,4.7 L1520,3.1 L1536,2.5 L1552,2.5 L1568,3.9 L1584,3.5 L1600,3.9" pathLength="1" fill="none" stroke="#b5532e" stroke-width="1.5" vector-effect="non-scaling-stroke" />
    </svg>
</header>
