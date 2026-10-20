<?php

use App\Enums\BookingStatus;
use App\Enums\LeadStatus;
use App\Enums\PaymentStatus;
use App\Events\BookingConfirmed;
use App\Events\PaymentSucceeded;
use App\Models\Setting;
use App\Notifications\BookingConfirmed as BookingConfirmedMail;
use App\Notifications\PaymentReceived;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Spatie\WebhookServer\CallWebhookJob;
use Tests\Support\Fixtures;

beforeEach(function () {
    config([
        'services.paypal.mode' => 'sandbox',
        'services.paypal.client_id' => 'client',
        'services.paypal.client_secret' => 'secret',
        'services.paypal.webhook_id' => 'WH-123',
    ]);
    Notification::fake();
});

function paypalOrder(string $orderId, string $uuid, string $value = '600.00', string $currency = 'USD', string $status = 'COMPLETED'): array
{
    return [
        'id' => $orderId,
        'status' => $status,
        'payer' => ['email_address' => 'buyer@example.test', 'payer_id' => 'PAYER1'],
        'purchase_units' => [[
            'reference_id' => $uuid,
            'payments' => ['captures' => [[
                'id' => 'CAPTURE-1', 'status' => $status, 'custom_id' => $uuid,
                'amount' => ['value' => $value, 'currency_code' => $currency],
            ]]],
        ]],
    ];
}

function paypalWebhook(string $uuid, string $value = '600.00'): array
{
    return [
        'id' => 'WH-EVT-1',
        'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
        'resource' => ['id' => 'CAPTURE-1', 'status' => 'COMPLETED', 'custom_id' => $uuid, 'amount' => ['value' => $value, 'currency_code' => 'USD']],
    ];
}

function paypalHeaders(): array
{
    return [
        'PAYPAL-AUTH-ALGO' => 'SHA256withRSA',
        'PAYPAL-CERT-URL' => 'https://api.sandbox.paypal.com/v1/notifications/certs/CERT',
        'PAYPAL-TRANSMISSION-ID' => 'tx-1',
        'PAYPAL-TRANSMISSION-SIG' => 'sig',
        'PAYPAL-TRANSMISSION-TIME' => now()->toIso8601String(),
    ];
}

it('creates an order and redirects to PayPal with server-side amounts', function () {
    $booking = Fixtures::booking();
    $payment = Fixtures::payment($booking);

    Http::fake([
        '*/v1/oauth2/token' => Http::response(['access_token' => 'TOKEN', 'expires_in' => 3600]),
        '*/v2/checkout/orders' => Http::response(['id' => 'ORDER-1', 'status' => 'CREATED', 'links' => [
            ['rel' => 'approve', 'href' => 'https://www.sandbox.paypal.com/checkoutnow?token=ORDER-1'],
        ]], 201),
    ]);

    $this->post(route('pay.start', [$payment, 'paypal']), ['amount' => 1])
        ->assertRedirect('https://www.sandbox.paypal.com/checkoutnow?token=ORDER-1');

    expect($payment->fresh()->gateway_reference)->toBe('ORDER-1');

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/v2/checkout/orders')
        && $request['purchase_units'][0]['amount']['value'] === '600.00'
        && $request['purchase_units'][0]['custom_id'] === $payment->uuid
        && $request['application_context']['shipping_preference'] === 'NO_SHIPPING');
});

it('captures on return, marks paid, confirms booking and increments seats once even when the webhook also arrives', function () {
    Event::fake([PaymentSucceeded::class, BookingConfirmed::class]);

    $booking = Fixtures::booking();
    $payment = Fixtures::payment($booking, ['gateway' => 'paypal', 'gateway_reference' => 'ORDER-1']);
    $departure = $booking->departure;

    Http::fake([
        '*/v1/oauth2/token' => Http::response(['access_token' => 'TOKEN', 'expires_in' => 3600]),
        '*/v2/checkout/orders/ORDER-1/capture' => Http::response(paypalOrder('ORDER-1', $payment->uuid), 201),
        '*/v1/notifications/verify-webhook-signature' => Http::response(['verification_status' => 'SUCCESS']),
    ]);

    $this->get(route('pay.return', [$payment, 'paypal']).'?token=ORDER-1&PayerID=PAYER1')
        ->assertRedirect(route('pay.success', $payment));

    $this->withHeaders(paypalHeaders())->postJson(route('webhooks.payments', 'paypal'), paypalWebhook($payment->uuid))->assertOk();
    $this->get(route('pay.return', [$payment, 'paypal']).'?token=ORDER-1')->assertRedirect(route('pay.success', $payment));

    $payment->refresh();
    $booking->refresh();

    expect($payment->status)->toBe(PaymentStatus::Paid)
        ->and($payment->gateway_reference)->toBe('CAPTURE-1')
        ->and($payment->paid_at)->not->toBeNull()
        ->and($booking->status)->toBe(BookingStatus::Confirmed)
        ->and((float) $booking->amount_paid)->toBe(600.0)
        ->and($departure->fresh()->seats_booked)->toBe(2)
        ->and($booking->lead->fresh()->status)->toBe(LeadStatus::DepositPaid);

    Event::assertDispatchedTimes(PaymentSucceeded::class, 1);
    Event::assertDispatchedTimes(BookingConfirmed::class, 1);
    Notification::assertSentOnDemandTimes(PaymentReceived::class, 1);
    Notification::assertSentOnDemandTimes(BookingConfirmedMail::class, 1);

    $this->get(route('pay.success', $payment))->assertOk()->assertSee($booking->number);
});

