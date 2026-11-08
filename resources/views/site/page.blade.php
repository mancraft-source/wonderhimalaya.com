@php
    $blocks = collect($page->blocks ?? [])->values();
    // A first heading that only repeats the page title ("Alert" on "Alerts") is dropped; the hero already says it.
    if (($blocks->first()['type'] ?? null) === 'rich_text') {
        $blocks = $blocks->replace([0 => array_replace_recursive($blocks->first(), ['data' => ['content' => \App\Support\RichText::withoutTitleHeading((string) ($blocks->first()['data']['content'] ?? ''), $page->title)]])]);
    }
    $sectionLabel = $page->parent?->title ?? (($page->template ?? null) === 'legal' ? 'Booking & policies' : 'In this section');
    $leadHero = ($blocks->first()['type'] ?? null) === 'hero';
    $template = $page->template ?? 'default';
    $crumbs = array_values(array_filter([
        $page->parent ? ['label' => $page->parent->title, 'url' => url($page->parent->publicPath())] : null,
        ['label' => $page->title],
    ]));
    $heroMedia = $page->getFirstMedia('hero');

    /*
     | Process pages show their steps as a tracker in the left column. Steps come from the content's own numbered
     | headings ("Step 1: …") when it has them; prose-only pages are mapped here instead, each step anchored to the
     | paragraph it starts at ("before") or, without one, closing the article as a final card.
     */
    $definedProcesses = [
        'how-to-book-a-trip-with-wonder-himalaya' => [
            'eyebrow' => 'How booking works',
            'heading' => 'Book your trek in four steps',
            'steps' => [
                ['title' => 'Choose your trip', 'summary' => 'Check the itinerary and read our Terms and Conditions. Questions? Email, call or WhatsApp us.', 'before' => 'As you are here at the booking page'],
                ['title' => 'Pay the 30% deposit', 'summary' => '30% of the total trip fee books your trip. It is non-refundable.', 'before' => 'Regarding Deposite'],
                ['title' => 'Choose how to pay', 'summary' => 'Bank transfer, credit card (3.5% fee) or cash if you are already in Nepal.', 'before' => 'Swift/ Wired Transfer'],
                ['title' => 'Pay the rest & set off', 'summary' => 'Pay the balance after arriving in Kathmandu or before the trip starts.', 'finale' => 'You can pay the rest of the payment after arriving at Kathmandu or before we start the trip.'],
            ],
        ],
    ];
    $process = null;
    if (in_array($template, ['guide', 'legal'], true)) {
        $definedProcess = $definedProcesses[$page->path] ?? null;
        if ($definedProcess) {
            $process = $definedProcess;
            $process['steps'] = collect($definedProcess['steps'])->map(fn ($step, $i) => $step + ['id' => 'step-'.($i + 1), 'number' => $i + 1])->all();
        } else {
            $detectedSteps = \App\Support\RichText::stepsFromOutline(\App\Support\RichText::outline($blocks->map(fn ($b) => \App\Support\RichText::normalize($b['data']['content'] ?? ''))->implode('')));
            $process = $detectedSteps ? ['eyebrow' => 'Step by step', 'heading' => null, 'steps' => $detectedSteps] : null;
        }
    }
