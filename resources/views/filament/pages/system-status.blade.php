<x-filament-panels::page>
    @php
        $row = 'flex items-baseline justify-between gap-4 border-t border-gray-950/5 py-2 text-sm first:border-t-0 first:pt-0 dark:border-white/10';
        $label = 'text-gray-500 dark:text-gray-400';
        $value = 'text-end font-medium text-gray-950 dark:text-white';
    @endphp

    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
        {{-- Application --}}
        <x-filament::section :icon="\Filament\Support\Icons\Heroicon::OutlinedCommandLine" heading="Application">
            @foreach ($app as $name => $text)
                <div class="{{ $row }}">
                    <span class="{{ $label }}">{{ $name }}</span>
                    @if ($name === 'Search indexing' || $name === 'Debug mode')
                        <x-filament::badge :color="match (true) {
                            $name === 'Search indexing' => $text === 'On' ? 'success' : 'warning',
                            default => $text === 'On' && app()->isProduction() ? 'danger' : 'gray',
                        }">{{ $text }}</x-filament::badge>
                    @else
                        <span class="{{ $value }} font-mono text-xs">{{ $text }}</span>
                    @endif
                </div>
            @endforeach
        </x-filament::section>

        {{-- Scheduler --}}
        <x-filament::section :icon="\Filament\Support\Icons\Heroicon::OutlinedClock" heading="Scheduler (cron)">
            <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                <x-filament::badge class="shrink-0 whitespace-nowrap" :color="$scheduler['ok'] ? 'success' : 'danger'" :icon="$scheduler['ok'] ? \Filament\Support\Icons\Heroicon::CheckCircle : \Filament\Support\Icons\Heroicon::ExclamationTriangle">
                    {{ $scheduler['ok'] ? 'Running' : 'Not running' }}
                </x-filament::badge>
                <span class="text-sm text-gray-700 dark:text-gray-300" @if ($scheduler['ranAt']) title="{{ $scheduler['ranAt']->setTimezone('Asia/Kathmandu')->format('D j M Y, H:i:s') }}" @endif>{{ $scheduler['label'] }}</span>
            </div>
            @unless ($scheduler['ok'])
                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                    Scheduled jobs (emails, balance reminders, backups) run from a cron entry that calls
                    <code class="font-mono text-xs">php artisan schedule:run</code> every minute.
                    @if ($scheduler['ranAt'])
                        It has not checked in for more than {{ \App\Filament\Pages\SystemStatus::HEARTBEAT_STALE_MINUTES }} minutes.
                    @endif
                </p>
            @endunless
        </x-filament::section>

        {{-- Queue --}}
        <x-filament::section :icon="\Filament\Support\Icons\Heroicon::OutlinedQueueList" heading="Queue">
            <x-slot name="afterHeader">
                @if ($queue['failed'])
                    {{ $this->retryFailedJobsAction }}
                @endif
            </x-slot>
            <div class="{{ $row }}">
                <span class="{{ $label }}">Connection</span>
                <span class="{{ $value }}">{{ $queue['connection'] }}</span>
            </div>
            <div class="{{ $row }}">
                <span class="{{ $label }}">Pending jobs</span>
                <span class="{{ $value }}">{{ $queue['pending'] ?? 'n/a' }}</span>
            </div>
            <div class="{{ $row }}">
                <span class="{{ $label }}">Failed jobs</span>
                @if ($queue['failed'])
                    <x-filament::badge color="danger">{{ $queue['failed'] }}</x-filament::badge>
                @else
                    <span class="{{ $value }}">{{ $queue['failed'] ?? 'n/a' }}</span>
                @endif
            </div>
        </x-filament::section>

        {{-- Mail --}}
        <x-filament::section :icon="\Filament\Support\Icons\Heroicon::OutlinedEnvelope" heading="Email">
            <x-slot name="afterHeader">
                {{ $this->sendTestEmailAction }}
            </x-slot>
            <div class="flex items-start gap-3">
                <x-filament::icon
                    :icon="$mail['ok'] ? \Filament\Support\Icons\Heroicon::OutlinedCheckCircle : \Filament\Support\Icons\Heroicon::OutlinedExclamationTriangle"
                    @class(['mt-0.5 size-5 shrink-0', 'text-success-600' => $mail['ok'], 'text-warning-600' => ! $mail['ok']])
                />
                <div>
                    <p class="text-sm font-medium text-gray-950 dark:text-white">{{ $mail['heading'] }}</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $mail['description'] }}</p>
                </div>
            </div>
        </x-filament::section>

        {{-- Backups --}}
        <x-filament::section :icon="\Filament\Support\Icons\Heroicon::OutlinedCircleStack" heading="Backups" class="md:col-span-2 xl:col-span-2">
            <x-slot name="afterHeader">
                {{ $this->runBackupAction }}
            </x-slot>
            @forelse ($backups as $backup)
                <div class="{{ $row }}">
                    <span class="{{ $label }}">Disk <span class="font-mono text-xs">{{ $backup['disk'] }}</span></span>
                    @if (! $backup['reachable'])
                        <x-filament::badge color="danger">Unreachable</x-filament::badge>
                    @elseif ($backup['newestAt'])
                        <span class="{{ $value }}">
                            Latest {{ $backup['newestAt']->diffForHumans() }}
                            <span class="font-normal text-gray-500 dark:text-gray-400">
                                · {{ $backup['newestAt']->setTimezone('Asia/Kathmandu')->format('j M Y, H:i') }}
                                · {{ $backup['newestSize'] }}
                                · {{ $backup['count'] }} {{ str('backup')->plural($backup['count']) }}, {{ $backup['usedStorage'] }} in total
                            </span>
                        </span>
                    @else
                        <span class="text-sm text-gray-500 dark:text-gray-400">None yet</span>
                    @endif
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">No backup destinations are configured (config/backup.php).</p>
            @endforelse
        </x-filament::section>
    </div>

    {{-- Recent errors --}}
    <x-filament::section :icon="\Filament\Support\Icons\Heroicon::OutlinedBugAnt" heading="Recent errors" description="The latest error-level entries in storage/logs, newest first.">
        @forelse ($logErrors as $error)
            <details class="group border-t border-gray-950/5 py-2 first:border-t-0 first:pt-0 dark:border-white/10">
                <summary class="flex cursor-pointer list-none items-start gap-3 text-sm">
                    <x-filament::badge size="sm" :color="$error['level'] === 'error' ? 'warning' : 'danger'">{{ $error['level'] }}</x-filament::badge>
                    <span class="min-w-0 flex-1 break-words text-gray-950 dark:text-white">{{ $error['message'] }}</span>
                    <span class="shrink-0 text-xs text-gray-500 dark:text-gray-400" @if ($error['time']) title="{{ $error['time']->format('Y-m-d H:i:s T') }} · {{ $error['file'] }}" @endif>
                        {{ $error['time']?->diffForHumans() ?? $error['file'] }}
                    </span>
                </summary>
                @if ($error['trace'] !== '')
                    <pre class="mt-2 max-h-64 overflow-auto rounded-lg bg-gray-950/5 p-3 font-mono text-xs whitespace-pre-wrap text-gray-700 dark:bg-white/5 dark:text-gray-300">{{ $error['trace'] }}</pre>
                @endif
            </details>
        @empty
            <p class="text-sm text-gray-500 dark:text-gray-400">No errors logged recently. All quiet.</p>
        @endforelse
    </x-filament::section>
</x-filament-panels::page>
