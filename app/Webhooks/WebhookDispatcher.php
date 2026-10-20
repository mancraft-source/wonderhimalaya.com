<?php

namespace App\Webhooks;

use App\Models\Booking;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\WebhookServer\WebhookCall;

/**
 * Outbound webhooks (Zapier, Make, partner CRMs...). Endpoints are configured in the
 * `webhooks` setting: [{url, secret, events: ["lead.created", "booking.confirmed", "payment.succeeded" | "*"]}].
 *
 * Every call is queued, POSTed as JSON and signed by spatie/laravel-webhook-server's
 * DefaultSigner: header `Signature` = hash_hmac('sha256', <raw body>, <endpoint secret>).
 *
 * Body: {"id": "<uuid>", "event": "lead.created", "occurred_at": "<ISO-8601>", "data": {...}}
 * Payloads are deliberately small and contain no sensitive data — fetch details via the API.
 */
class WebhookDispatcher
{
    public const EVENTS = ['lead.created', 'booking.confirmed', 'payment.succeeded'];

    /** @param array<string, mixed> $data */
    public function dispatch(string $event, array $data): int
    {
        $sent = 0;

        foreach ($this->endpointsFor($event) as $endpoint) {
            WebhookCall::create()
                ->url($endpoint['url'])
                ->payload([
                    'id' => (string) Str::uuid(),
                    'event' => $event,
                    'occurred_at' => now()->toIso8601String(),
                    'data' => $data,
                ])
                ->useSecret($endpoint['secret'])
                ->withHeaders(['X-Wonder-Event' => $event])
                ->meta(['event' => $event])
                ->dispatch();

            $sent++;
        }

        return $sent;
    }

    /** @return array<int, array{url: string, secret: string, events: array<int, string>}> */
    public function endpointsFor(string $event): array
    {
        $configured = Setting::get('webhooks', []);

        return collect(is_array($configured) ? $configured : [])
            ->filter(function ($endpoint) use ($event) {
                if (! is_array($endpoint) || empty($endpoint['url']) || empty($endpoint['secret'])) {
                    return false;
                }

                if (! filter_var($endpoint['url'], FILTER_VALIDATE_URL) || (! str_starts_with($endpoint['url'], 'https://') && app()->isProduction())) {
                    Log::warning('Skipping invalid webhook endpoint', ['url' => $endpoint['url']]);

                    return false;
                }

                $events = (array) ($endpoint['events'] ?? []);

                return $events === [] || in_array('*', $events, true) || in_array($event, $events, true);
            })
            ->map(fn (array $e) => ['url' => (string) $e['url'], 'secret' => (string) $e['secret'], 'events' => (array) ($e['events'] ?? [])])
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    public static function leadPayload(Lead $lead): array
    {
        $lead->loadMissing('trip');

        return [
            'id' => $lead->id,
            'reference' => $lead->reference,
            'type' => $lead->type?->value,
            'status' => $lead->status?->value,
            'trip' => $lead->trip ? ['id' => $lead->trip->id, 'slug' => $lead->trip->slug, 'title' => $lead->trip->title] : null,
            'adults' => $lead->adults,
            'children' => $lead->children,
            'start_date' => $lead->start_date?->toDateString(),
            'travel_month' => $lead->travel_month,
            'source' => $lead->source,
            'created_at' => $lead->created_at?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    public static function bookingPayload(Booking $booking): array
    {
        $booking->loadMissing('trip');

        return [
            'id' => $booking->id,
            'number' => $booking->number,
            'status' => $booking->status?->value,
            'trip' => $booking->trip ? ['id' => $booking->trip->id, 'slug' => $booking->trip->slug, 'title' => $booking->trip->title] : null,
            'start_date' => $booking->start_date?->toDateString(),
            'pax' => $booking->pax,
            'currency' => $booking->currency,
            'total' => (string) $booking->total,
            'amount_paid' => (string) $booking->amount_paid,
            'lead_reference' => $booking->lead_id ? Lead::whereKey($booking->lead_id)->value('reference') : null,
        ];
    }

    /** @return array<string, mixed> */
    public static function paymentPayload(Payment $payment): array
    {
        $payable = $payment->payable;

        return [
            'uuid' => $payment->uuid,
            'type' => $payment->type?->value,
            'gateway' => $payment->gateway,
            'amount' => (string) $payment->amount,
            'currency' => $payment->currency,
            'paid_at' => $payment->paid_at?->toIso8601String(),
            'booking_number' => $payable instanceof Booking ? $payable->number : null,
        ];
    }
}
