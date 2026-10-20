@php $o = $this->options; $selectBase = 'field !py-2.5 !rounded-full !pr-10 text-sm'; @endphp
<div>
    <div class="sticky top-20 z-30 -mx-5 border-b border-stone-300/60 bg-stone-100/95 px-5 py-4 backdrop-blur-xl sm:-mx-8 sm:px-8 lg:top-24 lg:-mx-12 lg:px-12" x-data="{ more: false }">
        <div class="flex flex-wrap items-center gap-3">
            <label class="relative min-w-56 flex-1 lg:max-w-xs">
                <span class="sr-only">Search journeys</span>
                <x-heroicon-o-magnifying-glass class="pointer-events-none absolute top-1/2 left-4 size-4 -translate-y-1/2 text-stone-400" />
                <input type="search" wire:model.live.debounce.400ms="q" placeholder="Search Everest, Mustang, Kailash…" class="field !rounded-full !py-2.5 !pl-10 text-sm">
            </label>
            <select wire:model.live="destination" class="{{ $selectBase }} w-auto" aria-label="Destination">
                <option value="">All destinations</option>
                @foreach ($o['destinations'] as $d)<option value="{{ $d['slug'] }}">{{ $d['child'] ? '— ' : '' }}{{ $d['name'] }}</option>@endforeach
            </select>
            <select wire:model.live="activity" class="{{ $selectBase }} w-auto" aria-label="Travel style">
                <option value="">All styles</option>
                @foreach ($o['activities'] as $a)<option value="{{ $a['slug'] }}">{{ $a['name'] }}</option>@endforeach
            </select>
            <select wire:model.live="duration" class="{{ $selectBase }} hidden w-auto md:block" aria-label="Duration">
                <option value="">Any length</option>
                @foreach (\App\Livewire\TripFinder::DURATIONS as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach
            </select>
            <button type="button" class="btn-outline !py-2.5 !text-sm" x-on:click="more = !more" x-bind:aria-expanded="more">
                <x-heroicon-o-adjustments-horizontal class="size-4" /> More
                @if ($this->activeCount())<span class="ml-1 rounded-full bg-terracotta-500 px-2 text-xs text-white">{{ $this->activeCount() }}</span>@endif
            </button>
            <div class="ml-auto flex items-center gap-3">
                <select wire:model.live="sort" class="{{ $selectBase }} w-auto" aria-label="Sort">
                    <option value="recommended">Recommended</option>
                    <option value="price-asc">Price: low to high</option>
                    <option value="price-desc">Price: high to low</option>
                    <option value="duration-asc">Shortest first</option>
                    <option value="duration-desc">Longest first</option>
                </select>
            </div>
        </div>
        <div x-show="more" x-collapse x-cloak>
            <div class="flex flex-wrap items-center gap-3 pt-4">
                <select wire:model.live="duration" class="{{ $selectBase }} w-auto md:hidden" aria-label="Duration">
                    <option value="">Any length</option>
                    @foreach (\App\Livewire\TripFinder::DURATIONS as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach
                </select>
                <select wire:model.live="difficulty" class="{{ $selectBase }} w-auto" aria-label="Difficulty">
                    <option value="">Any grade</option>
                    @foreach ($o['difficulties'] as $d)<option value="{{ $d['slug'] }}">{{ $d['name'] }}</option>@endforeach
                </select>
                <select wire:model.live="tier" class="{{ $selectBase }} w-auto" aria-label="Comfort level">
                    <option value="">Any comfort level</option>
                    @foreach ($o['tiers'] as $t)<option value="{{ $t['slug'] }}">{{ $t['name'] }}</option>@endforeach
                </select>
                <label class="inline-flex cursor-pointer items-center gap-2 rounded-full bg-white px-4 py-2.5 text-sm ring-1 ring-stone-300">
                    <input type="checkbox" wire:model.live="fixed" class="rounded border-stone-300 text-juniper-800 focus:ring-juniper-500"> Group departures only
                </label>
                @if ($this->activeCount())
                    <button type="button" wire:click="clear" class="text-sm font-semibold text-terracotta-500 underline underline-offset-4">Clear all</button>
                @endif
            </div>
        </div>
    </div>

    <div class="mt-10 flex items-baseline justify-between">
        <p class="text-sm text-ink-soft" aria-live="polite"><span class="font-display text-2xl text-juniper-800">{{ $this->trips->total() }}</span> {{ str('journey')->plural($this->trips->total()) }}</p>
        <div wire:loading.delay class="text-sm text-stone-500">Updating…</div>
    </div>

    <div class="mt-8 grid gap-x-8 gap-y-14 transition-opacity sm:grid-cols-2 xl:grid-cols-3" wire:loading.class="opacity-50">
        @forelse ($this->trips as $trip)
            <x-site.trip-card :trip="$trip" wire:key="trip-{{ $trip->id }}" />
        @empty
            <div class="col-span-full rounded-[2rem] bg-stone-50 p-12 text-center ring-1 ring-stone-300/60">
                <h3 class="font-display text-3xl">Nothing on the shelf matches — yet.</h3>
                <p class="mx-auto mt-3 max-w-md text-ink-soft">Every journey we run can be tailored. Tell us what you're after and we'll build it around you.</p>
                <div class="mt-8 flex justify-center gap-3">
                    <button type="button" wire:click="clear" class="btn-outline">Clear filters</button>
                    <a href="{{ route('tailor-made') }}" class="btn-primary">Design my trip</a>
                </div>
            </div>
        @endforelse
    </div>

    <div class="mt-16">{{ $this->trips->links() }}</div>
</div>
