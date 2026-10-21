<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $trip->title }} — itinerary</title>
    <style>
        @page { margin: 28mm 18mm 22mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #1b1b1a; line-height: 1.55; }
        header { position: fixed; top: -20mm; left: 0; right: 0; height: 14mm; border-bottom: 1px solid #d8ccb8; }
        header img { height: 12mm; float: left; }
        header .contact { float: right; text-align: right; font-size: 9px; color: #4a4843; padding-top: 2mm; }
        footer { position: fixed; bottom: -14mm; left: 0; right: 0; font-size: 8.5px; color: #8f7f66; text-align: center; }
        h1 { font-family: DejaVu Serif, serif; font-weight: normal; font-size: 26px; color: #1f3a33; margin: 0 0 4px; }
        h2 { font-family: DejaVu Serif, serif; font-weight: normal; font-size: 16px; color: #1f3a33; margin: 22px 0 8px; padding-bottom: 4px; border-bottom: 2px solid #b5532e; }
        .lead { color: #4a4843; font-size: 11.5px; }
        table.facts { width: 100%; border-collapse: collapse; margin-top: 12px; }
        table.facts td { width: 25%; padding: 7px 8px; background: #f4efe6; border: 2px solid #fff; vertical-align: top; }
        table.facts .k { display: block; font-size: 8px; text-transform: uppercase; letter-spacing: 1.2px; color: #8f7f66; }
        table.facts .v { font-weight: bold; color: #1f3a33; }
        .day { margin: 0 0 9px; page-break-inside: avoid; }
        .day .n { display: inline-block; width: 42px; font-weight: bold; color: #b5532e; }
        .day .t { font-weight: bold; color: #1f3a33; }
        .day .m { color: #8f7f66; font-size: 9px; margin-left: 42px; }
        ul { margin: 4px 0 0 0; padding-left: 14px; }
        li { margin-bottom: 3px; }
        .cols td { width: 50%; vertical-align: top; padding-right: 12px; }
        .price { font-size: 18px; color: #1f3a33; font-family: DejaVu Serif, serif; }
        .note { margin-top: 18px; padding: 10px 12px; background: #f4efe6; color: #4a4843; font-size: 9.5px; }
    </style>
</head>
<body>
    <header>
        <img src="{{ public_path('images/brand/logo-badge-160.png') }}" alt="Wonder Himalaya">
        <div class="contact">{{ $company['phone'] ?? '' }} · {{ $company['email'] ?? '' }}<br>{{ $company['address'] ?? 'Thamel, Kathmandu, Nepal' }} · wonderhimalaya.com</div>
    </header>
    <footer>{{ $trip->title }} · Trip code {{ $trip->trip_code ?? '—' }} · Itinerary may change with weather and trail conditions · {{ $trip->url() }}</footer>

    <h1>{{ $trip->title }}</h1>
    @if ($trip->excerpt)<p class="lead">{{ str($trip->excerpt)->limit(320) }}</p>@endif

    <table class="facts">
        <tr>
            <td><span class="k">Duration</span><span class="v">{{ $trip->durationLabel() ?? '—' }}</span></td>
            <td><span class="k">Max altitude</span><span class="v">{{ $trip->max_altitude_m ? number_format($trip->max_altitude_m).' m' : '—' }}</span></td>
            <td><span class="k">Difficulty</span><span class="v">{{ $trip->difficulty?->name ?? '—' }}</span></td>
            <td><span class="k">From</span><span class="v">{{ $trip->displayPrice() ? 'US$'.number_format($trip->displayPrice()).' pp' : 'On request' }}</span></td>
        </tr>
        <tr>
            <td><span class="k">Region</span><span class="v">{{ $trip->destination?->name ?? '—' }}</span></td>
            <td><span class="k">Best season</span><span class="v">{{ $trip->best_season ?: ($trip->facts['Best Season'] ?? '—') }}</span></td>
            <td><span class="k">Accommodation</span><span class="v">{{ $trip->accommodation ?: ($trip->facts['Accommodation'] ?? '—') }}</span></td>
            <td><span class="k">Start / end</span><span class="v">{{ $trip->start_point ?: ($trip->facts['Arrival City'] ?? 'Kathmandu') }}</span></td>
        </tr>
    </table>

    @if ($trip->highlights)
        <h2>Highlights</h2>
        <ul>@foreach ($trip->highlights as $h)<li>{{ is_array($h) ? implode(' ', $h) : $h }}</li>@endforeach</ul>
    @endif

    @if ($trip->days->isNotEmpty())
        <h2>Itinerary</h2>
        @foreach ($trip->days as $day)
            <div class="day">
                <span class="n">Day {{ $day->day_number }}</span><span class="t">{{ strip_tags($day->title) }}</span>
                @php $meta = array_filter([$day->altitude_m ? number_format($day->altitude_m).' m' : null, $day->distance_km ? rtrim(rtrim((string) $day->distance_km, '0'), '.').' km' : null, $day->durationLabel(), $day->accommodation, $day->meals ? collect($day->meals)->map(fn ($m) => ucfirst($m))->implode(', ') : null]); @endphp
                @if ($meta)<div class="m">{{ implode(' · ', $meta) }}</div>@endif
            </div>
        @endforeach
    @endif

    @if ($includes || $excludes)
        <h2>Price includes & excludes</h2>
        <table class="cols" width="100%"><tr>
            <td><strong>Includes</strong><ul>@foreach ($includes as $i)<li>{{ $i }}</li>@endforeach</ul></td>
            <td><strong>Excludes</strong><ul>@foreach ($excludes as $i)<li>{{ $i }}</li>@endforeach</ul></td>
        </tr></table>
    @endif

    @if ($trip->prices->isNotEmpty())
        <h2>Packages</h2>
        <table class="facts"><tr>
            @foreach ($trip->prices->take(4) as $p)
                <td><span class="k">{{ $p->tier?->name ?? $p->label }}</span><span class="price">US${{ number_format($p->effectivePrice()) }}</span> pp</td>
            @endforeach
        </tr></table>
    @endif

    <div class="note">Ready to go, or want changes? Reply to your trip specialist, WhatsApp {{ $company['whatsapp'] ?? '' }}, or enquire at {{ $trip->url() }}. A {{ \App\Models\Setting::get('booking.deposit_percent', 20) }}% deposit secures your place.</div>
</body>
</html>
