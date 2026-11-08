@php
    $blocks = collect($page->blocks ?? []);
    $leadHero = ($blocks->first()['type'] ?? null) === 'hero';
    $template = $page->template ?? 'default';
    $crumbs = array_values(array_filter([
        $page->parent ? ['label' => $page->parent->title, 'url' => url($page->parent->publicPath())] : null,
        ['label' => $page->title],
    ]));
    $heroMedia = $page->getFirstMedia('hero');
@endphp
<x-layouts.site :seo="$seo" :transparent="$leadHero || ($heroMedia && $template !== 'legal')">
    @if ($leadHero)
        @include('site.blocks.hero', ['data' => $blocks->first()['data'] ?? [], 'crumbs' => $crumbs])
        @php $blocks = $blocks->slice(1); @endphp
    @else
        <x-site.page-hero :media="$template === 'legal' ? null : $heroMedia" :eyebrow="$page->parent?->title" :title="e($page->title)" :intro="$page->excerpt" :crumbs="$crumbs" />
    @endif

    @if (in_array($template, ['guide', 'legal'], true) && $siblings->isNotEmpty())
        @php
            $bodyHtml = $blocks->map(fn ($b) => \App\Support\RichText::normalize($b['data']['content'] ?? ''))->implode('');
            $outline = collect(\App\Support\RichText::outline($bodyHtml));
            $outline = ($outline->where('level', 2)->count() >= 4 ? $outline->where('level', 2) : $outline)->values();
            $chapter = 0;
            $position = $siblings->search(fn ($s) => $s->id === $page->id);
            $previous = $position !== false && $position > 0 ? $siblings[$position - 1] : null;
            $next = $position !== false ? $siblings->get($position + 1) : null;
            $isGuide = $template === 'guide';
        @endphp
        <div class="container-page grid gap-12 pt-14 pb-6 lg:grid-cols-12 lg:gap-10 lg:pt-20 lg:pb-10">
            <aside class="lg:col-span-3">
                <details class="group rounded-2xl bg-stone-50 ring-1 ring-stone-300/60 lg:hidden">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 text-sm font-semibold text-juniper-800 [&::-webkit-details-marker]:hidden">
                        <span class="min-w-0"><span class="block text-[0.65rem] tracking-[0.18em] text-stone-500 uppercase">{{ $page->parent?->title ?? 'In this section' }}</span><span class="block truncate">{{ $page->title }}</span></span>
                        <x-heroicon-m-chevron-down class="size-4 shrink-0 transition group-open:rotate-180" />
                    </summary>
                    <ul class="space-y-1 px-5 pb-4">
                        @foreach ($siblings as $s)
                            <li><a href="{{ url($s->publicPath()) }}" @class(['block py-1.5 text-[0.95rem]', 'font-semibold text-terracotta-500' => $s->id === $page->id, 'text-ink-soft' => $s->id !== $page->id]) @if ($s->id === $page->id) aria-current="page" @endif>{{ $s->title }}</a></li>
                        @endforeach
                    </ul>
                </details>
                <nav class="hidden lg:sticky lg:top-32 lg:block" aria-label="{{ $page->parent?->title ?? $page->title }}">
                    <p class="text-xs font-semibold tracking-[0.18em] text-stone-500 uppercase">{{ $page->parent?->title ?? 'In this section' }}</p>
                    <ul class="mt-4 space-y-0.5 border-l border-stone-300/70">
                        @foreach ($siblings as $s)
                            <li><a href="{{ url($s->publicPath()) }}" @class(['-ml-px block border-l-2 py-1.5 pl-4 text-[0.95rem] transition', 'border-terracotta-500 font-semibold text-juniper-800' => $s->id === $page->id, 'border-transparent text-ink-soft hover:border-stone-400 hover:text-juniper-800' => $s->id !== $page->id]) @if ($s->id === $page->id) aria-current="page" @endif>{{ $s->title }}</a></li>
                        @endforeach
                    </ul>
                </nav>
            </aside>

            <article class="min-w-0 lg:col-span-9 xl:col-span-6">
                @if ($outline->count() >= 2)
                    {{-- Below xl the index sits above the text. --}}
                    <nav class="mb-8 rounded-2xl bg-stone-200/50 p-6 xl:hidden" aria-label="On this page">
                        <p class="text-xs font-semibold tracking-[0.18em] text-stone-500 uppercase">On this page</p>
                        <ol class="mt-3 space-y-1.5 text-[0.95rem]">
                            @foreach ($outline as $h)
                                <li @class(['flex gap-3', 'pl-9 text-sm' => $h['level'] === 3])>@if ($h['level'] === 2)<span class="data pt-0.5 text-terracotta-500">{{ str_pad(++$chapter, 2, '0', STR_PAD_LEFT) }}</span>@endif<a href="#{{ $h['id'] }}" class="text-juniper-800 hover:underline">{{ $h['text'] }}</a></li>
                            @endforeach
                            @php $chapter = 0; @endphp
                        </ol>
                    </nav>
                @endif

                <div class="prose-guide">
                    @foreach ($blocks as $block)
                        @includeFirst(['site.blocks.'.str_replace('_', '-', $block['type'] ?? 'rich-text'), 'site.blocks.rich-text'], ['data' => $block['data'] ?? [], 'narrow' => true])
                    @endforeach
                </div>

                @if ($previous || $next)
                    <nav class="mt-14 grid gap-4 border-t border-stone-300/70 pt-10 sm:grid-cols-2" aria-label="More in {{ $page->parent?->title ?? 'this section' }}">
                        @if ($previous)
                            <a href="{{ url($previous->publicPath()) }}" class="group rounded-2xl p-5 ring-1 ring-stone-300/70 transition hover:bg-stone-50 hover:ring-juniper-500">
                                <span class="flex items-center gap-2 text-xs font-semibold tracking-[0.16em] text-stone-500 uppercase"><x-heroicon-m-arrow-left class="size-4 transition group-hover:-translate-x-1" /> Previous</span>
                                <span class="mt-2 block font-display text-xl text-juniper-800">{{ $previous->title }}</span>
                            </a>
                        @endif
                        @if ($next)
                            <a href="{{ url($next->publicPath()) }}" class="group rounded-2xl p-5 text-right ring-1 ring-stone-300/70 transition hover:bg-stone-50 hover:ring-juniper-500 sm:col-start-2">
                                <span class="flex items-center justify-end gap-2 text-xs font-semibold tracking-[0.16em] text-stone-500 uppercase">Next <x-heroicon-m-arrow-right class="size-4 transition group-hover:translate-x-1" /></span>
                                <span class="mt-2 block font-display text-xl text-juniper-800">{{ $next->title }}</span>
                            </a>
                        @endif
                    </nav>
                @endif
            </article>

            <aside class="hidden xl:col-span-3 xl:block">
                <div class="sticky top-32 space-y-6">
                    @if ($outline->count() >= 2)
                        <nav aria-label="On this page" data-toc>
                            <p class="text-xs font-semibold tracking-[0.18em] text-stone-500 uppercase">On this page</p>
                            <ol class="mt-4 space-y-2 text-sm">
                                @foreach ($outline as $h)
                                    <li @class(['pl-8' => $h['level'] === 3])><a href="#{{ $h['id'] }}" data-toc-link class="flex gap-3 text-ink-soft transition hover:text-juniper-800 data-[active]:font-semibold data-[active]:text-juniper-800">@if ($h['level'] === 2)<span class="data pt-px text-stone-400">{{ str_pad(++$chapter, 2, '0', STR_PAD_LEFT) }}</span>@endif<span>{{ $h['text'] }}</span></a></li>
                                @endforeach
                            </ol>
                        </nav>
                    @endif
                    @if ($isGuide)
                        <div class="relative overflow-hidden rounded-[1.75rem] bg-juniper-900 p-7 text-stone-100">
                            <div class="topo pointer-events-none absolute inset-0 opacity-15 invert"></div>
                            <x-site.gear icon="compass" class="absolute -top-4 -right-4 w-28 rotate-12 text-saffron-300/25" />
                            <p class="relative font-display text-2xl leading-snug text-white">Questions about your trek?</p>
                            <p class="relative mt-2 text-sm text-stone-300">Talk to our Kathmandu team about seasons, permits, kit and the trek that suits you.</p>
                            <a href="{{ route('contact') }}" class="btn-primary relative mt-6 !px-5 !py-3 text-sm">Ask a trek expert <x-heroicon-m-arrow-right class="size-4" /></a>
                        </div>
                    @endif
                </div>
            </aside>
        </div>
    @else
        <div class="pb-10">
            @foreach ($blocks as $block)
                @includeFirst(['site.blocks.'.str_replace('_', '-', $block['type'] ?? 'rich-text'), 'site.blocks.rich-text'], ['data' => $block['data'] ?? [], 'narrow' => false])
            @endforeach
        </div>
    @endif
</x-layouts.site>
