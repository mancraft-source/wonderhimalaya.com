@php $hero = $post->getFirstMedia('hero'); @endphp
<x-layouts.site :seo="$seo">
    <article>
        <header class="container-narrow pt-14 text-center lg:pt-20">
            <x-site.breadcrumbs class="flex justify-center" :items="array_values(array_filter([['label' => 'Journal', 'url' => route('blog.index')], $post->category ? ['label' => $post->category->name, 'url' => route('blog.category', $post->category->slug)] : null, ['label' => str($post->title)->limit(40)->toString()]]))" />
            <h1 class="mt-8 font-display text-display-md">{{ $post->title }}</h1>
            <p class="mt-6 text-sm text-stone-500">
                @if ($post->author)By <a class="font-semibold text-juniper-800 hover:underline" href="{{ url($post->author->publicPath()) }}">{{ $post->author->name }}</a> · @endif
                <time datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->format('j F Y') }}</time>
                @if ($post->reading_minutes) · {{ $post->reading_minutes }} min read @endif
            </p>
        </header>
        @if ($hero)
            <figure class="container-page mt-12">
                <div class="aspect-[16/8] overflow-hidden rounded-[2rem]"><x-site.picture :media="$hero" :alt="$post->title" sizes="100vw" eager class="h-full w-full" /></div>
            </figure>
        @endif
        <div class="container-page mt-14 grid gap-14 lg:grid-cols-12">
            <div class="prose prose-lg prose-wh lg:col-span-8 lg:col-start-2">{!! $post->body !!}</div>
            <aside class="lg:col-span-3">
                <div class="space-y-8 lg:sticky lg:top-32">
                    @if ($post->tags->isNotEmpty())
                        <div><p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">Topics</p><div class="mt-3 flex flex-wrap gap-2">@foreach ($post->tags as $tag)<span class="chip">{{ $tag->name }}</span>@endforeach</div></div>
                    @endif
                    @foreach ($suggestedTrips as $trip)<x-site.trip-card :trip="$trip" :show-price="false" />@endforeach
                </div>
            </aside>
        </div>
    </article>
    @if ($more->isNotEmpty())
        <section class="container-page mt-24 border-t border-stone-300/70 pt-16">
            <h2 class="font-display text-display-sm">Keep reading</h2>
            <div class="mt-10 grid gap-10 md:grid-cols-3">@foreach ($more as $p)<x-site.post-card :post="$p" />@endforeach</div>
        </section>
    @endif
</x-layouts.site>
