{{--
    Responsive image from a media-library model, with an organic placeholder when missing.
    <x-site.picture :model="$trip" collection="hero" sizes="(min-width: 1024px) 33vw, 100vw" class="..." />
    Or a plain URL: <x-site.picture src="/path.jpg" alt="..." />
--}}
@props(['model' => null, 'collection' => 'hero', 'media' => null, 'src' => null, 'alt' => null, 'sizes' => '100vw', 'eager' => false, 'imgClass' => 'h-full w-full object-cover'])
@php
    $media ??= $model?->getFirstMedia($collection);
    $alt ??= $media?->getCustomProperty('alt') ?: ($model->title ?? $model->name ?? '');
    $srcset = null;
    if ($media) {
        $set = collect(['thumb' => 480, 'card' => 960, 'wide' => 1920])
            ->filter(fn ($w, $c) => $media->hasGeneratedConversion($c))
            ->map(fn ($w, $c) => $media->getUrl($c).' '.$w.'w');
        $srcset = $set->isNotEmpty() ? $set->implode(', ') : null;
        $src = $media->hasGeneratedConversion('card') ? $media->getUrl('card') : $media->getUrl();
    }
@endphp
@if ($src)
    <img src="{{ $src }}" @if ($srcset) srcset="{{ $srcset }}" sizes="{{ $sizes }}" @endif alt="{{ $alt }}"
         {{ $attributes->class($imgClass) }} loading="{{ $eager ? 'eager' : 'lazy' }}" decoding="async" @if ($eager) fetchpriority="high" @endif>
@else
    <div {{ $attributes->class('relative overflow-hidden bg-gradient-to-br from-juniper-700 via-juniper-800 to-juniper-950') }} role="img" aria-label="{{ $alt }}">
        <div class="topo absolute inset-0 opacity-40 invert"></div>
        <svg class="absolute inset-x-0 bottom-0 w-full text-juniper-950/70" viewBox="0 0 400 120" preserveAspectRatio="none" aria-hidden="true"><path fill="currentColor" d="M0 120V82l48-30 34 18 58-52 40 34 30-16 52 44 36-22 50 30 52-28v60Z"/><path fill="#f4efe6" fill-opacity=".85" d="m140 18 17 14-9-2-8 7-6-5-10 4Zm-58 34 9 8-6-1-3 3-4-3-6 2Z"/></svg>
    </div>
@endif
