<?php

namespace App\Http\Resources\V1;

use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Lead */
class LeadResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'type' => $this->type?->value,
            'status' => $this->status?->value,
            'status_label' => $this->status?->getLabel(),
            'contact_id' => $this->contact_id,
            'owner_id' => $this->owner_id,
            'trip' => $this->whenLoaded('trip', fn () => $this->trip ? ['id' => $this->trip->id, 'title' => $this->trip->title, 'slug' => $this->trip->slug] : null),
            'trip_departure_id' => $this->trip_departure_id,
            'tier_id' => $this->tier_id,
            'destinations' => $this->destinations ?? [],
            'interests' => $this->interests ?? [],
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'travel_month' => $this->travel_month,
            'date_flexibility' => $this->date_flexibility,
            'duration_days' => $this->duration_days,
            'adults' => $this->adults,
            'children' => $this->children,
            'budget' => $this->budget,
            'estimated_value' => $this->estimated_value,
            'currency' => $this->currency,
            'message' => $this->message,
            'source' => $this->source,
            'utm' => $this->utm ?? [],
            'landing_page' => $this->landing_page,
            'referrer' => $this->referrer,
            // Technical fingerprint data is restricted to admins.
            $this->mergeWhen((bool) $request->user()?->isAdmin(), fn () => [
                'ip_address' => $this->ip_address,
                'user_agent' => $this->user_agent,
            ]),
            'lost_reason' => $this->lost_reason,
            'next_follow_up_at' => $this->next_follow_up_at?->toIso8601String(),
            'converted_at' => $this->converted_at?->toIso8601String(),
            'contact' => new ContactResource($this->whenLoaded('contact')),
            'owner' => $this->whenLoaded('owner', fn () => $this->owner ? ['id' => $this->owner->id, 'name' => $this->owner->name] : null),
            'notes' => NoteResource::collection($this->whenLoaded('notes')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
