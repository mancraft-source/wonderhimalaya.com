@props(['light' => false, 'compact' => false])
<span {{ $attributes->class('inline-flex items-center gap-3') }}>
    <svg viewBox="0 0 48 48" aria-hidden="true" class="size-10 shrink-0 transition-colors duration-500">
        <circle cx="34" cy="13" r="5.2" class="fill-saffron-500" />
        <path d="M2 41 16.5 17.5 23 27l6.5-9.5L46 41Z" @class(['transition-colors duration-500', 'fill-white' => $light, 'fill-juniper-800' => ! $light]) />
        <path d="m16.5 17.5 3.4 5.5-2.1-1.2-1.6 2.2-1.4-1.6-2.1 1.3Z M29.5 17.5l4.2 6.2-2.4-1.1-1.6 1.8-1.5-1.7-1.7.9Z" @class(['transition-colors duration-500', 'fill-juniper-800' => $light, 'fill-stone-100' => ! $light]) />
        <path d="M21 41c1.5-4.5 6.5-5 6-8.5S23.5 29 26 26.5" fill="none" stroke-width="1.6" stroke-linecap="round" stroke-dasharray="0.1 3.2" class="stroke-terracotta-500" />
    </svg>
    @unless ($compact)
        <span class="flex flex-col leading-none">
            <span @class(['font-display text-[1.45rem] tracking-tight transition-colors duration-500', 'text-white' => $light, 'text-juniper-800' => ! $light])>Wonder Himalaya</span>
            <span @class(['mt-1 text-[0.6rem] font-semibold uppercase tracking-[0.32em] transition-colors duration-500', 'text-white/70' => $light, 'text-stone-500' => ! $light])>Treks · Tours · Expeditions</span>
        </span>
    @endunless
</span>
