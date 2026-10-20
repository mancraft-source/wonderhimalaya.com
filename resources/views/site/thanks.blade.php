<x-layouts.site :seo="$seo">
    <section class="container-narrow py-32 text-center">
        <x-heroicon-o-check-circle class="mx-auto size-16 text-juniper-500" />
        <h1 class="mt-8 font-display text-display-md">Dhanyabad — thank you.</h1>
        <p class="mt-6 text-lg text-ink-soft">Your message is with our team. We'll reply personally within 24 hours.@if ($reference) Your reference is <span class="data text-juniper-800">{{ $reference }}</span>.@endif</p>
        <a href="{{ route('trips.index') }}" class="btn-dark mt-10">Keep exploring</a>
    </section>
</x-layouts.site>
