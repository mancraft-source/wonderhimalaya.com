<?php

use App\Filament\Pages\ManageHomepage;
use App\Filament\Pages\ManageLandingPages;
use App\Filament\Pages\ManageTripPage;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Testimonials\Pages\ListTestimonials;
use App\Filament\Resources\Testimonials\TestimonialResource;
use App\Filament\Resources\Trips\Pages\EditTrip;
use App\Filament\Resources\Trips\RelationManagers\TestimonialsRelationManager;
use App\Filament\Resources\Trips\TripResource;
use App\Models\Setting;
use App\Models\Testimonial;
use App\Models\User;
use App\Support\Content;
use Database\Factories\PageFactory;
use Database\Factories\TestimonialFactory;
use Database\Factories\TripFactory;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create()->assignRole('admin');
    $this->actingAs($this->admin);
});

it('loads the content editor pages', function () {
    $this->get(ManageHomepage::getUrl())->assertOk()->assertSee('Walk among');
    $this->get(ManageTripPage::getUrl())->assertOk()->assertSee('Book with confidence');
});

it('keeps the content editors away from users without the content permission', function () {
    $this->actingAs(User::factory()->create()->assignRole('sales'));

    $this->get(ManageHomepage::getUrl())->assertForbidden();
    $this->get(ManageTripPage::getUrl())->assertForbidden();
});

