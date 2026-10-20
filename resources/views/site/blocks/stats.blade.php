@php $items = ! empty($data['items']) && empty($data['use_site_stats']) ? $data['items'] : \App\Models\Setting::get('stats', []); @endphp
@if ($items)
    <section @class(['py-10' => $narrow, 'container-page py-16' => ! $narrow])>
        @if (! empty($data['heading']))<h2 class="mb-10 font-display text-display-sm">{{ $data['heading'] }}</h2>@endif
        <dl class="grid grid-cols-2 gap-px overflow-hidden rounded-[2rem] bg-stone-300/60 ring-1 ring-stone-300/60 lg:grid-cols-4">
            @foreach ($items as $s)<div class="bg-stone-100 p-8"><dt class="text-sm text-ink-soft">{{ $s['label'] ?? '' }}</dt><dd class="mt-3 font-display text-5xl text-juniper-800">{{ $s['value'] ?? '' }}</dd></div>@endforeach
        </dl>
    </section>
@endif
