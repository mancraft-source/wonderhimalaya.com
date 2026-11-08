<x-filament-panels::page>
    @php
        $dot = fn (int $score) => $score >= 75 ? '#22c55e' : ($score >= 50 ? '#f59e0b' : '#ef4444');
        $tile = 'border-radius:.75rem; padding:1rem 1.1rem;';
    @endphp

    @unless ($indexable)
        <div style="{{ $tile }} border:1px solid rgba(245,158,11,.4); background:rgba(245,158,11,.08);" class="text-sm text-gray-700 dark:text-gray-300">
            <strong>Search engines are currently blocked.</strong>
            {{ app()->isProduction() ? 'Indexing is switched off under SEO settings → Indexing & robots.txt.' : 'This is not the production environment, so every page sends noindex and robots.txt disallows crawling. This switches on automatically on the live site.' }}
        </div>
    @endunless

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:1rem;">
        <div style="{{ $tile }}" class="bg-white ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <p class="text-xs text-gray-500 dark:text-gray-400">Average SEO score</p>
            <p style="font-size:1.9rem; font-weight:700; color:{{ $dot($average) }};">{{ $average }}</p>
            <div style="display:flex; height:6px; border-radius:9999px; overflow:hidden; margin-top:.4rem;">
                @foreach (['good' => '#22c55e', 'ok' => '#f59e0b', 'bad' => '#ef4444'] as $band => $color)
                    <span style="width:{{ $total ? $bands[$band] / $total * 100 : 0 }}%; background:{{ $color }};"></span>
                @endforeach
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400" style="margin-top:.4rem;">{{ $bands['good'] }} good · {{ $bands['ok'] }} OK · {{ $bands['bad'] }} need work</p>
        </div>
        @foreach ([
            ['In the sitemap', $inSitemap, "of {$total} pages", url('/sitemap.xml')],
            ['Hidden (noindex)', $noindex, 'pages', null],
            ['No focus keyphrase', $noKeyphrase, 'pages', null],
            ['No meta description', $noDescription, 'pages', null],
            ['404s logged', $notFoundCount, $redirectCount.' active redirects', \App\Filament\Resources\NotFoundLogs\NotFoundLogResource::getUrl()],
        ] as [$label, $value, $sub, $link])
            <a @if ($link) href="{{ $link }}" @endif style="{{ $tile }} display:block;" class="bg-white ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $label }}</p>
                <p style="font-size:1.9rem; font-weight:700;" class="text-gray-950 dark:text-white">{{ $value }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $sub }}</p>
            </a>
        @endforeach
    </div>

    <x-filament::section heading="Needs attention" description="The lowest-scoring pages and the first thing to fix on each.">
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;" class="text-sm">
                <thead>
                    <tr class="text-left text-xs text-gray-500 dark:text-gray-400">
                        <th style="padding:.5rem;">Score</th><th style="padding:.5rem;">Page</th><th style="padding:.5rem;">SEO title</th><th style="padding:.5rem;">Top issue</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($attention as $row)
                        <tr style="border-top:1px solid rgba(127,127,127,.15);" class="text-gray-700 dark:text-gray-300">
                            <td style="padding:.6rem .5rem;"><span style="display:inline-flex; align-items:center; gap:.4rem; font-weight:600;"><span style="width:.6rem; height:.6rem; border-radius:50%; background:{{ $dot($row['score']) }};"></span>{{ $row['score'] }}</span></td>
                            <td style="padding:.6rem .5rem;"><span class="text-gray-950 dark:text-white">{{ $row['name'] }}</span><br><span class="text-xs text-gray-500">{{ $row['type'] }}</span></td>
                            <td style="padding:.6rem .5rem; max-width:22rem;">{{ $row['title'] }}</td>
                            <td style="padding:.6rem .5rem; max-width:24rem;">{{ $row['issue'] }}</td>
                            <td style="padding:.6rem .5rem; text-align:right;">@if ($row['editUrl'])<x-filament::link :href="$row['editUrl']" size="sm">Fix</x-filament::link>@endif</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:1.5rem;">
        <x-filament::section heading="Duplicate SEO titles" description="Pages competing for the same search result.">
            @forelse ($duplicates as $title => $rows)
                <div style="padding:.5rem 0; border-top:1px solid rgba(127,127,127,.15);" class="text-sm">
                    <p class="font-medium text-gray-950 dark:text-white">{{ $title }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        @foreach ($rows as $row)@if ($row['editUrl'])<a href="{{ $row['editUrl'] }}" style="text-decoration:underline;">{{ $row['type'] }}: {{ $row['name'] }}</a>@else{{ $row['name'] }}@endif{{ $loop->last ? '' : ' · ' }}@endforeach
                    </p>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">No duplicates. Every page has a unique title.</p>
            @endforelse
        </x-filament::section>

        <x-filament::section heading="Most-hit 404s" description="Broken URLs visitors reached. Redirect them to keep traffic and link value.">
            @forelse ($notFound as $log)
                <div style="display:flex; justify-content:space-between; gap:1rem; padding:.45rem 0; border-top:1px solid rgba(127,127,127,.15);" class="text-sm">
                    <code class="text-gray-700 dark:text-gray-300" style="overflow-wrap:anywhere;">{{ $log->path }}</code>
                    <span class="text-gray-500" style="white-space:nowrap;">{{ $log->hits }} {{ str('hit')->plural($log->hits) }}</span>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">No 404s logged yet.</p>
            @endforelse
            @if ($notFoundCount)
                <div style="margin-top:.75rem;"><x-filament::link :href="\App\Filament\Resources\NotFoundLogs\NotFoundLogResource::getUrl()" size="sm">Open the 404 monitor</x-filament::link></div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
