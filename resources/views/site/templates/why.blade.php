{{-- Why Wonder Himalaya — designed template. Reasons from the client's existing page. --}}
@php
    $up = fn (string $p) => asset('storage/wp-content/uploads/'.$p);
    $reasons = [
        ['clock', '24/7 service', 'Our Thamel office is open around the clock — drop in or call any time for questions, help on the trail or last-minute planning.'],
        ['shield-check', 'Reputable & trustworthy', 'Hundreds of travellers have trusted us with their Himalayan journeys — and many come back. We treat every guest as family.'],
        ['adjustments-horizontal', 'Tailored itineraries', 'Every itinerary can be reshaped around you: pace, lodges, extra acclimatisation days, a helicopter return. Flexibility is our default.'],
        ['heart', 'Social contributions', 'A share of every booking goes to charitable work in Nepal. When you travel with us, you give back too.'],
        ['light-bulb', 'Young, fresh thinking', 'A young founding team with fresh ideas — balancing business with responsibility to society and to sustainable tourism.'],
        ['sparkles', 'Unique packages', 'From helicopter tours to Everest and Annapurna base camps to cultural tours and peak climbing — something for every traveller.'],
        ['bolt', 'Last-minute booking', 'Plans changed? We accept bookings up to two days before departure — and will still make it wonderful.'],
    ];
    $testimonials = \App\Models\Testimonial::where('is_published', true)->orderByDesc('is_featured')->limit(3)->get();
@endphp
<x-layouts.site :seo="$seo" transparent>
    <section class="relative isolate flex h-[72svh] min-h-[34rem] max-h-[48rem] flex-col justify-end overflow-hidden bg-juniper-950 text-white">
        <div class="absolute inset-0 -z-10" data-parallax><img src="{{ \App\Support\BrandImage::url('gokyo-ri.png', $up('2026/03/IMG_0752-scaled.jpeg')) }}" alt="" class="h-full w-full animate-slow-zoom object-cover" fetchpriority="high"></div>
        <div class="scrim-hero absolute inset-0 -z-10"></div>
        <div class="container-page pt-36 pb-16 lg:pb-24">
            <x-site.breadcrumbs :items="[['label' => 'About', 'url' => url('/about-us')], ['label' => 'Why Wonder Himalaya']]" light />
            <p class="eyebrow eyebrow-light mt-8">Why Wonder Himalaya</p>
            <h1 class="mt-5 max-w-4xl font-display text-display-lg text-white">There's an agency on every street in Kathmandu. <em class="text-saffron-300">Here's why travellers choose us.</em></h1>
        </div>
    </section>

    <section class="container-page grid gap-12 py-24 lg:grid-cols-12 lg:py-32">
        <div class="lg:col-span-4">
            <div class="lg:sticky lg:top-36">
                <p class="eyebrow">The simple answer</p>
                <p class="mt-6 font-display text-display-sm leading-snug text-juniper-800">Proper hospitality — and a team that helps you through every question, before, during and after your trip.</p>
                <a href="{{ route('tailor-made') }}" class="btn-primary mt-10">Start planning</a>
            </div>
        </div>
        <ol class="grid gap-6 sm:grid-cols-2 lg:col-span-8">
            @foreach ($reasons as $i => [$icon, $title, $text])
                <li @class(['card-surface relative overflow-hidden p-8', 'sm:col-span-2 bg-juniper-800 !ring-juniper-800 text-stone-100' => $i === 0]) data-reveal>
                    <span @class(['data absolute top-6 right-7', 'text-saffron-300' => $i === 0, 'text-stone-400' => $i !== 0])>{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                    <x-dynamic-component :component="'heroicon-o-'.$icon" @class(['size-9', 'text-saffron-300' => $i === 0, 'text-terracotta-500' => $i !== 0]) />
                    <h2 @class(['mt-6 font-display text-2xl', 'text-white' => $i === 0])>{{ $title }}</h2>
                    <p @class(['mt-3', 'text-stone-300 max-w-xl' => $i === 0, 'text-ink-soft' => $i !== 0])>{{ $text }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    @if ($testimonials->isNotEmpty())
        <section class="bg-stone-200/50 py-24">
            <div class="container-page">
                <x-site.section-heading eyebrow="Don't take our word for it" title="What travellers say" />
                <div class="mt-12 grid gap-6 md:grid-cols-3">
                    @foreach ($testimonials as $t)
                        <figure class="rounded-[1.75rem] bg-stone-50 p-8 ring-1 ring-stone-300/60" data-reveal>
                            <div class="flex gap-0.5 text-saffron-500">@for ($i = 0; $i < $t->rating; $i++)<x-heroicon-s-star class="size-4" />@endfor</div>
                            <blockquote class="mt-4 text-ink-soft">“{{ str($t->body)->limit(280) }}”</blockquote>
                            <figcaption class="mt-6 text-sm font-semibold text-juniper-800">{{ $t->author_name }}<span class="font-normal text-stone-500"> · {{ ucfirst($t->source) }}</span></figcaption>
                        </figure>
                    @endforeach
                </div>
                <a href="{{ route('reviews') }}" class="link-arrow mt-10">Read all reviews <x-heroicon-m-arrow-right class="size-4" /></a>
            </div>
        </section>
    @endif

    @include('site.partials.tailor-made-band')
</x-layouts.site>
