<?php

namespace App\Payments\Gateways;

use App\Models\Payment;
use App\Payments\CheckoutSession;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Exceptions\GatewayException;
use App\Payments\Exceptions\InvalidWebhookSignature;
use App\Payments\PaymentLog;
use App\Payments\PaymentResult;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * PayPal REST v2 Orders API (intent CAPTURE) using the Laravel HTTP client.
 *
 * Flow: create order → redirect to approve link → customer returns with ?token={order id}
 * → capture → verify status/amount/currency/custom_id. The PAYMENT.CAPTURE.COMPLETED webhook
 * is a backup path (customer closed the tab before returning); both paths are idempotent.
 */
class PayPalGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'paypal';
    }

    public function label(): string
    {
        return 'PayPal';
    }

    public function isAvailable(): bool
    {
        return filled(config('services.paypal.client_id')) && filled(config('services.paypal.client_secret'));
    }

    public function baseUrl(): string
    {
        return config('services.paypal.mode') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    public function createCheckout(Payment $payment): CheckoutSession
    {
        $payment->loadMissing('payable');

        $response = $this->api()
            ->withHeaders(['PayPal-Request-Id' => 'order-'.$payment->uuid.'-'.$payment->updated_at?->timestamp])
            ->post('/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => $payment->uuid,
                    'custom_id' => $payment->uuid,
                    'invoice_id' => $payment->uuid,
                    'description' => mb_substr((string) ($payment->description ?: 'Wonder Himalaya booking'), 0, 127),
                    'amount' => [
                        'currency_code' => strtoupper($payment->currency),
                        'value' => number_format((float) $payment->amount, 2, '.', ''),
                    ],
                ]],
                'application_context' => [
                    'brand_name' => 'Wonder Himalaya',
                    'user_action' => 'PAY_NOW',
                    'shipping_preference' => 'NO_SHIPPING',
                    'return_url' => route('pay.return', [$payment, $this->key()]),
                    'cancel_url' => route('pay.cancel', $payment),
                ],
            ]);

        $this->ensureSuccessful($response, 'create order', $payment);

        $orderId = (string) $response->json('id');
        $approve = collect($response->json('links', []))
            ->first(fn (array $link) => in_array($link['rel'] ?? null, ['approve', 'payer-action'], true));

        if ($orderId === '' || ! $approve) {
            throw new GatewayException('PayPal order response missing id or approve link.');
        }

        PaymentLog::info('paypal.order.created', ['payment' => $payment->uuid, 'order_id' => $orderId, 'status' => $response->json('status')]);

        return CheckoutSession::redirect($approve['href'], $orderId);
    }

    public function handleReturn(Payment $payment, Request $request): PaymentResult
    {
        $orderId = (string) $request->query('token', '');

        // The order id must be the one we created for THIS payment (prevents swapping in another order).
        if ($orderId === '' || ! hash_equals((string) $payment->gateway_reference, $orderId)) {
            PaymentLog::warning('paypal.return.order_mismatch', ['payment' => $payment->uuid, 'order_id' => $orderId]);

            return PaymentResult::failed('PayPal order does not belong to this payment.');
        }

        $response = $this->api()
            ->withHeaders(['PayPal-Request-Id' => 'capture-'.$orderId])
            ->withBody('{}', 'application/json')
            ->post("/v2/checkout/orders/{$orderId}/capture");

        if ($response->status() === 422 && $this->hasIssue($response, 'ORDER_ALREADY_CAPTURED')) {
            $response = $this->api()->get("/v2/checkout/orders/{$orderId}");
        }

        if ($response->status() === 422 && $this->hasIssue($response, 'INSTRUMENT_DECLINED')) {
            PaymentLog::info('paypal.capture.declined', ['payment' => $payment->uuid, 'order_id' => $orderId]);

            return PaymentResult::failed('Your payment method was declined. Please try again or choose another method.');
        }

        $this->ensureSuccessful($response, 'capture order', $payment);

        $order = $response->json();
        PaymentLog::info('paypal.capture.response', ['payment' => $payment->uuid, 'order_id' => $orderId, 'status' => $order['status'] ?? null]);

        return $this->resultFromOrder($payment, $order);
    }

    /** @param array<string, mixed> $order */
    public function resultFromOrder(Payment $payment, array $order): PaymentResult
    {
        $unit = Arr::first($order['purchase_units'] ?? []) ?? [];
        $capture = Arr::first(data_get($unit, 'payments.captures', [])) ?? [];
        $payload = $this->summarise($order, $capture);

        if (($order['status'] ?? null) !== 'COMPLETED' || ($capture['status'] ?? null) !== 'COMPLETED') {
            return ($capture['status'] ?? null) === 'PENDING'
                ? PaymentResult::pending('PayPal is reviewing this payment.', $payload, reference: $capture['id'] ?? null)
                : PaymentResult::failed('PayPal did not complete the payment.', $payload);
        }

        $customId = $capture['custom_id'] ?? $unit['custom_id'] ?? null;

        if ($customId !== $payment->uuid) {
            return PaymentResult::mismatch('custom_id mismatch', $payload, $capture['id'] ?? null);
        }

        if (! PaymentResult::amountMatches($payment, data_get($capture, 'amount.value'), data_get($capture, 'amount.currency_code'))) {
            return PaymentResult::mismatch('amount/currency mismatch', $payload, $capture['id'] ?? null);
        }

        return PaymentResult::paid((string) $capture['id'], $payload);
    }

    public function handleWebhook(Request $request): ?PaymentResult
    {
        $raw = $request->getContent();
        $event = json_decode($raw, true);

        if (! is_array($event) || ! $this->verifyWebhookSignature($request, $raw)) {
            PaymentLog::warning('paypal.webhook.invalid_signature', ['transmission_id' => $request->header('paypal-transmission-id')]);

            throw new InvalidWebhookSignature('PayPal webhook signature verification failed.');
        }

        PaymentLog::info('paypal.webhook.received', ['event' => $event['event_type'] ?? null, 'id' => $event['id'] ?? null]);

        $resource = $event['resource'] ?? [];
        $uuid = $resource['custom_id'] ?? $resource['invoice_id'] ?? null;

        if (! $uuid) {
            return null;
        }

        $payload = ['event_id' => $event['id'] ?? null, 'event_type' => $event['event_type'] ?? null, 'capture_id' => $resource['id'] ?? null,
            'amount' => $resource['amount'] ?? null, 'status' => $resource['status'] ?? null];

        return match ($event['event_type'] ?? null) {
            'PAYMENT.CAPTURE.COMPLETED' => new PaymentResult(
                status: PaymentResult::PAID,
                reference: (string) ($resource['id'] ?? ''),
                paymentUuid: $uuid,
                payload: $payload,
                amount: (string) data_get($resource, 'amount.value'),
                currency: (string) data_get($resource, 'amount.currency_code'),
            ),
            'PAYMENT.CAPTURE.DENIED', 'PAYMENT.CAPTURE.DECLINED' => PaymentResult::failed('Capture denied', $payload, $resource['id'] ?? null, $uuid),
            default => null,
        };
    }

    /** Uses PayPal's verify-webhook-signature endpoint; the raw event body is forwarded untouched. */
    public function verifyWebhookSignature(Request $request, string $rawBody): bool
    {
        $webhookId = config('services.paypal.webhook_id');
        $headers = [
            'auth_algo' => $request->header('paypal-auth-algo'),
            'cert_url' => $request->header('paypal-cert-url'),
            'transmission_id' => $request->header('paypal-transmission-id'),
            'transmission_sig' => $request->header('paypal-transmission-sig'),
            'transmission_time' => $request->header('paypal-transmission-time'),
        ];

        if (! $webhookId || in_array(null, $headers, true) || ! $this->isAvailable()) {
            return false;
        }

        // Build the JSON by hand so `webhook_event` is byte-identical to what PayPal signed.
        $body = rtrim(json_encode($headers + ['webhook_id' => $webhookId], JSON_UNESCAPED_SLASHES), '}')
            .',"webhook_event":'.$rawBody.'}';

        $response = $this->api()->withBody($body, 'application/json')->post('/v1/notifications/verify-webhook-signature');

        return $response->successful() && $response->json('verification_status') === 'SUCCESS';
    }

    public function accessToken(): string
    {
        $cacheKey = 'paypal.token.'.config('services.paypal.mode').'.'.md5((string) config('services.paypal.client_id'));

        if ($token = Cache::get($cacheKey)) {
            return $token;
        }

        $response = Http::asForm()
            ->withBasicAuth((string) config('services.paypal.client_id'), (string) config('services.paypal.client_secret'))
            ->timeout(20)
            ->post($this->baseUrl().'/v1/oauth2/token', ['grant_type' => 'client_credentials']);

        if (! $response->successful() || ! $response->json('access_token')) {
            PaymentLog::error('paypal.oauth.failed', ['status' => $response->status()]);

            throw new GatewayException('PayPal authentication failed.');
        }

        $token = (string) $response->json('access_token');
        Cache::put($cacheKey, $token, max(60, (int) $response->json('expires_in', 3600) - 120));

        return $token;
    }

    private function api(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl())
            ->withToken($this->accessToken())
            ->acceptJson()
            ->asJson()
            ->timeout(30);
    }

    private function ensureSuccessful(Response $response, string $action, Payment $payment): void
    {
        if ($response->successful()) {
            return;
        }

        PaymentLog::error("paypal.{$action}.failed", [
            'payment' => $payment->uuid,
            'status' => $response->status(),
            'debug_id' => $response->json('debug_id'),
            'issue' => data_get($response->json(), 'details.0.issue') ?? $response->json('name'),
        ]);

        throw new GatewayException("PayPal could not {$action}.");
    }

    private function hasIssue(Response $response, string $issue): bool
    {
        return collect($response->json('details', []))->contains(fn ($d) => ($d['issue'] ?? null) === $issue);
    }

    /**
     * Keep only what staff need for reconciliation.
     *
     * @param  array<string, mixed>  $order
     * @param  array<string, mixed>  $capture
     * @return array<string, mixed>
     */
    private function summarise(array $order, array $capture): array
    {
        return [
            'order_id' => $order['id'] ?? null,
            'order_status' => $order['status'] ?? null,
            'capture_id' => $capture['id'] ?? null,
            'capture_status' => $capture['status'] ?? null,
            'amount' => $capture['amount'] ?? null,
            'fee' => data_get($capture, 'seller_receivable_breakdown.paypal_fee'),
            'payer_email' => data_get($order, 'payer.email_address'),
            'payer_id' => data_get($order, 'payer.payer_id'),
        ];
    }
}
