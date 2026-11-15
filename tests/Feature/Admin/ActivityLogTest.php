<?php

use App\Enums\LeadStatus;
use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs;
use App\Filament\Resources\Bookings\Pages\EditBooking;
use App\Filament\Resources\Bookings\RelationManagers\PaymentsRelationManager;
use App\Filament\Resources\Leads\Pages\ViewLead;
use App\Filament\Resources\Trips\Pages\EditTrip;
use App\Models\User;
use Database\Factories\BookingFactory;
use Database\Factories\LeadFactory;
use Database\Factories\PaymentFactory;
use Database\Factories\TripFactory;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('lets admins and managers open the activity log but not sales or content staff', function (string $role, int $status) {
    $this->actingAs(User::factory()->role($role)->create())
        ->get(ActivityLogResource::getUrl('index'))
        ->assertStatus($status);
})->with([
    'admin' => ['admin', 200],
    'manager' => ['manager', 200],
    'sales' => ['sales', 403],
    'content' => ['content', 403],
]);

it('lists changes newest first and filters by user, type and event', function () {
    $admin = User::factory()->admin()->create(['name' => 'Asha Admin']);
    $this->actingAs($admin);

    $lead = LeadFactory::new()->status(LeadStatus::New)->create();
    $lead->update(['status' => LeadStatus::Contacted]);
    $trip = TripFactory::new()->create();

    $leadCreated = Activity::query()->forSubject($lead)->where('event', 'created')->sole();
    $leadUpdated = Activity::query()->forSubject($lead)->where('event', 'updated')->sole();
    $tripCreated = Activity::query()->forSubject($trip)->sole();
    $byOther = Activity::query()->forSubject($trip)->sole()->replicate()->fill(['causer_id' => User::factory()->admin()->create()->id]);
    $byOther->save();

    Livewire::test(ListActivityLogs::class)
        ->assertCanSeeTableRecords([$byOther, $tripCreated, $leadUpdated, $leadCreated], inOrder: true)
        ->filterTable('causer', $admin->id)
        ->assertCanNotSeeTableRecords([$byOther])
        ->filterTable('subject_type', 'lead')
        ->assertCanSeeTableRecords([$leadCreated, $leadUpdated])
        ->assertCanNotSeeTableRecords([$tripCreated])
        ->filterTable('event', 'updated')
        ->assertCanSeeTableRecords([$leadUpdated])
        ->assertCanNotSeeTableRecords([$leadCreated]);
});

it('shows a readable diff and masks sensitive attributes', function () {
    $this->actingAs(User::factory()->admin()->create());
    $booking = BookingFactory::new()->create();
    $activity = Activity::query()->create([
        'subject_type' => 'booking',
        'subject_id' => $booking->id,
        'event' => 'updated',
        'description' => 'updated',
        'attribute_changes' => [
            'attributes' => ['status' => 'confirmed', 'emergency_contact' => 'Dawa +977 9800000000', 'passport_number' => 'N1234567', 'gateway_payload' => ['token' => 'tok_live_abc']],
            'old' => ['status' => 'pending_deposit', 'emergency_contact' => null, 'passport_number' => 'X7654321', 'gateway_payload' => null],
        ],
    ]);

    Livewire::test(ListActivityLogs::class)
        ->mountAction(TestAction::make('view')->table($activity))
        ->assertMountedActionModalSee('Confirmed')
        ->assertMountedActionModalSee('Awaiting deposit')
        ->assertMountedActionModalSee('(hidden)')
        ->assertMountedActionModalDontSee('Dawa +977')
        ->assertMountedActionModalDontSee('N1234567')
        ->assertMountedActionModalDontSee('X7654321')
        ->assertMountedActionModalDontSee('tok_live_abc');
});

it("shows only the record's own events in its History slide-over", function () {
    $this->actingAs(User::factory()->admin()->create());
    $lead = LeadFactory::new()->status(LeadStatus::New)->create(['lost_reason' => null]);
    $lead->update(['lost_reason' => 'Chose another operator']);
    $other = LeadFactory::new()->create(['lost_reason' => null]);
    $other->update(['lost_reason' => 'Budget too small']);

    Livewire::test(ViewLead::class, ['record' => $lead->getRouteKey()])
        ->mountAction('history')
        ->assertMountedActionModalSee('Chose another operator')
        ->assertMountedActionModalDontSee('Budget too small');
});

it('offers History on bookings, trips and payments', function () {
    $this->actingAs(User::factory()->admin()->create());
    $booking = BookingFactory::new()->create();
    $payment = PaymentFactory::new()->forBooking($booking)->create(['gateway_reference' => 'PAY-REF-1']);
    $payment->update(['gateway_reference' => 'PAY-REF-2']);
    $trip = TripFactory::new()->create(['title' => 'Mardi Himal Trek']);

    Livewire::test(EditBooking::class, ['record' => $booking->getRouteKey()])->assertActionVisible('history');
    Livewire::test(EditTrip::class, ['record' => $trip->getRouteKey()])
        ->mountAction('history')
        ->assertMountedActionModalSee('Mardi Himal Trek');
    Livewire::test(PaymentsRelationManager::class, ['ownerRecord' => $booking, 'pageClass' => EditBooking::class])
        ->mountAction(TestAction::make('history')->table($payment))
        ->assertMountedActionModalSee('PAY-REF-2');
});
