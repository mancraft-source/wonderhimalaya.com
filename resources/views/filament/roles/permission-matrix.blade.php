{{-- Roles & permissions → section matrix: one row per admin section, View / Create / Edit / Delete columns. --}}
@php
    $statePath = $getStatePath();
    $disabled = $isDisabled();
    $permissionsFor = fn (array $sections, ?string $ability = null): array => collect($sections)
        ->flatMap(fn (string $section): array => collect($sectionAbilities[$section])
            ->when($ability, fn ($abilities) => $abilities->filter(fn (string $candidate): bool => $candidate === $ability))
            ->map(fn (string $candidate): string => $section.'.'.$candidate)
            ->all())
        ->values()
        ->all();
    $allSections = collect($groups)->flatMap(fn (array $sections): array => array_keys($sections))->all();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{
            state: $wire.$entangle(@js($statePath)),
            grantable: @js($grantable),
            disabled: @js($disabled),
            has(permission) { return (this.state ?? []).includes(permission) },
            all(permissions) { return permissions.length > 0 && permissions.every((permission) => this.has(permission)) },
            some(permissions) { return permissions.some((permission) => this.has(permission)) },
            set(permissions, on) {
                if (this.disabled) return
                const next = new Set(this.state ?? [])
                permissions.filter((permission) => this.grantable.includes(permission))
                    .forEach((permission) => on ? next.add(permission) : next.delete(permission))
                if (on) {
                    // Create, edit or delete without view makes no sense: add view too.
                    permissions.forEach((permission) => {
                        const section = permission.split('.')[0]
                        if (this.grantable.includes(section + '.view')) next.add(section + '.view')
                    })
                } else {
                    // Removing view removes everything else in that section.
                    permissions.filter((permission) => permission.endsWith('.view')).forEach((permission) => {
                        const section = permission.split('.')[0]
                        ;['create', 'update', 'delete'].forEach((ability) => next.delete(section + '.' + ability))
                    })
                }
                this.state = Array.from(next)
            },
            toggle(permissions) { this.set(permissions, ! this.all(permissions)) },
        }"
        class="overflow-x-auto rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10"
    >
        <table class="w-full min-w-[34rem] text-sm">
            <thead class="bg-gray-50 dark:bg-white/5">
                <tr>
                    <th scope="col" class="px-4 py-2.5 text-start font-medium text-gray-950 dark:text-white">Section</th>
                    @foreach ($abilities as $ability => $label)
                        <th scope="col" class="w-20 px-2 py-2.5 text-center">
                            <button
                                type="button"
                                x-on:click="toggle(@js($permissionsFor($allSections, $ability)))"
                                x-bind:disabled="disabled"
                                class="rounded-md px-1.5 py-0.5 text-xs font-semibold text-gray-700 hover:bg-gray-950/5 hover:text-primary-600 disabled:pointer-events-none dark:text-gray-200 dark:hover:bg-white/10 dark:hover:text-primary-400"
                                title="Select or clear {{ strtolower($label) }} for every section"
                            >{{ $label }}</button>
                        </th>
                    @endforeach
                    <th scope="col" class="w-16 px-2 py-2.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-400">All</th>
                </tr>
            </thead>

            @foreach ($groups as $group => $sections)
                @php($groupPermissions = $permissionsFor(array_keys($sections)))
                <tbody class="divide-y divide-gray-950/5 border-t border-gray-950/5 dark:divide-white/5 dark:border-white/10">
                    <tr class="bg-gray-50/60 dark:bg-white/[0.03]">
                        <th scope="rowgroup" colspan="{{ count($abilities) + 2 }}" class="px-4 py-1.5 text-start">
                            <div class="flex items-center gap-3">
                                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $group }}</span>
                                <button
                                    type="button"
                                    x-show="! disabled"
                                    x-on:click="toggle(@js($groupPermissions))"
                                    x-text="all(@js($groupPermissions)) ? 'Clear group' : 'Select group'"
                                    class="text-xs font-medium text-primary-600 hover:underline dark:text-primary-400"
                                ></button>
                            </div>
                        </th>
                    </tr>

                    @foreach ($sections as $section => $sectionLabel)
                        @php($rowPermissions = $permissionsFor([$section]))
                        <tr class="hover:bg-gray-50/70 dark:hover:bg-white/[0.02]" x-bind:class="some(@js($rowPermissions)) && 'bg-primary-50/40 dark:bg-primary-400/5'">
                            <th scope="row" class="px-4 py-2 text-start font-normal text-gray-950 dark:text-white">{{ $sectionLabel }}</th>
                            @foreach ($abilities as $ability => $label)
                                @php($permission = $section.'.'.$ability)
                                <td class="px-2 py-2 text-center">
                                    @if (in_array($ability, $sectionAbilities[$section], true))
                                        <input
                                            type="checkbox"
                                            class="fi-checkbox-input"
                                            aria-label="{{ $sectionLabel }}: {{ $label }}"
                                            data-permission="{{ $permission }}"
                                            x-bind:checked="has(@js($permission))"
                                            x-on:change="set([@js($permission)], $event.target.checked)"
                                            @disabled($disabled || ! in_array($permission, $grantable, true))
                                        />
                                    @else
                                        <span class="text-gray-300 dark:text-gray-600" aria-hidden="true">—</span>
                                    @endif
                                </td>
                            @endforeach
                            <td class="px-2 py-2 text-center">
                                <input
                                    type="checkbox"
                                    class="fi-checkbox-input"
                                    aria-label="{{ $sectionLabel }}: all"
                                    x-bind:checked="all(@js($rowPermissions))"
                                    x-on:change="toggle(@js($rowPermissions))"
                                    @disabled($disabled)
                                />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            @endforeach
        </table>
    </div>
</x-dynamic-component>
