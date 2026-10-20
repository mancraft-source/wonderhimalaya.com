<x-layouts.site title="Page not found">
    @push('head')<meta name="robots" content="noindex">@endpush
    <section class="grain relative overflow-hidden">
        <div class="topo pointer-events-none absolute inset-0 opacity-80"></div>
        <div class="container-narrow relative py-32 text-center">
            <p class="data text-terracotta-500">410 · retired route</p>
            <h1 class="mt-6 font-display text-display-lg">This journey has been retired.</h1>
            <p class="mt-6 text-lg text-ink-soft">The page you're looking for has moved or no longer exists. Let's get you back on a marked path.</p>
            <div class="mt-10 flex flex-wrap justify-center gap-3">
                <a href="{{ route('home') }}" class="btn-dark">Home</a>
                <a href="{{ route('trips.index') }}" class="btn-outline">Browse journeys</a>
                <a href="{{ route('contact') }}" class="btn-outline">Ask our team</a>
            </div>
        </div>
    </section>
</x-layouts.site>
