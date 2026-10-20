<?php

namespace App\Listeners;

use App\Events\LeadCreated;
use App\Webhooks\WebhookDispatcher;

/** lead.created outbound webhook (HTTP calls are queued by WebhookDispatcher). */
class DispatchLeadWebhook
{
    public function __construct(private readonly WebhookDispatcher $webhooks) {}

    public function handle(LeadCreated $event): void
    {
        $this->webhooks->dispatch('lead.created', WebhookDispatcher::leadPayload($event->lead));
    }
}
