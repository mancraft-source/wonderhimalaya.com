@php $img = blank($data['image'] ?? null) ? null : (str_starts_with((string) $data['image'], 'http') ? $data['image'] : \Illuminate\Support\Facades\Storage::disk('public')->url($data['image'])); @endphp
<section class="relative isolate flex h-[72svh] min-h-[34rem] max-h-[48rem] flex-col justify-end overflow-hidden bg-juniper-950 text-white">
    <div class="absolute inset-0 -z-10" data-parallax><x-site.picture :src="$img" :alt="$data['heading'] ?? ''" eager class="h-full w-full animate-slow-zoom" /></div>
    <div class="scrim-hero absolute inset-0 -z-10"></div>
    <div class="hero-copy container-page pt-36 pb-16 lg:pb-24">
        @if (! empty($crumbs))<x-site.breadcrumbs :items="$crumbs" light />@endif
        @if (! empty($data['eyebrow']))<p class="eyebrow eyebrow-light mt-6">{{ $data['eyebrow'] }}</p>@endif
        <h1 class="mt-5 max-w-5xl font-display text-display-lg text-white">{{ $data['heading'] ?? '' }}</h1>
        @if (! empty($data['subheading']))<p class="hero-glass mt-6 max-w-2xl text-lg text-white/90">{{ $data['subheading'] }}</p>@endif
        @if (! empty($data['cta_label']) && ! empty($data['cta_url']))<a href="{{ $data['cta_url'] }}" class="btn-primary mt-10">{{ $data['cta_label'] }} <x-heroicon-m-arrow-right class="size-4" /></a>@endif
    </div>
</section>
