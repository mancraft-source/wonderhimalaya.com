@php $items = \App\Models\Testimonial::where('is_published', true)->orderByDesc('is_featured')->latest('reviewed_at')->limit((int) ($data['limit'] ?? 3) ?: 3)->get(); @endphp
@if ($items->isNotEmpty())
    <section @class(['py-10' => $narrow, 'container-page py-16 lg:py-24' => ! $narrow])>
        @if (! empty($data['heading']))<x-site.section-heading eyebrow="In their words" :title="e($data['heading'])" />@endif
        <div @class(['mt-10 grid gap-6', 'md:grid-cols-3' => ! $narrow, 'md:grid-cols-2' => $narrow])>
            @foreach ($items as $t)
                <figure class="rounded-[1.75rem] bg-stone-50 p-7 ring-1 ring-stone-300/60" data-reveal>
                    <div class="flex gap-0.5 text-saffron-500">@for ($i = 0; $i < $t->rating; $i++)<x-heroicon-s-star class="size-4" />@endfor</div>
                    <blockquote class="mt-4 text-ink-soft">“{{ str($t->body)->limit(300) }}”</blockquote>
                    <figcaption class="mt-5 text-sm font-semibold text-juniper-800">{{ $t->author_name }}</figcaption>
                </figure>
            @endforeach
        </div>
    </section>
@endif
