{{-- One question in an FAQ list. Native <details> (works without JS, searchable with Ctrl+F); initAccordion() in
     resources/js/app.js animates the height and fades the answer in. Wrap a list in a divide-y container.
     <x-site.faq-item :question="$faq->question" :number="$loop->iteration" :open="$loop->first">{!! $faq->answer !!}</x-site.faq-item> --}}
@props(['question', 'number' => null, 'open' => false])
<details data-accordion {{ $attributes->class('group/faq') }} @if ($open) open @endif>
    <summary class="flex cursor-pointer list-none items-center gap-5 py-5 outline-none [&::-webkit-details-marker]:hidden focus-visible:[&>span:last-child]:ring-2 focus-visible:[&>span:last-child]:ring-terracotta-500">
        @if ($number)
            <span class="data w-6 shrink-0 text-xs text-stone-400 transition-colors duration-500 group-open/faq:text-terracotta-500">{{ str_pad($number, 2, '0', STR_PAD_LEFT) }}</span>
        @endif
        <span class="flex-1 font-display text-xl leading-snug text-juniper-800 transition-colors duration-300 group-hover/faq:text-terracotta-600">{{ $question }}</span>
        <span class="grid size-9 shrink-0 place-items-center rounded-full text-terracotta-500 ring-1 ring-juniper-800/15 transition duration-500 ease-(--ease-mountain) group-hover/faq:ring-terracotta-500/60 group-open/faq:rotate-[135deg] group-open/faq:bg-terracotta-500 group-open/faq:text-white group-open/faq:ring-terracotta-500">
            <x-heroicon-m-plus class="size-4" />
        </span>
    </summary>
    <div data-accordion-body @class(['prose prose-wh max-w-none pb-7 pr-14 text-ink-soft', 'pl-11' => $number])>{{ $slot }}</div>
</details>
