<?php

namespace App\Listeners;

use App\Events\PaymentSucceeded;
use App\Webhooks\WebhookDispatcher;

/** payment.succeeded outbound webhook. */
class DispatchPaymentWebhook
{
    public function __construct(private readonly WebhookDispatcher $webhooks) {}

    public function handle(PaymentSucceeded $event): void
    {
        $this->webhooks->dispatch('payment.succeeded', WebhookDispatcher::paymentPayload($event->payment));
    }
}
