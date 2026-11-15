{{-- Profile → Sign-ins & sessions (App\Filament\Pages\Auth\EditProfile). Times are shown in Kathmandu time. --}}
@php
    $timezone = \App\Filament\Pages\Dashboard::TIMEZONE;
    $sessions = $this->activeSessions();
    $otherSessions = $sessions->reject(fn (array $session): bool => $session['is_current'])->count();
@endphp

<div class="grid gap-6">
    <x-filament::section
        :icon="\Filament\Support\Icons\Heroicon::OutlinedFingerPrint"
        heading="Recent sign-ins"
        description="If a time or network here does not look like you, change your password and sign out other devices."
    >
        <dl class="grid gap-4 sm:grid-cols-2">
            @foreach ($this->recentLogins() as $login)
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">{{ $login['label'] }}</dt>
                    <dd class="mt-1 text-sm font-medium text-gray-950 dark:text-white">
                        @if ($login['at'])
                            <span title="{{ $login['at']->copy()->setTimezone($timezone)->format('D j M Y, H:i') }} (Kathmandu)">
                                {{ $login['at']->copy()->setTimezone($timezone)->format('j M Y, H:i') }}
                            </span>
                            <span class="font-normal text-gray-500 dark:text-gray-400">· {{ $login['at']->diffForHumans() }}</span>
                            <span class="mt-0.5 block font-mono text-xs font-normal text-gray-500 dark:text-gray-400">IP {{ $login['ip'] ?? 'unknown' }}</span>
                        @else
                            <span class="font-normal text-gray-500 dark:text-gray-400">Not recorded yet</span>
                        @endif
                    </dd>
                </div>
            @endforeach
        </dl>
    </x-filament::section>

    <x-filament::section
        :icon="\Filament\Support\Icons\Heroicon::OutlinedComputerDesktop"
        heading="Active sessions"
        description="Browsers and devices currently signed in to your account."
    >
        @if ($otherSessions > 0)
            <x-slot name="afterHeader">
                {{ $this->signOutOtherDevicesAction }}
            </x-slot>
        @endif

        @if ($sessions->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">
                @if ($this->usesDatabaseSessions())
                    No sessions found.
                @else
                    Sessions are listed when they are stored in the database (SESSION_DRIVER=database).
                @endif
            </p>
        @else
            <ul class="divide-y divide-gray-950/5 dark:divide-white/10">
                @foreach ($sessions as $session)
                    <li class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 py-3 first:pt-0 last:pb-0">
                        <div class="min-w-0">
                            <p class="flex items-center gap-2 text-sm font-medium text-gray-950 dark:text-white">
                                {{ $session['device'] }}
                                @if ($session['is_current'])
                                    <x-filament::badge color="success" size="sm">This device</x-filament::badge>
                                @endif
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                <span class="font-mono">{{ $session['ip'] ?? 'unknown IP' }}</span>
                                · <span title="{{ $session['last_active']->copy()->setTimezone($timezone)->format('D j M Y, H:i') }} (Kathmandu)">
                                    {{ $session['is_current'] ? 'Active now' : 'Last active '.$session['last_active']->diffForHumans() }}
                                </span>
                            </p>
                        </div>

                        @unless ($session['is_current'])
                            {{ ($this->signOutSessionAction)(['session' => $session['key']]) }}
                        @endunless
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</div>
