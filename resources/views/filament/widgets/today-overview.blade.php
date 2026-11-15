@php
    use Filament\Support\Icons\Heroicon;

    $panels = array_filter([
        'followUps' => $followUps ? [
            'heading' => 'Follow-ups due',
            'icon' => Heroicon::OutlinedPhoneArrowUpRight,
            'data' => $followUps,
            'empty' => $seesEveryone ? 'No lead follow-ups due today.' : 'None of your leads need a follow-up today.',
            'more' => 'All leads',
        ] : null,
        'tasks' => $tasks ? [
            'heading' => $seesEveryone ? 'Tasks due' : 'My tasks due',
            'icon' => Heroicon::OutlinedCheckCircle,
            'data' => $tasks,
            'empty' => 'Nothing due today. Enjoy the quiet.',
            'more' => 'All tasks',
        ] : null,
        'departures' => $departures ? [
            'heading' => 'Departing in the next '.\App\Filament\Widgets\TodayOverview::DEPARTURE_DAYS.' days',
            'icon' => Heroicon::OutlinedPaperAirplane,
            'data' => $departures,
            'empty' => 'No departures this week.',
            'more' => 'All departures',
        ] : null,
        'balances' => $balances ? [
            'heading' => 'Balances due',
            'icon' => Heroicon::OutlinedBanknotes,
            'data' => $balances,
            'empty' => 'No balances due in the next '.\App\Filament\Widgets\TodayOverview::BALANCE_DAYS.' days.',
            'more' => 'All bookings',
        ] : null,
    ]);
@endphp

<x-filament-widgets::widget class="wh-today">
    <x-filament::section>
        <x-slot name="heading">
            <span class="font-display">Today</span>
        </x-slot>
        <x-slot name="description">
            {{ $now->format('l j F') }} · what needs your attention
        </x-slot>

        <div @class([
            'grid gap-4 sm:grid-cols-2',
            'xl:grid-cols-4' => count($panels) === 4,
            'xl:grid-cols-3' => count($panels) === 3,
        ])>
            @foreach ($panels as $key => $panel)
                <div class="wh-today-panel flex flex-col rounded-xl border border-gray-950/5 bg-white/60 p-4 dark:border-white/10 dark:bg-white/5" data-panel="{{ $key }}">
                    <div class="mb-3 flex items-center gap-2">
                        <x-filament::icon :icon="$panel['icon']" class="size-5 text-wh-terracotta" />
                        <h3 class="text-sm font-semibold text-wh-navy-900 dark:text-white">{{ $panel['heading'] }}</h3>
                        @if ($panel['data']['count'] > 0)
                            <x-filament::badge
                                class="ms-auto"
                                size="sm"
                                :color="collect($panel['data']['items'])->contains('overdue', true) ? 'danger' : 'warning'"
                            >
                                {{ $panel['data']['count'] }}
                            </x-filament::badge>
                        @endif
                    </div>

                    @forelse ($panel['data']['items'] as $item)
                        <div class="flex items-start gap-3 border-t border-gray-950/5 py-2 first:border-t-0 first:pt-0 dark:border-white/10">
                            <div class="min-w-0 flex-1">
                                @if ($item['url'])
                                    <a href="{{ $item['url'] }}" class="block truncate text-sm font-medium text-gray-950 hover:text-wh-terracotta dark:text-white">{{ $item['title'] }}</a>
                                @else
                                    <p class="truncate text-sm font-medium text-gray-950 dark:text-white">{{ $item['title'] }}</p>
                                @endif
                                @if ($item['meta'])
                                    <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $item['meta'] }}</p>
                                @endif
                                <p @class([
                                    'text-xs',
                                    'font-medium text-danger-600 dark:text-danger-400' => $item['overdue'],
                                    'text-gray-500 dark:text-gray-400' => ! $item['overdue'],
                                ])>{{ $item['overdue'] && ! str_starts_with($item['when'], 'Overdue') ? 'Overdue · ' : '' }}{{ $item['when'] }}</p>
                            </div>

                            @if (($item['canComplete'] ?? false) && isset($item['id']))
                                <x-filament::icon-button
                                    :icon="Heroicon::OutlinedCheck"
                                    color="success"
                                    size="sm"
                                    label="Mark as done"
                                    tooltip="Mark as done"
                                    wire:click="completeTask({{ $item['id'] }})"
                                />
                            @endif
                        </div>
                    @empty
                        <p class="py-2 text-sm text-gray-500 dark:text-gray-400">{{ $panel['empty'] }}</p>
                    @endforelse

                    @if ($panel['data']['count'] > count($panel['data']['items']) && $panel['data']['url'])
                        <div class="mt-auto pt-3">
                            <x-filament::link :href="$panel['data']['url']" size="sm">
                                {{ $panel['more'] }} ({{ $panel['data']['count'] }})
                            </x-filament::link>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
