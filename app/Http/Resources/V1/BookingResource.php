<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** emergency_contact / internal notes are never exposed. @mixin \App\Models\Booking */
class BookingResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'status' => $this->status?->value,
            'status_label' => $this->status?->getLabel(),
            'lead_id' => $this->lead_id,
            'quote_id' => $this->quote_id,
            'contact_id' => $this->contact_id,
            'owner_id' => $this->owner_id,
            'trip' => $this->whenLoaded('trip', fn () => $this->trip ? ['id' => $this->trip->id, 'title' => $this->trip->title, 'slug' => $this->trip->slug] : null),
            'tier' => new TaxonomyResource($this->whenLoaded('tier')),
            'departure' => new DepartureResource($this->whenLoaded('departure')),
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'pax' => $this->pax,
            'currency' => $this->currency,
            'total' => $this->total,
            'deposit_amount' => $this->deposit_amount,
            'amount_paid' => $this->amount_paid,
            'balance_due' => $this->balanceDue(),
            'balance_due_on' => $this->balance_due_on?->toDateString(),
            'special_requests' => $this->special_requests,
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'contact' => new ContactResource($this->whenLoaded('contact')),
            'travellers' => TravellerResource::collection($this->whenLoaded('travellers')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
