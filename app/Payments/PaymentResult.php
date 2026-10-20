<?php

namespace App\Payments;

use App\Models\Payment;

/** Verified outcome of a gateway return or webhook. */
final readonly class PaymentResult
{
    public const PAID = 'paid';

    public const FAILED = 'failed';

    public const CANCELLED = 'cancelled';

    public const PENDING = 'pending';

    /** Gateway confirmed money but it does not match our record (amount/currency/reference). */
    public const MISMATCH = 'mismatch';

    /**
     * @param  array<string, mixed>  $payload  sanitized gateway response (no secrets / card data)
     */
    public function __construct(
        public string $status,
        public ?string $reference = null,
        public ?string $paymentUuid = null,
        public array $payload = [],
        public ?string $message = null,
        // Amount/currency reported by the gateway, when the gateway (e.g. a webhook) is not
        // bound to a Payment yet. PaymentService re-checks them against the Payment record.
        public ?string $amount = null,
        public ?string $currency = null,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function paid(string $reference, array $payload = [], ?string $paymentUuid = null): self
    {
        return new self(self::PAID, $reference, $paymentUuid, $payload);
    }

    /** @param array<string, mixed> $payload */
    public static function failed(?string $message, array $payload = [], ?string $reference = null, ?string $paymentUuid = null): self
    {
        return new self(self::FAILED, $reference, $paymentUuid, $payload, $message);
    }

    /** @param array<string, mixed> $payload */
    public static function cancelled(array $payload = [], ?string $paymentUuid = null): self
    {
        return new self(self::CANCELLED, null, $paymentUuid, $payload, 'Cancelled by customer');
    }

    /** @param array<string, mixed> $payload */
    public static function pending(?string $message = null, array $payload = [], ?string $paymentUuid = null, ?string $reference = null): self
    {
        return new self(self::PENDING, $reference, $paymentUuid, $payload, $message);
    }

    /** @param array<string, mixed> $payload */
    public static function mismatch(string $message, array $payload = [], ?string $reference = null, ?string $paymentUuid = null): self
    {
        return new self(self::MISMATCH, $reference, $paymentUuid, $payload, $message);
    }

    public function isPaid(): bool
    {
        return $this->status === self::PAID;
    }

    /** Compare a gateway-reported amount/currency against the Payment record. */
    public static function amountMatches(Payment $payment, mixed $amount, mixed $currency): bool
    {
        return is_numeric($amount)
            && (int) round((float) $amount * 100) === (int) round((float) $payment->amount * 100)
            && strtoupper((string) $currency) === strtoupper((string) $payment->currency);
    }
}
