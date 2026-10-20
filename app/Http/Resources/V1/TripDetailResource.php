<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;

/** Full trip page payload. @mixin \App\Models\Trip */
class TripDetailResource extends TripResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'overview' => $this->overview,
            'min_age' => $this->min_age,
            'accommodation' => $this->accommodation,
            'meals' => $this->meals,
            'transport' => $this->transport,
            'start_point' => $this->start_point,
            'end_point' => $this->end_point,
            'highlights' => $this->highlights ?? [],
            'includes' => $this->includes ?? [],
            'excludes' => $this->excludes ?? [],
            'facts' => $this->facts ?? [],
            'videos' => $this->videos ?? [],
            'gallery' => MediaUrls::all($this->resource, 'gallery'),
            'map' => MediaUrls::first($this->resource, 'map'),
            'days' => $this->whenLoaded('days', fn () => $this->days->map(fn ($day) => [
                'day_number' => $day->day_number,
                'title' => $day->title,
                'description' => $day->description,
                'altitude_m' => $day->altitude_m,
                'distance_km' => $day->distance_km,
                'duration' => $day->duration,
                'meals' => $day->meals ?? [],
                'accommodation' => $day->accommodation,
            ])->values()),
            'faqs' => $this->whenLoaded('faqs', fn () => $this->faqs->map(fn ($faq) => [
                'question' => $faq->question,
                'answer' => $faq->answer,
            ])->values()),
            'prices' => $this->whenLoaded('prices', fn () => $this->prices->map(fn ($price) => [
                'tier' => $price->tier ? ['id' => $price->tier->id, 'name' => $price->tier->name, 'slug' => $price->tier->slug] : null,
                'label' => $price->label,
                'pax_min' => $price->pax_min,
                'pax_max' => $price->pax_max,
                'price' => $price->price,
                'sale_price' => $price->sale_price,
                'effective_price' => $price->effectivePrice(),
            ])->values()),
            'upcoming_departures' => DepartureResource::collection($this->whenLoaded('upcomingDepartures')),
            'related' => TripResource::collection($this->whenLoaded('related')),
            'seo' => SeoData::for($this->resource),
        ]);
    }
}
