@if (! empty($data['items']))
    @push('head')
        <script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => collect($data['items'])->map(fn ($i) => ['@type' => 'Question', 'name' => $i['question'] ?? '', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($i['answer'] ?? '')]])->all()], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    @endpush
    <section @class(['py-10' => $narrow, 'container-narrow py-16' => ! $narrow])>
        @if (! empty($data['heading']))<h2 class="font-display text-display-sm">{{ $data['heading'] }}</h2>@endif
        <div class="mt-8 divide-y divide-stone-300/70 border-y border-stone-300/70">
            @foreach ($data['items'] as $item)
                <details class="group py-2">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-6 py-4 font-display text-xl text-juniper-800 [&::-webkit-details-marker]:hidden">{{ $item['question'] ?? '' }}<x-heroicon-m-plus class="size-5 shrink-0 text-terracotta-500 transition group-open:rotate-45" /></summary>
                    <div class="prose prose-wh pb-6">{!! $item['answer'] ?? '' !!}</div>
                </details>
            @endforeach
        </div>
    </section>
@endif
