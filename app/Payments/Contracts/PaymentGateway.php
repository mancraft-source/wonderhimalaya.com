<?php

namespace App\Payments\Contracts;

use App\Models\Payment;
use App\Payments\CheckoutSession;
use App\Payments\Exceptions\InvalidWebhookSignature;
use App\Payments\PaymentResult;
use Illuminate\Http\Request;

interface PaymentGateway
{
    /** Stable key used in URLs and stored on payments.gateway (e.g. "paypal"). */
    public function key(): string;

    /** Customer-facing name. */
    public function label(): string;

    /** True when the gateway is configured and may be offered. */
    public function isAvailable(): bool;

    /**
     * Start a checkout for the payment. The amount/currency MUST be read from the
     * Payment record, never from request input.
     */
    public function createCheckout(Payment $payment): CheckoutSession;

    /** Customer returned from the gateway (redirect / browser POST). Verify before trusting. */
    public function handleReturn(Payment $payment, Request $request): PaymentResult;

    /**
     * Server-to-server notification. Returns null when the event is irrelevant.
     *
     * @throws InvalidWebhookSignature
     */
    public function handleWebhook(Request $request): ?PaymentResult;
}