it('confirms via webhook alone and queues outbound webhooks', function () {
    Queue::fake();
    Setting::put('webhooks', [['url' => 'https://hooks.example.test/wh', 'secret' => 's3cret', 'events' => ['payment.succeeded', 'booking.confirmed']]]);

    $booking = Fixtures::booking();
    $payment = Fixtures::payment($booking);

    Http::fake([
        '*/v1/oauth2/token' => Http::response(['access_token' => 'TOKEN', 'expires_in' => 3600]),
        '*/v1/notifications/verify-webhook-signature' => Http::response(['verification_status' => 'SUCCESS']),
    ]);

    $this->withHeaders(paypalHeaders())->postJson(route('webhooks.payments', 'paypal'), paypalWebhook($payment->uuid))->assertOk();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($booking->fresh()->status)->toBe(BookingStatus::Confirmed);

    Queue::assertPushed(CallWebhookJob::class, 2);
    Queue::assertPushed(CallWebhookJob::class, fn (CallWebhookJob $job) => $job->webhookUrl === 'https://hooks.example.test/wh'
        && $job->payload['event'] === 'payment.succeeded'
        && $job->payload['data']['uuid'] === $payment->uuid
        && isset($job->headers['Signature']));
});

it('rejects webhooks with a bad signature', function () {
    $payment = Fixtures::payment(Fixtures::booking());

    Http::fake([
        '*/v1/oauth2/token' => Http::response(['access_token' => 'TOKEN', 'expires_in' => 3600]),
        '*/v1/notifications/verify-webhook-signature' => Http::response(['verification_status' => 'FAILURE']),
    ]);

    $this->withHeaders(paypalHeaders())->postJson(route('webhooks.payments', 'paypal'), paypalWebhook($payment->uuid))->assertStatus(400);
    $this->postJson(route('webhooks.payments', 'paypal'), paypalWebhook($payment->uuid))->assertStatus(400); // no headers at all

    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending);
});

it('rejects amount mismatches on return and webhook', function () {
    $booking = Fixtures::booking();
    $payment = Fixtures::payment($booking, ['gateway' => 'paypal', 'gateway_reference' => 'ORDER-1']);

    Http::fake([
        '*/v1/oauth2/token' => Http::response(['access_token' => 'TOKEN', 'expires_in' => 3600]),
        '*/v2/checkout/orders/ORDER-1/capture' => Http::response(paypalOrder('ORDER-1', $payment->uuid, '1.00'), 201),
        '*/v1/notifications/verify-webhook-signature' => Http::response(['verification_status' => 'SUCCESS']),
    ]);

    $this->get(route('pay.return', [$payment, 'paypal']).'?token=ORDER-1')->assertRedirect(route('pay.show', $payment));
    $this->withHeaders(paypalHeaders())->postJson(route('webhooks.payments', 'paypal'), paypalWebhook($payment->uuid, '1.00'))->assertOk();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending)
        ->and($booking->fresh()->status)->toBe(BookingStatus::PendingDeposit)
        ->and($booking->departure->fresh()->seats_booked)->toBe(0);
});

it('refuses a return whose order id belongs to another payment', function () {
    $payment = Fixtures::payment(Fixtures::booking(), ['gateway' => 'paypal', 'gateway_reference' => 'ORDER-1']);
    Http::fake(['*/v1/oauth2/token' => Http::response(['access_token' => 'TOKEN', 'expires_in' => 3600])]);

    $this->get(route('pay.return', [$payment, 'paypal']).'?token=ORDER-OTHER')->assertRedirect(route('pay.show', $payment));

    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending);
    Http::assertNotSent(fn ($request) => str_contains($request->url(), '/capture'));
});
