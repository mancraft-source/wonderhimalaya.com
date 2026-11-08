{{-- Hand-drawn hiking-gear line art (original SVG). Decorative only.
     <x-site.gear icon="compass" class="absolute right-10 top-20 w-28 text-juniper-800/15" data-parallax="-0.08" /> --}}
@props(['icon' => 'compass'])
<svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" {{ $attributes->class('pointer-events-none') }}>
    @switch($icon)
        @case('compass')
            <circle cx="32" cy="34" r="22" />
            <circle cx="32" cy="34" r="17.5" stroke-dasharray="1.5 3.2" />
            <path d="M29 10.5a3 3 0 1 1 6 0" />
            <path d="M32 18 37 34 32 50 27 34Z" />
            <path d="M32 18 37 34H27Z" fill="currentColor" fill-opacity=".35" />
            <circle cx="32" cy="34" r="1.8" />
            <path d="M32 13.5v2.5M32 52v2.5M10.5 34H13M51 34h2.5" />
            @break
        @case('ice-axe')
            <path d="M7 19c9-6 20-8 27-7l21-2.5-3.5 6.5L35 17c-8 0-18 1-28 2Z" />
            <path d="M31 13.5h5.5v41.5a2.75 2.75 0 0 1-5.5 0Z" />
            <path d="M31 56.5 33.75 62l2.75-5.5" />
            <path d="M36.5 28c7 1.5 9 6 6.5 10.5s-6.5 4-6.5 4" />
            <path d="M31 24h5.5M31 46h5.5" stroke-dasharray="1 2" />
            @break
        @case('boot')
            <path d="M15 8h15l1.5 21c9 1.5 17.5 5.5 21.5 11.5 2 3 1.5 7.5-2 9.5H12c-3 0-4.5-2-4.5-4.5Z" />
            <path d="M7.5 44.5H54" />
            <path d="M9 54h8M21 54h8M33 54h8M45 54h7" stroke-dasharray="2 2.5" />
            <path d="M8 50h46" />
            <path d="M24 12l6 3M23.5 17l6.5 3M23 22l7 3M22.5 27l7.5 3" />
            <path d="M15 8c-1-2 1-4 3-4h9c2 0 4 2 3 4" />
            @break
        @case('backpack')
            <rect x="15" y="15" width="34" height="40" rx="11" />
            <path d="M26 15c0-8 12-8 12 0" />
            <path d="M17 25c8 3 22 3 30 0" />
            <rect x="22" y="34" width="20" height="13" rx="4" />
            <path d="M27 34v-3M37 34v-3M22 40h20" />
            <rect x="13" y="54" width="38" height="7" rx="3.5" />
            <path d="M20 54v7M44 54v7" stroke-dasharray="1.5 2" />
            @break
        @case('tent')
            <path d="M7 52 32 13l25 39Z" />
            <path d="M32 13 25 52M32 13l7 39" />
            <path d="M25 52c3-6 11-6 14 0" />
            <path d="M2 52h60" />
            <path d="M7 52l-4 6M57 52l4 6" stroke-dasharray="1.5 2" />
            <path d="M32 13V5l7 2.5-7 2.5" />
            @break
        @case('poles')
            <path d="M22 9 38 59M44 9 28 59" />
            <rect x="18.5" y="4" width="6" height="11" rx="3" transform="rotate(-18 21.5 9.5)" />
            <rect x="41" y="4" width="6" height="11" rx="3" transform="rotate(18 44 9.5)" />
            <ellipse cx="35.5" cy="51" rx="4.5" ry="1.6" transform="rotate(-18 35.5 51)" />
            <ellipse cx="30.5" cy="51" rx="4.5" ry="1.6" transform="rotate(18 30.5 51)" />
            <path d="M20 14c-3 4-3 8 1 11M46 14c3 4 3 8-1 11" />
            @break
        @case('carabiner')
            <path d="M26 6h11c10 0 15 7 15 16v18c0 11-7 18-17 18h-6c-11 0-17-7-17-18V22c0-9 5-16 14-16Z" />
            <path d="M18 18 22 46" />
            <rect x="18" y="20" width="5" height="9" rx="1.5" transform="rotate(-8 20.5 24.5)" />
            <path d="M44 16c2 3 3 6 3 9v14" stroke-dasharray="1.5 2.5" />
            @break
        @case('map')
            <path d="M7 14 23 8l18 6 16-6v42l-16 6-18-6-16 6Z" />
            <path d="M23 8v42M41 14v42" />
            <path d="M12 45c5-9 11-3 15-11s9-9 14-6 8-4 11-10" stroke-dasharray="2 2.5" />
            <path d="M49 14l4 4M53 14l-4 4" />
            <circle cx="12" cy="45" r="1.6" fill="currentColor" />
            <path d="M14 22l2 3 2-3M34 40l2 3 2-3" />
            @break
        @case('flags')
            <path d="M2 14c20 10 40 10 60 0" />
            @foreach ([[8, 17.3], [17, 20.6], [26, 22.2], [35, 22.5], [44, 21.2], [53, 18.3]] as [$x, $y])
                <path d="M{{ $x }} {{ $y }}h6l-1 8h-4Z" @if ($loop->odd) fill="currentColor" fill-opacity=".3" @endif />
            @endforeach
            @break
    @endswitch
</svg>
