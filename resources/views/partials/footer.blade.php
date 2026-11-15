@php
    $phone = $company['phone'] ?? '+977-01-5928066';
    $email = $company['email'] ?? 'info@wonderhimalaya.com';
    $socialIcons = ['facebook' => 'Facebook', 'instagram' => 'Instagram', 'tripadvisor' => 'Tripadvisor', 'tiktok' => 'TikTok', 'youtube' => 'YouTube', 'twitter' => 'X'];
@endphp
<x-site.mountain-divider class="mt-10 sm:mt-16 -mb-px lg:mt-24" />
{{-- Trip pages: leave room for the mobile sticky booking bar so the legal links are reachable. --}}
<footer @class(['relative overflow-hidden bg-navy-900 text-stone-200', 'max-lg:pb-20' => request()->routeIs('trips.show')])>
    <div class="topo pointer-events-none absolute inset-0 opacity-[0.35] invert" aria-hidden="true"></div>

    {{-- Closing call to action (hidden on transactional pages) --}}
    @unless (request()->routeIs('quotes.*', 'pay.*', 'bookings.*', 'thanks'))
    <div class="container-page relative border-b border-white/10 py-14 sm:py-20 lg:py-28">
        <div class="grid items-end gap-10 lg:grid-cols-12">
            <div class="lg:col-span-8">
                <p class="eyebrow eyebrow-light">Begin the conversation</p>
                <h2 class="mt-5 font-display text-display-lg text-stone-100 sm:mt-6">Tell us how you'd like the mountains to <em class="text-saffron-300">feel.</em></h2>
            </div>
            <div class="flex flex-col gap-3 min-[400px]:flex-row min-[400px]:flex-wrap lg:col-span-4 lg:justify-end">
                <a href="{{ route('tailor-made') }}" class="btn-primary">Plan your journey <x-heroicon-m-arrow-right class="size-4" /></a>
                <a href="{{ route('contact') }}" class="btn-ghost-light">Talk to a specialist</a>
            </div>
        </div>
    </div>
    @endunless

    <div class="container-page relative grid grid-cols-2 gap-x-6 gap-y-10 py-12 sm:gap-12 sm:py-16 lg:grid-cols-12">
        <div class="col-span-2 md:col-span-1 lg:col-span-4">
            <x-site.logo light size="lg" />
            <p class="mt-6 max-w-sm text-stone-300">{{ $company['tagline'] ?? 'Wander Always, Wonder Often' }}. A locally owned Kathmandu operator crafting treks, climbs and cultural journeys across Nepal, Tibet and Bhutan.</p>
            <dl class="mt-8 space-y-3 text-sm">
                <div class="flex gap-3"><dt class="sr-only">Address</dt><x-heroicon-o-map-pin class="size-5 shrink-0 text-saffron-300" /><dd>{{ $company['address'] ?? 'Thamel, Kathmandu, Nepal' }}</dd></div>
                <div class="flex gap-3"><dt class="sr-only">Phone</dt><x-heroicon-o-phone class="size-5 shrink-0 text-saffron-300" /><dd><a class="hover:text-white" href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}">{{ $phone }}</a></dd></div>
                <div class="flex gap-3"><dt class="sr-only">Email</dt><x-heroicon-o-envelope class="size-5 shrink-0 text-saffron-300" /><dd><a class="hover:text-white" href="mailto:{{ $email }}">{{ $email }}</a></dd></div>
            </dl>
        </div>

        <div class="min-w-0 lg:col-span-2">
            <h3 class="font-sans text-xs font-semibold uppercase tracking-[0.2em] text-stone-100">Explore</h3>
            <ul class="mt-3 text-sm sm:mt-5 sm:space-y-3">
                @foreach (collect($nav['destinations'])->flatMap(fn ($c) => $c['children'] ?: [$c])->take(7) as $d)
                    <li><a class="block py-2.5 leading-snug hover:text-white sm:inline sm:py-0 sm:leading-relaxed" href="{{ $d['url'] }}">{{ $d['name'] }}</a></li>
                @endforeach
                <li><a class="block py-2.5 leading-snug hover:text-white sm:inline sm:py-0 sm:leading-relaxed" href="{{ route('trips.index') }}">All journeys</a></li>
            </ul>
        </div>

        <div class="min-w-0 lg:col-span-2">
            <h3 class="font-sans text-xs font-semibold uppercase tracking-[0.2em] text-stone-100">Company</h3>
            <ul class="mt-3 text-sm sm:mt-5 sm:space-y-3">
                <li><a class="block py-2.5 leading-snug hover:text-white sm:inline sm:py-0 sm:leading-relaxed" href="{{ url('/about-us') }}">About us</a></li>
                <li><a class="block py-2.5 leading-snug hover:text-white sm:inline sm:py-0 sm:leading-relaxed" href="{{ route('team.index') }}">Our team</a></li>
                <li><a class="block py-2.5 leading-snug hover:text-white sm:inline sm:py-0 sm:leading-relaxed" href="{{ url('/why-wonder-himalaya') }}">Why Wonder Himalaya</a></li>
                <li><a class="block py-2.5 leading-snug hover:text-white sm:inline sm:py-0 sm:leading-relaxed" href="{{ url('/sustainable-tourism') }}">Sustainable tourism</a></li>
                <li><a class="block py-2.5 leading-snug hover:text-white sm:inline sm:py-0 sm:leading-relaxed" href="{{ route('departures') }}">Group departures</a></li>
                <li><a class="block py-2.5 leading-snug hover:text-white sm:inline sm:py-0 sm:leading-relaxed" href="{{ route('b2b') }}">Travel partners (B2B)</a></li>
                <li><a class="block py-2.5 leading-snug hover:text-white sm:inline sm:py-0 sm:leading-relaxed" href="{{ route('reviews') }}">Reviews</a></li>
                <li><a class="block py-2.5 leading-snug hover:text-white sm:inline sm:py-0 sm:leading-relaxed" href="{{ url('/travel-guide') }}">Travel guide</a></li>
                <li><a class="block py-2.5 leading-snug hover:text-white sm:inline sm:py-0 sm:leading-relaxed" href="{{ route('blog.index') }}">Journal</a></li>
                <li><a class="block py-2.5 leading-snug hover:text-white sm:inline sm:py-0 sm:leading-relaxed" href="{{ route('contact') }}">Contact</a></li>
            </ul>
        </div>

        <div class="col-span-2 lg:col-span-4">
            <h3 class="font-sans text-xs font-semibold uppercase tracking-[0.2em] text-stone-100">Letters from the trail</h3>
            <p class="mt-5 text-sm text-stone-300">Seasonal departures, new routes and stories from the field. Four or five emails a year — never more.</p>
            <livewire:newsletter-signup />
            <div class="mt-6 flex flex-wrap gap-2 sm:mt-8">
                @foreach ($social as $network => $link)
                    <a href="{{ $link }}" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center rounded-full border border-white/15 px-4 py-2 text-xs font-medium hover:border-white hover:text-white sm:min-h-0">{{ $socialIcons[$network] ?? ucfirst($network) }}</a>
                @endforeach
            </div>
        </div>
    </div>

    <div class="relative border-t border-white/10">
        <div class="container-page flex flex-col items-center gap-5 py-8 sm:gap-6 sm:py-10 md:flex-row md:justify-between">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-stone-400">Registered & affiliated with</p>
            <ul class="flex flex-wrap items-center justify-center gap-2.5 sm:gap-4">
                @foreach ([['aff-nepal-government.png', 'Government of Nepal'], ['aff-ntb.jpg', 'Nepal Tourism Board'], ['aff-taan.png', 'Trekking Agencies’ Association of Nepal (TAAN)'], ['aff-nma.png', 'Nepal Mountaineering Association (NMA)'], ['aff-keep.webp', 'Kathmandu Environmental Education Project (KEEP)']] as [$file, $label])
                    <li class="flex size-[3.25rem] items-center justify-center rounded-xl bg-white p-1.5 sm:size-16 sm:rounded-2xl sm:p-2"><img src="{{ asset('images/brand/'.$file) }}" alt="{{ $label }}" title="{{ $label }}" loading="lazy" class="max-h-full max-w-full object-contain"></li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="relative border-t border-white/10">
        <div class="container-page flex flex-col gap-3 py-8 text-xs text-stone-400 sm:gap-4 md:flex-row md:items-center md:justify-between">
            <p>© {{ now()->year }} {{ $company['legal_name'] ?? 'Wonder Himalaya' }}. All rights reserved.@if (! empty($company['registration'])) · {{ $company['registration'] }}@endif</p>
            <ul class="flex flex-wrap gap-x-5 sm:gap-x-6 sm:gap-y-2">
                @foreach ($nav['legal'] as $item)
                    <li><a class="inline-block py-2 hover:text-white sm:py-0" href="{{ $item['url'] }}">{{ $item['name'] }}</a></li>
                @endforeach
            </ul>
            <div class="prayer-flags hidden md:flex" aria-hidden="true"><span class="bg-glacier-300"></span><span class="bg-stone-100"></span><span class="bg-terracotta-500"></span><span class="bg-navy-500"></span><span class="bg-saffron-500"></span></div>
        </div>
    </div>
</footer>
