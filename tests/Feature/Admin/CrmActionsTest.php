<?php

use App\Enums\BookingStatus;
use App\Enums\LeadStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\PublishStatus;
use App\Enums\QuoteStatus;
use App\Filament\Pages\LeadPipeline;
use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\Bookings\Pages\EditBooking;
use App\Filament\Resources\Bookings\RelationManagers\TravellersRelationManager;
use App\Filament\Resources\Contacts\Pages\EditContact;
use App\Filament\Resources\CrmTasks\Pages\ListCrmTasks;
use App\Filament\Resources\Leads\Pages\ViewLead;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Filament\Resources\Payments\PaymentResource;
use App\Filament\Resources\Quotes\Pages\CreateQuote;
use App\Filament\Resources\Quotes\Pages\EditQuote;
use App\Filament\Resources\Trips\Pages\CreateTrip;
use App\Filament\Resources\Trips\Pages\ListTrips;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\UserResource;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\Setting;
use App\Models\Trip;
use App\Models\User;
use Database\Factories\BookingFactory;
use Database\Factories\ContactFactory;
use Database\Factories\CrmTaskFactory;
use Database\Factories\DestinationFactory;
use Database\Factories\LeadFactory;
use Database\Factories\PaymentFactory;
use Database\Factories\QuoteFactory;
use Database\Factories\TierFactory;
use Database\Factories\TravellerFactory;
use Database\Factories\TripDepartureFactory;
use Database\Factories\TripFactory;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('creates a priced quote from a lead', function () {
    Setting::put('booking', ['deposit_percent' => 25, 'currency' => 'USD']);
    $trip = TripFactory::new()->create(['price_from' => 1500, 'sale_price' => 1290, 'currency' => 'USD']);
    $lead = LeadFactory::new()->forTrip($trip)->create(['adults' => 2, 'children' => 1]);

    Livewire::test(ViewLead::class, ['record' => $lead->getRouteKey()])
        ->callAction('create_quote')
        ->assertHasNoActionErrors()
        ->assertRedirect();

    $quote = Quote::with('items')->sole();

    expect($quote->lead_id)->toBe($lead->id)
        ->and($quote->contact_id)->toBe($lead->contact_id)
        ->and($quote->title)->toBe($trip->title)
        ->and($quote->pax)->toBe(3)
        ->and($quote->deposit_percent)->toBe(25)
        ->and($quote->valid_until->toDateString())->toBe(now()->addDays(14)->toDateString())
        ->and($quote->items)->toHaveCount(1)
        ->and((float) $quote->items->first()->unit_price)->toBe(1290.0)
        ->and((float) $quote->subtotal)->toBe(3870.0)
        ->and((float) $quote->total)->toBe(3870.0)
        ->and((float) $quote->deposit_amount)->toBe(967.5)
        ->and($lead->fresh()->status)->toBe(LeadStatus::Quoted);
});

it('prices a quote from the departure and names tailor-made quotes', function () {
    $departure = TripDepartureFactory::new()->create(['price' => 2000, 'sale_price' => 1800]);
    $lead = LeadFactory::new()->create(['trip_id' => $departure->trip_id, 'trip_departure_id' => $departure->id, 'adults' => 2, 'children' => 0]);

    Livewire::test(ViewLead::class, ['record' => $lead->getRouteKey()])->callAction('create_quote');

    $quote = Quote::sole();
    expect((float) $quote->total)->toBe(3600.0)
        ->and((float) $quote->deposit_amount)->toBe(720.0)
        ->and($quote->start_date->toDateString())->toBe($departure->start_date->toDateString());

    $tailor = LeadFactory::new()->tailorMade()->create();
    Livewire::test(ViewLead::class, ['record' => $tailor->getRouteKey()])->callAction('create_quote');

    expect(Quote::where('lead_id', $tailor->id)->sole()->title)->toBe('Tailor-made trip');
});

