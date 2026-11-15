@php $cc = \App\Support\Content::landing('contact'); $c = \App\Models\Setting::get('company', []); $wa = preg_replace('/\D/', '', $c['whatsapp'] ?? ''); @endphp
<x-layouts.site :seo="$seo">
    <x-site.page-hero :eyebrow="$cc['eyebrow']" :title="e($cc['title']).' <em class=\'text-terracotta-500\'>'.e($cc['title_em']).'</em>'" :intro="$cc['intro']" :crumbs="[['label' => 'Contact']]" />
    <section class="container-page grid gap-12 py-12 sm:gap-14 sm:py-16 lg:grid-cols-12 lg:py-24">
        <div class="min-w-0 space-y-3 sm:space-y-6 lg:col-span-4">
            @foreach (array_filter([
                ['Call the office', $c['phone'] ?? null, 'tel:'.preg_replace('/[^0-9+]/', '', $c['phone'] ?? ''), 'phone'],
                ['WhatsApp', $wa ? '+'.$wa : null, $wa ? 'https://wa.me/'.$wa : null, 'chat-bubble-left-right'],
                ['Email', $c['email'] ?? null, 'mailto:'.($c['email'] ?? ''), 'envelope'],
                ['Visit us', $c['address'] ?? null, 'https://maps.google.com/?q='.rawurlencode('Wonder Himalaya, '.($c['address'] ?? 'Thamel, Kathmandu')), 'map-pin'],
            ], fn ($row) => filled($row[1])) as [$label, $value, $href, $icon])
                <a href="{{ $href }}" @if (str_starts_with($href, 'http')) target="_blank" rel="noopener" @endif class="group flex min-w-0 items-center gap-4 rounded-[1.5rem] bg-stone-50 p-4 ring-1 sm:items-start sm:gap-5 sm:rounded-[1.75rem] sm:p-6 ring-stone-300/60 transition hover:ring-navy-500">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-navy-800 text-saffron-300 sm:size-12"><x-dynamic-component :component="'heroicon-o-'.$icon" class="size-5" /></span>
                    <span class="min-w-0"><span class="block text-xs font-semibold uppercase tracking-[0.16em] text-stone-500 sm:tracking-[0.18em]">{{ $label }}</span><span class="mt-0.5 block font-display text-lg leading-snug [overflow-wrap:anywhere] text-navy-800 group-hover:underline sm:mt-1 sm:text-xl">{{ $value }}</span></span>
                </a>
            @endforeach
            @if (! empty($c['hours']))<p class="px-2 text-sm text-ink-soft">Office hours: {{ $c['hours'] }}</p>@endif
        </div>
        <div class="min-w-0 lg:col-span-8"><livewire:contact-form /></div>
    </section>
    <section class="container-page pb-10">
        <div class="overflow-hidden rounded-[1.5rem] ring-1 sm:rounded-[2rem] ring-stone-300/60">
            <iframe title="Map: Wonder Himalaya, Thamel" src="https://www.google.com/maps?q={{ rawurlencode($cc['map_query']) }}&output=embed" class="h-[20rem] w-full grayscale-[60%] sm:h-[26rem]" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
    </section>
</x-layouts.site>
