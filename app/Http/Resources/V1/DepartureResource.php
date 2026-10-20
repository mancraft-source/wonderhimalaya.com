<?php

namespace App\Http\Resources\V1;

use App\Models\TripDeparture;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TripDeparture */
class DepartureResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'trip_id' => $this->trip_id,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'price' => $this->price,
            'sale_price' => $this->sale_price,
            'effective_price' => $this->effectivePrice(),
            'seats_total' => $this->seats_total,
            'seats_left' => $this->seatsLeft(),
            'status' => $this->status?->value,
            'status_label' => $this->status?->getLabel(),
            'is_bookable' => $this->isBookable(),
            'note' => $this->note,
            'tier' => new TaxonomyResource($this->whenLoaded('tier')),
            'trip' => new TripResource($this->whenLoaded('trip')),
        ];
    }
}
