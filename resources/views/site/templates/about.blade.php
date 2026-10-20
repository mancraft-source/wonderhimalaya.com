{{-- About us — designed template. Copy from the client's existing About page. --}}
@php
    $up = fn (string $p) => asset('storage/wp-content/uploads/'.$p);
    $team = \App\Models\TeamMember::where('is_published', true)->with('media')->orderBy('sort')->orderBy('name')->get();
    $founders = $team->where('is_guide', false)->take(4);
    $stats = \App\Models\Setting::get('stats', []);
    $faqs = [
        ['What is the best time to visit Nepal?', 'Nepal is a year-round destination. Each season offers something different, from lush greenery during the monsoon to crystal-clear Himalayan views in autumn and spring.'],
        ['Can I customise my trip?', 'Absolutely. We specialise in personalised itineraries built around your interests, preferences and schedule.'],
        ['Do you cater to solo travellers or small groups?', 'Yes. Whether you travel alone or with friends, we make the experience seamless — and our fixed departures are a great way to join a small group.'],
        ['How physically challenging are the treks and tours?', 'We offer options for every fitness level, from easy cultural tours to strenuous high-altitude treks. We\'ll help you choose the right one.'],
        ['What safety measures do you provide?', 'Experienced guides, first-aid kits, careful acclimatisation planning and 24/7 support from our Kathmandu office — your safety is our top priority.'],
        ['Do I need special permits for trekking in Nepal?', 'Most treks need a TIMS card and area-specific permits. We handle all the paperwork for you.'],
        ['Are meals and accommodation included?', 'Most packages include meals and accommodation — tea-houses or camping on treks, and exclusive stays on our luxury journeys.'],
    ];
@endphp
<x-layouts.site :seo="$seo" transparent>
    {{-- Hero --}}
    <section class="relative isolate flex h-[78svh] min-h-[36rem] max-h-[52rem] flex-col justify-end overflow-hidden bg-juniper-950 text-white">
        <div class="absolute inset-0 -z-10" data-parallax>
            <img src="{{ \App\Support\BrandImage::url('annapurna-base-camp-min-scaled.jpg', $up('2026/04/IMG_1124-scaled.jpeg')) }}" alt="Trekkers on a Himalayan trail with Wonder Himalaya" class="h-full w-full animate-slow-zoom object-cover" fetchpriority="high">
        </div>
        <div class="scrim-hero absolute inset-0 -z-10"></div>
        <div class="container-page pt-36 pb-16 lg:pb-24">
            <x-site.breadcrumbs :items="[['label' => 'About us']]" light />
            <p class="eyebrow eyebrow-light mt-8">Our story</p>
            <h1 class="mt-5 max-w-4xl font-display text-display-lg text-white">Five friends, one love: <em class="text-saffron-300">the Himalaya.</em></h1>
            <p class="mt-6 max-w-2xl text-lg text-white/85">A company built by travellers, for travellers — based in the heart of Kathmandu's Thamel district.</p>
        </div>
    </section>

    {{-- Story --}}
    <section class="grain relative overflow-hidden py-24 lg:py-32">
        <div class="topo pointer-events-none absolute -top-32 -right-40 size-[56rem] opacity-70" aria-hidden="true"></div>
        <div class="container-page relative grid items-center gap-16 lg:grid-cols-12">
            <div class="lg:col-span-5" data-reveal>
                <div class="relative">
                    <div class="aspect-[4/5] overflow-hidden img-organic">
                        <img src="{{ $up('2025/02/about-wonder-himalaya-in-nepal-scaled.jpg') }}" alt="The Wonder Himalaya office in Thamel, Kathmandu" class="h-full w-full object-cover" loading="lazy">
                    </div>
                    <div class="absolute -right-6 -bottom-8 hidden rounded-[1.75rem] bg-juniper-800 p-6 text-stone-100 shadow-2xl sm:block">
                        <p class="font-display text-4xl text-saffron-300">Thamel</p>
                        <p class="text-sm text-stone-300">Kathmandu, Nepal</p>
                    </div>
                </div>
            </div>
            <div class="lg:col-span-6 lg:col-start-7" data-reveal>
                <p class="eyebrow">About Wonder Himalaya</p>
                <h2 class="mt-5 font-display text-display-md">More than a trip — a story you'll treasure for a lifetime.</h2>
                <div class="mt-8 space-y-5 text-lg text-ink-soft">
                    <p>At Wonder Himalaya, we are a passionate group of friends brought together by a shared love for adventure, exploration and the breathtaking beauty of the Himalayas.</p>
                    <p>From our base in Thamel, we craft unforgettable journeys across Nepal, Bhutan and Tibet — trekking, cultural exploration, luxury travel and heli tours for every type of traveller, from those seeking rugged adventure to those desiring luxurious escapes.</p>
                    <p>Through responsible and sustainable tourism, we work to preserve Nepal's natural beauty, support local communities and connect our guests to the soul of the mountains.</p>
                </div>
                <div class="mt-10 flex flex-wrap gap-3">
                    <a href="{{ route('tailor-made') }}" class="btn-primary">Plan your journey</a>
                    <a href="{{ route('team.index') }}" class="btn-outline">Meet the team</a>
                </div>
            </div>
        </div>
    </section>

    {{-- Vision & mission --}}
    <section class="bg-juniper-900 py-24 text-stone-100 lg:py-32">
        <div class="container-page"><div class="grid gap-px overflow-hidden rounded-[2.5rem] bg-white/10 lg:grid-cols-2">
            @foreach ([
                ['Our vision', 'The bridge between the traveller and the unexplored heart of the Himalaya.', 'We bring travellers closer to the beauty, culture and traditions of Nepal, Bhutan and Tibet in a way that fosters mutual respect, cultural understanding and a deep connection to the places and people we encounter — growing sustainably, journey by journey.'],
                ['Our mission', 'Meaningful connections between our guests and the land, culture and people of Nepal.', 'Every itinerary is designed to be safe, personal and enriching — so that each adventure leaves you with more than memories: with an understanding of, and a connection to, the places we visit, and a positive impact on the destinations we cherish.'],
            ] as [$label, $lead, $body])
                <div class="relative bg-juniper-900 p-10 lg:p-16" data-reveal>
                    <p class="eyebrow eyebrow-light">{{ $label }}</p>
                    <p class="mt-6 font-display text-3xl leading-snug text-white lg:text-4xl">{{ $lead }}</p>
                    <p class="mt-6 text-stone-300">{{ $body }}</p>
                </div>
            @endforeach
        </div></div>
    </section>

    {{-- Values --}}
    <section class="py-24 lg:py-32">
        <div class="container-page">
            <x-site.section-heading eyebrow="Why choose us" title="Your trusted partner in the Himalaya" align="center" />
            <div class="mt-16 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['guide.png', 'Guided by experts', 'Seasoned professionals who lead you to the hidden gems.'],
                    ['travel-agent.png', 'Tailored experiences', 'Your dream adventure, personalised to perfection.'],
                    ['sustainable-1.png', 'Sustainable travel', 'Explore responsibly while preserving the beauty of nature.'],
                    ['hospitality.png', 'Unrivalled hospitality', 'The warmth and care of our team at every step.'],
                ] as [$icon, $title, $text])
                    <div class="card-surface p-8 text-center" data-reveal>
                        <div class="mx-auto flex size-20 items-center justify-center rounded-full bg-stone-200/70 p-4">
                            <img src="{{ $up('2025/02/'.$icon) }}" alt="" class="max-h-full" loading="lazy">
                        </div>
                        <h3 class="mt-6 font-display text-2xl">{{ $title }}</h3>
                        <p class="mt-3 text-ink-soft">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Stats --}}
    @if ($stats)
        <section class="container-page pb-24">
            <dl class="grid grid-cols-2 gap-px overflow-hidden rounded-[2rem] bg-stone-300/60 ring-1 ring-stone-300/60 lg:grid-cols-4" data-reveal>
                @foreach ($stats as $s)
                    <div class="bg-stone-100 p-8 lg:p-10"><dt class="text-sm text-ink-soft">{{ $s['label'] }}</dt><dd class="mt-3 font-display text-5xl text-juniper-800 lg:text-6xl">{{ $s['value'] }}</dd></div>
                @endforeach
            </dl>
        </section>
    @endif

    {{-- Team --}}
    @if ($founders->isNotEmpty())
        <section class="bg-stone-200/50 py-24 lg:py-32">
            <div class="container-page">
                <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                    <x-site.section-heading eyebrow="Meet the team" title="Masters of the mountains" intro="The passionate explorers, expert guides and dedicated professionals behind every Wonder Himalaya journey." />
                    <a href="{{ route('team.index') }}" class="link-arrow shrink-0" data-reveal>The whole team <x-heroicon-m-arrow-right class="size-4" /></a>
                </div>
                <div class="mt-14 grid grid-cols-2 gap-8 lg:grid-cols-4">
                    @foreach ($founders as $m)
                        <a href="{{ url($m->publicPath()) }}" class="group" data-reveal>
                            <div class="aspect-[4/5] overflow-hidden rounded-[1.75rem] bg-stone-200"><x-site.picture :model="$m" collection="photo" sizes="25vw" class="h-full w-full transition duration-700 group-hover:scale-[1.03]" /></div>
                            <h3 class="mt-5 font-display text-2xl text-juniper-800">{{ $m->name }}</h3>
                            <p class="text-sm text-ink-soft">{{ $m->role }}</p>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Affiliations --}}
    <section class="py-20">
        <div class="container-page text-center">
            <p class="eyebrow justify-center">Registered & affiliated</p>
            <ul class="mt-10 flex flex-wrap items-center justify-center gap-10">
                @foreach ([['aff-nepal-government.png', 'Government of Nepal'], ['aff-ntb.jpg', 'Nepal Tourism Board'], ['aff-taan.png', 'TAAN'], ['aff-nma.png', 'Nepal Mountaineering Association'], ['aff-keep.webp', 'KEEP']] as [$file, $label])
                    <li class="flex flex-col items-center gap-3" data-reveal><img src="{{ asset('images/brand/'.$file) }}" alt="{{ $label }}" class="h-20 w-auto object-contain" loading="lazy"><span class="text-xs text-stone-500">{{ $label }}</span></li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="container-narrow pb-16">
        <x-site.section-heading eyebrow="Your questions" title="Answered with care" align="center" />
        <div class="mt-10 divide-y divide-stone-300/70 border-y border-stone-300/70">
            @foreach ($faqs as [$q, $a])
                <details class="group py-2" @if ($loop->first) open @endif>
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-6 py-4 font-display text-xl text-juniper-800 [&::-webkit-details-marker]:hidden">{{ $q }}<x-heroicon-m-plus class="size-5 shrink-0 text-terracotta-500 transition group-open:rotate-45" /></summary>
                    <p class="pb-6 text-ink-soft">{{ $a }}</p>
                </details>
            @endforeach
        </div>
    </section>
</x-layouts.site>
