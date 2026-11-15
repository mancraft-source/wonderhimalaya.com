{{-- A record's History slide-over: its own activity-log entries, newest first. --}}
<div class="wh-activity-timeline">
    @forelse ($entries as $entry)
        <article class="relative border-s border-gray-950/10 ps-5 pb-6 last:pb-0 dark:border-white/10" data-activity="{{ $entry['id'] }}">
            <span class="absolute -start-1.5 top-1.5 size-3 rounded-full bg-wh-terracotta ring-4 ring-white dark:ring-gray-900"></span>

            <header class="mb-2 flex flex-wrap items-center gap-2 text-sm">
                <x-filament::badge size="sm" :color="$entry['event_color']">{{ $entry['event_label'] }}</x-filament::badge>
                <span class="font-medium text-gray-950 dark:text-white">{{ $entry['causer'] }}</span>
                @if ($entry['when'])
                    <time class="ms-auto text-gray-500 dark:text-gray-400" datetime="{{ $entry['when']->toIso8601String() }}" title="{{ $entry['when']->format('j M Y, H:i') }}">
                        {{ $entry['when']->diffForHumans() }}
                    </time>
                @endif
            </header>

            @include('filament.activity.changes', ['changes' => $entry['changes']])
        </article>
    @empty
        <p class="text-sm text-gray-500 dark:text-gray-400">No history recorded for this record yet.</p>
    @endforelse
</div>
