{{-- Long-form "About" copy for taxonomy pages (destinations, activities): prose on the right, a sticky aside on the left
     with an "In this guide" index (scroll-spy via initToc) and a facts slot, so the column is never an empty void.
     <x-site.about-section eyebrow="About Annapurna" :html="$destination->description"> …facts card… </x-site.about-section> --}}
@props(['eyebrow', 'html'])
@php
    $bodyHtml = \App\Support\RichText::normalize($html);
    $outline = collect(\App\Support\RichText::outline($bodyHtml))->where('level', 2)->values();
@endphp
<section {{ $attributes->class('container-page grid gap-10 py-16 lg:grid-cols-12 lg:gap-16 lg:py-24') }}>
    <aside class="lg:col-span-4">
        <div class="space-y-8 lg:sticky lg:top-32">
            <p class="eyebrow">{{ $eyebrow }}</p>
            @if ($outline->count() >= 2)
                <nav aria-label="In this guide" data-toc class="hidden border-l border-juniper-800/15 pl-5 lg:block">
                    <p class="text-xs font-semibold tracking-[0.18em] text-ink-soft uppercase">In this guide</p>
                    <ol class="mt-4 space-y-3 text-[0.95rem]">
                        @foreach ($outline as $h)
                            <li><a href="#{{ $h['id'] }}" data-toc-link class="flex gap-3 text-ink-soft transition hover:text-juniper-800 data-[active]:font-semibold data-[active]:text-juniper-800"><span class="data pt-px text-stone-400">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><span>{{ $h['text'] }}</span></a></li>
                        @endforeach
                    </ol>
                </nav>
            @endif
            {{ $slot }}
        </div>
    </aside>
    <div class="prose-guide lg:col-span-8 xl:col-span-7">
        <div class="prose prose-lg prose-wh max-w-none">{!! $bodyHtml !!}</div>
    </div>
</section>
