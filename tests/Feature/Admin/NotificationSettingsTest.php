<?php

use App\Actions\Leads\SubmitEnquiry;
use App\Filament\Pages\ManageNotifications;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\EnquiryReceived;
use App\Notifications\StaffAlert;
use Database\Factories\LeadFactory;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->admin()->create(['email' => 'admin@wonderhimalaya.test']);
    Notification::fake();
});

function enquire(): void
{
    app(SubmitEnquiry::class)->handle(['type' => 'contact', 'first_name' => 'Ana', 'last_name' => 'Silva', 'email' => 'ana@example.com']);
}

it('sends the customer confirmation with the edited wording', function () {
    Setting::put('notification_settings', ['enquiry_received' => ['subject' => 'Thanks {first_name}! Ref {reference}', 'intro' => 'Hello from Thamel.']]);

    enquire();

    Notification::assertSentOnDemand(EnquiryReceived::class, function (EnquiryReceived $notification, array $channels, AnonymousNotifiable $notifiable) {
        $mail = $notification->toMail($notifiable);

        return str_starts_with($mail->subject, 'Thanks Ana! Ref WH-') && in_array('Hello from Thamel.', $mail->introLines, true);
    });
});

it('does not send emails that are switched off', function () {
    Setting::put('notification_settings', ['enquiry_received' => ['enabled' => false], 'new_lead' => ['enabled' => false]]);

    enquire();

    Notification::assertNothingSent();
});

it('alerts chosen staff in the admin bell and extra addresses by email', function () {
    $sales = User::factory()->sales()->create();
    $other = User::factory()->sales()->create();
    Setting::put('notification_settings', ['new_lead' => ['all_staff' => false, 'user_ids' => [$sales->id], 'channels' => ['database'], 'emails' => ['inbox@wonderhimalaya.test']]]);

    enquire();

    Notification::assertSentTo($sales, StaffAlert::class, fn (StaffAlert $alert, array $channels) => $channels === ['database'] && str_contains($alert->subject, 'Ana Silva'));
    Notification::assertNotSentTo([$other, $this->admin], StaffAlert::class);
    Notification::assertSentOnDemand(StaffAlert::class, fn ($alert, $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === 'inbox@wonderhimalaya.test');
});

it('keeps emailing the old sales inbox until recipients are configured', function () {
    Setting::put('notifications', ['sales_email' => 'sales@wonderhimalaya.test']);

    enquire();

    Notification::assertSentOnDemand(StaffAlert::class, fn ($alert, $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === 'sales@wonderhimalaya.test');
});

it('tells a team member when a lead is assigned to them', function () {
    $sales = User::factory()->sales()->create();
    $lead = LeadFactory::new()->create();

    $this->actingAs($this->admin);
    $lead->update(['owner_id' => $sales->id]);
    Notification::assertSentTo($sales, StaffAlert::class, fn (StaffAlert $alert) => $alert->event === 'lead_assigned');

    $this->actingAs($sales);
    $lead->update(['owner_id' => null]);
    $lead->update(['owner_id' => $sales->id]);
    Notification::assertSentToTimes($sales, StaffAlert::class, 1);
});

it('lets admins edit and save notification settings', function () {
    $this->actingAs($this->admin)->get(ManageNotifications::getUrl())->assertOk()->assertSee('Enquiry confirmation')->assertSee('Lead assigned to me');

    Livewire::actingAs($this->admin)
        ->test(ManageNotifications::class)
        ->set('data.enquiry_received.subject', 'Your enquiry {reference}')
        ->set('data.new_lead.all_staff', false)
        ->set('data.new_lead.user_ids', [$this->admin->id])
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::get('notification_settings.enquiry_received.subject'))->toBe('Your enquiry {reference}')
        ->and(Setting::get('notification_settings.new_lead.user_ids'))->toBe([$this->admin->id]);
});
