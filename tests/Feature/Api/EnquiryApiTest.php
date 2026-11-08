<?php

use App\Events\LeadCreated;
use App\Models\Lead;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    Event::fake([LeadCreated::class]);
    RateLimiter::clear('enquiry-min:127.0.0.1');
});

$payload = fn (array $overrides = []) => array_merge([
    'type' => 'tailor_made',
    'first_name' => 'Ana',
    'last_name' => 'Silva',
    'email' => 'ana@example.com',
    'adults' => 2,
    'privacy_accepted' => true,
], $overrides);

it('creates a lead from a JSON enquiry', function () use ($payload) {
    $response = $this->postJson('/api/v1/enquiries', $payload())->assertCreated();

    expect($response->json('data.reference'))->toStartWith('WH-')
        ->and($response->json('data.status'))->toBe('new')
        ->and(Lead::first()->source)->toBe('api')
        ->and(Lead::first()->ip_address)->toBe('127.0.0.1');

    Event::assertDispatched(LeadCreated::class);
});

it('validates enquiries with 422 and requires privacy acceptance', function () use ($payload) {
    $this->postJson('/api/v1/enquiries', $payload(['email' => 'not-an-email', 'privacy_accepted' => null]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'privacy_accepted']);

    expect(Lead::count())->toBe(0);
});

it('rate limits enquiries', function () use ($payload) {
    foreach (range(1, 3) as $i) {
        $this->postJson('/api/v1/enquiries', $payload(['email' => "a{$i}@example.com"]))->assertCreated();
    }

    $this->postJson('/api/v1/enquiries', $payload(['email' => 'a4@example.com']))->assertStatus(429);
});

it('verifies a Turnstile token when configured', function () use ($payload) {
    config(['services.turnstile.secret' => 'ts-secret']);
    Http::fake(['challenges.cloudflare.com/*' => Http::sequence()->push(['success' => false])->push(['success' => true])]);

    $this->postJson('/api/v1/enquiries', $payload())->assertUnprocessable()->assertJsonValidationErrors('turnstile_token');
    $this->postJson('/api/v1/enquiries', $payload(['turnstile_token' => 'bad']))->assertUnprocessable();
    $this->postJson('/api/v1/enquiries', $payload(['turnstile_token' => 'good']))->assertCreated();

    Http::assertSent(fn ($request) => $request['secret'] === 'ts-secret' && $request['response'] === 'good');
});
