{{-- Booking / payment summary card. Vars: $payment, $booking (nullable) --}}
@php use App\Support\Money; @endphp
<div class="card-surface overflow-hidden">
    @if ($booking?->trip && ($img = $booking->trip->imageUrl('hero', 'card')))
        <img src="{{ $img }}" alt="" class="aspect-[16/9] w-full object-cover">
    @endif
    <div class="p-6 sm:p-8">
        @if ($booking)
            <p class="eyebrow">Booking {{ $booking->number }}</p>
            <h2 class="mt-3 text-2xl">{{ $booking->trip?->title ?? 'Your Himalayan journey' }}</h2>
            <dl class="mt-5 grid grid-cols-2 gap-4 text-sm">
                @if ($booking->start_date)
                    <div><dt class="text-ink-soft">Starts</dt><dd class="font-semibold">{{ $booking->start_date->format('j M Y') }}</dd></div>
                @endif
                <div><dt class="text-ink-soft">Travellers</dt><dd class="font-semibold">{{ $booking->pax }}</dd></div>
                <div><dt class="text-ink-soft">Trip total</dt><dd class="font-semibold data">{{ Money::format($booking->total, $booking->currency) }}</dd></div>
                <div><dt class="text-ink-soft">Paid so far</dt><dd class="font-semibold data">{{ Money::format($booking->amount_paid, $booking->currency) }}</dd></div>
            </dl>
        @else
            <h2 class="text-2xl">{{ $payment->description }}</h2>
        @endif
    </div>
</div>
