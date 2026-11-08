<?php

use App\Enums\LeadType;
use App\Enums\PublishStatus;
use App\Livewire\TripEnquiry;
use App\Models\Lead;
use App\Models\TeamMember;
use App\Models\Trip;
use Livewire\Livewire;

function dailyTrip(): Trip
{
    $trip = Trip::create([
        'title' => 'Mardi Himal Trek', 'slug' => 'mardi-himal-trek-fixed-departure', 'status' => PublishStatus::Published, 'published_at' => now()->subDay(),
        'duration_days' => 8, 'price_from' => 560, 'is_fixed_departure' => true, 'departure_mode' => 'daily', 'departure_cutoff_days' => 10, 'departure_capacity' => 10,
    ]);
    $trip->prices()->create(['label' => 'Group', 'pax_min' => 2, 'price' => 560, 'child_price' => 400, 'description' => '<ul><li>Tea-house lodges</li></ul>', 'is_primary' => true, 'sort' => 1]);

    return $trip;
}

it('lets travellers join a daily-departure group trip on any date after the cut-off', function () {
    $trip = dailyTrip();
    $date = now()->addDays(20)->toDateString();

    Livewire::test(TripEnquiry::class, ['trip' => $trip])
        ->assertSet('mode', 'group')
        ->call('pickDate', $date)
        ->set('first_name', 'Noa')->set('last_name', 'Traveller')->set('email', 'noa@example.com')->set('adults', 2)->set('privacy_accepted', true)
        ->call('submit')
        ->assertHasNoErrors();

    $lead = Lead::first();
    expect($lead->type)->toBe(LeadType::Departure)
        ->and($lead->start_date->toDateString())->toBe($date)
        ->and($lead->trip_departure_id)->toBeNull();
});

it('rejects daily-departure dates inside the booking cut-off', function () {
    Livewire::test(TripEnquiry::class, ['trip' => dailyTrip()])
        ->call('pickDate', now()->addDays(3)->toDateString())
        ->set('first_name', 'Noa')->set('last_name', 'Traveller')->set('email', 'noa@example.com')->set('privacy_accepted', true)
        ->call('submit')
        ->assertHasErrors(['start_date']);
});

it('shows daily departures, package details and child prices like the live site', function () {
    dailyTrip();

    $this->get('/trip/mardi-himal-trek-fixed-departure')->assertOk()
        ->assertSee('Departs every day')
        ->assertSee('What this package includes')
        ->assertSee('Tea-house lodges')
        ->assertSee('Child: US$400')
        ->assertSee('Minimum 2 travellers');

    $this->get('/fixed-departures')->assertOk()->assertSee('Join on any day you like')->assertSee('Mardi Himal Trek');
});

it('shows team highlights and guest quotes', function () {
    TeamMember::create(['name' => 'Suraj Dhakal', 'slug' => 'suraj-dhakal', 'role' => 'Trekking Guide', 'is_guide' => true,
        'highlights' => ['Served 1,000+ clients'], 'praise' => 'Trekkers praise his calm nature.', 'quotes' => ['Best guide we could ask for.']]);

    $this->get('/our-team/suraj-dhakal')->assertOk()
        ->assertSee('Served 1,000+ clients')->assertSee('What people say about Suraj')->assertSee('Best guide we could ask for.');
});
