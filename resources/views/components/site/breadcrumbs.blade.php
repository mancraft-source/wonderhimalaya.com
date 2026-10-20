{{-- $items: [['label' => 'Trips', 'url' => '/trips'], ['label' => 'Current']] --}}
@props(['items' => [], 'light' => false])
@php
    $all = array_merge([['label' => 'Home', 'url' => url('/')]], $items);
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => collect($all)->values()->map(fn ($i, $k) => array_filter([
            '@type' => 'ListItem', 'position' => $k + 1, 'name' => $i['label'], 'item' => $i['url'] ?? url()->current(),
        ]))->all(),
    ];
@endphp
<nav aria-label="Breadcrumb" {{ $attributes->class(['text-xs font-medium tracking-wide', 'text-white/75' => $light, 'text-stone-500' => ! $light]) }}>
    <ol class="flex flex-wrap items-center gap-x-2 gap-y-1">
        @foreach ($all as $item)
            <li class="flex items-center gap-2">
                @if (! $loop->first)<span aria-hidden="true" class="opacity-50">/</span>@endif
                @if (! $loop->last && ! empty($item['url']))
                    <a href="{{ $item['url'] }}" @class(['hover:text-white' => $light, 'hover:text-juniper-800' => ! $light])>{{ $item['label'] }}</a>
                @else
                    <span aria-current="page" @class(['text-white' => $light, 'text-juniper-800' => ! $light])>{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
<script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
