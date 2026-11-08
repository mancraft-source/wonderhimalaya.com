@php $img = blank($data['image'] ?? null) ? null : (str_starts_with((string) $data['image'], 'http') ? $data['image'] : \Illuminate\Support\Facades\Storage::disk('public')->url($data['image'])); $right = ($data['image_position'] ?? 'left') === 'right'; @endphp
<section @class(['py-10' => $narrow, 'container-page py-16 lg:py-24' => ! $narrow])>
    <div class="grid items-center gap-12 lg:grid-cols-2 lg:gap-20" data-reveal>
        <div @class(['aspect-[4/5] overflow-hidden img-organic', 'lg:order-2' => $right])><x-site.picture :src="$img" :alt="$data['heading'] ?? ''" class="h-full w-full" /></div>
        <div>
            @if (! empty($data['eyebrow']))<p class="eyebrow">{{ $data['eyebrow'] }}</p>@endif
            @if (! empty($data['heading']))<h2 class="mt-5 font-display text-display-sm">{{ $data['heading'] }}</h2>@endif
            @if (! empty($data['content']))<div class="prose prose-lg prose-wh mt-6">{!! \App\Support\RichText::normalize($data['content']) !!}</div>@endif
            @if (! empty($data['cta_label']) && ! empty($data['cta_url']))<a href="{{ $data['cta_url'] }}" class="link-arrow mt-8">{{ $data['cta_label'] }} <x-heroicon-m-arrow-right class="size-4" /></a>@endif
        </div>
    </div>
</section>
