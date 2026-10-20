@props(['trip', 'size' => 'md', 'showPrice' => true])
@php
    $price = $trip->displayPrice();
    $region = $trip->destination?->name;
@endphp
<article {{ $attributes->class('group relative flex flex-col') }}>
    <a href="{{ url($trip->publicPath()) }}" class="relative block overflow-hidden rounded-[1.75rem] bg-juniper-900" aria-label="{{ $trip->title }}">
        <div @class(['overflow-hidden', 'aspect-[3/2]' => $size === 'lg', 'aspect-[4/3]' => $size !== 'lg'])>
            <x-site.picture :model="$trip" collection="hero" sizes="(min-width: 1280px) 30vw, (min-width: 768px) 45vw, 92vw"
                class="h-full w-full transition duration-[1.4s] ease-(--ease-mountain) group-hover:scale-[1.04]" />
        </div>
        <div class="absolute inset-x-0 top-0 flex flex-wrap gap-2 p-4">
            @if ($trip->is_fixed_departure)<span class="chip-light">Group departure</span>@endif
            @if ($trip->is_top_selling)<span class="chip-light !bg-saffron-500/90 !text-ink">Top seller</span>@endif
            @if ($trip->difficulty)<span class="chip-light">{{ $trip->difficulty->name }}</span>@endif
        </div>
        @if ($trip->max_altitude_m)
            <div class="absolute right-4 bottom-4 rounded-full bg-stone-100/90 px-3 py-1.5 text-juniper-800 backdrop-blur">
                <span class="data">▲ {{ number_format($trip->max_altitude_m) }} m</span>
            </div>
        @endif
    </a>
    <div class="flex flex-1 flex-col pt-5">
        <div class="flex items-center gap-3 text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">
            @if ($region)<span>{{ $region }}</span>@endif
            @if ($region && $trip->duration_days)<span class="size-1 rounded-full bg-terracotta-500"></span>@endif
            @if ($trip->duration_days)<span>{{ $trip->durationLabel() }}</span>@endif
        </div>
        <h3 @class(['mt-2 font-display leading-tight text-juniper-800', 'text-[1.7rem]' => $size === 'lg', 'text-2xl' => $size !== 'lg'])>
            <a href="{{ url($trip->publicPath()) }}" class="decoration-terracotta-500/40 decoration-1 underline-offset-4 group-hover:underline">{{ $trip->title }}</a>
        </h3>
        @if ($trip->excerpt && $size === 'lg')
            <p class="mt-3 line-clamp-2 text-ink-soft">{{ $trip->excerpt }}</p>
        @endif
        @if ($showPrice)
            <div class="mt-auto flex items-end justify-between pt-4">
                @if ($price)
                    <p class="text-sm text-ink-soft">From
                        @if ($trip->sale_price && $trip->price_from && $trip->sale_price < $trip->price_from)
                            <s class="text-stone-400">US${{ number_format((float) $trip->price_from) }}</s>
                        @endif
                        <span class="font-display text-xl text-juniper-800">US${{ number_format($price) }}</span>
                    </p>
                @else
                    <p class="text-sm text-ink-soft">Price on request</p>
                @endif
                <span class="inline-flex size-10 items-center justify-center rounded-full border border-juniper-800/15 text-juniper-800 transition group-hover:border-terracotta-500 group-hover:bg-terracotta-500 group-hover:text-white">
                    <x-heroicon-m-arrow-up-right class="size-4" />
                </span>
            </div>
        @endif
    </div>
</article>
