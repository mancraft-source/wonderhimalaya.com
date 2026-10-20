@props(['post', 'large' => false])
<article {{ $attributes->class('group') }}>
    <a href="{{ url($post->publicPath()) }}" class="block overflow-hidden rounded-[1.75rem]">
        <div @class(['overflow-hidden', 'aspect-[16/10]' => $large, 'aspect-[4/3]' => ! $large])>
            <x-site.picture :model="$post" collection="hero" sizes="(min-width: 768px) 33vw, 100vw" class="h-full w-full transition duration-[1.4s] ease-(--ease-mountain) group-hover:scale-[1.04]" />
        </div>
    </a>
    <div class="pt-5">
        <p class="flex items-center gap-3 text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">
            @if ($post->category)<a href="{{ route('blog.category', $post->category->slug) }}" class="text-terracotta-500 hover:underline">{{ $post->category->name }}</a>@endif
            @if ($post->reading_minutes)<span>{{ $post->reading_minutes }} min read</span>@endif
        </p>
        <h3 @class(['mt-3 font-display leading-tight text-juniper-800', 'text-3xl lg:text-4xl' => $large, 'text-2xl' => ! $large])>
            <a href="{{ url($post->publicPath()) }}" class="decoration-terracotta-500/40 decoration-1 underline-offset-4 group-hover:underline">{{ $post->title }}</a>
        </h3>
        @if ($post->excerpt)<p @class(['mt-3 text-ink-soft', 'line-clamp-3' => $large, 'line-clamp-2' => ! $large])>{{ strip_tags($post->excerpt) }}</p>@endif
    </div>
</article>
