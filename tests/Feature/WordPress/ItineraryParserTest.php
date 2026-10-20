<?php

use App\Services\WordPress\HtmlCleaner;
use App\Services\WordPress\ItineraryParser;

it('parses altitude in metres from titles', function (string $text, ?int $expected) {
    expect(ItineraryParser::altitude($text))->toBe($expected);
})->with([
    ['Fly from Kathmandu to Lukla (2860m/9383ft) and Trek to Phakding (2652m)', 2860],
    ['Phakding (2,610m/8,562ft) - Namche (3440 m/11,285ft):', 3440],
    ['Tatopani to Ghorepani (2,860)', 2860],
    ['5,364 meters (Everest Base Camp)', 5364],
    ['6,419m/21,058ft. Max', 6419],
    ['Full day Kathmandu City Tour', null],
]);

it('parses distance and duration', function () {
    expect(ItineraryParser::distance('Trek to Dharepani (1,860m/6,102ft). Distance: 12–13 km, Duration: 5–6 hrs'))->toBe(13.0)
        ->and(ItineraryParser::distance('Distance: 8 km'))->toBe(8.0)
        ->and(ItineraryParser::distance('Acclimatization Day'))->toBeNull()
        ->and(ItineraryParser::duration('Distance: 8 km, Duration: 3-4 hrs'))->toBe('3-4 hrs')
        ->and(ItineraryParser::duration('about 5–6 walking hours'))->toBe('5–6 hrs');
});

it('cleans day titles', function () {
    expect(ItineraryParser::cleanTitle('Day 19: Tatopani to Ghorepani (2,860). Distance: 15–16 km, Duration: 6–7 hrs.'))
        ->toBe('Tatopani to Ghorepani (2,860)')
        ->and(ItineraryParser::cleanTitle('Summit Chulu West (6,419m) and Return to Base Camp. 10–12 km, Duration: 8–10 hrs'))
        ->toBe('Summit Chulu West (6,419m) and Return to Base Camp');
});

it('parses a full WTE itinerary day and lifts the facts table out of the description', function () {
    $day = (new ItineraryParser(new HtmlCleaner))->parse([
        'title' => 'Fly from Kathmandu to Lukla (2860m/9383ft) and Trek to Phakding (2652m). Distance: 8 km, Duration: 3-4 hrs',
        'content' => '<!-- wp:table --><figure class="wp-block-table"><table class="has-fixed-layout"><tbody><tr><td>Accommodation:</td><td>Tea House</td></tr><tr><td>Meals:</td><td>Breakfast, Lunch &amp; Dinner</td></tr><tr><td>Notes</td><td>-</td></tr></tbody></table></figure><!-- /wp:table --><!-- wp:paragraph --><p>Today we fly to Lukla.</p><!-- /wp:paragraph -->',
        'meals_included' => [],
        'overnights' => [['location' => '', 'altitude' => 0]],
    ]);

    expect($day['title'])->toBe('Fly from Kathmandu to Lukla (2860m/9383ft) and Trek to Phakding (2652m)')
        ->and($day['altitude_m'])->toBe(2860)
        ->and($day['distance_km'])->toBe(8.0)
        ->and($day['duration'])->toBe('3-4 hrs')
        ->and($day['meals'])->toBe(['Breakfast', 'Lunch', 'Dinner'])
        ->and($day['accommodation'])->toBe('Tea House')
        ->and($day['description'])->toBe('<p>Today we fly to Lukla.</p>');
});
