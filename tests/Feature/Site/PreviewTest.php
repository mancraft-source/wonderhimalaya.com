<?php

use App\Support\Preview;
use Database\Factories\PageFactory;
use Database\Factories\PostFactory;
use Database\Factories\TeamMemberFactory;
use Database\Factories\TripFactory;

dataset('unpublished records', [
    'draft trip' => [fn () => TripFactory::new()->draft()->create(['title' => 'Secret Ridge Trek']), 'Secret Ridge Trek'],
    'draft page' => [fn () => PageFactory::new()->draft()->create(['title' => 'Unreleased Guide']), 'Unreleased Guide'],
    'draft post' => [fn () => PostFactory::new()->draft()->create(['title' => 'Upcoming Story']), 'Upcoming Story'],
    'scheduled post' => [fn () => PostFactory::new()->scheduled()->create(['title' => 'Scheduled Story']), 'Scheduled Story'],
    'unpublished team member' => [fn () => TeamMemberFactory::new()->unpublished()->create(['name' => 'New Guide']), 'New Guide'],
]);

it('renders an unpublished record from a signed preview link with the preview bar and noindex', function (Closure $makeRecord, string $heading) {
    $record = $makeRecord();

    $this->get($record->publicPath())->assertNotFound();

    $this->get(Preview::signedUrl($record))
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
        ->assertSee('data-preview-bar', false)
        ->assertSee('Preview — not published yet')
        ->assertSee($heading);
})->with('unpublished records');

it('rejects an unsigned, tampered or expired preview link', function () {
    $draft = TripFactory::new()->draft()->create();
    $other = TripFactory::new()->draft()->create();
    $signed = Preview::signedUrl($draft);

    $this->get(route('preview', ['type' => 'trip', 'id' => $draft->id]))->assertForbidden();
    $this->get(str_replace('/preview/trip/'.$draft->id, '/preview/trip/'.$other->id, $signed))->assertForbidden();

    $this->travel(Preview::VALID_FOR_HOURS + 1)->hours();
    $this->get($signed)->assertForbidden();
});

it('returns not found for a preview of a deleted record', function () {
    $trip = TripFactory::new()->draft()->create();
    $signed = Preview::signedUrl($trip);
    $trip->delete();

    $this->get($signed)->assertNotFound();
});

it('does not show the preview bar on the normal public page', function () {
    $trip = TripFactory::new()->create();

    $this->get($trip->publicPath())->assertOk()->assertDontSee('data-preview-bar', false);
});
