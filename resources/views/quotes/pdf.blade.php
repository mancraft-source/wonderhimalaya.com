@php use App\Support\Money; @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $quote->number }} — {{ $quote->title }}</title>
    <style>
        @page { margin: 28mm 18mm 22mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10.5pt; color: #1b1b1a; line-height: 1.5; }
        h1, h2, h3 { font-family: DejaVu Serif, Georgia, serif; font-weight: normal; color: #1f3a33; margin: 0; }
        h1 { font-size: 22pt; line-height: 1.15; }
        h2 { font-size: 13pt; margin: 22px 0 8px; padding-bottom: 4px; border-bottom: 1px solid #d8ccb8; }
        .header { position: fixed; top: -20mm; left: 0; right: 0; height: 14mm; border-bottom: 2px solid #1f3a33; }
        .brand { font-family: DejaVu Serif, serif; font-size: 15pt; color: #1f3a33; }
        .muted { color: #4a4843; }
        .eyebrow { font-size: 8pt; letter-spacing: 2px; text-transform: uppercase; color: #b5532e; }
        .footer { position: fixed; bottom: -14mm; left: 0; right: 0; font-size: 8pt; color: #8f7f66; text-align: center; }
        table { width: 100%; border-collapse: collapse; }
        .meta td { padding: 3px 0; vertical-align: top; }
        .items th { background: #e9e1d3; text-align: left; font-size: 8.5pt; text-transform: uppercase; letter-spacing: 1px; padding: 7px 8px; }
        .items td { padding: 7px 8px; border-bottom: 1px solid #e9e1d3; }
        .num { text-align: right; white-space: nowrap; }
        .totals td { padding: 4px 8px; }
        .grand td { font-size: 12pt; font-weight: bold; color: #1f3a33; border-top: 2px solid #1f3a33; padding-top: 8px; }
        .box { background: #f4efe6; border-radius: 6px; padding: 10px 14px; margin-top: 14px; }
        .day { margin: 0 0 5px; }
        .day b { color: #1f3a33; }
        .terms { font-size: 8.5pt; color: #4a4843; }
    </style>
</head>
<body>
    <div class="header">
        <table><tr>
            <td class="brand">Wonder Himalaya</td>
            <td class="num muted" style="font-size:8.5pt">
                {{ $company['phone'] ?? '' }} @if (! empty($company['email'])) · {{ $company['email'] }} @endif
            </td>
        </tr></table>
    </div>
    <div class="footer">
        {{ $company['name'] ?? 'Wonder Himalaya' }} @if (! empty($company['address'])) · {{ is_array($company['address']) ? implode(', ', $company['address']) : $company['address'] }} @endif
        · Quote {{ $quote->number }}
    </div>

    <p class="eyebrow">Proposal {{ $quote->number }}</p>
    <h1>{{ $quote->title }}</h1>

    <table class="meta" style="margin-top:14px">
        <tr>
            <td style="width:50%">
                <span class="muted">Prepared for</span><br>
                <b>{{ $quote->contact?->name }}</b><br>
                {{ $quote->contact?->email }}
            </td>
            <td style="width:50%">
                <span class="muted">Issued</span> {{ ($quote->sent_at ?? $quote->created_at)?->format('j M Y') }}<br>
                @if ($quote->valid_until)<span class="muted">Valid until</span> {{ $quote->valid_until->format('j M Y') }}<br>@endif
                @if ($quote->start_date)<span class="muted">Travel dates</span> {{ $quote->start_date->format('j M Y') }}@if ($quote->end_date) – {{ $quote->end_date->format('j M Y') }}@endif<br>@endif
                <span class="muted">Travellers</span> {{ $quote->pax }}
                @if ($quote->tier)<br><span class="muted">Service level</span> {{ $quote->tier->name }}@endif
            </td>
        </tr>
    </table>

    @if ($quote->introduction)
        <div style="margin-top:14px">{!! $quote->introduction !!}</div>
    @endif

    <h2>Price</h2>
    <table class="items">
        <thead><tr><th>Description</th><th class="num">Qty</th><th class="num">Unit</th><th class="num">Amount</th></tr></thead>
        <tbody>
            @foreach ($quote->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="num">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td>
                    <td class="num">{{ Money::format($item->unit_price, $quote->currency) }}</td>
                    <td class="num">{{ Money::format($item->total, $quote->currency) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <table class="totals" style="width:55%; margin-left:45%; margin-top:6px">
        <tr><td>Subtotal</td><td class="num">{{ Money::format($quote->subtotal, $quote->currency) }}</td></tr>
        @if ((float) $quote->discount > 0)<tr><td>Discount</td><td class="num">−{{ Money::format($quote->discount, $quote->currency) }}</td></tr>@endif
        @if ((float) $quote->tax > 0)<tr><td>Tax</td><td class="num">{{ Money::format($quote->tax, $quote->currency) }}</td></tr>@endif
        <tr class="grand"><td>Total</td><td class="num">{{ Money::format($quote->total, $quote->currency) }}</td></tr>
    </table>

    @if ((float) $quote->deposit_amount > 0)
        <div class="box">
            Deposit to confirm ({{ $quote->deposit_percent }}%): <b>{{ Money::format($quote->deposit_amount, $quote->currency) }}</b>.
            Accept and pay online: {{ route('quotes.show', $quote->uuid) }}
        </div>
    @endif

    @if ($quote->trip && $quote->trip->days->isNotEmpty())
        <h2>Itinerary — {{ $quote->trip->title }}</h2>
        @foreach ($quote->trip->days as $day)
            <p class="day"><b>Day {{ $day->day_number }}</b> · {{ $day->title }}@if ($day->altitude_m) <span class="muted">({{ number_format($day->altitude_m) }} m)</span>@endif</p>
        @endforeach
    @endif

    @if ($quote->terms)
        <h2>Terms &amp; conditions</h2>
        <div class="terms">{!! $quote->terms !!}</div>
    @endif
</body>
</html>
