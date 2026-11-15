{{-- Old → new diff for one activity-log entry. Values arrive pre-formatted and masked by App\Filament\Support\ActivityHistory. --}}

@if (count($changes))
    <dl class="wh-activity-changes divide-y divide-gray-950/5 rounded-lg border border-gray-950/5 text-sm dark:divide-white/10 dark:border-white/10">
        @foreach ($changes as $change)
            <div class="grid gap-1 px-3 py-2 sm:grid-cols-[9rem_1fr]" data-attribute="{{ $change['attribute'] }}">
                <dt class="font-medium text-gray-700 dark:text-gray-200">{{ $change['label'] }}</dt>
                <dd class="flex flex-wrap items-center gap-x-2 gap-y-1 break-words">
                    <span class="text-gray-500 line-through decoration-gray-400/60 dark:text-gray-400">{{ $change['old'] }}</span>
                    <span aria-hidden="true" class="text-gray-400">→</span>
                    <span class="sr-only">changed to</span>
                    <span class="text-gray-950 dark:text-white">{{ $change['new'] }}</span>
                </dd>
            </div>
        @endforeach
    </dl>
@else
    <p class="text-sm text-gray-500 dark:text-gray-400">No attribute changes were recorded.</p>
@endif
