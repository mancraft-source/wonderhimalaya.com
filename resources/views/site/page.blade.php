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
        <div class="container-page grid gap-14 py-16 lg:grid-cols-12 lg:py-20">
            <aside class="lg:col-span-3">
                <details class="group rounded-2xl bg-stone-50 ring-1 ring-stone-300/60 lg:hidden">
                    <summary class="flex cursor-pointer list-none items-center justify-between px-5 py-4 text-sm font-semibold text-juniper-800 [&::-webkit-details-marker]:hidden">
                        In this section: {{ $page->title }} <x-heroicon-m-chevron-down class="size-4 transition group-open:rotate-180" />
                    </summary>
                    <ul class="space-y-1 px-5 pb-4">
                        @foreach ($siblings as $s)
                            <li><a href="{{ url($s->publicPath()) }}" @class(['block py-1.5 text-[0.95rem]', 'font-semibold text-terracotta-500' => $s->id === $page->id, 'text-ink-soft' => $s->id !== $page->id])>{{ $s->title }}</a></li>
                        @endforeach
                    </ul>
                </details>
                <nav class="hidden lg:sticky lg:top-32 lg:block" aria-label="{{ $page->parent?->title ?? $page->title }}">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">{{ $page->parent?->title ?? 'In this section' }}</p>
                    <ul class="mt-4 space-y-1 border-l border-stone-300/70">
                        @foreach ($siblings as $s)
                            <li><a href="{{ url($s->publicPath()) }}" @class(['-ml-px block border-l-2 py-1.5 pl-4 text-[0.95rem] transition', 'border-terracotta-500 font-semibold text-juniper-800' => $s->id === $page->id, 'border-transparent text-ink-soft hover:text-juniper-800' => $s->id !== $page->id])>{{ $s->title }}</a></li>
                        @endforeach
                    </ul>
                </nav>
            </aside>
            <div class="min-w-0 lg:col-span-8 lg:col-start-5">
                @foreach ($blocks as $block)
                    @includeFirst(['site.blocks.'.str_replace('_', '-', $block['type'] ?? 'rich-text'), 'site.blocks.rich-text'], ['data' => $block['data'] ?? [], 'narrow' => true])
                @endforeach
            </div>
        </div>
    @else
        <div class="pb-10">
            @foreach ($blocks as $block)
                @includeFirst(['site.blocks.'.str_replace('_', '-', $block['type'] ?? 'rich-text'), 'site.blocks.rich-text'], ['data' => $block['data'] ?? [], 'narrow' => false])
            @endforeach
        </div>
    @endif
</x-layouts.site>
