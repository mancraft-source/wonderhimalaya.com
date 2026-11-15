{{-- Activity log → view modal: one entry's context and attribute diff. --}}
<div class="space-y-4">
    <dl class="grid gap-x-6 gap-y-2 text-sm sm:grid-cols-2">
        <div>
            <dt class="text-gray-500 dark:text-gray-400">When</dt>
            <dd class="text-gray-950 dark:text-white">{{ $activity->created_at?->format('j M Y, H:i:s') }}</dd>
        </div>
        <div>
            <dt class="text-gray-500 dark:text-gray-400">Who</dt>
            <dd class="text-gray-950 dark:text-white">{{ $causer }}</dd>
        </div>
        <div>
            <dt class="text-gray-500 dark:text-gray-400">Record</dt>
            <dd class="text-gray-950 dark:text-white">
                @if ($subjectUrl)
                    <a href="{{ $subjectUrl }}" class="text-primary-600 underline-offset-2 hover:underline dark:text-primary-400">{{ $subject }}</a>
                @else
                    {{ $subject }}
                @endif
            </dd>
        </div>
        <div>
            <dt class="text-gray-500 dark:text-gray-400">Event</dt>
            <dd><x-filament::badge size="sm" class="inline-flex" :color="$eventColor">{{ $event }}</x-filament::badge></dd>
        </div>
    </dl>

    @include('filament.activity.changes', ['changes' => $changes])
</div>
