@if (! empty($data['items']))
    @push('head')
        <script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => collect($data['items'])->map(fn ($i) => ['@type' => 'Question', 'name' => $i['question'] ?? '', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($i['answer'] ?? '')]])->all()], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    @endpush
    <section @class(['py-10' => $narrow, 'container-narrow py-16' => ! $narrow])>
        @if (! empty($data['heading']))<h2 class="font-display text-display-sm">{{ $data['heading'] }}</h2>@endif
        <div class="mt-8 divide-y divide-stone-300/70 border-y border-stone-300/70">
            @foreach ($data['items'] as $item)
                <x-site.faq-item :question="$item['question'] ?? ''" :number="$loop->iteration" data-reveal>{!! $item['answer'] ?? '' !!}</x-site.faq-item>
            @endforeach
        </div>
    </section>
@endif
