{{-- SEO analysis (Yoast-style traffic lights) for the current form state. --}}
@php
    $score = $analysis['score'];
    $ring = $score >= 75 ? '#22c55e' : ($score >= 50 ? '#f59e0b' : '#ef4444');
    $dots = ['good' => '#22c55e', 'ok' => '#f59e0b', 'bad' => '#ef4444'];
@endphp
<div style="display:grid; gap:1rem;">
    <div style="display:flex; align-items:center; gap:1rem;">
        <div style="position:relative; width:64px; height:64px; border-radius:50%; background:conic-gradient({{ $ring }} {{ $score * 3.6 }}deg, rgba(127,127,127,.18) 0); display:grid; place-items:center;">
            <div style="width:50px; height:50px; border-radius:50%; display:grid; place-items:center; font-weight:700; font-size:1.05rem;" class="bg-white text-gray-950 dark:bg-gray-900 dark:text-white">{{ $score }}</div>
        </div>
        <div>
            <p class="text-sm font-semibold text-gray-950 dark:text-white">{{ $score >= 75 ? 'Good SEO' : ($score >= 50 ? 'OK — some improvements possible' : 'Needs improvement') }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Updates as you type. Content checks use the copy in this form.</p>
        </div>
    </div>
    <ul style="display:grid; gap:.5rem; margin:0; padding:0; list-style:none;">
        @foreach ($analysis['checks'] as $check)
            <li style="display:flex; gap:.6rem; align-items:flex-start;" class="text-sm text-gray-700 dark:text-gray-300">
                <span style="flex:none; margin-top:.35rem; width:.6rem; height:.6rem; border-radius:50%; background:{{ $dots[$check['status']] }};"></span>
                <span>{{ $check['label'] }}</span>
            </li>
        @endforeach
    </ul>
</div>