it('fills the homepage editor with the default copy and saves edits', function () {
    $trip = TripFactory::new()->create();

    Livewire::test(ManageHomepage::class)
        ->assertSchemaStateSet(['hero_title' => 'Walk among', 'why_title' => Content::homeDefaults()['why_title']])
        ->fillForm([
            'hero_title' => 'Walk with',
            'ticker' => ['Everest', 'Annapurna'],
            'feature_trip_id' => $trip->id,
            'signature_trip_ids' => [$trip->id],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $home = Content::home();

    expect(Setting::get('home.hero_title'))->toBe('Walk with')
        ->and($home['hero_title'])->toBe('Walk with')
        ->and($home['ticker'])->toBe(['Everest', 'Annapurna'])
        ->and((int) $home['feature_trip_id'])->toBe($trip->id)
        ->and($home['intro_title'])->toBe(Content::homeDefaults()['intro_title'])
        ->and($home['why_tiles'])->toHaveCount(4);
});

it('resets the homepage to its defaults', function () {
    Setting::put('home', ['hero_title' => 'Custom']);

    Livewire::test(ManageHomepage::class)
        ->assertSchemaStateSet(['hero_title' => 'Custom'])
        ->callAction('resetToDefaults')
        ->assertSchemaStateSet(['hero_title' => 'Walk among']);

    expect(Setting::query()->where('key', 'home')->exists())->toBeFalse()
        ->and(Content::home()['hero_title'])->toBe('Walk among');
});

it('saves the trip page content', function () {
    Livewire::test(ManageTripPage::class)
        ->fillForm([
            'itinerary_note' => 'Plans may change in the mountains.',
            'packing_default' => [['group' => 'Essentials', 'items' => [['item' => 'Passport'], ['item' => 'Sunscreen']]]],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $tripPage = Content::tripPage();

    expect($tripPage['itinerary_note'])->toBe('Plans may change in the mountains.')
        ->and($tripPage['packing_default'])->toBe([['group' => 'Essentials', 'items' => ['Passport', 'Sunscreen']]])
        ->and($tripPage['trust'])->toHaveCount(4);
});

it('saves a trip packing list and itinerary note', function () {
    $trip = TripFactory::new()->create();

    Livewire::test(EditTrip::class, ['record' => $trip->getRouteKey()])
        ->fillForm([
            'itinerary_note' => 'Day 6 may move with the weather.',
            'packing' => [['group' => 'Clothing', 'items' => [['item' => 'Down jacket'], ['item' => 'Fleece']]]],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $trip->refresh();

    expect($trip->itinerary_note)->toBe('Day 6 may move with the weather.')
        ->and($trip->packing)->toBe([['group' => 'Clothing', 'items' => ['Down jacket', 'Fleece']]]);
});

it('copies the default packing list into a trip', function () {
    $trip = TripFactory::new()->create();

    Livewire::test(EditTrip::class, ['record' => $trip->getRouteKey()])
        ->callAction(TestAction::make('copyDefaultPacking')->schemaComponent('packing'))
        ->call('save')
        ->assertHasNoFormErrors();

    expect($trip->refresh()->packing)->toBe(Content::tripPage()['packing_default']);
});

it('rejects a group price row whose pax max is below pax min', function () {
    $trip = TripFactory::new()->create();

    Livewire::test(EditTrip::class, ['record' => $trip->getRouteKey()])
        ->fillForm(['prices' => [['tier_id' => null, 'pax_min' => 6, 'pax_max' => 2, 'price' => 1450]]])
        ->call('save')
        ->assertHasFormErrors();

    expect($trip->prices()->count())->toBe(0);
});

it('renders the trip edit page with the reviews relation manager', function () {
    $trip = TripFactory::new()->create();
    TestimonialFactory::new()->forTrip($trip)->create(['author_name' => 'Ana Traveller']);
    $trip->prices()->create(['pax_min' => 2, 'pax_max' => 5, 'price' => 1585, 'sort' => 1]);

    $this->get(TripResource::getUrl('edit', ['record' => $trip]))
        ->assertOk()
        ->assertSee('Standard · 2–5 pax · US$1,585');

    Livewire::test(TestimonialsRelationManager::class, ['ownerRecord' => $trip, 'pageClass' => EditTrip::class])
        ->assertOk()
        ->assertSee('Ana Traveller');
});

it('creates a trip review from the relation manager', function () {
    $trip = TripFactory::new()->create();

    Livewire::test(TestimonialsRelationManager::class, ['ownerRecord' => $trip, 'pageClass' => EditTrip::class])
        ->callAction(TestAction::make('create')->table(), [
            'author_name' => 'Maya',
            'author_email' => 'maya@example.com',
            'rating' => 5,
            'body' => 'Wonderful trek.',
            'source' => 'website',
            'travelled_on' => '2026-04-10',
            'is_published' => false,
        ])
        ->assertHasNoFormErrors();

    $review = Testimonial::query()->firstOrFail();

    expect($review->trip_id)->toBe($trip->id)
        ->and($review->author_email)->toBe('maya@example.com')
        ->and($review->travelled_on->toDateString())->toBe('2026-04-10')
        ->and($review->is_published)->toBeFalse();
});

it('approves pending testimonials and badges them in the navigation', function () {
    $pending = TestimonialFactory::new()->unpublished()->count(2)->create();
    TestimonialFactory::new()->create();

    expect(TestimonialResource::getNavigationBadge())->toBe('2');

    Livewire::test(ListTestimonials::class, ['activeTab' => 'pending'])
        ->assertCanSeeTableRecords($pending)
        ->selectTableRecords($pending)
        ->callAction(TestAction::make('approve')->table()->bulk());

    expect(Testimonial::query()->where('is_published', false)->count())->toBe(0)
        ->and(TestimonialResource::getNavigationBadge())->toBeNull();
});

it('edits the copy of a designed page template', function () {
    $page = PageFactory::new()->template('about')->create(['title' => 'About us', 'slug' => 'about-us']);

    $this->get(PageResource::getUrl('edit', ['record' => $page]))->assertOk()->assertSee('Template content');

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->assertSchemaStateSet(['content.hero_title' => Content::pageDefaults('about')['hero_title']])
        ->fillForm(['content.hero_title' => 'Six friends, one love:'])
        ->call('save')
        ->assertHasNoFormErrors();

    $page->refresh();

    expect($page->content['hero_title'])->toBe('Six friends, one love:')
        ->and(Content::page($page)['hero_title'])->toBe('Six friends, one love:')
        ->and($page->content['values'])->toHaveCount(4);
});

it('prefills designed template copy when the template is chosen on create', function () {
    Livewire::test(CreatePage::class)
        ->fillForm(['template' => 'why'])
        ->assertSchemaStateSet(['content.lead_text' => Content::pageDefaults('why')['lead_text']]);
});

it('edits and resets the landing page copy', function () {
    Setting::put('b2b', ['credentials' => ['TAAN member'], 'imported_flag' => true]);

    $this->get(ManageLandingPages::getUrl())->assertOk()->assertSee('Your local partner');

    Livewire::test(ManageLandingPages::class)
        ->assertSchemaStateSet(['b2b.credentials' => ['TAAN member'], 'contact.map_query' => 'Thamel, Kathmandu, Nepal'])
        ->fillForm([
            'b2b.hero_title' => 'Your partner on the ground',
            'tailor_made.title' => 'Made for you.',
            'contact.map_query' => 'Wonder Himalaya, Thamel',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Content::landing('b2b')['hero_title'])->toBe('Your partner on the ground')
        ->and(Content::landing('b2b')['services'])->toHaveCount(4)
        ->and(Setting::get('b2b.imported_flag'))->toBeTrue()
        ->and(Content::landing('tailor_made')['title'])->toBe('Made for you.')
        ->and(Content::landing('contact')['map_query'])->toBe('Wonder Himalaya, Thamel');

    Livewire::test(ManageLandingPages::class)
        ->callAction('resetToDefaults')
        ->assertSchemaStateSet(['b2b.hero_title' => 'Your local partner']);

    expect(Content::landing('contact')['map_query'])->toBe('Thamel, Kathmandu, Nepal')
        ->and(Setting::get('b2b'))->toBe(['imported_flag' => true])
        ->and(Setting::query()->where('key', 'contact')->exists())->toBeFalse();
});
