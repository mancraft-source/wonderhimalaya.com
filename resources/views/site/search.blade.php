<x-layouts.site :seo="$seo">
    <x-site.page-hero eyebrow="Search" :title="$q !== '' ? 'Results for “'.e($q).'”' : 'What are you looking for?'" :crumbs="[['label' => 'Search']]">
        <form action="{{ route('search') }}" method="get" class="mt-8 flex max-w-2xl gap-2 rounded-full bg-white p-2 ring-1 ring-stone-300" role="search">
            <label for="site-search" class="sr-only">Search</label>
            <input id="site-search" type="search" name="q" value="{{ $q }}" placeholder="Everest, Mustang, visa, packing list…" class="min-w-0 flex-1 rounded-full border-0 bg-transparent px-5 focus:ring-0">
            <button class="btn-primary !py-3">Search</button>
        </form>
    </x-site.page-hero>
    <section class="container-page py-16">
        @if ($q !== '' && $trips->isEmpty() && $posts->isEmpty() && $destinations->isEmpty())
            <div class="rounded-[2rem] bg-stone-200/50 p-10">
                <h2 class="font-display text-3xl">Nothing found for “{{ $q }}”.</h2>
                <p class="mt-3 text-ink-soft">Try a region or a trek name — or tell us what you're dreaming of and we'll design it.</p>
                <a href="{{ route('tailor-made') }}" class="btn-primary mt-6">Plan a tailor-made trip</a>
            </div>
        @endif
        @if ($destinations->isNotEmpty())
            <div class="mb-12 flex flex-wrap gap-3">
                @foreach ($destinations as $d)<a href="{{ url($d->publicPath()) }}" class="chip !px-4 !py-2 !text-sm">{{ $d->name }} →</a>@endforeach
            </div>
        @endif
        @if ($trips->isNotEmpty())
            <h2 class="font-display text-display-sm">Journeys</h2>
            <div class="mt-8 grid gap-x-8 gap-y-14 sm:grid-cols-2 xl:grid-cols-3">@foreach ($trips as $trip)<x-site.trip-card :trip="$trip" />@endforeach</div>
        @endif
        @if ($posts->isNotEmpty())
            <h2 class="mt-20 font-display text-display-sm">From the Journal</h2>
            <div class="mt-8 grid gap-10 md:grid-cols-3">@foreach ($posts as $p)<x-site.post-card :post="$p" />@endforeach</div>
        @endif
    </section>
</x-layouts.site>
