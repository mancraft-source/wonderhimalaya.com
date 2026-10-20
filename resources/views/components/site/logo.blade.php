{{-- Brand badge from the live site. `light` adds a soft halo so it reads on dark photography. --}}
@props(['light' => false, 'size' => 'md'])
<span {{ $attributes->class('inline-flex items-center') }}>
    <img src="{{ asset('images/brand/logo-badge-160.webp') }}"
         srcset="{{ asset('images/brand/logo-badge-160.webp') }} 1x, {{ asset('images/brand/logo-badge-256.webp') }} 2x"
         alt="Wonder Himalaya — Trekking, Tour, Expedition, Heli" width="160" height="160"
         @class([
             'w-auto transition-[height] duration-500 ease-(--ease-mountain)',
             'h-16 lg:h-[4.6rem] group-data-[scrolled]/header:lg:h-16' => $size === 'md',
             'h-24 lg:h-28' => $size === 'lg',
             'drop-shadow-[0_6px_18px_rgba(0,0,0,0.35)]' => $light,
         ])>
</span>
