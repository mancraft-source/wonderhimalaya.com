@php $steps = \App\Livewire\TailorMadeBuilder::STEPS; @endphp
<div x-data x-on:step-changed.window="$el.scrollIntoView({ behavior: 'smooth', block: 'start' })" class="scroll-mt-28">
    @if ($reference)
        <div class="mx-auto max-w-2xl rounded-[2.5rem] bg-juniper-800 p-10 text-center text-stone-100 sm:p-16" data-reveal>
            <svg viewBox="0 0 120 60" class="mx-auto h-16 text-saffron-300" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M2 58 32 20l14 18 16-24 28 44" /><circle cx="92" cy="12" r="7" /><path d="M10 58c18-6 30 2 46-4s22-14 40-6" stroke-dasharray="2 4" /></svg>
            <h2 class="mt-8 font-display text-display-sm text-stone-100">Your journey is taking shape.</h2>
            <p class="mx-auto mt-4 max-w-lg text-stone-300">{{ $first_name }}, a Kathmandu trip specialist is reading your brief now. Expect a personal reply — with a first draft itinerary — within 24 hours.</p>
            <p class="mt-6 text-sm text-stone-400">Reference <span class="data text-saffron-300">{{ $reference }}</span></p>
            <a href="{{ route('trips.index') }}" class="btn-ghost-light mt-10">Browse journeys meanwhile</a>
        </div>
    @else
        {{-- Progress --}}
        <ol class="mb-10 flex items-center gap-2 overflow-x-auto pb-2 no-scrollbar" aria-label="Progress">
            @foreach ($steps as $i => $label)
                @php $n = $i + 1; @endphp
                <li class="flex shrink-0 items-center gap-2">
                    <button type="button" wire:click="goTo({{ $n }})" @disabled($n >= $step)
                        @class(['flex items-center gap-2.5 rounded-full py-2 pr-4 pl-2 text-sm font-semibold transition',
                            'bg-juniper-800 text-stone-100' => $n === $step,
                            'bg-juniper-100 text-juniper-800 hover:bg-juniper-50' => $n < $step,
                            'text-stone-400' => $n > $step])
                        @if ($n === $step) aria-current="step" @endif>
                        <span @class(['flex size-7 items-center justify-center rounded-full text-xs', 'bg-saffron-500 text-ink' => $n === $step, 'bg-juniper-800 text-stone-100' => $n < $step, 'bg-stone-200' => $n > $step])>
                            @if ($n < $step)<x-heroicon-m-check class="size-4" />@else{{ $n }}@endif
                        </span>
                        {{ $label }}
                    </button>
                    @unless ($loop->last)<span class="h-px w-6 bg-stone-300"></span>@endunless
                </li>
            @endforeach
        </ol>

        <form wire:submit="submit" class="rounded-[2.5rem] bg-stone-50 p-6 ring-1 ring-stone-300/60 sm:p-12" novalidate>
            <x-honeypot livewire-model="extraFields" />

            {{-- 1. Where --}}
            <section @class(['hidden' => $step !== 1]) aria-labelledby="tm-1">
                <p class="eyebrow">Step 1 of 5</p>
                <h2 id="tm-1" class="mt-4 font-display text-display-sm">Where are you dreaming of?</h2>
                <p class="mt-3 text-ink-soft">Choose as many as you like. Combining Nepal with Bhutan or Tibet is something we do often.</p>
                <div class="mt-8 grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-4">
                    @foreach ($this->places as $place)
                        @php $on = in_array($place, $destinations, true); @endphp
                        <button type="button" wire:key="p-{{ md5($place) }}" wire:click="toggle('destinations', @js($place))" aria-pressed="{{ $on ? 'true' : 'false' }}"
                            @class(['group relative flex min-h-24 flex-col justify-between rounded-3xl p-5 text-left ring-1 transition duration-300',
                                'bg-juniper-800 text-stone-100 ring-juniper-800' => $on,
                                'bg-white text-juniper-800 ring-stone-300 hover:-translate-y-0.5 hover:ring-juniper-500' => ! $on])>
                            <span @class(['absolute top-4 right-4 flex size-6 items-center justify-center rounded-full ring-1', 'bg-saffron-500 text-ink ring-saffron-500' => $on, 'ring-stone-300' => ! $on])>
                                @if ($on)<x-heroicon-m-check class="size-4" />@endif
                            </span>
                            <x-heroicon-o-map class="size-6 opacity-60" />
                            <span class="mt-4 font-display text-lg leading-tight">{{ $place }}</span>
                        </button>
                    @endforeach
                </div>
                @error('destinations')<p class="field-error">{{ $message }}</p>@enderror
            </section>

            {{-- 2. Experience --}}
            <section @class(['hidden' => $step !== 2]) aria-labelledby="tm-2">
                <p class="eyebrow">Step 2 of 5</p>
                <h2 id="tm-2" class="mt-4 font-display text-display-sm">How would you like it to feel?</h2>
                <p class="mt-3 text-ink-soft">Pick the experiences that matter most — we'll weave them together.</p>
                <div class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach (\App\Livewire\TailorMadeBuilder::INTERESTS as $key => [$label, $desc])
                        @php $on = in_array($key, $interests, true); @endphp
                        <button type="button" wire:key="i-{{ $key }}" wire:click="toggle('interests', '{{ $key }}')" aria-pressed="{{ $on ? 'true' : 'false' }}"
                            @class(['flex items-start gap-4 rounded-3xl p-5 text-left ring-1 transition duration-300',
                                'bg-juniper-800 text-stone-100 ring-juniper-800' => $on,
                                'bg-white ring-stone-300 hover:ring-juniper-500' => ! $on])>
                            <span @class(['mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full ring-1', 'bg-saffron-500 text-ink ring-saffron-500' => $on, 'ring-stone-300' => ! $on])>
                                @if ($on)<x-heroicon-m-check class="size-4" />@endif
                            </span>
                            <span>
                                <span @class(['block font-display text-lg', 'text-juniper-800' => ! $on])>{{ $label }}</span>
                                <span @class(['mt-1 block text-sm', 'text-stone-300' => $on, 'text-ink-soft' => ! $on])>{{ $desc }}</span>
                            </span>
                        </button>
                    @endforeach
                </div>
                @error('interests')<p class="field-error">{{ $message }}</p>@enderror
            </section>

            {{-- 3. When --}}
            <section @class(['hidden' => $step !== 3]) aria-labelledby="tm-3">
                <p class="eyebrow">Step 3 of 5</p>
                <h2 id="tm-3" class="mt-4 font-display text-display-sm">When, and for how long?</h2>
                <p class="mt-3 text-ink-soft">Spring (Mar–May) and autumn (Sep–Nov) bring the clearest skies; we'll advise on the best window for your route.</p>
                <p class="field-label mt-8">Month of travel</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($this->months as $value => $label)
                        <button type="button" wire:key="m-{{ $value }}" wire:click="$set('travel_month', '{{ $value }}')"
                            @class(['rounded-full px-4 py-2 text-sm font-medium ring-1 transition', 'bg-juniper-800 text-stone-100 ring-juniper-800' => $travel_month === $value, 'bg-white text-juniper-800 ring-stone-300 hover:ring-juniper-500' => $travel_month !== $value])>{{ $label }}</button>
                    @endforeach
                </div>
                <div class="mt-8 grid gap-6 md:grid-cols-2">
                    <x-site.field label="Exact start date" name="start_date" type="date" wire:model="start_date" min="{{ now()->addDays(7)->toDateString() }}" optional prefix="tm" />
                    <x-site.field label="Flexibility" name="date_flexibility" type="select" wire:model="date_flexibility" prefix="tm">
                        @foreach (['Fixed dates', 'Flexible by a few days', 'Flexible by a week or two', 'Very flexible'] as $opt)<option>{{ $opt }}</option>@endforeach
                    </x-site.field>
                </div>
                <div class="mt-8" x-data="{ d: @entangle('duration_days') }">
                    <div class="flex items-baseline justify-between">
                        <label for="tm-duration" class="field-label">Trip length</label>
                        <span class="font-display text-2xl text-juniper-800"><span x-text="d"></span> days</span>
                    </div>
                    <input id="tm-duration" type="range" min="3" max="35" x-model="d" class="mt-3 w-full accent-terracotta-500">
                    <div class="mt-1 flex justify-between text-xs text-stone-500"><span>Short escape</span><span>Grand traverse</span></div>
                </div>
            </section>

            {{-- 4. Who --}}
            <section @class(['hidden' => $step !== 4]) aria-labelledby="tm-4">
                <p class="eyebrow">Step 4 of 5</p>
                <h2 id="tm-4" class="mt-4 font-display text-display-sm">Who's travelling, and in what style?</h2>
                <div class="mt-8 grid max-w-md grid-cols-2 gap-4">
                    @foreach (['adults' => ['Adults', 1], 'children' => ['Children', 0]] as $field => [$label, $min])
                        <div>
                            <p class="field-label">{{ $label }}</p>
                            <div class="flex items-center gap-3 rounded-2xl bg-white p-2 ring-1 ring-stone-300">
                                <button type="button" class="flex size-10 items-center justify-center rounded-xl hover:bg-stone-200" wire:click="$set('{{ $field }}', {{ max($min, $this->{$field} - 1) }})" aria-label="Fewer {{ strtolower($label) }}"><x-heroicon-m-minus class="size-4" /></button>
                                <span class="flex-1 text-center font-display text-2xl text-juniper-800">{{ $this->{$field} }}</span>
                                <button type="button" class="flex size-10 items-center justify-center rounded-xl hover:bg-stone-200" wire:click="$set('{{ $field }}', {{ min(40, $this->{$field} + 1) }})" aria-label="More {{ strtolower($label) }}"><x-heroicon-m-plus class="size-4" /></button>
                            </div>
                        </div>
                    @endforeach
                </div>
                @error('adults')<p class="field-error">{{ $message }}</p>@enderror

                @if ($this->tiers->isNotEmpty())
                    <p class="field-label mt-10">Comfort level</p>
                    <div class="grid gap-3 md:grid-cols-3">
                        @php $tierCopy = ['standard' => 'Best local tea-houses & hotels, licensed guide, great value.', 'premium' => 'Upgraded lodges with attached bathrooms, boutique hotels, smaller groups.', 'luxury' => 'The finest lodges, 5★ Kathmandu stays, private guide, heli options.']; @endphp
                        @foreach ($this->tiers as $tier)
                            <button type="button" wire:key="t-{{ $tier->id }}" wire:click="$set('tier_id', {{ $tier_id === $tier->id ? 'null' : $tier->id }})"
                                @class(['rounded-3xl p-6 text-left ring-1 transition', 'bg-juniper-800 text-stone-100 ring-juniper-800' => $tier_id === $tier->id, 'bg-white ring-stone-300 hover:ring-juniper-500' => $tier_id !== $tier->id])>
                                <span @class(['block font-display text-2xl', 'text-juniper-800' => $tier_id !== $tier->id])>{{ $tier->name }}</span>
                                <span @class(['mt-2 block text-sm', 'text-stone-300' => $tier_id === $tier->id, 'text-ink-soft' => $tier_id !== $tier->id])>{{ $tier->description ?: ($tierCopy[$tier->slug] ?? '') }}</span>
                            </button>
                        @endforeach
                    </div>
                @endif

                <p class="field-label mt-10">Budget per person (excluding international flights)</p>
                <div class="flex flex-wrap gap-2">
                    @foreach (\App\Livewire\TailorMadeBuilder::BUDGETS as $b)
                        <button type="button" wire:key="b-{{ md5($b) }}" wire:click="$set('budget', @js($b))"
                            @class(['rounded-full px-4 py-2 text-sm font-medium ring-1 transition', 'bg-juniper-800 text-stone-100 ring-juniper-800' => $budget === $b, 'bg-white text-juniper-800 ring-stone-300 hover:ring-juniper-500' => $budget !== $b])>{{ $b }}</button>
                    @endforeach
                </div>
            </section>

            {{-- 5. You --}}
            <section @class(['hidden' => $step !== 5]) aria-labelledby="tm-5">
                <p class="eyebrow">Step 5 of 5</p>
                <h2 id="tm-5" class="mt-4 font-display text-display-sm">Finally, where should we send your itinerary?</h2>
                <div class="mt-8 grid gap-5 sm:grid-cols-2">
                    <x-site.field label="First name" name="first_name" wire:model="first_name" autocomplete="given-name" required prefix="tm" />
                    <x-site.field label="Last name" name="last_name" wire:model="last_name" autocomplete="family-name" optional prefix="tm" />
                    <x-site.field label="Email" name="email" type="email" wire:model="email" autocomplete="email" required prefix="tm" />
                    <x-site.field label="Phone / WhatsApp" name="phone" type="tel" wire:model="phone" autocomplete="tel" optional prefix="tm" hint="Many travellers prefer a quick WhatsApp chat." />
                    <x-site.field label="Country of residence" name="country" type="select" wire:model="country" class="sm:col-span-2" prefix="tm">
                        <option value="">Select…</option>
                        @foreach (\App\Support\Countries::all() as $c)<option>{{ $c }}</option>@endforeach
                    </x-site.field>
                    <x-site.field label="Tell us more" name="message" type="textarea" wire:model="message" rows="5" class="sm:col-span-2" optional prefix="tm"
                        placeholder="Previous trekking experience, a must-see place, a special occasion, mobility needs…" />
                    <div class="sm:col-span-2"><x-site.consent prefix="tm" /></div>
                </div>
            </section>

            <div class="mt-12 flex items-center justify-between gap-4 border-t border-stone-300/60 pt-8">
                @if ($step > 1)
                    <button type="button" wire:click="back" x-on:click="$dispatch('step-changed')" class="btn-outline"><x-heroicon-m-arrow-left class="size-4" /> Back</button>
                @else
                    <span class="text-sm text-stone-500">Takes about two minutes</span>
                @endif

                @if ($step < count($steps))
                    <button type="button" wire:click="next" x-on:click="$dispatch('step-changed')" class="btn-dark" wire:loading.attr="disabled">Continue <x-heroicon-m-arrow-right class="size-4" /></button>
                @else
                    <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="submit">Send my brief</span>
                        <span wire:loading wire:target="submit">Sending…</span>
                    </button>
                @endif
            </div>
        </form>
    @endif
</div>
