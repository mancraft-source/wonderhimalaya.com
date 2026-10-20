{{-- Public payment page: /pay/{uuid} (noindex). --}}
@php
    use App\Enums\PaymentStatus;
    use App\Support\Money;

    $seo = \App\Support\Seo::make('Secure payment');
    $seo->noindex = true;
    $payable = $payment->isPayable();
    $expired = $payment->status === PaymentStatus::Pending && ! $payable;
    $icons = ['paypal' => 'PayPal account or card via PayPal', 'card' => 'Visa / Mastercard — processed by our bank', 'bank_transfer' => 'SWIFT / local bank transfer'];
@endphp

<x-layouts.site :seo="$seo">
    <div class="container-page grid gap-10 py-12 lg:grid-cols-[minmax(0,1fr)_26rem] lg:py-20">
        <div>
            <p class="eyebrow">Secure payment</p>
            <h1 class="mt-4 text-display-md">{{ $payment->type?->getLabel() ?? 'Payment' }}</h1>
            <p class="mt-3 text-ink-soft">{{ $payment->description }}</p>

            <div class="mt-8 flex items-baseline gap-3">
                <span class="font-display text-5xl text-juniper-800">{{ Money::format($payment->amount, $payment->currency) }}</span>
                <span class="chip">{{ $payment->status->getLabel() }}</span>
            </div>

            @if (session('status'))
                <div class="mt-8 rounded-2xl bg-juniper-50 p-5 text-juniper-800 ring-1 ring-juniper-100" role="status">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="mt-8 rounded-2xl bg-terracotta-500/10 p-5 text-terracotta-600 ring-1 ring-terracotta-500/30" role="alert">{{ session('error') }}</div>
            @endif

            @if ($payment->status === PaymentStatus::Paid)
                <div class="mt-10 rounded-2xl bg-juniper-50 p-6 ring-1 ring-juniper-100">
                    <h2 class="text-2xl">Paid — thank you!</h2>
                    <p class="mt-2 text-ink-soft">Received on {{ $payment->paid_at?->format('j F Y') }}. A receipt has been sent by e-mail.</p>
                    <a href="{{ route('pay.success', $payment) }}" class="link-arrow mt-4">What happens next</a>
                </div>
            @elseif ($expired)
                <div class="mt-10 rounded-2xl bg-saffron-300/30 p-6 ring-1 ring-saffron-500/40">
                    <h2 class="text-2xl">This payment link has expired</h2>
                    <p class="mt-2 text-ink-soft">For your security, payment links are valid for a limited time. Contact us and we will send you a fresh one.</p>
                </div>
            @elseif (! $payable)
                <div class="mt-10 rounded-2xl bg-stone-200/70 p-6">
                    <h2 class="text-2xl">This payment is {{ strtolower($payment->status->getLabel()) }}</h2>
                    <p class="mt-2 text-ink-soft">Please contact us if you believe this is a mistake.</p>
                </div>
            @else
                <section class="mt-10" aria-labelledby="methods">
                    <h2 id="methods" class="text-2xl">Choose how to pay</h2>
                    <div class="mt-6 grid gap-4 sm:grid-cols-2">
                        @forelse ($gateways as $key => $gateway)
                            <form method="POST" action="{{ route('pay.start', [$payment, $key]) }}">
                                @csrf
                                <button type="submit" class="group flex h-full w-full flex-col items-start rounded-2xl bg-white p-6 text-left ring-1 ring-stone-300 transition hover:-translate-y-0.5 hover:ring-2 hover:ring-juniper-500 focus-visible:ring-2">
                                    <span class="text-lg font-semibold text-juniper-800">{{ $gateway->label() }}</span>
                                    <span class="mt-1 text-sm text-ink-soft">{{ $icons[$key] ?? '' }}</span>
                                    <span class="mt-4 text-sm font-semibold text-terracotta-500 group-hover:underline">Continue →</span>
                                </button>
                            </form>
                        @empty
                            <p class="text-ink-soft">Online payment is not available right now. Please contact us.</p>
                        @endforelse

                        {{-- Layout preview only (never in production): gateways awaiting merchant credentials. --}}
                        @unless (app()->isProduction())
                            @foreach (array_diff_key(config('payments.gateways', []), $gateways) as $key => $class)
                                <div class="flex h-full flex-col items-start rounded-2xl border border-dashed border-stone-300 bg-white/60 p-6 text-left">
                                    <span class="text-lg font-semibold text-juniper-800/70">{{ app(\App\Payments\PaymentManager::class)->gateway($key)->label() }}</span>
                                    <span class="mt-1 text-sm text-ink-soft">{{ $icons[$key] ?? '' }}</span>
                                    <span class="mt-4 rounded-full bg-saffron-300/40 px-3 py-1 text-xs font-semibold text-ink">Preview · awaiting merchant credentials</span>
                                </div>
                            @endforeach
                        @endunless
                    </div>
                    <p class="mt-6 flex items-center gap-2 text-sm text-ink-soft">
                        <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 1 1 8 0v3"/></svg>
                        Payments are processed securely by our payment partners. We never see or store your card details.
                    </p>
                    @if ($payment->expires_at)
                        <p class="mt-2 text-sm text-ink-soft">This link is valid until {{ $payment->expires_at->format('j F Y') }}.</p>
                    @endif
                </section>
            @endif
        </div>

        <aside class="lg:pt-6">
            @include('payments._summary', ['payment' => $payment, 'booking' => $booking])
        </aside>
    </div>
</x-layouts.site>
