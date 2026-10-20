<x-layouts.site :seo="$seo">
    <x-site.page-hero eyebrow="Every journey" title="Find your way into the <em class='text-terracotta-500'>Himalaya</em>"
        intro="Guided treks, peak climbs, heli tours and cultural journeys across Nepal, Tibet and Bhutan — every one adaptable to your dates, pace and comfort."
        :crumbs="[['label' => 'Journeys']]" />
    <section class="container-page pb-10">
        <livewire:trip-finder />
    </section>
</x-layouts.site>