it('changes lead status and requires a reason when lost', function () {
    $lead = LeadFactory::new()->create();

    Livewire::test(ViewLead::class, ['record' => $lead->getRouteKey()])
        ->callAction('change_status', data: ['status' => LeadStatus::Lost->value, 'lost_reason' => null])
        ->assertHasActionErrors(['lost_reason' => 'required']);

    Livewire::test(ViewLead::class, ['record' => $lead->getRouteKey()])
        ->callAction('change_status', data: ['status' => LeadStatus::Lost->value, 'lost_reason' => 'Went with a competitor'])
        ->assertHasNoActionErrors();

    expect($lead->fresh())
        ->status->toBe(LeadStatus::Lost)
        ->lost_reason->toBe('Went with a competitor');
});

it('assigns a lead to the current user', function () {
    $lead = LeadFactory::new()->create(['owner_id' => null]);

    Livewire::test(ViewLead::class, ['record' => $lead->getRouteKey()])->callAction('assign_to_me');

    expect($lead->fresh()->owner_id)->toBe($this->admin->id);
});

it('moves leads on the pipeline board and stores the order', function () {
    $a = LeadFactory::new()->status(LeadStatus::Qualified)->create();
    $b = LeadFactory::new()->create();

    Livewire::test(LeadPipeline::class)
        ->call('moveLead', $b->id, LeadStatus::Qualified->value, [$b->id, $a->id])
        ->assertOk();

    expect($b->fresh())->status->toBe(LeadStatus::Qualified)->sort->toBe(0)
        ->and($a->fresh()->sort)->toBe(1);
});

it('recalculates quote totals on save and ignores posted totals', function () {
    $contact = ContactFactory::new()->create();

    Livewire::test(CreateQuote::class)
        ->fillForm([
            'title' => 'Annapurna Base Camp',
            'contact_id' => $contact->id,
            'pax' => 2,
            'currency' => 'USD',
            'discount' => 100,
            'tax' => 0,
            'deposit_percent' => 20,
            'items' => [
                ['description' => 'Trek per person', 'quantity' => 2, 'unit_price' => 1000],
                ['description' => 'Single supplement', 'quantity' => 1, 'unit_price' => 150],
            ],
        ])
        ->set('data.total', 1)
        ->call('create')
        ->assertHasNoFormErrors();

    $quote = Quote::sole();
    expect((float) $quote->subtotal)->toBe(2150.0)
        ->and((float) $quote->total)->toBe(2050.0)
        ->and((float) $quote->deposit_amount)->toBe(410.0)
        ->and($quote->created_by_id)->toBe($this->admin->id);

    Livewire::test(EditQuote::class, ['record' => $quote->getRouteKey()])
        ->fillForm(['discount' => 0, 'deposit_percent' => 50])
        ->call('save')
        ->assertHasNoFormErrors();

    expect((float) $quote->fresh()->total)->toBe(2150.0)
        ->and((float) $quote->fresh()->deposit_amount)->toBe(1075.0);
});

it('marks a quote as sent', function () {
    $quote = QuoteFactory::new()->withItems()->create();

    Livewire::test(EditQuote::class, ['record' => $quote->getRouteKey()])->callAction('mark_as_sent');

    expect($quote->fresh())->status->toBe(QuoteStatus::Sent)->sent_at->not->toBeNull();
});

it('marks a payment as paid manually and syncs the booking', function () {
    $booking = BookingFactory::new()->create(['total' => 3000, 'deposit_amount' => 600, 'amount_paid' => 0]);
    $payment = PaymentFactory::new()->forBooking($booking)->create(['amount' => 600]);

    Livewire::test(ListPayments::class)
        ->callAction(TestAction::make('mark_as_paid')->table($payment), data: [
            'amount' => 600,
            'method' => 'bank_transfer',
            'reference' => 'NIC-12345',
            'paid_at' => now()->toDateTimeString(),
        ])
        ->assertHasNoActionErrors();

    expect($payment->fresh())
        ->status->toBe(PaymentStatus::Paid)
        ->gateway->toBe('manual')
        ->gateway_reference->toBe('NIC-12345')
        ->and($booking->fresh())
        ->amount_paid->toEqual('600.00')
        ->status->toBe(BookingStatus::Confirmed)
        ->confirmed_at->not->toBeNull();
});

