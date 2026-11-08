{{-- Google result preview (SEO panel). Inline styles: the admin panel has no custom theme. --}}
@php
    $parts = parse_url($url);
    $crumb = ($parts['host'] ?? '').collect(explode('/', trim($parts['path'] ?? '', '/')))->filter()->map(fn ($p) => ' › '.$p)->implode('');
    $titleLength = mb_strlen($title);
    $descriptionLength = mb_strlen((string) $description);
    $bar = fn (int $length, int $min, int $max) => $length === 0 ? ['0%', '#ef4444'] : [min(100, round($length / $max * 100)).'%', $length < $min ? '#f59e0b' : ($length <= $max ? '#22c55e' : '#ef4444')];
    [$titleWidth, $titleColor] = $bar($titleLength, 30, 60);
    [$descriptionWidth, $descriptionColor] = $bar($descriptionLength, 120, 160);
@endphp
<div x-data="{ mobile: false }" style="display:grid; gap:.75rem;">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:1rem;">
        <span class="text-sm font-medium text-gray-950 dark:text-white">Google preview</span>
        <div style="display:inline-flex; border-radius:.5rem; padding:2px;" class="bg-gray-100 dark:bg-white/5">
            <button type="button" x-on:click="mobile = false" :style="!mobile && 'background:white; box-shadow:0 1px 2px rgba(0,0,0,.1)'" style="padding:.25rem .65rem; border-radius:.4rem; font-size:.75rem;" class="text-gray-700 dark:text-gray-300">Desktop</button>
            <button type="button" x-on:click="mobile = true" :style="mobile && 'background:white; box-shadow:0 1px 2px rgba(0,0,0,.1)'" style="padding:.25rem .65rem; border-radius:.4rem; font-size:.75rem;" class="text-gray-700 dark:text-gray-300">Mobile</button>
        </div>
    </div>
    <div :style="mobile ? 'max-width:380px' : 'max-width:600px'" style="background:#fff; border:1px solid #e5e7eb; border-radius:.75rem; padding:1rem 1.1rem; font-family:arial,sans-serif; transition:max-width .3s;">
        <div style="display:flex; align-items:center; gap:.6rem;">
            <span style="display:grid; place-items:center; width:28px; height:28px; border-radius:50%; background:#f1f3f4; overflow:hidden;"><img src="{{ asset('images/brand/logo-badge-160.webp') }}" alt="" style="width:22px; height:22px; object-fit:contain;"></span>
            <span style="line-height:1.2;">
                <span style="display:block; font-size:14px; color:#202124;">{{ \App\Support\Seo\SeoSettings::siteName() }}</span>
                <span style="display:block; font-size:12px; color:#4d5156; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:480px;">{{ $crumb }}</span>
            </span>
        </div>
        <div :style="mobile ? 'font-size:18px' : 'font-size:20px'" style="margin-top:.5rem; color:#1a0dab; line-height:1.3; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $title }}</div>
        <div style="margin-top:.25rem; font-size:14px; line-height:1.58; color:#4d5156; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
            @if ($description){{ $description }}@else<em style="color:#9aa0a6;">No meta description — Google will pick text from the page.</em>@endif
        </div>
    </div>
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; max-width:600px;">
        @foreach ([['Title', $titleLength, 60, $titleWidth, $titleColor], ['Description', $descriptionLength, 160, $descriptionWidth, $descriptionColor]] as [$label, $length, $max, $width, $color])
            <div>
                <div style="display:flex; justify-content:space-between; font-size:.7rem;" class="text-gray-500 dark:text-gray-400"><span>{{ $label }} length</span><span>{{ $length }} / {{ $max }}</span></div>
                <div style="margin-top:.25rem; height:4px; border-radius:9999px; background:rgba(127,127,127,.2); overflow:hidden;"><div style="height:100%; width:{{ $width }}; background:{{ $color }}; transition:width .3s;"></div></div>
            </div>
        @endforeach
    </div>
</div>
