<div>
    @if ($reference)
        <div class="card-surface p-10 text-center" data-reveal>
            <x-heroicon-o-check-circle class="mx-auto size-12 text-juniper-500" />
            <h3 class="mt-5 font-display text-3xl">Message received — dhanyabad!</h3>
            <p class="mt-3 text-ink-soft">A trip specialist will reply within 24 hours. Your reference is <span class="data text-juniper-800">{{ $reference }}</span>.</p>
        </div>
    @else
        <form wire:submit="submit" class="card-surface grid gap-5 p-6 sm:grid-cols-2 sm:p-10" novalidate>
            <x-honeypot livewire-model="extraFields" />
            <x-site.field label="First name" name="first_name" wire:model="first_name" autocomplete="given-name" required prefix="c" />
            <x-site.field label="Last name" name="last_name" wire:model="last_name" autocomplete="family-name" prefix="c" />
            <x-site.field label="Email" name="email" type="email" wire:model="email" autocomplete="email" required prefix="c" />
            <x-site.field label="Phone / WhatsApp" name="phone" type="tel" wire:model="phone" autocomplete="tel" optional prefix="c" />
            <x-site.field label="Country of residence" name="country" type="select" wire:model="country" class="sm:col-span-2" prefix="c">
                <option value="">Select…</option>
                @foreach (\App\Support\Countries::all() as $c)<option>{{ $c }}</option>@endforeach
            </x-site.field>
            <x-site.field label="How can we help?" name="message" type="textarea" wire:model="message" rows="6" class="sm:col-span-2" required prefix="c"
                placeholder="Tell us about the trip you have in mind, your dates and any questions." />
            <div class="sm:col-span-2"><x-site.consent prefix="c" /></div>
            <div class="sm:col-span-2">
                <button type="submit" class="btn-primary w-full sm:w-auto" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="submit">Send message</span>
                    <span wire:loading wire:target="submit">Sending…</span>
                </button>
            </div>
        </form>
    @endif
</div>