it('does not let content editors mark payments as paid', function () {
    $payment = PaymentFactory::new()->create();
    $this->actingAs(User::factory()->role('content')->create());

    $this->get(PaymentResource::getUrl('index'))->assertForbidden();
    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending);
});

it('creates a payment link for a booking balance', function () {
    $booking = BookingFactory::new()->create(['total' => 3000, 'deposit_amount' => 600, 'amount_paid' => 600]);

    Livewire::test(EditBooking::class, ['record' => $booking->getRouteKey()])
        ->callAction('create_payment_link', data: [
            'type' => PaymentType::Balance->value,
            'amount' => 2400,
            'gateway' => 'paypal',
            'expires_in_days' => 10,
        ])
        ->assertHasNoActionErrors()
        ->assertActionMounted('paymentLink');

    $payment = Payment::sole();
    expect($payment)
        ->status->toBe(PaymentStatus::Pending)
        ->type->toBe(PaymentType::Balance)
        ->gateway->toBe('paypal')
        ->contact_id->toBe($booking->contact_id)
        ->created_by_id->toBe($this->admin->id)
        ->and((float) $payment->amount)->toBe(2400.0)
        ->and($payment->payable->is($booking))->toBeTrue()
        ->and($payment->expires_at->isFuture())->toBeTrue();
});

it('hides traveller passport fields from staff without payment rights', function () {
    $booking = BookingFactory::new()->create();
    TravellerFactory::new()->create(['booking_id' => $booking->id, 'passport_number' => 'X1234567']);

    $this->get(BookingResource::getUrl('edit', ['record' => $booking]))
        ->assertOk();

    expect(TravellersRelationManager::canSeeSensitive())->toBeTrue();

    $this->actingAs(User::factory()->role('content')->create());
    expect(TravellersRelationManager::canSeeSensitive())->toBeFalse();
});

it('duplicates a trip with its itinerary, faqs and prices', function () {
    $trip = TripFactory::new()->create(['title' => 'Mardi Himal Trek', 'slug' => 'mardi-himal-trek']);
    $trip->days()->create(['day_number' => 1, 'title' => 'Drive to Pokhara', 'meals' => ['lunch', 'dinner']]);
    $trip->faqs()->create(['question' => 'Is it hard?', 'answer' => 'Moderate']);
    $trip->prices()->create(['pax_min' => 1, 'price' => 999]);

    Livewire::test(ListTrips::class)
        ->callAction(TestAction::make('replicate')->table($trip))
        ->assertHasNoActionErrors();

    $copy = Trip::where('id', '!=', $trip->id)->sole();
    expect($copy)
        ->title->toBe('Mardi Himal Trek (copy)')
        ->slug->toBe('mardi-himal-trek-copy')
        ->status->toBe(PublishStatus::Draft)
        ->and($copy->days()->first()->meals)->toBe(['lunch', 'dinner'])
        ->and($copy->faqs()->count())->toBe(1)
        ->and($copy->prices()->count())->toBe(1);
});

it('completes a task from the task list', function () {
    $task = CrmTaskFactory::new()->assignedTo($this->admin)->create();

    Livewire::test(ListCrmTasks::class)
        ->assertCanSeeTableRecords([$task])
        ->callAction(TestAction::make('toggle_complete')->table($task));

    expect($task->fresh()->completed_at)->not->toBeNull();
});

