<section @class(['py-10' => $narrow, 'container-narrow py-16' => ! $narrow])>
    @if (! empty($data['heading']))<h2 class="font-display text-display-sm">{{ $data['heading'] }}</h2>@endif
    @if (! empty($data['intro']))<p class="mt-4 text-ink-soft">{{ $data['intro'] }}</p>@endif
    <div class="mt-8"><livewire:contact-form /></div>
</section>
