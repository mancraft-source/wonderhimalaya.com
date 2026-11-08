{{-- Scroll altimeter: reading the page is the climb from Kathmandu to the summit of Everest.
     Driven by initAltimeter() in resources/js/app.js; decorative, desktop only. --}}
@php
    $base = 1400;
    $summit = 8849;
    $waypoints = [
        ['Kathmandu', 1400], ['Lukla', 2860], ['Namche Bazaar', 3440], ['Tengboche', 3867],
        ['Dingboche', 4410], ['Everest Base Camp', 5364], ['Camp II', 6400], ['South Col', 7906], ['Summit of Everest', 8849],
    ];
@endphp
<div data-altimeter data-base="{{ $base }}" data-summit="{{ $summit }}" data-waypoints='@json($waypoints)'
     class="altimeter pointer-events-none fixed top-1/2 right-3 z-40 hidden -translate-y-1/2 xl:block" aria-hidden="true">
    <div class="flex flex-col items-center gap-2 rounded-full bg-stone-100/75 px-1.5 py-2.5 shadow-[0_10px_30px_-12px_rgba(15,29,25,0.45)] ring-1 ring-juniper-800/10 backdrop-blur-md">
        <svg viewBox="0 0 16 12" class="w-3.5 text-juniper-800"><path d="M1 11 6 3l2.5 3.5L10.5 1 15 11Z" fill="currentColor" /><path d="m10.5 1-1.6 3.4 1.6-.8 1.4.9Z" fill="#fbf9f5" /></svg>
        <div class="relative h-[38vh] w-[3px] rounded-full bg-juniper-800/15">
            <div class="altimeter__fill absolute inset-x-0 bottom-0 rounded-full bg-gradient-to-t from-juniper-500 to-terracotta-500"></div>
            @foreach ($waypoints as [$name, $alt])
                @continue($loop->first || $loop->last)
                <span class="absolute left-1/2 h-px w-2 -translate-x-1/2 bg-juniper-800/35" style="bottom: {{ round(($alt - $base) / ($summit - $base) * 100, 2) }}%"></span>
            @endforeach
            <div class="altimeter__marker absolute left-1/2 size-2.5 -translate-x-1/2 translate-y-1/2 rounded-full border-2 border-stone-100 bg-terracotta-500 shadow">
                <span class="altimeter__pill absolute top-1/2 right-full mr-3 rounded-full bg-juniper-900 px-3 py-1.5 text-right whitespace-nowrap text-white shadow-lg">
                    <span data-alt-value class="data block text-[0.8rem] leading-none text-saffron-300">{{ number_format($base) }} m</span>
                    <span data-alt-place class="mt-1 block text-[0.6rem] font-semibold tracking-[0.16em] uppercase leading-none text-white/70">Kathmandu</span>
                </span>
            </div>
        </div>
        <span class="data text-[0.55rem] text-juniper-800/60">KTM</span>
    </div>
</div>
