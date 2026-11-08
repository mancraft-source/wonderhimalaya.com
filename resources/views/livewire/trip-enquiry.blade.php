<div x-data x-on:scroll-to-enquiry.window="$el.scrollIntoView({ behavior: 'smooth', block: 'start' })" class="scroll-mt-28">
    @if ($reference)
        <div class="rounded-[2rem] bg-juniper-800 p-8 text-stone-100 sm:p-12" data-reveal>
            <x-heroicon-o-check-badge class="size-12 text-saffron-300" />
            <h3 class="mt-5 font-display text-3xl text-stone-100">Thank you — your request is with us.</h3>
            <p class="mt-3 text-stone-300">A specialist will reply personally within 24 hours with availability and a detailed quote. Reference <span class="data text-saffron-300">{{ $reference }}</span>.</p>
        </div>
    @else
        <div class="overflow-hidden rounded-[2rem] bg-stone-50 ring-1 ring-stone-300/60">
            <div class="grid grid-cols-2 border-b border-stone-300/60 text-sm font-semibold" role="tablist">
                <button type="button" role="tab" wire:click="$set('mode', 'group')" aria-selected="{{ $mode === 'group' ? 'true' : 'false' }}"
                    @class(['px-6 py-5 transition', 'bg-juniper-800 text-stone-100' => $mode === 'group', 'text-juniper-800 hover:bg-stone-200/60' => $mode !== 'group'])>
                    Join a group departure
                </button>
                <button type="button" role="tab" wire:click="$set('mode', 'private')" aria-selected="{{ $mode === 'private' ? 'true' : 'false' }}"
                    @class(['px-6 py-5 transition', 'bg-juniper-800 text-stone-100' => $mode === 'private', 'text-juniper-800 hover:bg-stone-200/60' => $mode !== 'private'])>
                    Private trip, your dates
                </button>
            </div>

            <form wire:submit="submit" class="grid gap-5 p-6 sm:grid-cols-2 sm:p-10" novalidate>
                <x-honeypot livewire-model="extraFields" />

                @if ($mode === 'group')
                    <div class="sm:col-span-2">
                        <p class="field-label">{{ $this->departures->isEmpty() && $this->trip->departsDaily() ? 'When would you like to start?' : 'Choose a departure' }}</p>
                        @if ($this->departures->isEmpty() && $this->trip->departsDaily())
                            <div class="grid gap-4 sm:grid-cols-2">
                                <x-site.field label="Start date" name="start_date" type="date" wire:model="start_date" min="{{ $this->trip->earliestStartDate()->toDateString() }}" required prefix="te"
                                    hint="This group trip departs every day. Bookings close {{ $this->trip->departure_cutoff_days }} days before departure." />
                                <div class="flex items-center gap-3 self-start rounded-2xl bg-juniper-50 p-4 text-sm text-juniper-800 sm:mt-7">
                                    <x-heroicon-o-calendar-days class="size-6 shrink-0 text-juniper-500" />
                                    <span>Daily departures{{ $this->trip->departure_capacity ? ' · up to '.$this->trip->departure_capacity.' travellers per group' : '' }}</span>
                                </div>
                            </div>
                        @elseif ($this->departures->isEmpty())
                            <p class="rounded-2xl bg-stone-200/60 p-5 text-sm text-ink-soft">No group dates are published for this trip right now — switch to <button type="button" class="font-semibold text-juniper-800 underline" wire:click="$set('mode', 'private')">a private trip</button> and we'll run it on the dates you choose.</p>
                        @else
                            <div class="grid gap-2 sm:grid-cols-2">
                                @foreach ($this->departures as $d)
                                    <label wire:key="dep-{{ $d->id }}" @class(['flex cursor-pointer items-center justify-between gap-3 rounded-2xl px-4 py-3 ring-1 transition', 'bg-juniper-50 ring-2 ring-juniper-500' => $trip_departure_id === $d->id, 'bg-white ring-stone-300 hover:ring-juniper-300' => $trip_departure_id !== $d->id])>
                                        <span class="flex items-center gap-3">
                                            <input type="radio" class="sr-only" wire:model.live="trip_departure_id" value="{{ $d->id }}">
                                            <span>
                                                <span class="block font-semibold text-juniper-800">{{ $d->start_date->format('j M Y') }}</span>
                                                <span class="block text-xs text-stone-500">{{ $d->seatsLeft() }} seats left · {{ $d->status->getLabel() }}</span>
                                            </span>
                                        </span>
                                        @if ($d->effectivePrice())<span class="font-display text-lg text-juniper-800">US${{ number_format($d->effectivePrice()) }}</span>@endif
                                    </label>
                                @endforeach
                            </div>
                        @endif
                        @error('trip_departure_id')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                @else
                    <x-site.field label="Preferred start date" name="start_date" type="date" wire:model="start_date" min="{{ now()->addDays(7)->toDateString() }}" optional prefix="te" />
                    @if ($this->tierOptions->count() > 1)
                        <x-site.field label="Package" name="tier_id" type="select" wire:model="tier_id" optional prefix="te">
                            <option value="">No preference</option>
                            @foreach ($this->tierOptions as $tier)<option value="{{ $tier->id }}">{{ $tier->name }}</option>@endforeach
                        </x-site.field>
                    @else
                        <div></div>
                    @endif
                @endif

                <div class="grid grid-cols-2 gap-4 sm:col-span-2">
                    <x-site.field label="Adults" name="adults" type="number" min="1" max="40" wire:model="adults" prefix="te" />
                    <x-site.field label="Children (under 16)" name="children" type="number" min="0" max="20" wire:model="children" prefix="te" />
                </div>
                <x-site.field label="First name" name="first_name" wire:model="first_name" autocomplete="given-name" required prefix="te" />
                <x-site.field label="Last name" name="last_name" wire:model="last_name" autocomplete="family-name" prefix="te" />
                <x-site.field label="Email" name="email" type="email" wire:model="email" autocomplete="email" required prefix="te" />
                <x-site.field label="Phone / WhatsApp" name="phone" type="tel" wire:model="phone" autocomplete="tel" optional prefix="te" />
                <x-site.field label="Country" name="country" type="select" wire:model="country" prefix="te">
                    <option value="">Select…</option>
                    @foreach (\App\Support\Countries::all() as $c)<option>{{ $c }}</option>@endforeach
                </x-site.field>
                <x-site.field label="How did you find us?" name="heard_about" type="select" wire:model="heard_about" optional prefix="te">
                    <option value="">Select…</option>
                    @foreach (\App\Livewire\TripEnquiry::HEARD_ABOUT as $option)<option>{{ $option }}</option>@endforeach
                </x-site.field>
                <x-site.field label="Anything we should know?" name="message" type="textarea" wire:model="message" class="sm:col-span-2" optional prefix="te"
                    placeholder="Fitness, dietary needs, extra days in Kathmandu, a celebration…" />
                <div class="sm:col-span-2"><x-site.consent prefix="te" /></div>
                <div class="flex flex-col gap-4 sm:col-span-2 sm:flex-row sm:items-center sm:justify-between">
                    <p class="flex items-center gap-2 text-sm text-ink-soft"><x-heroicon-o-shield-check class="size-5 text-juniper-500" /> No payment now · reply within 24 hours</p>
                    <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="submit">{{ $mode === 'group' ? 'Request to join' : 'Request a quote' }}</span>
                        <span wire:loading wire:target="submit">Sending…</span>
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>
