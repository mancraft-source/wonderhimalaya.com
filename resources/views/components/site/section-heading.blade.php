@props(['eyebrow' => null, 'title', 'intro' => null, 'align' => 'left', 'light' => false])
<div {{ $attributes->class(['max-w-3xl', 'mx-auto text-center' => $align === 'center']) }} data-reveal>
    @if ($eyebrow)
        <p @class(['eyebrow', 'eyebrow-light' => $light, 'justify-center' => $align === 'center'])>{{ $eyebrow }}</p>
    @endif
    <h2 @class(['mt-5 font-display text-display-md', 'text-stone-100' => $light])>{!! $title !!}</h2>
    @if ($intro)
        <p @class(['mt-6 text-lg', 'text-stone-300' => $light, 'text-ink-soft' => ! $light])>{{ $intro }}</p>
    @endif
    {{ $slot }}
</div>
