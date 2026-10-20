<div class="mt-5">
    @if ($done)
        <p class="rounded-2xl bg-white/10 px-5 py-4 text-sm text-stone-100">Dhanyabad — you're on the list. Watch for our next letter from the trail.</p>
    @else
        <form wire:submit="subscribe" class="flex flex-col gap-3 sm:flex-row">
            <x-honeypot livewire-model="extraFields" />
            <label for="newsletter-email" class="sr-only">Email address</label>
            <input id="newsletter-email" type="email" wire:model="email" required autocomplete="email" placeholder="Your email address"
                   class="min-w-0 flex-1 rounded-full border-0 bg-white/10 px-5 py-3.5 text-sm text-white ring-1 ring-white/20 placeholder:text-stone-400 focus:ring-2 focus:ring-saffron-300 focus:outline-none">
            <button type="submit" class="btn bg-stone-100 text-juniper-900 hover:bg-saffron-300" wire:loading.attr="disabled">Subscribe</button>
        </form>
        @error('email')<p class="mt-2 text-sm text-terracotta-400">{{ $message }}</p>@enderror
    @endif
</div>
