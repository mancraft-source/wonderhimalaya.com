@props(['marketing' => true, 'prefix' => 'f'])
<div class="space-y-3 text-sm text-ink-soft">
    <label class="flex items-start gap-3">
        <input type="checkbox" wire:model="privacy_accepted" class="mt-0.5 size-5 rounded border-stone-300 text-juniper-800 focus:ring-juniper-500">
        <span>I agree to the <a href="{{ url('/terms-and-conditions') }}" target="_blank" class="underline decoration-terracotta-500/50 underline-offset-2">terms &amp; conditions</a> and <a href="{{ url('/privacy-policy') }}" target="_blank" class="underline decoration-terracotta-500/50 underline-offset-2">privacy policy</a> — we only use your details to plan your trip.</span>
    </label>
    @error('privacy_accepted')<p class="field-error !mt-0">{{ $message }}</p>@enderror
    @if ($marketing)
        <label class="flex items-start gap-3">
            <input type="checkbox" wire:model="marketing_consent" class="mt-0.5 size-5 rounded border-stone-300 text-juniper-800 focus:ring-juniper-500">
            <span>Send me occasional letters with new departures and routes.</span>
        </label>
    @endif
    @if (config('services.turnstile.site_key'))
        <div wire:ignore class="pt-1"
             x-data
             x-init="const draw = () => window.turnstile
                ? turnstile.render($el, { sitekey: @js(config('services.turnstile.site_key')), theme: 'light', callback: (t) => $wire.set('turnstileToken', t, false) })
                : setTimeout(draw, 250); draw()"></div>
        @once
            @push('scripts')<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}" src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit" async defer></script>@endpush
        @endonce
        @error('turnstileToken')<p class="field-error !mt-0">{{ $message }}</p>@enderror
    @endif
</div>
