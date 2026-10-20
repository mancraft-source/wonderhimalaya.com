<x-filament-panels::page>
    @assets
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js" defer></script>
    @endassets

    <div style="display:flex; gap:.5rem; flex-wrap:wrap; align-items:center;">
        <x-filament::button size="sm" :color="$onlyMine ? 'primary' : 'gray'" wire:click="toggleMine" icon="heroicon-o-user">
            {{ $onlyMine ? 'Showing my leads' : 'Only my leads' }}
        </x-filament::button>
        <x-filament::button size="sm" :color="$showLost ? 'danger' : 'gray'" wire:click="toggleLost" icon="heroicon-o-x-circle">
            {{ $showLost ? 'Hide lost' : 'Show lost' }}
        </x-filament::button>
        <span class="text-sm text-gray-500 dark:text-gray-400">Drag cards between columns to update their stage.</span>
    </div>

    <div
        x-data="{
            init() {
                const boot = () => {
                    if (! window.Sortable) { return setTimeout(boot, 100) }
                    this.$root.querySelectorAll('[data-pipeline-column]').forEach((el) => {
                        if (el._sortable) { return }
                        el._sortable = window.Sortable.create(el, {
                            group: 'leads',
                            animation: 150,
                            draggable: '[data-lead-id]',
                            ghostClass: 'opacity-50',
                            onEnd: (evt) => {
                                const id = parseInt(evt.item.dataset.leadId)
                                const status = evt.to.dataset.status
                                const ids = Array.from(evt.to.querySelectorAll('[data-lead-id]')).map((c) => parseInt(c.dataset.leadId))
                                // Put the card back; Livewire re-renders the board from the server state.
                                const ref = evt.from.children[evt.oldIndex] ?? null
                                evt.from.insertBefore(evt.item, ref)
                                if (evt.from === evt.to && evt.oldIndex === evt.newIndex) { return }
                                $wire.moveLead(id, status, ids)
                            },
                        })
                    })
                }
                boot()
                Livewire.hook('morphed', () => this.$nextTick(boot))
            },
        }"
        style="display:flex; gap:1rem; overflow-x:auto; padding-bottom:1rem; align-items:flex-start;"
    >
        @foreach ($this->getColumns() as $value => $column)
            <div
                wire:key="col-{{ $value }}"
                style="flex:0 0 {{ $column['collapsed'] ? '9rem' : '18rem' }}; max-width:{{ $column['collapsed'] ? '9rem' : '18rem' }};"
                class="rounded-xl bg-gray-50 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10"
            >
                <div style="display:flex; justify-content:space-between; align-items:center; gap:.5rem; padding:.75rem .75rem .5rem;">
                    <x-filament::badge :color="$column['status']->getColor()">
                        {{ $column['status']->getLabel() }}
                    </x-filament::badge>
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                        {{ $column['count'] }}
                        @if ($column['value'] > 0)
                            · ${{ number_format($column['value']) }}
                        @endif
                    </span>
                </div>

                <div
                    data-pipeline-column
                    data-status="{{ $value }}"
                    style="display:flex; flex-direction:column; gap:.5rem; min-height:{{ $column['collapsed'] ? '4rem' : '8rem' }}; padding:0 .5rem .75rem;"
                >
                    @if ($column['collapsed'])
                        <p class="text-xs text-gray-500 dark:text-gray-400" style="padding:.5rem;">Drop here to mark as lost.</p>
                    @endif

                    @foreach ($column['leads'] as $lead)
                        <div
                            wire:key="lead-{{ $lead->id }}"
                            data-lead-id="{{ $lead->id }}"
                            class="rounded-lg bg-white p-3 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
                            style="cursor:grab;"
                        >
                            <div style="display:flex; justify-content:space-between; gap:.5rem; align-items:flex-start;">
                                <a href="{{ $this->leadUrl($lead) }}" wire:navigate class="text-xs font-semibold text-primary-600 hover:underline dark:text-primary-400">
                                    {{ $lead->reference }}
                                </a>
                                @if ($lead->owner)
                                    <span
                                        title="{{ $lead->owner->name }}"
                                        class="bg-primary-50 text-primary-700 dark:bg-primary-500/20 dark:text-primary-300"
                                        style="display:inline-flex; align-items:center; justify-content:center; width:1.5rem; height:1.5rem; border-radius:9999px; font-size:.65rem; font-weight:600;"
                                    >{{ \Illuminate\Support\Str::of($lead->owner->name)->explode(' ')->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('') }}</span>
                                @endif
                            </div>
                            <p class="text-sm font-medium text-gray-950 dark:text-white" style="margin-top:.25rem;">{{ $lead->contact?->name ?: $lead->contact?->email }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $lead->trip?->title ?? $lead->type?->getLabel() }}</p>
                            <div style="display:flex; justify-content:space-between; margin-top:.5rem;" class="text-xs text-gray-500 dark:text-gray-400">
                                <span>{{ $lead->travellerCount() }} pax · {{ $lead->created_at?->diffForHumans(short: true) }}</span>
                                @if ($lead->estimated_value)
                                    <span class="font-medium text-gray-700 dark:text-gray-200">{{ $lead->currency }} {{ number_format((float) $lead->estimated_value) }}</span>
                                @endif
                            </div>
                            @if ($lead->next_follow_up_at)
                                <p class="text-xs {{ $lead->next_follow_up_at->isPast() ? 'text-danger-600 dark:text-danger-400' : 'text-gray-500 dark:text-gray-400' }}" style="margin-top:.25rem;">
                                    Follow up {{ $lead->next_follow_up_at->diffForHumans() }}
                                </p>
                            @endif
                        </div>
                    @endforeach

                    @if (! $column['collapsed'] && $column['count'] > count($column['leads']))
                        <p class="text-xs text-gray-500" style="padding:.25rem .5rem;">+ {{ $column['count'] - count($column['leads']) }} more in the list view</p>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
