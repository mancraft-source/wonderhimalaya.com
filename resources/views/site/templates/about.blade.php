{{-- About us — designed template. Copy from the client's existing About page. --}}
@php
    $up = fn (string $p) => asset('storage/wp-content/uploads/'.$p);
    $c = \App\Support\Content::page($page);
    $team = \App\Models\TeamMember::where('is_published', true)->with('media')->orderBy('sort')->orderBy('name')->get();
    $founders = $team->where('is_guide', false)->take(4);
    $stats = \App\Models\Setting::get('stats', []);
@endphp
<x-layouts.site :seo="$seo" transparent>
    {{-- Hero --}}
    <section class="relative isolate flex h-[78svh] min-h-[36rem] max-h-[52rem] flex-col justify-end overflow-hidden bg-juniper-950 text-white">
        <div class="absolute inset-0 -z-10" data-parallax>
            <img src="{{ \App\Support\Content::image($c['hero_image'], \App\Support\BrandImage::url('annapurna-base-camp-min-scaled.jpg', $up('2026/04/IMG_1124-scaled.jpeg'))) }}" alt="Trekkers on a Himalayan trail with Wonder Himalaya" class="h-full w-full animate-slow-zoom object-cover" fetchpriority="high">
        </div>
        <div class="scrim-hero absolute inset-0 -z-10"></div>
        <div class="container-page pt-36 pb-16 lg:pb-24">
            <x-site.breadcrumbs :items="[['label' => 'About us']]" light />
            <p class="eyebrow eyebrow-light mt-8">{{ $c['hero_eyebrow'] }}</p>
            <h1 class="mt-5 max-w-4xl font-display text-display-lg text-white">{{ $c['hero_title'] }} <em class="text-saffron-300">{{ $c['hero_title_em'] }}</em></h1>
            <p class="mt-6 max-w-2xl text-lg text-white/85">{{ $c['hero_subtitle'] }}</p>
        </div>
    </section>

    {{-- Story --}}
    <section class="grain relative overflow-hidden py-24 lg:py-32">
        <div class="topo pointer-events-none absolute -top-32 -right-40 size-[56rem] opacity-70" aria-hidden="true"></div>
        <div class="container-page relative grid items-center gap-16 lg:grid-cols-12">
            <div class="lg:col-span-5" data-reveal>
                <div class="relative">
                    <div class="aspect-[4/5] overflow-hidden img-organic">
                        <img src="{{ \App\Support\Content::image($c['story_image'], $up('2025/02/about-wonder-himalaya-in-nepal-scaled.jpg')) }}" alt="The Wonder Himalaya office in Thamel, Kathmandu" class="h-full w-full object-cover" loading="lazy">
                    </div>
                    <div class="absolute -right-6 -bottom-8 hidden rounded-[1.75rem] bg-juniper-800 p-6 text-stone-100 shadow-2xl sm:block">
                        <p class="font-display text-4xl text-saffron-300">{{ $c['story_badge_title'] }}</p>
                        <p class="text-sm text-stone-300">{{ $c['story_badge_text'] }}</p>
                    </div>
                </div>
            </div>
            <div class="lg:col-span-6 lg:col-start-7" data-reveal>
                <p class="eyebrow">{{ $c['story_eyebrow'] }}</p>
                <h2 class="mt-5 font-display text-display-md">{{ $c['story_title'] }}</h2>
                <div class="prose prose-lg prose-wh mt-8 text-ink-soft">{!! $c['story_body'] !!}</div>
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
            @foreach ([['Our vision', $c['vision_lead'], $c['vision_body']], ['Our mission', $c['mission_lead'], $c['mission_body']]] as [$label, $lead, $body])
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
            <x-site.section-heading :eyebrow="$c['values_eyebrow']" :title="e($c['values_title'])" align="center" />
            <div class="mt-16 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($c['values'] as $v)
                    <div class="card-surface p-8 text-center" data-reveal>
                        <div class="mx-auto flex size-16 items-center justify-center rounded-full bg-stone-200/70 text-terracotta-500">
                            <x-dynamic-component :component="'heroicon-o-'.($v['icon'] ?? 'check-badge')" class="size-7" />
                        </div>
                        <h3 class="mt-6 font-display text-2xl">{{ $v['title'] ?? '' }}</h3>
                        <p class="mt-3 text-ink-soft">{{ $v['text'] ?? '' }}</p>
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
                    <x-site.section-heading :eyebrow="$c['team_eyebrow']" :title="e($c['team_title'])" :intro="$c['team_intro']" />
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
        <x-site.section-heading :eyebrow="$c['faq_eyebrow']" :title="e($c['faq_title'])" align="center" />
        <div class="mt-10 divide-y divide-stone-300/70 border-y border-stone-300/70">
            @foreach ($c['faqs'] as ['question' => $q, 'answer' => $a])
                <details class="group py-2" @if ($loop->first) open @endif>
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-6 py-4 font-display text-xl text-juniper-800 [&::-webkit-details-marker]:hidden">{{ $q }}<x-heroicon-m-plus class="size-5 shrink-0 text-terracotta-500 transition group-open:rotate-45" /></summary>
                    <div class="prose prose-wh pb-6 text-ink-soft">{!! str_contains((string) $a, '<') ? $a : '<p>'.e($a).'</p>' !!}</div>
                </details>
            @endforeach
        </div>
    </section>
</x-layouts.site>
