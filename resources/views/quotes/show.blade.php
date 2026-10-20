{{-- Public quote page: /quote/{uuid} (noindex). --}}
@php
    use App\Enums\QuoteStatus;
    use App\Support\Money;

    $seo = \App\Support\Seo::make('Your proposal — '.$quote->title);
    $seo->noindex = true;

    $expired = $quote->status === QuoteStatus::Expired || ($quote->isExpired() && in_array($quote->status, [QuoteStatus::Sent, QuoteStatus::Viewed], true));
    $declined = $quote->status === QuoteStatus::Declined;
    $accepted = $quote->status === QuoteStatus::Accepted;
    $acceptable = $quote->isAcceptable();
    $trip = $quote->trip;
    $phone = $company['whatsapp'] ?? $company['phone'] ?? null;
    $whatsapp = $phone ? 'https://wa.me/'.preg_replace('/\D+/', '', $phone).'?text='.rawurlencode('Hello! I have a question about my quote '.$quote->number.'.') : null;
    $email = $company['email'] ?? config('mail.from.address');
    $hero = $trip?->imageUrl('hero', 'wide');
@endphp

<x-layouts.site :seo="$seo">
    {{-- Hero --}}
    <section class="relative overflow-hidden bg-juniper-800 text-stone-100">
        @if ($hero)
            <img src="{{ $hero }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-30" loading="eager">
        @endif
        <div class="absolute inset-0 bg-gradient-to-t from-juniper-950/90 via-juniper-900/60 to-juniper-900/30"></div>

        <div class="container-page relative py-16 lg:py-24">
            <p class="eyebrow eyebrow-light">Proposal {{ $quote->number }}</p>
            <h1 class="mt-4 max-w-4xl font-display text-display-md text-stone-50">{{ $quote->title }}</h1>
            <p class="mt-4 text-lg text-stone-200">
                Prepared for {{ $quote->contact?->name ?: 'you' }}
                @if ($quote->creator) by {{ $quote->creator->name }} @endif
            </p>

            <dl class="mt-10 grid max-w-3xl grid-cols-2 gap-6 sm:grid-cols-4">
                @if ($quote->start_date)
                    <div><dt class="text-xs uppercase tracking-widest text-stone-300">Dates</dt>
                        <dd class="mt-1 font-semibold">{{ $quote->start_date->format('j M Y') }}@if ($quote->end_date) – {{ $quote->end_date->format('j M Y') }}@endif</dd></div>
                @endif
                <div><dt class="text-xs uppercase tracking-widest text-stone-300">Travellers</dt>
                    <dd class="mt-1 font-semibold">{{ $quote->pax }}</dd></div>
                @if ($trip?->duration_days)
                    <div><dt class="text-xs uppercase tracking-widest text-stone-300">Duration</dt>
                        <dd class="mt-1 font-semibold">{{ $trip->durationLabel() }}</dd></div>
                @endif
                <div><dt class="text-xs uppercase tracking-widest text-stone-300">Total</dt>
                    <dd class="mt-1 font-semibold">{{ Money::format($quote->total, $quote->currency) }}</dd></div>
            </dl>
        </div>
    </section>

    <div class="container-page grid gap-10 py-12 lg:grid-cols-[minmax(0,1fr)_24rem] lg:py-16">
        <div class="space-y-10">
            {{-- Flash + state banners --}}
            @if (session('status'))
                <div class="rounded-2xl bg-juniper-50 p-5 text-juniper-800 ring-1 ring-juniper-100" role="status">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-2xl bg-terracotta-500/10 p-5 text-terracotta-600 ring-1 ring-terracotta-500/30" role="alert">{{ session('error') }}</div>
            @endif

            @if ($expired)
                <div class="rounded-2xl bg-saffron-300/30 p-6 ring-1 ring-saffron-500/40">
                    <h2 class="text-2xl">This quote has expired</h2>
                    <p class="mt-2 text-ink-soft">Prices and availability in the Himalaya change with the seasons. Ask us and we will gladly refresh it for you.</p>
                </div>
            @elseif ($declined)
                <div class="rounded-2xl bg-stone-200/70 p-6">
                    <h2 class="text-2xl">You declined this quote</h2>
                    <p class="mt-2 text-ink-soft">Changed your mind, or want something different? Just get in touch — we love designing journeys.</p>
                </div>
            @elseif ($accepted)
                <div class="rounded-2xl bg-juniper-50 p-6 ring-1 ring-juniper-100">
                    <h2 class="text-2xl">Quote accepted — thank you!</h2>
                    @if ($quote->booking)
                        <p class="mt-2 text-ink-soft">Your booking reference is <strong class="data text-ink">{{ $quote->booking->number }}</strong>.</p>
                    @endif
                    @if ($pendingPayment)
                        <a href="{{ route('pay.show', $pendingPayment) }}" class="btn-primary mt-4">Continue to payment</a>
                    @endif
                </div>
            @endif

            @if ($quote->introduction)
                <section class="prose prose-lg prose-wh">{!! $quote->introduction !!}</section>
            @endif

            {{-- Trip summary --}}
            @if ($trip)
                <section class="card-surface p-6 sm:p-8">
                    <p class="eyebrow">Your journey</p>
                    <h2 class="mt-3 text-display-sm">{{ $trip->title }}</h2>
                    @if ($trip->excerpt)<p class="mt-3 text-ink-soft">{{ $trip->excerpt }}</p>@endif
                    <ul class="mt-5 flex flex-wrap gap-2">
                        @if ($trip->duration_days)<li class="chip">{{ $trip->durationLabel() }}</li>@endif
                        @if ($trip->max_altitude_m)<li class="chip">Max altitude {{ number_format($trip->max_altitude_m) }} m</li>@endif
                        @if ($quote->tier)<li class="chip">{{ $quote->tier->name }}</li>@endif
                        @if ($quote->departure)<li class="chip">Departure {{ $quote->departure->start_date->format('j M Y') }}</li>@endif
                    </ul>
                    @if ($trip->isPublished())
                        <a href="{{ $trip->url() }}" class="link-arrow mt-6" target="_blank" rel="noopener">See the full trip page</a>
                    @endif
                </section>

                @if ($trip->days->isNotEmpty())
                    <section>
                        <h2 class="text-display-sm">Day-by-day itinerary</h2>
                        <ol class="mt-6 space-y-3">
                            @foreach ($trip->days as $day)
                                <li class="flex gap-4 rounded-2xl bg-stone-50 p-4 ring-1 ring-stone-300/50">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-juniper-800 text-sm font-semibold text-stone-100">{{ $day->day_number }}</span>
                                    <div>
                                        <p class="font-semibold text-juniper-800">{{ $day->title }}</p>
                                        <p class="mt-1 text-sm text-ink-soft">
                                            @if ($day->altitude_m) {{ number_format($day->altitude_m) }} m @endif
                                            @if ($day->duration) · {{ $day->duration }} @endif
                                            @if ($day->accommodation) · {{ $day->accommodation }} @endif
                                        </p>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endif
            @endif

            {{-- Line items --}}
            <section>
                <h2 class="text-display-sm">Your price</h2>
                <div class="mt-6 overflow-hidden rounded-2xl ring-1 ring-stone-300/70">
                    <table class="w-full text-left text-sm sm:text-base">
                        <thead class="bg-stone-200/60 text-xs uppercase tracking-wider text-ink-soft">
                            <tr>
                                <th scope="col" class="px-4 py-3 font-semibold">Description</th>
                                <th scope="col" class="px-4 py-3 text-right font-semibold">Qty</th>
                                <th scope="col" class="hidden px-4 py-3 text-right font-semibold sm:table-cell">Unit</th>
                                <th scope="col" class="px-4 py-3 text-right font-semibold">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-200 bg-white">
                            @foreach ($quote->items as $item)
                                <tr>
                                    <td class="px-4 py-3">{{ $item->description }}</td>
                                    <td class="px-4 py-3 text-right data">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td>
                                    <td class="hidden px-4 py-3 text-right data sm:table-cell">{{ Money::format($item->unit_price, $quote->currency) }}</td>
                                    <td class="px-4 py-3 text-right data">{{ Money::format($item->total, $quote->currency) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-stone-50 text-sm">
                            <tr><td colspan="3" class="hidden px-4 pt-4 text-right sm:table-cell">Subtotal</td><td class="px-4 pt-4 text-right sm:hidden">Subtotal</td><td class="px-4 pt-4 text-right data">{{ Money::format($quote->subtotal, $quote->currency) }}</td></tr>
                            @if ((float) $quote->discount > 0)
                                <tr><td colspan="3" class="hidden px-4 pt-2 text-right sm:table-cell">Discount</td><td class="px-4 pt-2 text-right sm:hidden">Discount</td><td class="px-4 pt-2 text-right data">−{{ Money::format($quote->discount, $quote->currency) }}</td></tr>
                            @endif
                            @if ((float) $quote->tax > 0)
                                <tr><td colspan="3" class="hidden px-4 pt-2 text-right sm:table-cell">Tax</td><td class="px-4 pt-2 text-right sm:hidden">Tax</td><td class="px-4 pt-2 text-right data">{{ Money::format($quote->tax, $quote->currency) }}</td></tr>
                            @endif
                            <tr class="text-base font-semibold text-juniper-800"><td colspan="3" class="hidden px-4 pt-3 pb-4 text-right sm:table-cell">Total</td><td class="px-4 pt-3 pb-4 text-right sm:hidden">Total</td><td class="px-4 pt-3 pb-4 text-right data text-base">{{ Money::format($quote->total, $quote->currency) }}</td></tr>
                        </tfoot>
                    </table>
                </div>
            </section>

            @if ($quote->terms)
                <section id="terms">
                    <h2 class="text-display-sm">Terms &amp; conditions</h2>
                    <div class="prose prose-wh mt-4 max-h-96 overflow-y-auto rounded-2xl bg-stone-50 p-6 ring-1 ring-stone-300/50">{!! $quote->terms !!}</div>
                </section>
            @endif
        </div>

        {{-- Sticky action card --}}
        <aside class="lg:sticky lg:top-28 lg:self-start">
            <div class="card-surface p-6 sm:p-8">
                <p class="text-sm text-ink-soft">Total for {{ $quote->pax }} {{ str('traveller')->plural($quote->pax) }}</p>
                <p class="mt-1 font-display text-4xl text-juniper-800">{{ Money::format($quote->total, $quote->currency) }}</p>

                @if ((float) $quote->deposit_amount > 0 && (float) $quote->deposit_amount < (float) $quote->total)
                    <p class="mt-4 rounded-xl bg-stone-200/60 px-4 py-3 text-sm">
                        Secure your place with a <strong>{{ $quote->deposit_percent }}% deposit</strong> of
                        <strong class="data">{{ Money::format($quote->deposit_amount, $quote->currency) }}</strong> today.
                    </p>
                @endif

                @if ($quote->valid_until)
                    <p @class(['mt-4 text-sm', 'text-terracotta-600' => $expired, 'text-ink-soft' => ! $expired])>
                        {{ $expired ? 'Expired on' : 'Valid until' }} {{ $quote->valid_until->format('j F Y') }}
                    </p>
                @endif

                @if ($acceptable)
                    <form method="POST" action="{{ route('quotes.accept', $quote->uuid) }}" class="mt-6 space-y-4">
                        @csrf
                        <label class="flex items-start gap-3 text-sm">
                            <input type="checkbox" name="accept_terms" value="1" required class="mt-1 h-4 w-4 rounded border-stone-300 text-juniper-800 focus:ring-juniper-500" @checked(old('accept_terms'))>
                            <span>I have read and accept the {{ $quote->terms ? '' : 'booking ' }}<a href="{{ $quote->terms ? '#terms' : url('/terms-and-conditions') }}" class="underline decoration-terracotta-500/50 underline-offset-4">terms &amp; conditions</a>.</span>
                        </label>
                        @error('accept_terms')<p class="field-error">{{ $message }}</p>@enderror
                        <button type="submit" class="btn-primary w-full">Accept &amp; pay deposit</button>
                    </form>
                @endif

                <div class="mt-6 grid gap-3">
                    @if ($whatsapp)
                        <a href="{{ $whatsapp }}" class="btn-outline w-full" target="_blank" rel="noopener">Ask a question on WhatsApp</a>
                    @endif
                    @if ($email)
                        <a href="mailto:{{ $email }}?subject={{ rawurlencode('Question about quote '.$quote->number) }}" class="btn-outline w-full">Ask a question by e-mail</a>
                    @endif
                    <a href="{{ route('quotes.pdf', $quote->uuid) }}" class="link-arrow justify-center text-sm">Download PDF</a>
                </div>

                @if ($acceptable)
                    <details class="mt-6 border-t border-stone-300/60 pt-4 text-sm">
                        <summary class="cursor-pointer text-ink-soft">Not for you? Decline this quote</summary>
                        <form method="POST" action="{{ route('quotes.decline', $quote->uuid) }}" class="mt-3 space-y-3">
                            @csrf
                            <label for="decline-reason" class="field-label">Anything we should know? (optional)</label>
                            <textarea id="decline-reason" name="reason" rows="3" maxlength="1000" class="field"></textarea>
                            <button type="submit" class="btn-outline w-full">Decline quote</button>
                        </form>
                    </details>
                @endif
            </div>
        </aside>
    </div>
</x-layouts.site>
