<?php

namespace App\Listeners;

use App\Events\BookingConfirmed;
use App\Webhooks\WebhookDispatcher;

/** booking.confirmed outbound webhook. */
class DispatchBookingConfirmedWebhook
{
    public function __construct(private readonly WebhookDispatcher $webhooks) {}

    public function handle(BookingConfirmed $event): void
    {
        $this->webhooks->dispatch('booking.confirmed', WebhookDispatcher::bookingPayload($event->booking));
    }
}
