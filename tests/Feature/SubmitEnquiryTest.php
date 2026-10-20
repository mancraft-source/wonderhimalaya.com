<?php

use App\Actions\Leads\SubmitEnquiry;
use App\Enums\LeadStatus;
use App\Events\LeadCreated;
use App\Models\Contact;
use Illuminate\Support\Facades\Event;

it('creates a contact and lead and reuses the contact by email', function () {
    Event::fake([LeadCreated::class]);

    $action = app(SubmitEnquiry::class);
    $lead = $action->handle(['type' => 'tailor_made', 'first_name' => 'Ana', 'email' => 'Ana@Example.com', 'adults' => 2]);
    $second = $action->handle(['type' => 'contact', 'first_name' => 'Ana', 'email' => 'ana@example.com', 'phone' => '+44 7700 900123']);

    expect($lead->status)->toBe(LeadStatus::New)
        ->and($lead->reference)->toStartWith('WH-')
        ->and($second->contact_id)->toBe($lead->contact_id)
        ->and(Contact::count())->toBe(1)
        ->and(Contact::first()->phone)->toBe('+44 7700 900123');

    Event::assertDispatchedTimes(LeadCreated::class, 2);
});

it('creates a company for b2b enquiries', function () {
    Event::fake([LeadCreated::class]);

    $lead = app(SubmitEnquiry::class)->handle([
        'type' => 'b2b', 'first_name' => 'Jo', 'email' => 'jo@agency.test', 'company_name' => 'Peak Agency',
    ]);

    expect($lead->company->name)->toBe('Peak Agency')
        ->and($lead->contact->company_id)->toBe($lead->company_id);
});
