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
    x-bind:class="{ '!bg-stone-100': open || mega, 'is-menu-open': open }"
    x-effect="document.documentElement.classList.toggle('overflow-hidden', open)"
>
    <div class="container-page flex h-20 items-center justify-between gap-6 lg:h-24">
        <a href="{{ route('home') }}" class="relative z-10" aria-label="Wonder Himalaya — home" data-logo>
            <x-site.logo :light="$transparent" />
        </a>

        @php
            $linkBase = 'relative inline-flex items-center gap-1 whitespace-nowrap py-2 text-[0.95rem] font-medium transition-colors after:absolute after:inset-x-0 after:-bottom-0.5 after:h-px after:origin-left after:scale-x-0 after:bg-terracotta-500 after:transition-transform after:duration-300 hover:after:scale-x-100';
            $linkColor = $transparent ? 'text-white group-data-[scrolled]/header:text-navy-800' : 'text-navy-800';
        @endphp

        <nav class="hidden items-center gap-7 lg:flex xl:gap-9" aria-label="Main">
            <button type="button" class="{{ $linkBase }} {{ $linkColor }}" x-bind:class="mega && '!text-navy-800'" x-on:click="mega = mega === 'destinations' ? null : 'destinations'" x-on:mouseenter="mega && (mega = 'destinations')" x-bind:aria-expanded="mega === 'destinations'">
                Destinations <x-heroicon-m-chevron-down class="size-4 transition" x-bind:class="mega === 'destinations' && 'rotate-180'" />
            </button>
            <button type="button" class="{{ $linkBase }} {{ $linkColor }}" x-bind:class="mega && '!text-navy-800'" x-on:click="mega = mega === 'journeys' ? null : 'journeys'" x-on:mouseenter="mega && (mega = 'journeys')" x-bind:aria-expanded="mega === 'journeys'">
                Journeys <x-heroicon-m-chevron-down class="size-4 transition" x-bind:class="mega === 'journeys' && 'rotate-180'" />
            </button>
            <a href="{{ route('departures') }}" class="{{ $linkBase }} {{ $linkColor }}" x-bind:class="mega && '!text-navy-800'">Group departures</a>
            <button type="button" class="{{ $linkBase }} {{ $linkColor }}" x-bind:class="mega && '!text-navy-800'" x-on:click="mega = mega === 'about' ? null : 'about'" x-on:mouseenter="mega && (mega = 'about')" x-bind:aria-expanded="mega === 'about'">
                Why us <x-heroicon-m-chevron-down class="size-4 transition" x-bind:class="mega === 'about' && 'rotate-180'" />
            </button>
            <a href="{{ route('blog.index') }}" class="{{ $linkBase }} {{ $linkColor }}" x-bind:class="mega && '!text-navy-800'">Journal</a>
        </nav>

        <div class="flex items-center gap-3">
            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="hidden items-center gap-2 whitespace-nowrap text-sm font-medium 2xl:inline-flex {{ $linkColor }}" x-bind:class="mega && '!text-navy-800'">
                <x-heroicon-o-phone class="size-4" /> {{ $phone }}
            </a>
            <a href="{{ route('search') }}" class="hidden size-10 items-center justify-center rounded-full transition hover:bg-black/5 lg:inline-flex {{ $linkColor }}" x-bind:class="mega && '!text-navy-800'" aria-label="Search"><x-heroicon-o-magnifying-glass class="size-5" /></a>
            <a href="{{ route('tailor-made') }}" class="btn-primary hidden whitespace-nowrap !px-5 !py-3 sm:inline-flex">Plan your journey</a>
            <button type="button" class="relative z-10 inline-flex size-11 items-center justify-center rounded-full lg:hidden {{ $transparent ? 'text-white group-data-[scrolled]/header:text-navy-800' : 'text-navy-800' }}" x-bind:class="open && '!text-navy-800'" x-on:click="open = !open" x-bind:aria-expanded="open" aria-controls="mobile-menu">
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
        <div class="border-t border-stone-300/60 bg-stone-100 shadow-[0_30px_60px_-30px_rgba(1,9,30,0.35)]">
            <div class="container-page py-12">
                @php
                    $nepal = collect($nav['destinations'])->first(fn ($c) => $c['children'] !== []);
                    $beyond = collect($nav['destinations'])->reject(fn ($c) => $c === $nepal)->values();
                @endphp
                <div x-show="mega === 'destinations'" class="mega-panel grid grid-cols-12 gap-8 xl:gap-12">
                    @if ($nepal)
                        <div class="col-span-8 xl:col-span-9">
                            <div class="mega-item flex items-end justify-between gap-6 border-b border-stone-300/70 pb-5" style="--i: 0">
                                <div>
                                    <p class="eyebrow">Our home ground</p>
                                    <a href="{{ $nepal['url'] }}" class="group mt-3 flex items-baseline gap-4">
                                        <span class="font-display text-4xl text-navy-800 transition-colors group-hover:text-terracotta-500">{{ $nepal['name'] }}</span>
                                        <span class="data text-xs text-stone-500">{{ $nepal['count'] }} {{ str('journey')->plural($nepal['count']) }} · {{ count($nepal['children']) }} regions</span>
                                    </a>
                                </div>
                                <a href="{{ route('trips.index', ['destination' => str($nepal['url'])->afterLast('/')->toString()]) }}" class="link-arrow shrink-0">All Nepal journeys <x-heroicon-m-arrow-right class="size-4" /></a>
                            </div>
                            <ul class="mt-6 grid grid-cols-4 gap-4">
                                @foreach ($nepal['children'] as $region)
                                    <li class="mega-item" style="--i: {{ $loop->iteration }}">
                                        <a href="{{ $region['url'] }}" class="group relative block aspect-[16/11] overflow-hidden rounded-2xl bg-navy-900 outline-offset-4 focus-visible:outline-2 focus-visible:outline-terracotta-500">
                                            @if ($region['image'])
                                                <img src="{{ $region['image'] }}" alt="" loading="lazy" class="absolute inset-0 size-full object-cover opacity-90 transition duration-[1200ms] ease-(--ease-mountain) group-hover:scale-110 group-hover:opacity-100">
                                            @else
                                                {{-- No photo yet: an illustrated range so the tile still reads as a place. --}}
                                                <svg viewBox="0 0 160 110" preserveAspectRatio="xMidYMax slice" aria-hidden="true" class="absolute inset-0 size-full transition duration-[1200ms] ease-(--ease-mountain) group-hover:scale-110">
                                                    <rect width="160" height="110" fill="#082047" />
                                                    <circle cx="122" cy="26" r="9" fill="#ecc77e" opacity=".55" />
                                                    <path d="M0 78 22 52l14 12 24-34 22 26 16-14 26 30 16-10 20 20v28H0Z" fill="#9db8c2" opacity=".35" />
                                                    <path d="m60 30-7 11 5-2 4 3 4-4 3 3Z" fill="#fbf9f5" opacity=".7" />
                                                    <path d="M0 92 28 70l18 12 30-26 26 22 20-10 38 24v18H0Z" fill="#021131" />
                                                </svg>
                                            @endif
                                            <span class="absolute inset-0 bg-gradient-to-t from-navy-950/90 via-navy-950/25 to-transparent"></span>
                                            <span class="absolute inset-x-0 bottom-0 flex items-end justify-between gap-2 p-4 text-white">
                                                <span>
                                                    <span class="block font-display text-xl leading-tight">{{ str_replace(' Region', '', $region['name']) }}</span>
                                                    <span class="data mt-1 block text-[0.68rem] text-white/70">{{ $region['count'] ? $region['count'].' '.str('journey')->plural($region['count']) : 'Made to order' }}</span>
                                                </span>
                                                <span class="grid size-8 flex-none translate-x-2 place-items-center rounded-full bg-white/15 opacity-0 backdrop-blur transition duration-500 group-hover:translate-x-0 group-hover:opacity-100"><x-heroicon-m-arrow-up-right class="size-4" /></span>
                                            </span>
                                        </a>
                                    </li>
                                @endforeach
                                @if (count($nepal['children']) % 4 !== 0)
                                    <li class="mega-item" style="--i: {{ count($nepal['children']) + 1 }}">
                                        <a href="{{ route('tailor-made') }}" class="group relative flex aspect-[16/11] flex-col justify-between overflow-hidden rounded-2xl bg-terracotta-500 p-4 text-white outline-offset-4 focus-visible:outline-2 focus-visible:outline-navy-800">
                                            <x-site.gear icon="map" class="absolute -right-4 -bottom-4 w-28 text-white/20 transition duration-700 group-hover:-rotate-6 group-hover:scale-110" />
                                            <span class="text-xs font-semibold tracking-[0.18em] text-white/80 uppercase">Not sure where?</span>
                                            <span class="relative font-display text-xl leading-tight">Let us shape a route around you <x-heroicon-m-arrow-right class="inline size-4 transition group-hover:translate-x-1" /></span>
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    @endif
                    <div @class(['flex flex-col', 'col-span-4 border-l border-stone-300/70 pl-8 xl:col-span-3 xl:pl-12' => $nepal, 'col-span-12' => ! $nepal])>
                        <div class="mega-item" style="--i: 1"><p class="eyebrow">Beyond Nepal</p></div>
                        <ul class="mt-6 space-y-3">
                            @foreach ($beyond as $country)
                                <li class="mega-item" style="--i: {{ $loop->iteration + 2 }}">
                                    <a href="{{ $country['url'] }}" class="group flex items-center gap-4 rounded-2xl p-2 -m-2 transition hover:bg-stone-200/70 focus-visible:outline-2 focus-visible:outline-terracotta-500">
                                        <span class="relative size-20 flex-none overflow-hidden rounded-xl bg-navy-900">
                                            @if ($country['image'])<img src="{{ $country['image'] }}" alt="" loading="lazy" class="size-full object-cover transition duration-700 group-hover:scale-110">@endif
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block font-display text-2xl text-navy-800 transition-colors group-hover:text-terracotta-500">{{ $country['name'] }}</span>
                                            @if ($country['tagline'])<span class="line-clamp-2 text-sm leading-snug text-ink-soft">{{ $country['tagline'] }}</span>@endif
                                            <span class="data mt-0.5 block text-[0.68rem] text-stone-500">{{ $country['count'] }} {{ str('journey')->plural($country['count']) }}</span>
                                        </span>
                                        <x-heroicon-m-arrow-up-right class="size-4 flex-none text-stone-400 transition group-hover:translate-x-0.5 group-hover:-translate-y-0.5 group-hover:text-terracotta-500" />
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                        <div class="mega-item mt-auto space-y-3 border-t border-stone-300/70 pt-5 text-[0.95rem]" style="--i: 6">
                            <a href="{{ route('trips.index') }}" class="link-arrow">Every journey <x-heroicon-m-arrow-right class="size-4" /></a>
                            <a href="{{ route('departures') }}" class="flex items-center gap-2 text-ink-soft hover:text-navy-800"><x-heroicon-o-calendar-days class="size-4" /> Fixed group departures</a>
                        </div>
                    </div>
                </div>

                <div x-show="mega === 'journeys'" class="mega-panel grid grid-cols-12 gap-10">
                    <div class="col-span-3">
                        <p class="eyebrow">How you travel</p>
                        <p class="mt-4 font-display text-3xl text-navy-800">From tea-house trails to summit pushes.</p>
                        <a href="{{ route('tailor-made') }}" class="link-arrow mt-6">Design a private trip <x-heroicon-m-arrow-right class="size-4" /></a>
                    </div>
                    <ul class="col-span-9 grid grid-cols-3 gap-x-10 gap-y-4">
                        @foreach ($nav['activities'] as $activity)
                            <li class="mega-item" style="--i: {{ $loop->iteration }}"><a href="{{ $activity['url'] }}" class="group flex items-center justify-between border-b border-stone-300/70 py-3 text-lg text-navy-800 hover:border-terracotta-500">
                                <span class="font-display">{{ $activity['name'] }}</span>
                                <x-heroicon-m-arrow-up-right class="size-4 text-stone-400 transition group-hover:translate-x-0.5 group-hover:-translate-y-0.5 group-hover:text-terracotta-500" />
                            </a></li>
                        @endforeach
                        <li><a href="{{ route('trips.index', ['tier' => 'luxury']) }}" class="group flex items-center justify-between border-b border-stone-300/70 py-3 text-lg text-navy-800 hover:border-terracotta-500">
                            <span class="font-display italic">Luxury journeys</span>
                            <x-heroicon-m-arrow-up-right class="size-4 text-stone-400 group-hover:text-terracotta-500" />
                        </a></li>
                    </ul>
                </div>

                <div x-show="mega === 'about'" class="mega-panel grid grid-cols-12 gap-10">
                    <div class="col-span-3">
                        <p class="eyebrow">Why Wonder Himalaya</p>
                        <p class="mt-4 font-display text-3xl text-navy-800">A small Thamel team who know these trails by name.</p>
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

    {{-- Page dimmer under an open mega menu (absolute: the blurred header is the containing block). --}}
    <div class="absolute inset-x-0 top-full -z-10 hidden h-screen bg-navy-950/30 backdrop-blur-[2px] lg:block" x-show="mega" x-cloak
         x-transition:enter="transition-opacity duration-500" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

    {{-- Mobile drawer: scrolls on its own (page scroll is locked), items rise in staggered, any link tap closes it. --}}
    @php
        $email = $company['email'] ?? 'info@wonderhimalaya.com';
        $wa = preg_replace('/\D/', '', $company['whatsapp'] ?? '');
        $drawerHead = 'flex w-full items-center justify-between py-4 text-left font-display text-[1.65rem] leading-tight text-navy-800';
        $drawerLink = 'block py-4 font-display text-[1.65rem] leading-tight text-navy-800';
        $whyLinks = [
            ['About us', url('/about-us')],
            ['Meet the team', route('team.index')],
            ['Why travel with us', url('/why-wonder-himalaya')],
            ['Sustainable tourism', url('/sustainable-tourism')],
            ['Traveller reviews', route('reviews')],
            ['For travel partners (B2B)', route('b2b')],
            ['Travel guide', url('/travel-guide')],
        ];
    @endphp
    <div id="mobile-menu" class="fixed inset-0 top-20 z-40 overflow-y-auto overscroll-contain bg-stone-100 lg:hidden" x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-on:click="$event.target.closest('a') && (open = false)">
        <nav class="container-page flex flex-col pt-4 pb-[max(2rem,env(safe-area-inset-bottom))]" aria-label="Mobile">
            <div x-data="{ s: null }" class="divide-y divide-stone-300/70">
                <div class="drawer-item" style="--i: 0">
                    <button type="button" class="{{ $drawerHead }}" x-on:click="s = s === 'd' ? null : 'd'" x-bind:aria-expanded="s === 'd'">Destinations <x-heroicon-m-plus class="size-5 transition-transform duration-300" x-bind:class="s === 'd' && 'rotate-45'" /></button>
                    <div x-show="s === 'd'" x-collapse>
                        <div class="space-y-4 pb-5">
                            @foreach ($nav['destinations'] as $country)
                                <div>
                                    <a href="{{ $country['url'] }}" class="inline-block py-1 font-semibold text-navy-800">{{ $country['name'] }}</a>
                                    @if ($country['children'])
                                        <div class="mt-1.5 flex flex-wrap gap-2">
                                            @foreach ($country['children'] as $region)
                                                <a href="{{ $region['url'] }}" class="chip !py-2 !text-[0.8rem]">{{ $region['name'] }}</a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="drawer-item" style="--i: 1">
                    <button type="button" class="{{ $drawerHead }}" x-on:click="s = s === 'j' ? null : 'j'" x-bind:aria-expanded="s === 'j'">Journeys <x-heroicon-m-plus class="size-5 transition-transform duration-300" x-bind:class="s === 'j' && 'rotate-45'" /></button>
                    <div x-show="s === 'j'" x-collapse>
                        <div class="flex flex-wrap gap-2 pb-5">
                            @foreach ($nav['activities'] as $activity)
                                <a href="{{ $activity['url'] }}" class="chip !py-2 !text-[0.8rem]">{{ $activity['name'] }}</a>
                            @endforeach
                            <a href="{{ route('trips.index') }}" class="chip !bg-navy-800 !py-2 !text-[0.8rem] !text-stone-100">All journeys</a>
                        </div>
                    </div>
                </div>
                <a href="{{ route('departures') }}" class="drawer-item {{ $drawerLink }}" style="--i: 2">Group departures</a>
                <div class="drawer-item" style="--i: 3">
                    <button type="button" class="{{ $drawerHead }}" x-on:click="s = s === 'w' ? null : 'w'" x-bind:aria-expanded="s === 'w'">Why us <x-heroicon-m-plus class="size-5 transition-transform duration-300" x-bind:class="s === 'w' && 'rotate-45'" /></button>
                    <div x-show="s === 'w'" x-collapse>
                        <ul class="grid grid-cols-1 pb-4 min-[400px]:grid-cols-2 min-[400px]:gap-x-4">
                            @foreach ($whyLinks as [$label, $href])
                                <li><a href="{{ $href }}" class="flex items-center gap-2 py-2.5 text-[0.95rem] font-medium text-ink-soft hover:text-terracotta-500"><span class="size-1 shrink-0 rounded-full bg-terracotta-500"></span>{{ $label }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <a href="{{ route('blog.index') }}" class="drawer-item {{ $drawerLink }}" style="--i: 4">Journal</a>
                <a href="{{ route('contact') }}" class="drawer-item {{ $drawerLink }}" style="--i: 5">Contact</a>
            </div>
            <div class="drawer-item mt-6" style="--i: 6">
                <a href="{{ route('tailor-made') }}" class="btn-primary w-full">Plan your journey <x-heroicon-m-arrow-right class="size-4" /></a>
            </div>
            <div @class(['drawer-item mt-3 grid gap-2', 'grid-cols-3' => $wa, 'grid-cols-2' => ! $wa]) style="--i: 7">
                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="flex min-h-14 flex-col items-center justify-center gap-1 rounded-2xl bg-stone-50 px-2 py-2.5 text-xs font-semibold text-navy-800 ring-1 ring-stone-300/70" aria-label="Call {{ $phone }}"><x-heroicon-o-phone class="size-5 text-terracotta-500" /> Call</a>
                @if ($wa)
                    <a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener" class="flex min-h-14 flex-col items-center justify-center gap-1 rounded-2xl bg-stone-50 px-2 py-2.5 text-xs font-semibold text-navy-800 ring-1 ring-stone-300/70" aria-label="Chat on WhatsApp"><svg viewBox="0 0 24 24" class="size-5 text-[#1fa855]" fill="currentColor" aria-hidden="true"><path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.48-.89-.79-1.49-1.77-1.66-2.07-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.62-.92-2.22-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.8.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.21 3.08c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.69.63.71.23 1.36.2 1.87.12.57-.09 1.76-.72 2.01-1.41.25-.7.25-1.29.17-1.41-.07-.13-.27-.2-.57-.35M12.05 21.5h-.01a9.4 9.4 0 0 1-4.8-1.31l-.34-.2-3.56.93.95-3.47-.22-.36a9.4 9.4 0 0 1-1.44-5.01c0-5.19 4.23-9.42 9.43-9.42 2.52 0 4.88.98 6.66 2.76a9.36 9.36 0 0 1 2.76 6.67c0 5.2-4.23 9.42-9.43 9.42M20.07 3.9A11.3 11.3 0 0 0 12.05.57C5.8.57.72 5.65.72 11.9c0 2 .52 3.95 1.52 5.66L.62 23.43l6.01-1.58a11.3 11.3 0 0 0 5.41 1.38h.01c6.25 0 11.33-5.08 11.33-11.33 0-3.03-1.18-5.87-3.31-8.01"/></svg> WhatsApp</a>
                @endif
                <a href="mailto:{{ $email }}" class="flex min-h-14 flex-col items-center justify-center gap-1 rounded-2xl bg-stone-50 px-2 py-2.5 text-xs font-semibold text-navy-800 ring-1 ring-stone-300/70" aria-label="Email {{ $email }}"><x-heroicon-o-envelope class="size-5 text-terracotta-500" /> Email</a>
            </div>
            <p class="drawer-item mt-4 text-center text-sm text-ink-soft" style="--i: 8"><a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="py-1 hover:text-navy-800">{{ $phone }}</a> <span class="text-stone-400">·</span> <a href="mailto:{{ $email }}" class="py-1 hover:text-navy-800">{{ $email }}</a></p>
        </nav>
    </div>
    {{-- Ridge-line edge: the solid header ends in a mountain skyline; the terracotta line traces reading progress. --}}
    <svg viewBox="0 0 1600 12" preserveAspectRatio="none" aria-hidden="true" @class([
        'header-ridge pointer-events-none absolute inset-x-0 top-full h-3 w-full transition-opacity duration-500',
        'opacity-0 group-data-[scrolled]/header:opacity-100' => $transparent,
    ]) x-bind:class="(open || mega) && '!opacity-0'">
        <path d="M0,7.3 L16,7.9 L32,8.9 L48,8.5 L64,8.0 L80,8.1 L96,8.5 L112,7.8 L128,7.5 L144,6.5 L160,7.5 L176,7.6 L192,8.1 L208,8.6 L224,9.3 L240,10.9 L256,10.4 L272,9.6 L288,8.5 L304,5.9 L320,4.6 L336,3.9 L352,3.0 L368,2.6 L384,2.4 L400,1.0 L416,2.6 L432,3.5 L448,4.0 L464,4.0 L480,5.0 L496,4.4 L512,5.8 L528,5.3 L544,4.1 L560,3.1 L576,4.1 L592,3.5 L608,4.5 L624,5.3 L640,5.9 L656,7.0 L672,8.8 L688,9.7 L704,10.0 L720,10.7 L736,11 L752,9.6 L768,8.8 L784,8.9 L800,7.2 L816,6.9 L832,6.1 L848,5.6 L864,7.0 L880,5.7 L896,6.6 L912,6.6 L928,7.6 L944,6.7 L960,6.4 L976,6.3 L992,4.3 L1008,3.6 L1024,3.1 L1040,1 L1056,1 L1072,1 L1088,1.5 L1104,2.1 L1120,2.4 L1136,3.9 L1152,4.8 L1168,6.3 L1184,6.2 L1200,7.8 L1216,8.1 L1232,7.9 L1248,7.0 L1264,7.1 L1280,5.0 L1296,5.7 L1312,7.5 L1328,7.8 L1344,8.1 L1360,10.2 L1376,10.5 L1392,11 L1408,10.7 L1424,10.3 L1440,10.1 L1456,8.4 L1472,7.5 L1488,7.3 L1504,4.7 L1520,3.1 L1536,2.5 L1552,2.5 L1568,3.9 L1584,3.5 L1600,3.9 L1600,0 L0,0 Z" fill="#f4efe6" fill-opacity=".92" />
        <path d="M0,7.3 L16,7.9 L32,8.9 L48,8.5 L64,8.0 L80,8.1 L96,8.5 L112,7.8 L128,7.5 L144,6.5 L160,7.5 L176,7.6 L192,8.1 L208,8.6 L224,9.3 L240,10.9 L256,10.4 L272,9.6 L288,8.5 L304,5.9 L320,4.6 L336,3.9 L352,3.0 L368,2.6 L384,2.4 L400,1.0 L416,2.6 L432,3.5 L448,4.0 L464,4.0 L480,5.0 L496,4.4 L512,5.8 L528,5.3 L544,4.1 L560,3.1 L576,4.1 L592,3.5 L608,4.5 L624,5.3 L640,5.9 L656,7.0 L672,8.8 L688,9.7 L704,10.0 L720,10.7 L736,11 L752,9.6 L768,8.8 L784,8.9 L800,7.2 L816,6.9 L832,6.1 L848,5.6 L864,7.0 L880,5.7 L896,6.6 L912,6.6 L928,7.6 L944,6.7 L960,6.4 L976,6.3 L992,4.3 L1008,3.6 L1024,3.1 L1040,1 L1056,1 L1072,1 L1088,1.5 L1104,2.1 L1120,2.4 L1136,3.9 L1152,4.8 L1168,6.3 L1184,6.2 L1200,7.8 L1216,8.1 L1232,7.9 L1248,7.0 L1264,7.1 L1280,5.0 L1296,5.7 L1312,7.5 L1328,7.8 L1344,8.1 L1360,10.2 L1376,10.5 L1392,11 L1408,10.7 L1424,10.3 L1440,10.1 L1456,8.4 L1472,7.5 L1488,7.3 L1504,4.7 L1520,3.1 L1536,2.5 L1552,2.5 L1568,3.9 L1584,3.5 L1600,3.9" fill="none" stroke="#082047" stroke-opacity=".12" vector-effect="non-scaling-stroke" />
        <path class="header-ridge__progress" d="M0,7.3 L16,7.9 L32,8.9 L48,8.5 L64,8.0 L80,8.1 L96,8.5 L112,7.8 L128,7.5 L144,6.5 L160,7.5 L176,7.6 L192,8.1 L208,8.6 L224,9.3 L240,10.9 L256,10.4 L272,9.6 L288,8.5 L304,5.9 L320,4.6 L336,3.9 L352,3.0 L368,2.6 L384,2.4 L400,1.0 L416,2.6 L432,3.5 L448,4.0 L464,4.0 L480,5.0 L496,4.4 L512,5.8 L528,5.3 L544,4.1 L560,3.1 L576,4.1 L592,3.5 L608,4.5 L624,5.3 L640,5.9 L656,7.0 L672,8.8 L688,9.7 L704,10.0 L720,10.7 L736,11 L752,9.6 L768,8.8 L784,8.9 L800,7.2 L816,6.9 L832,6.1 L848,5.6 L864,7.0 L880,5.7 L896,6.6 L912,6.6 L928,7.6 L944,6.7 L960,6.4 L976,6.3 L992,4.3 L1008,3.6 L1024,3.1 L1040,1 L1056,1 L1072,1 L1088,1.5 L1104,2.1 L1120,2.4 L1136,3.9 L1152,4.8 L1168,6.3 L1184,6.2 L1200,7.8 L1216,8.1 L1232,7.9 L1248,7.0 L1264,7.1 L1280,5.0 L1296,5.7 L1312,7.5 L1328,7.8 L1344,8.1 L1360,10.2 L1376,10.5 L1392,11 L1408,10.7 L1424,10.3 L1440,10.1 L1456,8.4 L1472,7.5 L1488,7.3 L1504,4.7 L1520,3.1 L1536,2.5 L1552,2.5 L1568,3.9 L1584,3.5 L1600,3.9" pathLength="1" fill="none" stroke="#b5532e" stroke-width="1.5" vector-effect="non-scaling-stroke" />
    </svg>
</header>
