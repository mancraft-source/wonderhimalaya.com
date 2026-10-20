{{-- Sustainable tourism — designed template. Commitments from the client's existing page. --}}
@php
    $up = fn (string $p) => asset('storage/wp-content/uploads/'.$p);
    $pillars = [
        ['globe-asia-australia', 'Eco-friendly destinations', 'We champion places that protect their environment and wildlife — the Annapurna Conservation Area, Rara Lake, Chitwan and Langtang National Parks.'],
        ['home-modern', 'Local communities first', 'Local guides on most treks, homestays and family-run lodges, and we encourage every traveller to buy local food and crafts.'],
        ['map', 'Low-impact itineraries', 'We favour trekking over flying or driving wherever we can, and choose sustainable accommodation and lodges.'],
        ['heart', 'Ethical wildlife practice', 'No elephant rides, no activities that harm animals — ever. Wildlife is something to admire, not use.'],
    ];
@endphp
<x-layouts.site :seo="$seo" transparent>
    <section class="relative isolate flex h-[72svh] min-h-[34rem] max-h-[48rem] flex-col justify-end overflow-hidden bg-juniper-950 text-white">
        <div class="absolute inset-0 -z-10" data-parallax><img src="{{ \App\Support\BrandImage::url('rara-lake-trek-wonder-himalaya-scaled-1.png', $up('2026/01/annapurna-region-mardi-himal-wonder-himalaya.jpg')) }}" alt="" class="h-full w-full animate-slow-zoom object-cover" fetchpriority="high"></div>
        <div class="scrim-hero absolute inset-0 -z-10"></div>
        <div class="container-page pt-36 pb-16 lg:pb-24">
            <x-site.breadcrumbs :items="[['label' => 'About', 'url' => url('/about-us')], ['label' => 'Sustainable tourism']]" light />
            <p class="eyebrow eyebrow-light mt-8">Sustainable tourism</p>
            <h1 class="mt-5 max-w-4xl font-display text-display-lg text-white">Leave the mountains <em class="text-saffron-300">better than we found them.</em></h1>
        </div>
    </section>

    <section class="grain relative overflow-hidden py-24 lg:py-32">
        <div class="topo pointer-events-none absolute inset-0 opacity-60"></div>
        <div class="container-page relative grid gap-12 lg:grid-cols-12">
            <div class="lg:col-span-5" data-reveal>
                <p class="eyebrow">Why it matters</p>
                <h2 class="mt-5 font-display text-display-md">The Himalaya is what our guests come for — and what we must protect.</h2>
            </div>
            <div class="space-y-5 text-lg text-ink-soft lg:col-span-6 lg:col-start-7" data-reveal>
                <p>Tourism is our livelihood, and the landscapes, villages and wildlife of the Himalaya are what draw travellers here. As visitor numbers grow, the environment pays the price unless we travel differently.</p>
                <p>So we follow sustainable tourism in everything we do — harming nature as little as possible, supporting local communities, and making sure the places we visit stay beautiful and healthy for the generations to come. And we ask every traveller to do the same.</p>
            </div>
        </div>
    </section>

    <section class="container-page pb-24">
        <div class="grid gap-6 md:grid-cols-2">
            @foreach ($pillars as $i => [$icon, $title, $text])
                <div class="card-surface flex gap-6 p-8 lg:p-10" data-reveal>
                    <span class="flex size-14 shrink-0 items-center justify-center rounded-full bg-juniper-800 text-saffron-300"><x-dynamic-component :component="'heroicon-o-'.$icon" class="size-6" /></span>
                    <div>
                        <h3 class="font-display text-2xl">{{ $title }}</h3>
                        <p class="mt-3 text-ink-soft">{{ $text }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="bg-juniper-800 py-20 text-stone-100">
        <div class="container-page grid items-center gap-10 lg:grid-cols-12">
            <div class="flex justify-center lg:col-span-2"><img src="{{ asset('images/brand/aff-keep.webp') }}" alt="Kathmandu Environmental Education Project (KEEP)" class="h-28 w-28 rounded-3xl bg-white object-contain p-3" loading="lazy"></div>
            <div class="lg:col-span-7">
                <h2 class="font-display text-3xl text-white">Travel lightly — a few simple habits</h2>
                <ul class="mt-6 grid gap-3 text-stone-300 sm:grid-cols-2">
                    @foreach (['Carry a refillable bottle and purification tablets', 'Pack out what you pack in', 'Stay on marked trails', 'Ask before photographing people', 'Dress modestly at temples & monasteries', 'Buy local — tea-houses, crafts, food'] as $tip)
                        <li class="flex gap-3"><x-heroicon-m-check class="mt-1 size-4 shrink-0 text-saffron-300" /> {{ $tip }}</li>
                    @endforeach
                </ul>
            </div>
            <div class="lg:col-span-3 lg:text-right"><a href="{{ route('tailor-made') }}" class="btn-primary">Plan a responsible trip</a></div>
        </div>
    </section>
</x-layouts.site>
