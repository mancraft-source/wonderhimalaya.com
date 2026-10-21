<x-layouts.site :seo="$seo">
    <x-site.page-hero eyebrow="Our people" title="The faces behind <em class='text-terracotta-500'>every journey</em>" intro="A small Kathmandu office team and a family of licensed guides — many of them from the very mountain villages you'll walk through." :crumbs="[['label' => 'About', 'url' => url('/about-us')], ['label' => 'Our team']]" />
    @foreach ([['Office & leadership', $leaders], ['Mountain guides', $guides]] as [$heading, $members])
        @if ($members->isNotEmpty())
            <section class="container-page py-16">
                <h2 class="font-display text-display-sm">{{ $heading }}</h2>
                <div class="mt-10 grid grid-cols-2 gap-x-4 gap-y-10 sm:gap-x-8 sm:gap-y-14 lg:grid-cols-4">
                    @foreach ($members as $m)
                        <a href="{{ url($m->publicPath()) }}" class="group" data-reveal>
                            <div class="aspect-[4/5] overflow-hidden rounded-[1.75rem] bg-stone-200">
                                <x-site.picture :model="$m" collection="photo" sizes="(min-width: 1024px) 25vw, 50vw" class="h-full w-full grayscale-[25%] transition duration-700 group-hover:scale-[1.03] group-hover:grayscale-0" />
                            </div>
                            <h3 class="mt-4 font-display text-xl text-juniper-800 group-hover:underline sm:text-2xl">{{ $m->name }}</h3>
                            @if ($m->role)<p class="text-sm text-ink-soft">{{ $m->role }}</p>@endif
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    @endforeach
</x-layouts.site>
