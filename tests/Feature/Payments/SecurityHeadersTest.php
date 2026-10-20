<?php

use Illuminate\Support\Facades\Gate;
use Tests\Support\Fixtures;

it('adds security headers to web and API responses', function () {
    $quote = Fixtures::quote();

    foreach ([route('quotes.show', $quote->uuid), '/api/v1/trips'] as $url) {
        $response = $this->get($url)->assertOk();

        expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff')
            ->and($response->headers->get('X-Frame-Options'))->toBe('SAMEORIGIN')
            ->and($response->headers->get('Referrer-Policy'))->toBe('strict-origin-when-cross-origin')
            ->and($response->headers->get('Permissions-Policy'))->toBe('camera=(), microphone=(), geolocation=()')
            ->and($response->headers->has('Strict-Transport-Security'))->toBeFalse(); // production only
    }
});

it('sends a report-only CSP with a script nonce on public pages but not on admin', function () {
    $quote = Fixtures::quote();

    $csp = $this->get(route('quotes.show', $quote->uuid))->headers->get('Content-Security-Policy-Report-Only');

    expect($csp)->toContain("script-src 'self'")
        ->toContain("'nonce-")
        ->toContain('https://challenges.cloudflare.com')
        ->toContain('https://*.paypal.com')
        ->toContain('img-src');

    $admin = $this->get('/admin/login');
    expect($admin->headers->has('Content-Security-Policy-Report-Only'))->toBeFalse();
});

it('protects the API docs', function () {
    $this->get('/docs/api')->assertRedirect();

    $this->actingAs(Fixtures::user('sales'))->get('/docs/api')->assertForbidden();

    // Rendering the docs generates the whole OpenAPI document (slow / memory hungry), so check the gate directly.
    expect(Gate::forUser(Fixtures::user('manager'))->allows('viewApiDocs'))->toBeTrue()
        ->and(Gate::forUser(Fixtures::user('admin'))->allows('viewApiDocs'))->toBeTrue()
        ->and(Gate::forUser(Fixtures::user('manager', ['is_active' => false]))->allows('viewApiDocs'))->toBeFalse()
        ->and(Gate::forUser(null)->allows('viewApiDocs'))->toBeFalse();
});
