<?php

namespace App\Http\Requests;

use App\Enums\LeadType;
use Illuminate\Validation\Rule;

/** Single source of truth for enquiry validation (web forms, Livewire, API). */
final class EnquiryRules
{
    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(LeadType::class)],
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email:rfc,strict', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9 +().\-]{6,40}$/'],
            'whatsapp' => ['nullable', 'string', 'max:40', 'regex:/^[0-9 +().\-]{6,40}$/'],
            'country' => ['nullable', 'string', 'max:80'],
            'company_name' => ['nullable', 'required_if:type,b2b', 'string', 'max:160'],
            'company_website' => ['nullable', 'url:http,https', 'max:255'],
            'company_type' => ['nullable', 'string', 'max:40'],
            'trip_id' => ['nullable', 'integer', 'exists:trips,id'],
            'tier_id' => ['nullable', 'integer', 'exists:tiers,id'],
            'trip_departure_id' => ['nullable', 'integer', 'exists:trip_departures,id'],
            'destinations' => ['nullable', 'array', 'max:12'],
            'destinations.*' => ['string', 'max:80'],
            'interests' => ['nullable', 'array', 'max:20'],
            'interests.*' => ['string', 'max:80'],
            'start_date' => ['nullable', 'date', 'after_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'travel_month' => ['nullable', 'string', 'max:40'],
            'date_flexibility' => ['nullable', 'string', 'max:60'],
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:90'],
            'adults' => ['nullable', 'integer', 'min:1', 'max:100'],
            'children' => ['nullable', 'integer', 'min:0', 'max:50'],
            'budget' => ['nullable', 'string', 'max:60'],
            'message' => ['nullable', 'string', 'max:5000'],
            'marketing_consent' => ['nullable', 'boolean'],
            'privacy_accepted' => ['accepted'],
            'utm' => ['nullable', 'array'],
            'utm.*' => ['nullable', 'string', 'max:190'],
            'source' => ['nullable', 'string', 'max:60'],
            'landing_page' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
