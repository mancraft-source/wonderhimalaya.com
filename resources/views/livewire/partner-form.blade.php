<div>
    @if ($reference)
        <div class="rounded-[2rem] bg-stone-100 p-10 text-juniper-900" data-reveal>
            <x-heroicon-o-hand-raised class="size-12 text-terracotta-500" />
            <h3 class="mt-5 font-display text-3xl">Namaste, partner.</h3>
            <p class="mt-3 text-ink-soft">Our partnerships desk will be in touch within one business day with our B2B rate sheet and sample programmes. Reference <span class="data text-juniper-800">{{ $reference }}</span>.</p>
        </div>
    @else
        <form wire:submit="submit" class="grid gap-5 rounded-[2rem] bg-stone-100 p-6 text-ink sm:grid-cols-2 sm:p-10" novalidate>
            <x-honeypot livewire-model="extraFields" />
            <x-site.field label="Company name" name="company_name" wire:model="company_name" autocomplete="organization" required prefix="b2b" />
            <x-site.field label="Website" name="company_website" type="url" wire:model="company_website" placeholder="https://" optional prefix="b2b" />
            <x-site.field label="Company type" name="company_type" type="select" wire:model="company_type" prefix="b2b">
                @foreach (\App\Enums\CompanyType::cases() as $type)<option value="{{ $type->value }}">{{ $type->getLabel() }}</option>@endforeach
            </x-site.field>
            <x-site.field label="Country" name="country" type="select" wire:model="country" required prefix="b2b">
                <option value="">Select…</option>
                @foreach (\App\Support\Countries::all() as $c)<option>{{ $c }}</option>@endforeach
            </x-site.field>
            <x-site.field label="Your first name" name="first_name" wire:model="first_name" autocomplete="given-name" required prefix="b2b" />
            <x-site.field label="Last name" name="last_name" wire:model="last_name" autocomplete="family-name" optional prefix="b2b" />
            <x-site.field label="Work email" name="email" type="email" wire:model="email" autocomplete="email" required prefix="b2b" />
            <x-site.field label="Phone / WhatsApp" name="phone" type="tel" wire:model="phone" autocomplete="tel" optional prefix="b2b" />
            <fieldset class="sm:col-span-2">
                <legend class="field-label">Services you're interested in</legend>
                <div class="mt-1 flex flex-wrap gap-2">
                    @foreach (\App\Livewire\PartnerForm::SERVICES as $service)
                        <label wire:key="s-{{ md5($service) }}" class="cursor-pointer">
                            <input type="checkbox" class="peer sr-only" wire:model.live="interests" value="{{ $service }}">
                            <span class="inline-flex rounded-full bg-white px-4 py-2 text-sm font-medium text-juniper-800 ring-1 ring-stone-300 transition peer-checked:bg-juniper-800 peer-checked:text-stone-100 peer-checked:ring-juniper-800 peer-focus-visible:outline-2 peer-focus-visible:outline-terracotta-500">{{ $service }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
            <x-site.field label="Tell us about your clients & volumes" name="message" type="textarea" wire:model="message" class="sm:col-span-2" optional prefix="b2b"
                placeholder="Typical group sizes, seasons, markets, programmes you'd like us to quote…" />
            <div class="sm:col-span-2"><x-site.consent :marketing="false" prefix="b2b" /></div>
            <div class="sm:col-span-2">
                <button type="submit" class="btn-primary w-full sm:w-auto" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="submit">Request partner rates</span>
                    <span wire:loading wire:target="submit">Sending…</span>
                </button>
            </div>
        </form>
    @endif
</div>