it('exports and anonymises a contact', function () {
    $contact = ContactFactory::new()->create(['email' => 'jane@example.com', 'phone' => '+44 7700 900000']);
    LeadFactory::new()->create(['contact_id' => $contact->id, 'message' => 'Secret plans', 'ip_address' => '1.2.3.4']);
    $booking = BookingFactory::new()->create(['contact_id' => $contact->id]);
    TravellerFactory::new()->create(['booking_id' => $booking->id, 'passport_number' => 'P999']);

    Livewire::test(EditContact::class, ['record' => $contact->getRouteKey()])
        ->callAction('export_data')
        ->assertFileDownloaded('contact-'.$contact->id.'-data.json');

    Livewire::test(EditContact::class, ['record' => $contact->getRouteKey()])
        ->callAction('anonymise')
        ->assertHasNoActionErrors();

    $contact->refresh();
    expect($contact->email)->not->toContain('jane')
        ->and($contact->phone)->toBeNull()
        ->and($contact->leads()->first()->message)->toBeNull()
        ->and($booking->travellers()->first()->passport_number)->toBeNull()
        ->and($booking->fresh())->not->toBeNull();
});

it('only lets admins anonymise contacts', function () {
    $contact = ContactFactory::new()->create();
    $this->actingAs(User::factory()->manager()->create());

    Livewire::test(EditContact::class, ['record' => $contact->getRouteKey()])
        ->assertActionHidden('anonymise');
});

it('creates an API token and shows it once', function () {
    $user = User::factory()->sales()->create();

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->callAction('create_api_token', data: ['name' => 'Website', 'abilities' => ['read', 'leads:write'], 'expires_in' => '30'])
        ->assertHasNoActionErrors()
        ->assertActionMounted('apiTokenCreated');

    $token = $user->tokens()->sole();
    expect($token->name)->toBe('Website')
        ->and($token->abilities)->toBe(['read', 'leads:write'])
        ->and($token->expires_at->isFuture())->toBeTrue();
});

it('never exposes MFA secrets in the user form', function () {
    $user = User::factory()->sales()->create();

    $this->get(UserResource::getUrl('edit', ['record' => $user]))
        ->assertOk()
        ->assertDontSee('app_authentication_secret')
        ->assertDontSee('app_authentication_recovery_codes');
});

it('creates a trip with itinerary, prices, faqs and taxonomy through the form', function () {
    $destination = DestinationFactory::new()->create();
    $tier = TierFactory::new()->create();

    Livewire::test(CreateTrip::class)
        ->fillForm([
            'title' => 'Everest Base Camp Trek',
            'slug' => 'everest-base-camp-trek',
            'status' => PublishStatus::Published->value,
            'duration_days' => 14,
            'destination_id' => $destination->id,
            'destinations' => [$destination->id],
            'tiers' => [$tier->id],
            'currency' => 'USD',
            'price_from' => 1490,
            'highlights' => [['text' => 'Sunrise from Kala Patthar'], ['text' => 'Namche Bazaar']],
            'facts' => ['Permits' => 'Sagarmatha NP'],
            'days' => [
                ['day_number' => 1, 'title' => 'Arrive in Kathmandu', 'meals' => ['dinner']],
                ['day_number' => 2, 'title' => 'Fly to Lukla', 'meals' => ['breakfast', 'lunch', 'dinner'], 'altitude_m' => 2860],
            ],
            'prices' => [['tier_id' => $tier->id, 'pax_min' => 1, 'pax_max' => 4, 'price' => 1490]],
            'faqs' => [['question' => 'Do I need insurance?', 'answer' => '<p>Yes</p>']],
            'seo' => ['title' => 'EBC Trek', 'description' => 'Classic trek', 'noindex' => false],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $trip = Trip::where('slug', 'everest-base-camp-trek')->sole();
    expect($trip->highlights)->toBe(['Sunrise from Kala Patthar', 'Namche Bazaar'])
        ->and($trip->seo['title'])->toBe('EBC Trek')
        ->and($trip->days()->pluck('title')->all())->toBe(['Arrive in Kathmandu', 'Fly to Lukla'])
        ->and($trip->days()->first()->meals)->toBe(['dinner'])
        ->and($trip->prices()->sole()->tier_id)->toBe($tier->id)
        ->and($trip->faqs()->count())->toBe(1)
        ->and($trip->destinations()->pluck('destinations.id')->all())->toBe([$destination->id])
        ->and($trip->tiers()->count())->toBe(1);
});
