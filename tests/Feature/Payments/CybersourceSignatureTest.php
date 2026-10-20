<?php

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Payments\Gateways\NepalBankGateway;
use App\Payments\PaymentManager;
use Illuminate\Support\Facades\Notification;
use Tests\Support\Fixtures;

const CYBS_SECRET = 'test-secret-key';

it('signs Secure Acceptance fields exactly like the documented HMAC-SHA256 scheme', function () {
    // Vector computed independently: base64(hmac_sha256("name=value,..." in signed_field_names order, secret))
    $fields = [
        'access_key' => 'abc123',
        'profile_id' => 'PROFILE-1',
        'transaction_uuid' => '5f1c8a7e-0000-4000-8000-000000000001',
        'signed_field_names' => 'access_key,profile_id,transaction_uuid,signed_field_names,unsigned_field_names,signed_date_time,locale,transaction_type,reference_number,amount,currency',
        'unsigned_field_names' => '',
        'signed_date_time' => '2026-10-20T08:00:00Z',
        'locale' => 'en',
        'transaction_type' => 'sale',
        'reference_number' => 'REF-42',
        'amount' => '600.00',
        'currency' => 'USD',
    ];

    expect(NepalBankGateway::sign($fields, CYBS_SECRET))->toBe('MMTyBvkJS9mGDj8D0gmmaOWbHDTr/2cCOvqYlKB74p8=')
        ->and(NepalBankGateway::verify($fields + ['signature' => 'MMTyBvkJS9mGDj8D0gmmaOWbHDTr/2cCOvqYlKB74p8='], CYBS_SECRET))->toBeTrue()
        ->and(NepalBankGateway::verify(array_merge($fields, ['amount' => '1.00', 'signature' => 'MMTyBvkJS9mGDj8D0gmmaOWbHDTr/2cCOvqYlKB74p8=']), CYBS_SECRET))->toBeFalse()
        ->and(NepalBankGateway::verify($fields + ['signature' => 'MMTyBvkJS9mGDj8D0gmmaOWbHDTr/2cCOvqYlKB74p8='], ''))->toBeFalse();
});

it('is unavailable until the bank credentials are configured', function () {
    expect(app(PaymentManager::class)->gateway('card')->isAvailable())->toBeFalse()
        ->and(array_keys(app(PaymentManager::class)->available()))->toBe(['bank_transfer']);
});

it('builds a signed auto-submit form and accepts a signed bank response', function () {
    Notification::fake();
    config([
        'services.nepal_bank.access_key' => 'abc123',
        'services.nepal_bank.profile_id' => 'PROFILE-1',
        'services.nepal_bank.secret_key' => CYBS_SECRET,
        'services.nepal_bank.endpoint' => 'https://testsecureacceptance.cybersource.com/pay',
    ]);

    $booking = Fixtures::booking();
    $payment = Fixtures::payment($booking);

    $page = $this->post(route('pay.start', [$payment, 'card']))->assertOk()
        ->assertSee('action="https://testsecureacceptance.cybersource.com/pay"', false)
        ->assertSee('name="signature"', false)
        ->assertSee('value="600.00"', false)
        ->assertSee('value="'.$payment->uuid.'"', false);

    $response = [
        'decision' => 'ACCEPT', 'reason_code' => '100', 'transaction_id' => '7000000000000001',
        'req_reference_number' => $payment->uuid, 'req_amount' => '600.00', 'req_currency' => 'USD',
        'signed_date_time' => '2026-10-20T08:05:00Z',
    ];
    $response['signed_field_names'] = implode(',', array_keys($response)).',signed_field_names';
    $response['signature'] = NepalBankGateway::sign($response, CYBS_SECRET);

    // Tampered response is rejected.
    $this->post(route('pay.return', [$payment, 'card']), array_merge($response, ['req_amount' => '1.00']))->assertRedirect(route('pay.show', $payment));
    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending);

    // Browser POST-back (CSRF-exempt, signature verified).
    $this->post(route('pay.return', [$payment, 'card']), $response)->assertRedirect(route('pay.success', $payment));
    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($booking->fresh()->status)->toBe(BookingStatus::Confirmed);

    // Merchant POST notification afterwards is idempotent; bad signature is a 400.
    $this->post(route('webhooks.payments', 'card'), $response)->assertOk();
    $this->post(route('webhooks.payments', 'card'), array_merge($response, ['signature' => 'nope']))->assertStatus(400);
    expect($booking->departure->fresh()->seats_booked)->toBe(2);
});
