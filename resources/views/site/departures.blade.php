<x-layouts.site :seo="$seo">
    <x-site.page-hero eyebrow="Join a group" title="Fixed group <em class='text-terracotta-500'>departures</em>"
        intro="Travel solo or as a couple and trek with like-minded people — daily departures, small groups, shared costs and the same expert guiding as our private journeys."
        :crumbs="[['label' => 'Group departures']]" />

    <section class="container-page py-16">
        @php $daily = $fixedTrips->filter->departsDaily(); @endphp
        @if ($daily->isNotEmpty())
            <div class="mb-16" data-reveal>
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="eyebrow">Daily group departures</p>
                        <h2 class="mt-4 font-display text-display-sm">Join on any day you like</h2>
                        <p class="mt-3 max-w-2xl text-ink-soft">These group treks start every day — pick your date on the trip page. Bookings close a few days before departure.</p>
                    </div>
                </div>
                <div class="mt-8 divide-y divide-stone-300/70 overflow-hidden rounded-[2rem] bg-stone-50 ring-1 ring-stone-300/60">
                    @foreach ($daily as $trip)
                        <div class="grid items-center gap-4 p-5 sm:grid-cols-[7rem_1fr_auto_auto_auto] sm:gap-8 sm:p-6">
                            <a href="{{ url($trip->publicPath()) }}" class="relative block aspect-[4/3] overflow-hidden rounded-2xl">
                                <x-site.picture :model="$trip" collection="hero" sizes="10rem" class="h-full w-full" />
                                <span class="absolute bottom-1.5 left-1.5 rounded-full bg-stone-100/90 px-2 py-0.5 text-[0.6rem] font-bold uppercase tracking-[0.14em] text-terracotta-600">Daily</span>
                            </a>
                            <div>
                                <a href="{{ url($trip->publicPath()) }}" class="font-display text-xl text-juniper-800 hover:underline">{{ $trip->title }}</a>
                                <p class="text-sm text-ink-soft">{{ $trip->destination?->name }} · {{ $trip->durationLabel() }} @if ($trip->difficulty)· {{ $trip->difficulty->name }}@endif</p>
                            </div>
                            <span class="chip w-max">From {{ $trip->earliestStartDate()->format('j M Y') }}</span>
                            <span class="font-display text-2xl text-juniper-800">{{ $trip->displayPrice() ? 'US$'.number_format($trip->displayPrice()) : 'On request' }}</span>
                            <a href="{{ url($trip->publicPath()) }}#departures" class="btn-primary !px-5 !py-2.5 !text-xs">Choose date</a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @forelse ($months as $month => $departures)
            <div class="mb-14" data-reveal>
                <h2 class="sticky top-20 z-10 -mx-2 bg-stone-100/95 px-2 py-3 font-display text-3xl backdrop-blur lg:top-24">{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}</h2>
                <div class="mt-4 divide-y divide-stone-300/70 overflow-hidden rounded-[2rem] bg-stone-50 ring-1 ring-stone-300/60">
                    @foreach ($departures as $d)
                        <div class="grid items-center gap-4 p-5 sm:grid-cols-[6rem_1fr_auto_auto_auto] sm:gap-8 sm:p-6">
                            <div class="flex items-baseline gap-2 sm:block">
                                <p class="font-display text-4xl leading-none text-juniper-800">{{ $d->start_date->format('d') }}</p>
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">{{ $d->start_date->format('D') }}</p>
                            </div>
                            <div>
                                <a href="{{ url($d->trip->publicPath()) }}" class="font-display text-xl text-juniper-800 hover:underline">{{ $d->trip->title }}</a>
                                <p class="text-sm text-ink-soft">{{ $d->trip->destination?->name }} · {{ $d->trip->durationLabel() }} @if ($d->trip->difficulty)· {{ $d->trip->difficulty->name }}@endif</p>
                            </div>
                            <span class="chip w-max">{{ $d->status->getLabel() }} · {{ $d->seatsLeft() }} left</span>
                            <span class="font-display text-2xl text-juniper-800">{{ $d->effectivePrice() ? 'US$'.number_format($d->effectivePrice()) : 'On request' }}</span>
                            <a href="{{ url($d->trip->publicPath()) }}?departure={{ $d->id }}#enquire" @class(['btn-primary !px-5 !py-2.5 !text-xs', 'pointer-events-none opacity-50' => ! $d->isBookable()])>{{ $d->isBookable() ? 'Join' : 'Full' }}</a>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            @if ($daily->isEmpty())
            <div class="rounded-[2rem] bg-stone-200/50 p-10">
                <h2 class="font-display text-3xl">New season dates are being finalised.</h2>
                <p class="mt-3 max-w-2xl text-ink-soft">Our fixed-departure treks below run on set dates through spring and autumn. Tell us when you'd like to travel and we'll place you in a group.</p>
            </div>
            @endif
        @endforelse

        @if ($stories->isNotEmpty())
            <div class="mt-20">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <x-site.section-heading eyebrow="From the trail" title="Read our stories" />
                    <a href="{{ route('blog.index') }}" class="link-arrow">All stories <x-heroicon-m-arrow-right class="size-4" /></a>
                </div>
                <div class="mt-10 grid gap-10 md:grid-cols-3">@foreach ($stories as $post)<x-site.post-card :post="$post" />@endforeach</div>
            </div>
        @endif

        @if ($fixedTrips->isNotEmpty() && $daily->count() < $fixedTrips->count())
            <div class="mt-16">
                <x-site.section-heading eyebrow="Fixed-departure treks" title="Journeys that run on set dates" />
                <div class="mt-12 grid gap-x-8 gap-y-14 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($fixedTrips as $trip)<x-site.trip-card :trip="$trip" data-reveal />@endforeach
                </div>
            </div>
        @endif
    </section>
</x-layouts.site>
