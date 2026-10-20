<?php

namespace App\Actions\Quotes;

use App\Enums\LeadStatus;
use App\Enums\QuoteStatus;
use App\Models\Lead;
use App\Models\Quote;
use App\Models\User;
use App\Support\QuoteDefaults;
use Illuminate\Support\Facades\DB;

/**
 * Drafts a quote from an enquiry: contact, trip, tier, departure and party size
 * are copied over, with one "per person" line priced from the departure or trip.
 */
class CreateQuoteFromLead
{
    public function handle(Lead $lead, ?User $by = null): Quote
    {
        return DB::transaction(function () use ($lead, $by): Quote {
            $lead->loadMissing(['trip', 'departure.trip', 'contact']);

            $pax = max(1, $lead->travellerCount());
            $unitPrice = $lead->departure?->effectivePrice() ?? $lead->trip?->displayPrice() ?? 0;
            $currency = $lead->trip?->currency ?: ($lead->currency ?: QuoteDefaults::currency());

            $quote = Quote::create([
                'lead_id' => $lead->id,
                'contact_id' => $lead->contact_id,
                'trip_id' => $lead->trip_id,
                'tier_id' => $lead->tier_id,
                'trip_departure_id' => $lead->trip_departure_id,
                'created_by_id' => $by?->id,
                'title' => $lead->trip?->title ?? 'Tailor-made trip',
                'status' => QuoteStatus::Draft,
                'start_date' => $lead->departure?->start_date ?? $lead->start_date,
                'end_date' => $lead->departure?->end_date ?? $lead->end_date,
                'pax' => $pax,
                'currency' => $currency,
                'discount' => 0,
                'tax' => 0,
                'deposit_percent' => QuoteDefaults::depositPercent(),
                'valid_until' => now()->addDays(14)->toDateString(),
                'terms' => QuoteDefaults::terms(),
            ]);

            $quote->items()->create([
                'description' => 'Trip price per person',
                'quantity' => $pax,
                'unit_price' => $unitPrice,
                'sort' => 0,
            ]);

            $quote->unsetRelation('items');
            $quote->recalculate();
            $quote->save();

            if (in_array($lead->status, [LeadStatus::New, LeadStatus::Contacted, LeadStatus::Qualified], true)) {
                $lead->update(['status' => LeadStatus::Quoted]);
            }

            return $quote;
        });
    }
}
