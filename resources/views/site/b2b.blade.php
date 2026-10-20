<x-layouts.site :seo="$seo">
    {{-- Hero --}}
    <section class="relative overflow-hidden bg-juniper-900 text-stone-100">
        <div class="topo pointer-events-none absolute inset-0 opacity-30 invert" aria-hidden="true"></div>
        <div class="container-page relative grid gap-12 py-20 lg:grid-cols-12 lg:py-32">
            <div class="lg:col-span-8">
                <x-site.breadcrumbs :items="[['label' => 'Travel partners']]" light />
                <p class="eyebrow eyebrow-light mt-8">For agencies & tour operators</p>
                <h1 class="mt-6 font-display text-display-lg text-stone-100">Your local partner<br><em class="text-saffron-300">in the Himalaya.</em></h1>
                <p class="mt-8 max-w-2xl text-lg text-stone-300">Reliable, fully-licensed ground operations across Nepal, Tibet and Bhutan — delivered under your brand, at net rates, with a Kathmandu team that answers in hours, not days.</p>
                <div class="mt-10 flex flex-wrap gap-3">
                    <a href="#partner-form" class="btn-primary">Become a partner <x-heroicon-m-arrow-right class="size-4" /></a>
                    <a href="mailto:{{ \App\Models\Setting::get('company.email', 'info@wonderhimalaya.com') }}?subject=B2B%20rate%20sheet" class="btn-ghost-light">Request rate sheet</a>
                </div>
            </div>
            <div class="flex flex-col justify-end gap-3 lg:col-span-4">
                @foreach (\App\Models\Setting::get('b2b.credentials', ['All permits, TIMS & park fees handled', 'Licensed, first-aid trained guides', 'Porter welfare & insurance on every trip', 'Transparent net-rate pricing']) as $badge)
                    <p class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 px-5 py-4 text-sm"><x-heroicon-s-check-badge class="size-5 shrink-0 text-saffron-300" /> {{ $badge }}</p>
                @endforeach
            </div>
        </div>
    </section>

    {{-- What we do --}}
    <section class="py-24 lg:py-32">
        <div class="container-page">
            <x-site.section-heading eyebrow="What we do" title="Ground operations, done properly" intro="Hand us the guest, the dates and the brief. We handle permits, logistics, guides, lodges, flights and on-trip care — and keep you in the loop throughout." />
            <div class="mt-16 grid gap-px overflow-hidden rounded-[2rem] bg-stone-300/60 ring-1 ring-stone-300/60 md:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['White-label ground ops', 'Your brand on every document. We operate silently behind you — arrival to departure.', 'building-office-2'],
                    ['Bespoke FIT curation', 'Private itineraries for couples, families and high-end clients, quoted within 24 hours.', 'sparkles'],
                    ['Small-group expeditions', 'Fixed departures you can sell, treks and climbs to 6,000 m+, with experienced climbing Sherpas.', 'user-group'],
                    ['MICE & corporate', 'Incentive trips, offsites and group events in Kathmandu, Pokhara and beyond.', 'briefcase'],
                ] as [$title, $text, $icon])
                    <div class="bg-stone-100 p-8 lg:p-10" data-reveal>
                        <x-dynamic-component :component="'heroicon-o-'.$icon" class="size-9 text-terracotta-500" />
                        <h3 class="mt-8 font-display text-2xl">{{ $title }}</h3>
                        <p class="mt-3 text-ink-soft">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Destinations --}}
    <section class="bg-stone-200/50 py-24 lg:py-32">
        <div class="container-page grid gap-14 lg:grid-cols-12">
            <div class="lg:col-span-4">
                <x-site.section-heading eyebrow="Destinations we cover" title="Four countries, one trusted team" />
            </div>
            <div class="grid gap-6 sm:grid-cols-2 lg:col-span-8">
                @foreach ($regions as $country)
                    <div class="rounded-[1.75rem] bg-stone-50 p-7 ring-1 ring-stone-300/60" data-reveal>
                        <h3 class="font-display text-3xl">{{ $country->name }}</h3>
                        @if ($country->tagline)<p class="mt-1 text-sm text-stone-500">{{ $country->tagline }}</p>@endif
                        @if ($country->children->isNotEmpty())
                            <ul class="mt-5 flex flex-wrap gap-2">@foreach ($country->children as $r)<li class="chip">{{ $r->name }}</li>@endforeach</ul>
                        @elseif ($country->highlights)
                            <ul class="mt-5 flex flex-wrap gap-2">@foreach ($country->highlights as $h)<li class="chip">{{ $h }}</li>@endforeach</ul>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Operational integrity --}}
    <section class="py-24 lg:py-32">
        <div class="container-page">
            <x-site.section-heading eyebrow="Operational integrity" title="Numbers partners rely on" />
            <dl class="mt-14 grid grid-cols-2 gap-px overflow-hidden rounded-[2rem] bg-stone-300/60 ring-1 ring-stone-300/60 lg:grid-cols-4">
                @foreach ([['24h', 'Quote turnaround'], ['24/7', 'On-trip support line'], ...collect($stats)->take(2)->map(fn ($s) => [$s['value'], $s['label']])->all()] as [$v, $l])
                    <div class="bg-stone-100 p-8 lg:p-10"><dt class="text-sm text-ink-soft">{{ $l }}</dt><dd class="mt-3 font-display text-5xl text-juniper-800">{{ $v }}</dd></div>
                @endforeach
            </dl>
        </div>
    </section>

    @if ($testimonials->isNotEmpty())
        <section class="pb-24">
            <div class="container-page grid gap-6 md:grid-cols-3">
                @foreach ($testimonials as $t)
                    <figure class="rounded-[1.75rem] bg-stone-50 p-7 ring-1 ring-stone-300/60" data-reveal>
                        <blockquote class="text-ink-soft">“{{ str($t->body)->limit(260) }}”</blockquote>
                        <figcaption class="mt-5 text-sm font-semibold text-juniper-800">{{ $t->author_name }}</figcaption>
                    </figure>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Partnership form --}}
    <section id="partner-form" class="scroll-mt-28 bg-juniper-800 py-24 text-stone-100 lg:py-32">
        <div class="container-page grid gap-14 lg:grid-cols-12">
            <div class="lg:col-span-4">
                <p class="eyebrow eyebrow-light">Contact & partnership</p>
                <h2 class="mt-6 font-display text-display-md text-stone-100">Let's build something together.</h2>
                <p class="mt-6 text-stone-300">Tell us about your business. Our partnerships desk replies within one business day with net rates, sample programmes and terms.</p>
                <dl class="mt-10 space-y-4 text-sm">
                    <div class="flex gap-3"><x-heroicon-o-map-pin class="size-5 text-saffron-300" /><dd>{{ \App\Models\Setting::get('company.address', 'Thamel, Kathmandu, Nepal') }}</dd></div>
                    <div class="flex gap-3"><x-heroicon-o-phone class="size-5 text-saffron-300" /><dd>{{ \App\Models\Setting::get('company.phone') }}</dd></div>
                    <div class="flex gap-3"><x-heroicon-o-envelope class="size-5 text-saffron-300" /><dd>{{ \App\Models\Setting::get('company.email') }}</dd></div>
                </dl>
            </div>
            <div class="lg:col-span-8"><livewire:partner-form /></div>
        </div>
    </section>
</x-layouts.site>
