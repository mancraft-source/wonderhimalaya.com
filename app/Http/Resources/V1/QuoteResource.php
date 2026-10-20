<?php

namespace App\Http\Resources\V1;

use App\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Quote */
class QuoteResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'title' => $this->title,
            'status' => $this->status?->value,
            'status_label' => $this->status?->getLabel(),
            'lead_id' => $this->lead_id,
            'contact_id' => $this->contact_id,
            'trip' => $this->whenLoaded('trip', fn () => $this->trip ? ['id' => $this->trip->id, 'title' => $this->trip->title, 'slug' => $this->trip->slug] : null),
            'tier_id' => $this->tier_id,
            'trip_departure_id' => $this->trip_departure_id,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'pax' => $this->pax,
            'currency' => $this->currency,
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'tax' => $this->tax,
            'total' => $this->total,
            'deposit_percent' => $this->deposit_percent,
            'deposit_amount' => $this->deposit_amount,
            'valid_until' => $this->valid_until?->toDateString(),
            'is_acceptable' => $this->isAcceptable(),
            'url' => route('quotes.show', $this->uuid),
            'introduction' => $this->introduction,
            'terms' => $this->terms,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total' => $item->total,
            ])->values()),
            'contact' => new ContactResource($this->whenLoaded('contact')),
            'sent_at' => $this->sent_at?->toIso8601String(),
            'viewed_at' => $this->viewed_at?->toIso8601String(),
            'accepted_at' => $this->accepted_at?->toIso8601String(),
            'declined_at' => $this->declined_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