@endphp
<x-layouts.site :seo="$seo" :transparent="$leadHero || ($heroMedia && $template !== 'legal')">
    @if ($leadHero)
        @include('site.blocks.hero', ['data' => $blocks->first()['data'] ?? [], 'crumbs' => $crumbs])
        @php $blocks = $blocks->slice(1); @endphp
    @else
        <x-site.page-hero :media="$template === 'legal' ? null : $heroMedia" :eyebrow="$page->parent?->title" :title="e($page->title)" :intro="$page->excerpt" :crumbs="$crumbs" />
    @endif

    @if ($process)
        @php
            $steps = $process['steps'];
            $total = count($steps);
            $inline = collect($steps)->reject(fn ($step) => isset($step['finale']))->all();
            $finales = collect($steps)->filter(fn ($step) => isset($step['finale']));
        @endphp
        <div class="container-page grid gap-10 pt-10 pb-10 lg:grid-cols-12 lg:gap-10 lg:pt-20">
            <aside class="hidden lg:col-span-4 lg:block xl:col-span-3">
                <x-site.process-stepper :steps="$steps" :eyebrow="$process['eyebrow']" :heading="$process['heading']" class="sticky top-32" />
            </aside>

            <article class="min-w-0 lg:col-span-8 xl:col-span-6">
                <x-site.process-stepper :steps="$steps" :eyebrow="$process['eyebrow']" variant="chips" class="sticky top-20 z-30 -mx-4 mb-6 lg:hidden" />

                <div class="prose-guide prose-process">
                    @foreach ($blocks as $block)
                        @if (($block['type'] ?? 'rich_text') === 'rich_text')
                            @php $html = \App\Support\RichText::detailCards(\App\Support\RichText::markSteps(\App\Support\RichText::normalize($block['data']['content'] ?? ''), $inline, $total)); @endphp
                            @if ($html !== '')
                                <section class="py-6"><div class="prose prose-lg prose-wh">{!! $html !!}</div></section>
                            @endif
                        @else
                            @includeFirst(['site.blocks.'.str_replace('_', '-', $block['type'] ?? 'rich-text'), 'site.blocks.rich-text'], ['data' => $block['data'] ?? [], 'narrow' => true])
                        @endif
                    @endforeach
                </div>

                @foreach ($finales as $step)
                    <section id="{{ $step['id'] }}" class="process-finale relative mt-10 overflow-hidden rounded-[1.75rem] bg-juniper-900 p-7 text-stone-100 sm:p-10" data-reveal>
                        <div class="topo pointer-events-none absolute inset-0 opacity-15 invert"></div>
                        <x-site.gear icon="compass" class="absolute -right-6 -bottom-6 w-36 -rotate-12 text-saffron-300/20" />
                        <div class="relative flex items-start gap-5">
                            <span class="process-step__num process-step__num--light" aria-hidden="true">{{ $step['number'] }}</span>
                            <div class="min-w-0">
                                <p class="data text-[0.7rem] tracking-[0.16em] text-saffron-300 uppercase">Final step · {{ $step['number'] }} of {{ $total }}</p>
                                <h2 class="mt-1 font-display text-3xl leading-tight text-white sm:text-4xl">{{ $step['title'] }}</h2>
                                <p class="mt-3 max-w-xl text-stone-300">{{ $step['finale'] }}</p>
                            </div>
                        </div>
                        <div class="relative mt-8 flex flex-wrap gap-3 sm:pl-[4.25rem]">
                            <a href="{{ route('trips.index') }}" class="btn-primary !px-5 !py-3 text-sm">Start your adventure <x-heroicon-m-arrow-right class="size-4" /></a>
                            <a href="{{ route('contact') }}" class="btn-ghost-light !px-5 !py-3 text-sm">Ask us a question</a>
                        </div>
                    </section>
                @endforeach
            </article>

            <aside class="hidden xl:col-span-3 xl:block">
                <div class="sticky top-32 rounded-[1.75rem] bg-stone-50 p-7 ring-1 ring-stone-300/60">
                    <p class="font-display text-2xl leading-snug text-juniper-800">Need a hand booking?</p>
                    <p class="mt-2 text-sm text-ink-soft">Our Kathmandu team will help with dates, payments and anything in between.</p>
                    <ul class="mt-5 space-y-3 text-sm">
                        @if (! empty($company['email']))
                            <li><a href="mailto:{{ $company['email'] }}" class="flex items-center gap-3 text-juniper-800 hover:text-terracotta-500"><x-heroicon-o-envelope class="size-5 shrink-0 text-terracotta-500" /><span class="truncate">{{ $company['email'] }}</span></a></li>
                        @endif
                        @if (! empty($company['whatsapp']))
                            <li><a href="https://wa.me/{{ preg_replace('/\D/', '', $company['whatsapp']) }}" target="_blank" rel="noopener" class="flex items-center gap-3 text-juniper-800 hover:text-terracotta-500"><x-heroicon-o-chat-bubble-left-right class="size-5 shrink-0 text-terracotta-500" /><span>WhatsApp {{ $company['whatsapp'] }}</span></a></li>
                        @endif
                    </ul>
                    <a href="{{ route('contact') }}" class="btn-dark mt-6 !px-5 !py-3 text-sm">Contact us <x-heroicon-m-arrow-right class="size-4" /></a>
                </div>
            </aside>
        </div>
    @elseif (in_array($template, ['guide', 'legal'], true) && $siblings->isNotEmpty())
        @php
            $bodyHtml = $blocks->map(fn ($b) => \App\Support\RichText::normalize($b['data']['content'] ?? ''))->implode('');
            $outline = collect(\App\Support\RichText::outline($bodyHtml));
            // No h2s (e.g. a checklist of h3 questions): the h3s are the chapters.
            if ($outline->where('level', 2)->isEmpty()) {
                $outline = $outline->map(fn ($h) => ['level' => 2] + $h);
            }
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
                        <span class="min-w-0"><span class="block text-[0.65rem] tracking-[0.18em] text-stone-500 uppercase">{{ $sectionLabel }}</span><span class="block truncate">{{ $page->title }}</span></span>
                        <x-heroicon-m-chevron-down class="size-4 shrink-0 transition group-open:rotate-180" />
                    </summary>
                    <ul class="space-y-1 px-5 pb-4">
                        @foreach ($siblings as $s)
                            <li><a href="{{ url($s->publicPath()) }}" @class(['block py-1.5 text-[0.95rem]', 'font-semibold text-terracotta-500' => $s->id === $page->id, 'text-ink-soft' => $s->id !== $page->id]) @if ($s->id === $page->id) aria-current="page" @endif>{{ $s->title }}</a></li>
                        @endforeach
                    </ul>
                </details>
                <nav class="hidden lg:sticky lg:top-32 lg:block" aria-label="{{ $sectionLabel }}">
                    <p class="text-xs font-semibold tracking-[0.18em] text-stone-500 uppercase">{{ $sectionLabel }}</p>
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
                    <div class="relative overflow-hidden rounded-[1.75rem] bg-juniper-900 p-7 text-stone-100">
                        <div class="topo pointer-events-none absolute inset-0 opacity-15 invert"></div>
                        <x-site.gear :icon="$isGuide ? 'compass' : 'map'" class="absolute -top-4 -right-4 w-28 rotate-12 text-saffron-300/25" />
                        @if ($isGuide)
                            <p class="relative font-display text-2xl leading-snug text-white">Questions about your trek?</p>
                            <p class="relative mt-2 text-sm text-stone-300">Talk to our Kathmandu team about seasons, permits, kit and the trek that suits you.</p>
                            <a href="{{ route('contact') }}" class="btn-primary relative mt-6 !px-5 !py-3 text-sm">Ask a trek expert <x-heroicon-m-arrow-right class="size-4" /></a>
                        @else
                            <p class="relative pr-12 font-display text-2xl leading-snug text-white">Questions before you book?</p>
                            <p class="relative mt-2 text-sm text-stone-300">Ask our Kathmandu team about deposits, payments, dates or anything in these policies.</p>
                            <a href="{{ route('contact') }}" class="btn-primary relative mt-6 !px-5 !py-3 text-sm">Contact us <x-heroicon-m-arrow-right class="size-4" /></a>
                            @if (! empty($company['email']))
                                <a href="mailto:{{ $company['email'] }}" class="relative mt-4 flex items-center gap-2 text-sm text-stone-300 hover:text-white"><x-heroicon-o-envelope class="size-4 text-saffron-300" /> {{ $company['email'] }}</a>
                            @endif
                        @endif
                    </div>
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
