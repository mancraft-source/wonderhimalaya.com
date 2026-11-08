@php
    $path = request()->getPathInfo();
    $keywords = \App\Support\NotFoundSuggestions::keywords($path);
    $suggestions = \App\Support\NotFoundSuggestions::trips($path);
    $isMatch = $keywords !== [] && $suggestions->contains(fn ($trip) => collect($keywords)->contains(fn ($word) => str_contains(strtolower($trip->title.' '.$trip->slug), $word)));
    $nepal = collect($nav['destinations'] ?? [])->first(fn ($country) => $country['children'] !== []);
    $seo = \App\Support\Seo::make('Page not found', 'This page has moved or no longer exists.')->noindexFollow();
@endphp
<x-layouts.site :seo="$seo">
    <section class="grain relative isolate overflow-hidden bg-stone-200/40">
        <div class="topo pointer-events-none absolute inset-0 opacity-70"></div>
        <div class="container-page relative z-20 grid items-center gap-12 pt-16 pb-36 lg:grid-cols-12 lg:pt-24 lg:pb-52">
            <div class="lg:col-span-7" data-reveal>
                <p class="data inline-flex items-center gap-3 rounded-full bg-juniper-900 px-4 py-2 text-xs text-saffron-300">
                    <span class="size-1.5 animate-pulse rounded-full bg-terracotta-500"></span> 404 · off the map
                </p>
                <h1 class="mt-7 font-display text-display-lg text-juniper-900">This trail fades <em class="text-terracotta-500">into the clouds.</em></h1>
                <p class="mt-6 max-w-xl text-lg text-ink-soft">
                    We couldn't find <code class="rounded-md bg-white/70 px-2 py-0.5 font-mono text-[0.9em] text-juniper-800 ring-1 ring-stone-300/70">{{ \Illuminate\Support\Str::limit($path, 60) }}</code>.
                    It may have moved when we redesigned the site. Let's get you back on a marked path.
                </p>

                <form action="{{ route('search') }}" method="get" role="search" class="mt-9 flex max-w-xl items-center gap-2 rounded-full bg-white p-1.5 shadow-[0_18px_40px_-24px_rgba(15,29,25,0.45)] ring-1 ring-stone-300/70 focus-within:ring-2 focus-within:ring-juniper-500">
                    <x-heroicon-o-magnifying-glass class="ml-4 size-5 shrink-0 text-stone-400" />
                    <label for="not-found-search" class="sr-only">Search the site</label>
                    <input id="not-found-search" name="q" type="search" value="{{ implode(' ', $keywords) }}" placeholder="Search treks, regions, guides…"
                           class="min-w-0 flex-1 border-0 bg-transparent px-1 py-2.5 text-ink placeholder:text-stone-400 focus:ring-0 focus:outline-none">
                    <button type="submit" class="btn-primary !px-6 !py-3">Search</button>
                </form>

                <div class="mt-7 flex flex-wrap items-center gap-x-6 gap-y-3 text-[0.95rem]">
                    <a href="{{ route('home') }}" class="link-arrow">Back to the homepage <x-heroicon-m-arrow-right class="size-4" /></a>
                    <a href="{{ route('trips.index') }}" class="link-arrow">Browse every journey <x-heroicon-m-arrow-right class="size-4" /></a>
                    <a href="{{ route('contact') }}" class="link-arrow">Ask our Kathmandu team <x-heroicon-m-arrow-right class="size-4" /></a>
                </div>
            </div>

            {{-- A compass that can't find north: decorative, still under reduced motion. --}}
            <div class="relative hidden justify-center lg:col-span-5 lg:flex" aria-hidden="true">
                <div class="relative grid size-80 place-items-center rounded-full bg-stone-100/70 ring-1 ring-juniper-800/10 shadow-[0_40px_80px_-40px_rgba(15,29,25,0.5)] xl:size-96">
                    <div class="absolute inset-6 rounded-full border border-dashed border-juniper-800/20"></div>
                    <span class="data absolute top-8 text-xs text-terracotta-500">N</span>
                    <span class="data absolute bottom-8 text-xs text-stone-400">S</span>
                    <span class="data absolute left-8 text-xs text-stone-400">W</span>
                    <span class="data absolute right-8 text-xs text-stone-400">E</span>
                    <x-site.gear icon="compass" class="compass-lost w-56 text-juniper-800 xl:w-64" />
                    <p class="absolute -bottom-6 rounded-full bg-juniper-900 px-4 py-2 font-display text-lg text-stone-100 shadow-lg">You are here <span class="text-saffron-300">?</span></p>
                </div>
            </div>
        </div>
        <x-site.ridge variant="peaks" overlay to="#f4efe6" back="#c9d6da" size="lg" />
    </section>

    @if ($suggestions->isNotEmpty())
        <section class="container-page py-16 lg:py-20">
            <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                <x-site.section-heading :eyebrow="$isMatch ? 'Were you looking for' : 'Popular right now'" :title="$isMatch ? 'One of these journeys?' : 'Start with a favourite'" />
                <a href="{{ route('trips.index') }}" class="link-arrow shrink-0" data-reveal>All journeys <x-heroicon-m-arrow-right class="size-4" /></a>
            </div>
            <div class="mt-12 grid gap-x-8 gap-y-14 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($suggestions as $trip)
                    <x-site.trip-card :trip="$trip" data-reveal />
                @endforeach
            </div>
        </section>
    @endif

    @if ($nepal)
        <section class="container-page pb-20">
            <div class="rounded-[2rem] bg-juniper-900 px-7 py-9 text-stone-100 sm:px-10 lg:flex lg:items-center lg:justify-between lg:gap-10">
                <div>
                    <p class="eyebrow eyebrow-light">Or wander by region</p>
                    <p class="mt-3 font-display text-3xl">Where in the Himalaya?</p>
                </div>
                <div class="mt-6 flex flex-wrap gap-2 lg:mt-0 lg:justify-end">
                    @foreach ($nepal['children'] as $region)
                        <a href="{{ $region['url'] }}" class="rounded-full bg-white/10 px-4 py-2 text-sm ring-1 ring-white/10 transition hover:bg-white/20">{{ str_replace(' Region', '', $region['name']) }}</a>
                    @endforeach
                    @foreach (collect($nav['destinations'])->reject(fn ($c) => $c === $nepal) as $country)
                        <a href="{{ $country['url'] }}" class="rounded-full bg-saffron-300/15 px-4 py-2 text-sm text-saffron-300 ring-1 ring-saffron-300/30 transition hover:bg-saffron-300/25">{{ $country['name'] }}</a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layouts.site>
