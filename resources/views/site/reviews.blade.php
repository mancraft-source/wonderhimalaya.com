<x-layouts.site :seo="$seo">
    <x-site.page-hero eyebrow="Traveller reviews" title="‘Memorable’, ‘exciting’, <em class='text-terracotta-500'>‘unforgettable’</em>" intro="The words our travellers use — not ours. Every review below was left on Google or Tripadvisor." :crumbs="[['label' => 'Reviews']]">
        <div class="mt-10 flex flex-wrap gap-4">
            <div class="flex items-center gap-4 rounded-[1.5rem] bg-stone-50 px-6 py-4 ring-1 ring-stone-300/60">
                <svg viewBox="0 0 24 24" class="size-8" aria-hidden="true"><path fill="#4285F4" d="M23.5 12.3c0-.8-.1-1.6-.2-2.3H12v4.4h6.5a5.6 5.6 0 0 1-2.4 3.6v3h3.9c2.2-2.1 3.5-5.1 3.5-8.7z"/><path fill="#34A853" d="M12 24c3.2 0 6-1.1 7.9-2.9l-3.9-3c-1.1.7-2.4 1.2-4 1.2-3.1 0-5.7-2.1-6.6-4.9H1.4v3.1A12 12 0 0 0 12 24z"/><path fill="#FBBC05" d="M5.4 14.4a7.2 7.2 0 0 1 0-4.7V6.6h-4a12 12 0 0 0 0 10.8z"/><path fill="#EA4335" d="M12 4.8c1.8 0 3.3.6 4.6 1.8l3.4-3.4A12 12 0 0 0 1.4 6.6l4 3.1C6.3 6.9 8.9 4.8 12 4.8z"/></svg>
                <div><p class="font-display text-2xl leading-none text-juniper-800">{{ $reviews['google_label'] ?? 'Excellent' }}</p><p class="text-sm text-ink-soft">{{ $reviews['google_count'] ?? 78 }} Google reviews</p></div>
            </div>
            @if (! empty($social['tripadvisor']))
                <a href="{{ $social['tripadvisor'] }}" target="_blank" rel="noopener" class="flex items-center gap-4 rounded-[1.5rem] bg-stone-50 px-6 py-4 ring-1 ring-stone-300/60 hover:ring-juniper-500">
                    <span class="flex size-8 items-center justify-center rounded-full bg-[#34E0A1]"><x-heroicon-s-trophy class="size-4" /></span>
                    <div><p class="font-display text-2xl leading-none text-juniper-800">{{ $reviews['tripadvisor_award'] ?? "Travellers' Choice" }}</p><p class="text-sm text-ink-soft">on Tripadvisor →</p></div>
                </a>
            @endif
        </div>
    </x-site.page-hero>
    <section class="container-page py-16">
        <div class="columns-1 gap-6 md:columns-2 xl:columns-3">
            @forelse ($testimonials as $t)
                <figure class="mb-6 break-inside-avoid rounded-[1.75rem] bg-stone-50 p-8 ring-1 ring-stone-300/60">
                    <div class="flex items-center justify-between">
                        <div class="flex gap-0.5 text-saffron-500" aria-label="{{ $t->rating }} out of 5">@for ($i = 0; $i < $t->rating; $i++)<x-heroicon-s-star class="size-4" />@endfor</div>
                        <span class="text-xs uppercase tracking-[0.16em] text-stone-400">{{ $t->source }}</span>
                    </div>
                    @if ($t->title)<p class="mt-4 font-display text-xl text-juniper-800">{{ $t->title }}</p>@endif
                    <blockquote class="mt-4 text-ink-soft">“{{ $t->body }}”</blockquote>
                    <figcaption class="mt-6 flex items-center gap-3">
                        <span class="flex size-10 items-center justify-center rounded-full bg-juniper-800 font-display text-stone-100">{{ str($t->author_name)->substr(0, 1)->upper() }}</span>
                        <span><span class="block text-sm font-semibold text-juniper-800">{{ $t->author_name }}</span>
                        <span class="block text-xs text-stone-500">{{ collect([$t->author_country, $t->trip?->title, $t->reviewed_at?->format('M Y')])->filter()->implode(' · ') }}</span></span>
                    </figcaption>
                </figure>
            @empty
                <p class="text-ink-soft">Reviews are on their way.</p>
            @endforelse
        </div>
        <div class="mt-12">{{ $testimonials->links() }}</div>
    </section>
    @include('site.partials.tailor-made-band')
</x-layouts.site>
