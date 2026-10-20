{{-- Bank transfer instructions (ManualGateway). --}}
@php
    use App\Support\Money;
    $seo = \App\Support\Seo::make('Bank transfer');
    $seo->noindex = true;
@endphp
<x-layouts.site :seo="$seo">
    <div class="container-narrow py-12 lg:py-20">
        <p class="eyebrow">Bank transfer</p>
        <h1 class="mt-4 text-display-md">Pay {{ Money::format($payment->amount, $payment->currency) }} by bank transfer</h1>

        <div class="card-surface mt-8 p-6 sm:p-8">
            <div class="prose prose-wh whitespace-pre-line">{{ $instructions }}</div>
            <dl class="mt-6 grid gap-3 rounded-xl bg-stone-200/60 p-4 text-sm sm:grid-cols-2">
                <div><dt class="text-ink-soft">Amount</dt><dd class="data text-base font-semibold">{{ Money::format($payment->amount, $payment->currency) }}</dd></div>
                <div><dt class="text-ink-soft">Payment reference (please quote it)</dt><dd class="data text-base font-semibold">{{ $booking?->number ?? strtoupper(substr($payment->uuid, 0, 8)) }}</dd></div>
            </dl>
            <p class="mt-6 text-sm text-ink-soft">Bank charges are the sender’s responsibility. We will e-mail you as soon as your transfer arrives and your booking is confirmed.</p>
        </div>

        <a href="{{ route('pay.show', $payment) }}" class="link-arrow mt-8">Choose another payment method</a>
    </div>
</x-layouts.site>
