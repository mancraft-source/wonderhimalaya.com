@php $members = \App\Models\TeamMember::where('is_published', true)->when(! empty($data['guides_only']), fn ($q) => $q->where('is_guide', true))->with('media')->orderBy('sort')->get(); @endphp
<section @class(['py-10' => $narrow, 'container-page py-16 lg:py-24' => ! $narrow])>
    @if (! empty($data['heading']))<x-site.section-heading :title="e($data['heading'])" />@endif
    <div class="mt-10 grid grid-cols-2 gap-8 lg:grid-cols-4">
        @foreach ($members as $m)
            <a href="{{ url($m->publicPath()) }}" class="group" data-reveal>
                <div class="aspect-[4/5] overflow-hidden rounded-[1.5rem] bg-stone-200"><x-site.picture :model="$m" collection="photo" sizes="25vw" class="h-full w-full transition duration-700 group-hover:scale-[1.03]" /></div>
                <p class="mt-4 font-display text-xl text-juniper-800">{{ $m->name }}</p><p class="text-sm text-ink-soft">{{ $m->role }}</p>
            </a>
        @endforeach
    </div>
</section>
