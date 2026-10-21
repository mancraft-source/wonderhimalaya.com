<x-layouts.site :seo="$seo">
    <section class="container-page grid gap-14 py-14 lg:grid-cols-12 lg:py-20">
        <div class="lg:col-span-5">
            <div class="aspect-[4/5] overflow-hidden img-organic bg-stone-200 lg:sticky lg:top-32">
                <x-site.picture :model="$member" collection="photo" sizes="(min-width: 1024px) 40vw, 100vw" eager class="h-full w-full" />
            </div>
        </div>
        <div class="lg:col-span-7">
            <x-site.breadcrumbs :items="[['label' => 'Our team', 'url' => route('team.index')], ['label' => $member->name]]" />
            @if ($member->role)<p class="eyebrow mt-8">{{ $member->role }}</p>@endif
            <h1 class="mt-5 font-display text-display-lg">{{ $member->name }}</h1>
            <dl class="mt-8 flex flex-wrap gap-3">
                @if ($member->experience_years)<div class="chip"><dt class="sr-only">Experience</dt><dd>{{ $member->experience_years }}+ years in the mountains</dd></div>@endif
                @foreach ($member->languages ?? [] as $lang)<div class="chip"><dd>{{ $lang }}</dd></div>@endforeach
                @foreach ($member->certifications ?? [] as $cert)<div class="chip"><dd>{{ $cert }}</dd></div>@endforeach
            </dl>
            @if ($member->highlights)
                <ul class="mt-10 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach ($member->highlights as $h)
                        <li class="rounded-2xl bg-juniper-800 px-4 py-5 text-center text-sm font-semibold text-stone-100"><x-heroicon-s-check-badge class="mx-auto mb-2 size-5 text-saffron-300" />{{ $h }}</li>
                    @endforeach
                </ul>
            @endif
            @if ($member->bio)<div class="prose prose-lg prose-wh mt-10">{!! $member->bio !!}</div>@endif
            @if ($member->praise || $member->quotes)
                <section class="mt-14">
                    <p class="eyebrow">Guest stories</p>
                    <h2 class="mt-4 font-display text-display-sm">What people say about {{ str($member->name)->before(' ') }}</h2>
                    @if ($member->praise)<p class="mt-5 text-lg text-ink-soft">{{ $member->praise }}</p>@endif
                    @if ($member->quotes)
                        <div class="mt-8 grid gap-4">
                            @foreach ($member->quotes as $quote)
                                <figure class="rounded-[1.75rem] bg-stone-50 p-7 ring-1 ring-stone-300/60">
                                    <div class="flex gap-0.5 text-saffron-500">@for ($i = 0; $i < 5; $i++)<x-heroicon-s-star class="size-4" />@endfor</div>
                                    <blockquote class="mt-3 text-ink-soft">“{{ $quote }}”</blockquote>
                                </figure>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endif
            <a href="{{ route('tailor-made') }}" class="btn-primary mt-12">Plan a trip with our team</a>
        </div>
    </section>
    @if ($others->isNotEmpty())
        <section class="container-page py-16">
            <h2 class="font-display text-display-sm">More of the team</h2>
            <div class="mt-10 grid grid-cols-2 gap-8 lg:grid-cols-4">
                @foreach ($others as $m)
                    <a href="{{ url($m->publicPath()) }}" class="group">
                        <div class="aspect-square overflow-hidden rounded-[1.5rem] bg-stone-200"><x-site.picture :model="$m" collection="photo" sizes="25vw" class="h-full w-full transition duration-700 group-hover:scale-[1.03]" /></div>
                        <p class="mt-4 font-display text-xl text-juniper-800">{{ $m->name }}</p>
                        <p class="text-sm text-ink-soft">{{ $m->role }}</p>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</x-layouts.site>
