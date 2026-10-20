<?php

use App\Models\Traveller;
use Laravel\Sanctum\Sanctum;
use Tests\Support\Fixtures;

it('requires a token (401) and the right ability (403)', function () {
    $this->getJson('/api/v1/me')->assertUnauthorized();
    $this->getJson('/api/v1/leads')->assertUnauthorized();

    $lead = Fixtures::lead();
    $token = Fixtures::user('manager')->createToken('zapier', ['bookings:read'])->plainTextToken;

    $this->withToken($token)->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.token_abilities', ['bookings:read']);
    $this->withToken($token)->getJson('/api/v1/leads')->assertForbidden();
    $this->withToken($token)->patchJson("/api/v1/leads/{$lead->id}", ['status' => 'contacted'])->assertForbidden();
    app('auth')->forgetGuards();
    $this->withToken('1|invalid')->getJson('/api/v1/me')->assertUnauthorized();
});

it('enforces role permissions on top of token abilities', function () {
    Sanctum::actingAs(Fixtures::user('content'), ['*']);

    $this->getJson('/api/v1/leads')->assertForbidden();
    $this->getJson('/api/v1/bookings')->assertForbidden();
});

it('limits sales users to their own and unassigned leads', function () {
    $sales = Fixtures::user('sales');
    $other = Fixtures::user('sales');

    $mine = Fixtures::lead(['owner_id' => $sales->id]);
    $unassigned = Fixtures::lead();
    $theirs = Fixtures::lead(['owner_id' => $other->id]);

    Sanctum::actingAs($sales, ['leads:read', 'leads:write']);

    $ids = collect($this->getJson('/api/v1/leads')->assertOk()->json('data'))->pluck('id')->sort()->values()->all();
    expect($ids)->toBe(collect([$mine->id, $unassigned->id])->sort()->values()->all());

    $this->getJson("/api/v1/leads/{$theirs->id}")->assertForbidden();
    $this->getJson("/api/v1/leads/{$mine->id}")->assertOk()
        ->assertJsonPath('data.contact.email', $mine->contact->email)
        ->assertJsonMissingPath('data.ip_address');

    $this->patchJson("/api/v1/leads/{$theirs->id}", ['status' => 'contacted'])->assertForbidden();
    $this->patchJson("/api/v1/leads/{$unassigned->id}", ['owner_id' => $other->id])->assertForbidden();
    $this->patchJson("/api/v1/leads/{$unassigned->id}", ['owner_id' => $sales->id, 'status' => 'contacted'])
        ->assertOk()->assertJsonPath('data.status', 'contacted')->assertJsonPath('data.owner_id', $sales->id);

    $this->postJson("/api/v1/leads/{$mine->id}/notes", ['body' => 'Called, very keen.'])->assertCreated();
    expect($mine->notes()->first()->user_id)->toBe($sales->id);
});

it('shows ip_address to admins only', function () {
    $lead = Fixtures::lead();
    Sanctum::actingAs(Fixtures::user('admin'), ['read']);

    $this->getJson("/api/v1/leads/{$lead->id}")->assertOk()->assertJsonPath('data.ip_address', '203.0.113.9');
});

it('masks traveller passports and never exposes gateway payloads', function () {
    $booking = Fixtures::booking();
    Traveller::create(['booking_id' => $booking->id, 'first_name' => 'Ana', 'passport_number' => 'X12345678', 'medical_notes' => 'Asthma', 'date_of_birth' => '1990-01-01']);
    Fixtures::payment($booking, ['gateway_payload' => ['secret' => 'value']]);

    Sanctum::actingAs(Fixtures::user('sales'), ['bookings:read']);

    $response = $this->getJson("/api/v1/bookings/{$booking->number}")->assertOk();

    expect($response->json('data.travellers.0.passport_number'))->toBe('••••••678')
        ->and($response->getContent())->not->toContain('X12345678')
        ->not->toContain('Asthma')
        ->not->toContain('1990-01-01')
        ->not->toContain('gateway_payload')
        ->and($response->json('data.travellers.0.has_medical_notes'))->toBeTrue();
});

it('creates a payment link for the outstanding balance', function () {
    $booking = Fixtures::booking(['amount_paid' => 600, 'status' => 'confirmed']);
    Sanctum::actingAs(Fixtures::user('sales'), ['bookings:write']);

    $response = $this->postJson("/api/v1/bookings/{$booking->number}/payment-links")->assertCreated();

    expect($response->json('data.amount'))->toBe('2400.00')
        ->and($response->json('data.url'))->toContain('/pay/')
        ->and($response->json('data.is_payable'))->toBeTrue();

    $this->postJson("/api/v1/bookings/{$booking->number}/payment-links", ['type' => 'custom', 'amount' => 99999])->assertUnprocessable();
    Sanctum::actingAs(Fixtures::user('sales'), ['bookings:read']);
    $this->postJson("/api/v1/bookings/{$booking->number}/payment-links")->assertForbidden();
});
