<x-layouts.site :seo="$seo">
    <x-site.page-hero :eyebrow="$category ? 'The Journal' : 'Stories & advice'" :title="$category ? e($category->name) : 'The <em class=\'text-terracotta-500\'>Journal</em>'"
        :intro="$category?->description ?: 'Route intel, seasonal advice, culture and stories from the trail — written by our team and guides.'"
        :crumbs="array_values(array_filter([['label' => 'Journal', 'url' => $category ? route('blog.index') : null], $category ? ['label' => $category->name] : null]))">
        <div class="mt-8 flex flex-wrap gap-2">
            <a href="{{ route('blog.index') }}" @class(['rounded-full px-4 py-2 text-sm font-medium ring-1 transition', 'bg-juniper-800 text-stone-100 ring-juniper-800' => ! $category, 'ring-stone-300 hover:ring-juniper-500' => $category])>All</a>
            @foreach ($categories as $cat)
                <a href="{{ route('blog.category', $cat->slug) }}" @class(['rounded-full px-4 py-2 text-sm font-medium ring-1 transition', 'bg-juniper-800 text-stone-100 ring-juniper-800' => $category?->id === $cat->id, 'ring-stone-300 hover:ring-juniper-500' => $category?->id !== $cat->id])>{{ $cat->name }}</a>
            @endforeach
        </div>
    </x-site.page-hero>
    <section class="container-page py-16">
        @if ($posts->isEmpty())
            <p class="text-ink-soft">No stories here yet.</p>
        @else
            @php $first = $posts->onFirstPage() ? $posts->first() : null; @endphp
            @if ($first)
                <x-site.post-card :post="$first" large class="grid items-center gap-10 lg:grid-cols-2 [&>a]:lg:order-none" />
            @endif
            <div class="mt-16 grid gap-x-8 gap-y-14 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    @continue($first && $post->is($first))
                    <x-site.post-card :post="$post" data-reveal />
                @endforeach
            </div>
            <div class="mt-16">{{ $posts->links() }}</div>
        @endif
    </section>
</x-layouts.site>
