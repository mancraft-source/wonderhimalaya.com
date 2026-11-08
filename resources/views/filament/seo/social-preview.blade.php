{{-- Facebook / WhatsApp share card preview. --}}
<div style="display:grid; gap:.5rem; max-width:520px;">
    <span class="text-sm font-medium text-gray-950 dark:text-white">Share preview</span>
    <div style="border:1px solid #dadde1; border-radius:.5rem; overflow:hidden; background:#f0f2f5; font-family:Helvetica,Arial,sans-serif;">
        <div style="aspect-ratio:1.91/1; background:#e4e6eb {{ $image ? "url('".e($image)."') center/cover" : '' }}; display:grid; place-items:center; color:#8a8d91; font-size:.8rem;">
            @unless ($image)
                No image — the site default will be used
            @endunless
        </div>
        <div style="padding:.6rem .75rem; border-top:1px solid #dadde1;">
            <div style="font-size:12px; color:#606770; text-transform:uppercase;">{{ parse_url($url, PHP_URL_HOST) }}</div>
            <div style="font-size:16px; font-weight:600; color:#1d2129; line-height:1.3; margin-top:2px; overflow:hidden; white-space:nowrap; text-overflow:ellipsis;">{{ $ogTitle }}</div>
            <div style="font-size:14px; color:#606770; overflow:hidden; white-space:nowrap; text-overflow:ellipsis;">{{ $ogDescription }}</div>
        </div>
    </div>
</div>
