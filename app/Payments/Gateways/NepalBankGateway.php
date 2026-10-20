<?php

namespace App\Payments\Gateways;

use App\Models\Payment;
use App\Payments\CheckoutSession;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Exceptions\InvalidWebhookSignature;
use App\Payments\PaymentLog;
use App\Payments\PaymentResult;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Card payments through a Nepali acquiring bank (Himalayan Bank / NIC Asia etc.) that use
 * Cybersource Secure Acceptance Hosted Checkout: we render an auto-submitted, HMAC-SHA256
 * signed form POST to the bank's Cybersource endpoint; the customer pays on the hosted page
 * and is POSTed back (Customer Response Page = pay.return) and a Merchant POST notification
 * is sent server-to-server (= webhooks.payments/card). Both responses are signed the same way.
 *
 * PENDING FROM THE CLIENT'S BANK: merchant access_key / profile_id / secret_key, the live
 * endpoint, the allowed currency (USD vs NPR) and any bank-specific extra fields. Until
 * services.nepal_bank is configured, isAvailable() is false and the option is hidden.
 *
 * Signing scheme (public Cybersource docs): the value of `signed_field_names` lists the
 * signed fields in order; build "name=value" pairs joined by ",", HMAC-SHA256 with the
 * profile secret key, base64 encode.
 */
class NepalBankGateway implements PaymentGateway
{
    /** Fields we sign on the request (order matters: it is what signed_field_names lists). */
    public const SIGNED_FIELDS = [
        'access_key', 'profile_id', 'transaction_uuid', 'signed_field_names', 'unsigned_field_names',
        'signed_date_time', 'locale', 'transaction_type', 'reference_number', 'amount', 'currency',
        'override_custom_receipt_page', 'override_custom_cancel_page',
    ];

    public function key(): string
    {
        return 'card';
    }

    public function label(): string
    {
        return (string) config('services.nepal_bank.label', 'Credit / debit card');
    }

    public function isAvailable(): bool
    {
        return filled(config('services.nepal_bank.access_key'))
            && filled(config('services.nepal_bank.profile_id'))
            && filled(config('services.nepal_bank.secret_key'))
            && filled(config('services.nepal_bank.endpoint'));
    }

    public function createCheckout(Payment $payment): CheckoutSession
    {
        $fields = [
            'access_key' => (string) config('services.nepal_bank.access_key'),
            'profile_id' => (string) config('services.nepal_bank.profile_id'),
            'transaction_uuid' => (string) Str::uuid(),
            'signed_field_names' => implode(',', self::SIGNED_FIELDS),
            'unsigned_field_names' => '',
            'signed_date_time' => gmdate('Y-m-d\TH:i:s\Z'),
            'locale' => (string) config('services.nepal_bank.locale', 'en'),
            'transaction_type' => 'sale',
            'reference_number' => $payment->uuid,
            'amount' => number_format((float) $payment->amount, 2, '.', ''),
            'currency' => strtoupper($payment->currency),
            'override_custom_receipt_page' => route('pay.return', [$payment, $this->key()]),
            'override_custom_cancel_page' => route('pay.cancel', $payment),
        ];

        $fields['signature'] = self::sign($fields, (string) config('services.nepal_bank.secret_key'));

        PaymentLog::info('card.checkout.created', ['payment' => $payment->uuid, 'transaction_uuid' => $fields['transaction_uuid']]);

        return CheckoutSession::post((string) config('services.nepal_bank.endpoint'), $fields, $fields['transaction_uuid']);
    }

    public function handleReturn(Payment $payment, Request $request): PaymentResult
    {
        $data = $request->post();

        if (! self::verify($data, (string) config('services.nepal_bank.secret_key'))) {
            PaymentLog::warning('card.return.invalid_signature', ['payment' => $payment->uuid]);

            return PaymentResult::failed('We could not verify the response from the bank.');
        }

        return $this->resultFromResponse($data, $payment);
    }

    public function handleWebhook(Request $request): ?PaymentResult
    {
        $data = $request->post();

        if (! self::verify($data, (string) config('services.nepal_bank.secret_key'))) {
            PaymentLog::warning('card.webhook.invalid_signature');

            throw new InvalidWebhookSignature('Secure Acceptance signature verification failed.');
        }

        return $this->resultFromResponse($data);
    }

    /** @param array<string, mixed> $data verified response fields */
    public function resultFromResponse(array $data, ?Payment $payment = null): PaymentResult
    {
        $uuid = (string) ($data['req_reference_number'] ?? '');
        $reference = isset($data['transaction_id']) ? (string) $data['transaction_id'] : null;
        $payload = Arr::only($data, ['decision', 'reason_code', 'message', 'transaction_id', 'req_reference_number',
            'req_transaction_uuid', 'auth_code', 'auth_amount', 'req_amount', 'req_currency', 'req_card_type', 'signed_date_time']);

        PaymentLog::info('card.response', ['payment' => $uuid, 'decision' => $data['decision'] ?? null, 'reason_code' => $data['reason_code'] ?? null]);

        if ($payment && $uuid !== $payment->uuid) {
            return PaymentResult::mismatch('reference_number mismatch', $payload, $reference);
        }

        return match (strtoupper((string) ($data['decision'] ?? ''))) {
            'ACCEPT' => new PaymentResult(PaymentResult::PAID, (string) $reference, $uuid, $payload,
                amount: (string) ($data['req_amount'] ?? ''), currency: (string) ($data['req_currency'] ?? '')),
            'CANCEL' => PaymentResult::cancelled($payload, $uuid),
            'REVIEW' => PaymentResult::pending('Your payment is being reviewed by the bank.', $payload, $uuid, $reference),
            default => PaymentResult::failed((string) ($data['message'] ?? 'The bank declined the payment.'), $payload, $reference, $uuid),
        };
    }

    /** @param array<string, mixed> $fields must contain signed_field_names */
    public static function sign(array $fields, string $secretKey): string
    {
        $names = array_filter(explode(',', (string) ($fields['signed_field_names'] ?? '')));
        $pairs = array_map(fn (string $name) => $name.'='.($fields[$name] ?? ''), $names);

        return base64_encode(hash_hmac('sha256', implode(',', $pairs), $secretKey, true));
    }

    /** @param array<string, mixed> $fields response fields incl. signature + signed_field_names */
    public static function verify(array $fields, string $secretKey): bool
    {
        if ($secretKey === '' || empty($fields['signature']) || empty($fields['signed_field_names'])) {
            return false;
        }

        return hash_equals(self::sign($fields, $secretKey), (string) $fields['signature']);
    }
}
