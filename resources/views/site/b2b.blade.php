@php $c = \App\Support\Content::landing('b2b'); @endphp
<x-layouts.site :seo="$seo">
    {{-- Hero --}}
    <section class="relative overflow-hidden bg-juniper-900 text-stone-100">
        <div class="topo pointer-events-none absolute inset-0 opacity-30 invert" aria-hidden="true"></div>
        <div class="container-page relative grid gap-12 py-20 lg:grid-cols-12 lg:py-32">
            <div class="lg:col-span-8">
                <x-site.breadcrumbs :items="[['label' => 'Travel partners']]" light />
                <p class="eyebrow eyebrow-light mt-8">{{ $c['hero_eyebrow'] }}</p>
                <h1 class="mt-6 font-display text-display-lg text-stone-100">{{ $c['hero_title'] }}<br><em class="text-saffron-300">{{ $c['hero_title_em'] }}</em></h1>
                <p class="mt-8 max-w-2xl text-lg text-stone-300">{{ $c['hero_subtitle'] }}</p>
                <div class="mt-10 flex flex-wrap gap-3">
                    <a href="#partner-form" class="btn-primary">Become a partner <x-heroicon-m-arrow-right class="size-4" /></a>
                    <a href="mailto:{{ \App\Models\Setting::get('company.email', 'info@wonderhimalaya.com') }}?subject=B2B%20rate%20sheet" class="btn-ghost-light">Request rate sheet</a>
                </div>
            </div>
            <div class="flex flex-col justify-end gap-3 lg:col-span-4">
                @foreach ($c['credentials'] as $badge)
                    <p class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 px-5 py-4 text-sm"><x-heroicon-s-check-badge class="size-5 shrink-0 text-saffron-300" /> {{ $badge }}</p>
                @endforeach
            </div>
        </div>
    </section>

    {{-- What we do --}}
    <section class="py-24 lg:py-32">
        <div class="container-page">
            <x-site.section-heading :eyebrow="$c['services_eyebrow']" :title="e($c['services_title'])" :intro="$c['services_intro']" />
            <div class="mt-16 grid gap-px overflow-hidden rounded-[2rem] bg-stone-300/60 ring-1 ring-stone-300/60 md:grid-cols-2 lg:grid-cols-4">
                @foreach (collect($c['services'])->map(fn ($x) => [$x['title'] ?? '', $x['text'] ?? '', $x['icon'] ?? 'check-badge']) as [$title, $text, $icon])
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
                <x-site.section-heading :eyebrow="$c['destinations_eyebrow']" :title="e($c['destinations_title'])" />
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
            <x-site.section-heading :eyebrow="$c['integrity_eyebrow']" :title="e($c['integrity_title'])" />
            <dl class="mt-14 grid grid-cols-2 gap-px overflow-hidden rounded-[2rem] bg-stone-300/60 ring-1 ring-stone-300/60 lg:grid-cols-4">
                @foreach (collect($c['integrity'])->map(fn ($i) => [$i['value'] ?? '', $i['label'] ?? ''])->merge(collect($stats)->map(fn ($s) => [$s['value'], $s['label']]))->take(4) as [$v, $l])
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
                <p class="eyebrow eyebrow-light">{{ $c['form_eyebrow'] }}</p>
                <h2 class="mt-6 font-display text-display-md text-stone-100">{{ $c['form_title'] }}</h2>
                <p class="mt-6 text-stone-300">{{ $c['form_intro'] }}</p>
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
