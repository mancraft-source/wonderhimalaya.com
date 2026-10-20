<section @class(['py-10' => $narrow, 'container-page py-12' => ! $narrow])>
    <div @class(['relative overflow-hidden rounded-[2.5rem] px-8 py-14 sm:px-14', 'bg-terracotta-500 text-white' => ($data['style'] ?? 'accent') === 'accent', 'bg-juniper-800 text-stone-100' => ($data['style'] ?? 'accent') === 'dark', 'bg-stone-200/70' => ($data['style'] ?? 'accent') === 'light'])>
        <div class="topo pointer-events-none absolute inset-0 opacity-20 invert"></div>
        <div class="relative flex flex-col gap-8 lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-2xl">
                <h2 @class(['font-display text-display-sm', 'text-white' => ($data['style'] ?? 'accent') !== 'light'])>{{ $data['heading'] ?? 'Ready when you are.' }}</h2>
                @if (! empty($data['text']))<p class="mt-4 opacity-85">{{ $data['text'] }}</p>@endif
            </div>
            <a href="{{ $data['button_url'] ?? route('tailor-made') }}" class="btn {{ ($data['style'] ?? 'accent') === 'light' ? 'bg-juniper-800 text-white' : 'bg-white text-juniper-900' }} shrink-0">{{ $data['button_label'] ?? 'Plan your journey' }}</a>
        </div>
    </div>
</section>
