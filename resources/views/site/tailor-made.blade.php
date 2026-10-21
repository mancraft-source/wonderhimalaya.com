@php $c = \App\Support\Content::landing('tailor_made'); @endphp
<x-layouts.site :seo="$seo">
    <section class="grain relative overflow-hidden">
        <div class="topo pointer-events-none absolute -top-32 -left-40 size-[56rem] opacity-80" aria-hidden="true"></div>
        <div class="container-page relative grid gap-14 pt-14 pb-24 lg:grid-cols-12 lg:pt-20">
            <div class="lg:col-span-4">
                <div class="lg:sticky lg:top-36">
                    <x-site.breadcrumbs :items="[['label' => 'Tailor-made']]" />
                    <p class="eyebrow mt-8">{{ $c['eyebrow'] }}</p>
                    <h1 class="mt-5 font-display text-display-md">{{ $c['title'] }}</h1>
                    <p class="mt-6 text-lg text-ink-soft">{{ $c['intro'] }}</p>
                    <ul class="mt-10 space-y-5">
                        @foreach (collect($c['benefits'])->map(fn ($x) => [$x['text'] ?? '', $x['icon'] ?? 'check-badge']) as [$text, $icon])
                            <li class="flex gap-4"><span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-juniper-800 text-saffron-300"><x-dynamic-component :component="'heroicon-o-'.$icon" class="size-5" /></span><span class="pt-2 text-ink-soft">{{ $text }}</span></li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <div class="lg:col-span-8">
                <livewire:tailor-made-builder />
            </div>
        </div>
    </section>
</x-layouts.site>
