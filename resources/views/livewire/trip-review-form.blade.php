<div class="rounded-[1.75rem] bg-stone-200/50 p-6 sm:p-8">
    @if ($submitted)
        <div class="flex items-start gap-4">
            <x-heroicon-o-check-badge class="size-8 shrink-0 text-juniper-500" />
            <div>
                <p class="font-display text-xl text-juniper-800">Dhanyabad for your review!</p>
                <p class="mt-1 text-sm text-ink-soft">We check every review before it appears — usually within a day or two.</p>
            </div>
        </div>
    @elseif (! $open)
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="font-display text-xl text-juniper-800">Travelled this route with us?</p>
                <p class="text-sm text-ink-soft">Your story helps the next traveller decide.</p>
            </div>
            <button type="button" wire:click="$set('open', true)" class="btn-dark">Write a review</button>
        </div>
    @else
        <form wire:submit="submit" class="grid gap-5 sm:grid-cols-2" novalidate>
            <x-honeypot livewire-model="extraFields" />
            <fieldset class="sm:col-span-2">
                <legend class="field-label">Your rating</legend>
                <div class="flex gap-1" x-data="{ hover: 0 }" x-on:mouseleave="hover = 0">
                    @for ($i = 1; $i <= 5; $i++)
                        <button type="button" wire:click="$set('rating', {{ $i }})" x-on:mouseenter="hover = {{ $i }}" aria-label="{{ $i }} {{ str('star')->plural($i) }}"
                                class="text-stone-300 transition" x-bind:class="(hover || {{ $rating }}) >= {{ $i }} && '!text-saffron-500'">
                            <x-heroicon-s-star class="size-8" />
                        </button>
                    @endfor
                </div>
            </fieldset>
            <x-site.field label="Review title" name="title" wire:model="title" optional prefix="rv" class="sm:col-span-2" placeholder="e.g. Unforgettable sunrise at Kala Patthar" />
            <x-site.field label="Your review" name="body" type="textarea" wire:model="body" rows="5" class="sm:col-span-2" required prefix="rv" placeholder="What made the trip special? How were the guides, lodges and pace?" />
            <x-site.field label="Your name" name="author_name" wire:model="author_name" autocomplete="name" required prefix="rv" />
            <x-site.field label="Email" name="author_email" type="email" wire:model="author_email" autocomplete="email" required prefix="rv" hint="Never published — only to verify your trip." />
            <x-site.field label="Country" name="author_country" type="select" wire:model="author_country" optional prefix="rv">
                <option value="">Select…</option>
                @foreach (\App\Support\Countries::all() as $c)<option>{{ $c }}</option>@endforeach
            </x-site.field>
            <x-site.field label="When did you travel?" name="travelled_on" type="month" wire:model="travelled_on" max="{{ now()->format('Y-m') }}" optional prefix="rv" />
            <label class="flex items-start gap-3 text-sm text-ink-soft sm:col-span-2">
                <input type="checkbox" wire:model="consent" class="mt-0.5 size-5 rounded border-stone-300 text-juniper-800 focus:ring-juniper-500">
                <span>I travelled with Wonder Himalaya and agree to my review and first name being published.</span>
            </label>
            @error('consent')<p class="field-error -mt-3 sm:col-span-2">{{ $message }}</p>@enderror
            <div class="flex items-center gap-4 sm:col-span-2">
                <button type="submit" class="btn-primary" wire:loading.attr="disabled"><span wire:loading.remove wire:target="submit">Submit review</span><span wire:loading wire:target="submit">Sending…</span></button>
                <button type="button" wire:click="$set('open', false)" class="text-sm font-semibold text-stone-500 hover:text-juniper-800">Cancel</button>
            </div>
        </form>
    @endif
</div>
