<?php

namespace App\Actions\Leads;

use App\Enums\CompanyType;
use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Events\LeadCreated;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Trip;
use App\Models\TripDeparture;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Turns a validated enquiry (see EnquiryRules) into a CRM contact + lead.
 * Used by the website forms, the tailor-made builder and the public API.
 */
class SubmitEnquiry
{
    /**
     * @param  array<string, mixed>  $data  validated input
     * @param  array{ip?: ?string, user_agent?: ?string, referrer?: ?string}  $meta
     */
    public function handle(array $data, array $meta = []): Lead
    {
        $lead = DB::transaction(function () use ($data, $meta) {
            $type = LeadType::from($data['type']);
            $company = $this->resolveCompany($type, $data);
            $contact = $this->resolveContact($data, $company);

            $departure = isset($data['trip_departure_id']) ? TripDeparture::find($data['trip_departure_id']) : null;
            $tripId = $data['trip_id'] ?? $departure?->trip_id;
            $adults = (int) ($data['adults'] ?? 1);

            return Lead::create([
                'type' => $type,
                'status' => LeadStatus::New,
                'contact_id' => $contact->id,
                'company_id' => $company?->id,
                'trip_id' => $tripId,
                'tier_id' => $data['tier_id'] ?? null,
                'trip_departure_id' => $departure?->id,
                'destinations' => $data['destinations'] ?? null,
                'interests' => $data['interests'] ?? null,
                'start_date' => $data['start_date'] ?? $departure?->start_date,
                'end_date' => $data['end_date'] ?? $departure?->end_date,
                'travel_month' => $data['travel_month'] ?? null,
                'date_flexibility' => $data['date_flexibility'] ?? null,
                'duration_days' => $data['duration_days'] ?? null,
                'adults' => $adults,
                'children' => (int) ($data['children'] ?? 0),
                'budget' => $data['budget'] ?? null,
                'estimated_value' => $this->estimateValue($tripId, $departure, $adults + (int) ($data['children'] ?? 0), $data['tier_id'] ?? null),
                'message' => $data['message'] ?? null,
                'source' => $data['source'] ?? 'website',
                'utm' => Arr::only($data['utm'] ?? [], ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'fbclid']) ?: null,
                'landing_page' => $data['landing_page'] ?? null,
                'referrer' => isset($meta['referrer']) ? substr((string) $meta['referrer'], 0, 1000) : null,
                'ip_address' => $meta['ip'] ?? null,
                'user_agent' => isset($meta['user_agent']) ? substr((string) $meta['user_agent'], 0, 500) : null,
                'next_follow_up_at' => now()->addHours(4),
            ]);
        });

        LeadCreated::dispatch($lead);

        return $lead;
    }

    /** @param array<string, mixed> $data */
    private function resolveContact(array $data, ?Company $company): Contact
    {
        $contact = Contact::firstOrNew(['email' => strtolower(trim($data['email']))]);

        // Never overwrite existing details with blanks; fill gaps only.
        foreach (['first_name', 'last_name', 'phone', 'whatsapp', 'country'] as $field) {
            if (! empty($data[$field]) && (empty($contact->{$field}) || $field === 'phone')) {
                $contact->{$field} = $data[$field];
            }
        }

        $contact->company_id ??= $company?->id;
        $contact->source ??= $data['source'] ?? 'website';

        if (! empty($data['marketing_consent']) && ! $contact->marketing_consent) {
            $contact->marketing_consent = true;
            $contact->consent_at = now();
        }

        $contact->save();

        return $contact;
    }

    /** @param array<string, mixed> $data */
    private function resolveCompany(LeadType $type, array $data): ?Company
    {
        if ($type !== LeadType::B2B || empty($data['company_name'])) {
            return null;
        }

        return Company::firstOrCreate(
            ['name' => trim($data['company_name'])],
            [
                'type' => CompanyType::tryFrom($data['company_type'] ?? '') ?? CompanyType::Agency,
                'website' => $data['company_website'] ?? null,
                'country' => $data['country'] ?? null,
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
            ],
        );
    }

    /** Departure price when joining a group, otherwise the trip's group-pricing band for this party size. */
    private function estimateValue(?int $tripId, ?TripDeparture $departure, int $travellers, ?int $tierId): ?float
    {
        $travellers = max(1, $travellers);
        $price = $departure?->effectivePrice() ?? ($tripId ? Trip::with('prices')->find($tripId)?->priceFor($travellers, $tierId) : null);

        return $price ? round($price * $travellers, 2) : null;
    }
}
