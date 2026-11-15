<?php

use App\Filament\Pages\Auth\EditProfile;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\StaffAlert;
use App\Support\Notifications\StaffNotifier;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->sales = User::factory()->sales()->create(['email' => 'pema@wonderhimalaya.test'])->fresh();
    Notification::fake();
});

function newLeadAlert(): StaffAlert
{
    return new StaffAlert('new_lead', 'New lead', ['A lead arrived.'], 'Open', 'https://example.test');
}

it('shows the profile page with the notification choices the admin settings allow', function () {
    Setting::put('notification_settings', ['new_lead' => ['channels' => ['database']], 'payment_received_staff' => ['enabled' => false]]);

    $this->actingAs($this->sales)->get(EditProfile::getUrl())
        ->assertOk()
        ->assertSee(['Notifications', 'New enquiry', 'Admin bell (in-app)', 'Recent sign-ins', 'Active sessions'])
        ->assertDontSee('Payment received');
});

it('lets a team member switch off a channel or a whole alert for themselves', function () {
    Setting::put('notification_settings', ['new_lead' => ['channels' => ['database', 'mail']], 'booking_confirmed_staff' => ['channels' => ['database']]]);

    Livewire::actingAs($this->sales)
        ->test(EditProfile::class)
        ->assertSet('notificationPreferences.new_lead', ['database', 'mail'])
        ->set('notificationPreferences.new_lead', ['database'])
        ->set('notificationPreferences.booking_confirmed_staff', [])
        ->call('saveNotificationPreferences')
        ->assertHasNoErrors()
        ->assertNotified('Notification preferences saved');

    expect($this->sales->fresh()->notification_preferences)->toMatchArray([
        'new_lead' => ['database' => true, 'mail' => false],
        'booking_confirmed_staff' => ['database' => false],
    ]);

    StaffNotifier::send(newLeadAlert());
    StaffNotifier::send(new StaffAlert('booking_confirmed_staff', 'Booking', ['Confirmed.'], 'Open', 'https://example.test'));

    Notification::assertSentTo($this->sales, StaffAlert::class, fn (StaffAlert $alert, array $channels) => $alert->event === 'new_lead' && $channels === ['database']);
    Notification::assertNotSentTo($this->sales, StaffAlert::class, fn (StaffAlert $alert) => $alert->event === 'booking_confirmed_staff');
});

it('never widens what the admin settings allow', function () {
    Setting::put('notification_settings', ['new_lead' => ['channels' => ['database']]]);
    $this->sales->forceFill(['notification_preferences' => ['new_lead' => ['database' => true, 'mail' => true]]])->save();

    StaffNotifier::send(newLeadAlert());

    Notification::assertSentTo($this->sales, StaffAlert::class, fn (StaffAlert $alert, array $channels) => $channels === ['database']);

    // Not chosen by the admin at all: opting in on the profile changes nothing.
    $outsider = User::factory()->sales()->create(['notification_preferences' => null]);
    Setting::put('notification_settings', ['new_lead' => ['all_staff' => false, 'user_ids' => [$this->sales->id], 'channels' => ['database']]]);

    StaffNotifier::send(newLeadAlert());

    Notification::assertNotSentTo($outsider, StaffAlert::class);
});

it('rejects channels the admin settings do not allow', function () {
    Setting::put('notification_settings', ['new_lead' => ['channels' => ['database']]]);

    Livewire::actingAs($this->sales)
        ->test(EditProfile::class)
        ->set('notificationPreferences.new_lead', ['database', 'mail'])
        ->call('saveNotificationPreferences')
        ->assertHasErrors();

    expect($this->sales->fresh()->notification_preferences)->toBeNull();
});

it('keeps emailing extra addresses when a team member opts out of email', function () {
    Setting::put('notification_settings', ['new_lead' => ['channels' => ['mail'], 'emails' => ['pema@wonderhimalaya.test', 'inbox@wonderhimalaya.test']]]);
    $this->sales->forceFill(['notification_preferences' => ['new_lead' => ['mail' => false]]])->save();

    StaffNotifier::send(newLeadAlert());

    Notification::assertNotSentTo($this->sales, StaffAlert::class);
    Notification::assertSentOnDemand(StaffAlert::class, fn ($alert, $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === 'inbox@wonderhimalaya.test');
    Notification::assertSentOnDemand(StaffAlert::class, fn ($alert, $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === 'pema@wonderhimalaya.test');
});

it('lets the assignee mute lead assignment alerts', function () {
    $this->sales->forceFill(['notification_preferences' => ['lead_assigned' => ['database' => false]]])->save();

    StaffNotifier::send(new StaffAlert('lead_assigned', 'Assigned', ['Yours.'], 'Open', 'https://example.test'), $this->sales);

    Notification::assertNothingSent();
});
