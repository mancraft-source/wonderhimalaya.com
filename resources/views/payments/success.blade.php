{{-- Thank-you page after payment. --}}
@php
    use App\Enums\PaymentStatus;
    use App\Support\Money;
    $seo = \App\Support\Seo::make('Thank you');
    $seo->noindex = true;
    $paid = $payment->status === PaymentStatus::Paid;
@endphp
<x-layouts.site :seo="$seo">
    <div class="container-narrow py-16 text-center lg:py-24">
        <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full {{ $paid ? 'bg-juniper-800 text-stone-100' : 'bg-saffron-300 text-juniper-900' }}">
            <svg aria-hidden="true" class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="{{ $paid ? 'M5 12l5 5L20 7' : 'M12 7v5l3 3' }}"/></svg>
        </span>
        <h1 class="mt-6 text-display-md">{{ $paid ? 'Dhanyabad — payment received!' : 'Thank you — we are confirming your payment' }}</h1>

        @if (session('status'))
            <p class="mt-4 text-ink-soft">{{ session('status') }}</p>
        @endif

        @if ($paid)
            <p class="mt-4 text-lg text-ink-soft">We received {{ Money::format($payment->amount, $payment->currency) }}. A receipt is on its way to your inbox.</p>
        @elseif ($isManual)
            <p class="mt-4 text-lg text-ink-soft">As soon as your bank transfer arrives we will confirm your booking by e-mail.</p>
        @else
            <p class="mt-4 text-lg text-ink-soft">Your payment is being processed. You will receive an e-mail confirmation shortly — no need to pay again.</p>
        @endif

        @if ($booking)
            <div class="card-surface mx-auto mt-10 max-w-md p-6 text-left">
                <p class="text-sm text-ink-soft">Booking number</p>
                <p class="data text-2xl font-semibold text-juniper-800">{{ $booking->number }}</p>
                @if ($booking->trip)<p class="mt-2">{{ $booking->trip->title }}@if ($booking->start_date) · {{ $booking->start_date->format('j M Y') }}@endif</p>@endif
            </div>

            <div class="mx-auto mt-10 max-w-xl text-left">
                <h2 class="text-2xl">Next steps</h2>
                <ol class="mt-4 space-y-3">
                    <li class="flex gap-3"><span class="font-display text-xl text-terracotta-500">1</span> Send us each traveller’s passport &amp; insurance details so we can arrange permits.</li>
                    <li class="flex gap-3"><span class="font-display text-xl text-terracotta-500">2</span> Book your international flights to Kathmandu (we pick you up at the airport).</li>
                    <li class="flex gap-3"><span class="font-display text-xl text-terracotta-500">3</span> Your trip specialist will share a packing list and pre-departure briefing.</li>
                </ol>
                <a href="{{ route('bookings.travellers.edit', $booking->uuid) }}" class="btn-primary mt-8">Add traveller details</a>
            </div>
        @endif
    </div>
</x-layouts.site>
