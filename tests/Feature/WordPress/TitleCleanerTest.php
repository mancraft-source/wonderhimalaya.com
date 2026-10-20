<?php

use App\Services\WordPress\TitleCleaner;

it('cleans trip titles', function (string $raw, bool $trek, string $title, string $slug) {
    $clean = TitleCleaner::title($raw, $trek);

    expect($clean)->toBe($title)
        ->and(TitleCleaner::slug($clean))->toBe($slug);
})->with([
    ['Everest Base Camp (11N/12D) Fixed Departure', true, 'Everest Base Camp Trek', 'everest-base-camp-trek'],
    ['16 Days Everest Base Camp Trek', true, 'Everest Base Camp Trek', 'everest-base-camp-trek'],
    ['Manaslu Circuit Trek (12N|13D)Fixed Departure', true, 'Manaslu Circuit Trek', 'manaslu-circuit-trek'],
    ['5 Days &#8211; Everest Base Camp Luxury Heli Tour', false, 'Everest Base Camp Luxury Heli Tour', 'everest-base-camp-luxury-heli-tour'],
    ['12 Days – Mardi Himal Trek', true, 'Mardi Himal Trek', 'mardi-himal-trek'],
    ['05 &#8211; Days-Kathmandu Valley Cultural Tour', false, 'Kathmandu Valley Cultural Tour', 'kathmandu-valley-cultural-tour'],
    ['08-Day Nepal Cultural &amp; Nature Discovery Tour', false, 'Nepal Cultural & Nature Discovery Tour', 'nepal-cultural-and-nature-discovery-tour'],
    ["\u{00A0}Yala Peak Climbing &#8211; 15 days", true, 'Yala Peak Climbing', 'yala-peak-climbing'],
    ['Makalu Base Camp Trek-18 Days', true, 'Makalu Base Camp Trek', 'makalu-base-camp-trek'],
    ['Gosaikunda Helambu Trek – 13 Days of Himalayan Spiritual Awakening', true, 'Gosaikunda Helambu Trek', 'gosaikunda-helambu-trek'],
    ['22 Days : Manaslu Circuit Trek and Tsum Valley Trek', true, 'Manaslu Circuit Trek and Tsum Valley Trek', 'manaslu-circuit-trek-and-tsum-valley-trek'],
    ['Bhutan 3/4 Days tour', false, 'Bhutan Tour', 'bhutan-tour'],
    ['Bardiya National Park – 6 Days Jungle Safari', false, 'Bardiya National Park Jungle Safari', 'bardiya-national-park-jungle-safari'],
    ['Annapurna Base Camp &amp; Ghandruk Village Trek (09N/10D)', true, 'Annapurna Base Camp & Ghandruk Village Trek', 'annapurna-base-camp-and-ghandruk-village-trek'],
]);

it('detects fixed departures in titles', function () {
    expect(TitleCleaner::isFixedDeparture('Everest Base Camp (11N/12D) Fixed Departure'))->toBeTrue()
        ->and(TitleCleaner::isFixedDeparture('16 Days Everest Base Camp Trek'))->toBeFalse();
});

it('assigns unique slugs oldest first, using fixed-departure then duration suffixes', function () {
    $slugs = TitleCleaner::uniqueSlugs([
        ['id' => 6664, 'title' => 'Everest Base Camp Trek', 'date' => '2026-01-27', 'fixed' => true, 'days' => 12],
        ['id' => 2817, 'title' => 'Everest Base Camp Trek', 'date' => '2025-02-12', 'fixed' => false, 'days' => 16],
        ['id' => 3382, 'title' => 'Annapurna Circuit Trek', 'date' => '2025-04-10', 'fixed' => false, 'days' => 16],
        ['id' => 3078, 'title' => 'Annapurna Circuit Trek', 'date' => '2025-02-18', 'fixed' => false, 'days' => 22],
        ['id' => 6932, 'title' => 'Annapurna Circuit Trek', 'date' => '2026-03-12', 'fixed' => true, 'days' => 14],
        ['id' => 9, 'title' => 'Annapurna Circuit Trek', 'date' => '2026-04-01', 'fixed' => false, 'days' => 16],
    ]);

    expect($slugs)->toBe([
        2817 => 'everest-base-camp-trek',
        3078 => 'annapurna-circuit-trek',
        3382 => 'annapurna-circuit-trek-16-days',
        6664 => 'everest-base-camp-trek-fixed-departure',
        6932 => 'annapurna-circuit-trek-fixed-departure',
        9 => 'annapurna-circuit-trek-2',
    ]);
});
