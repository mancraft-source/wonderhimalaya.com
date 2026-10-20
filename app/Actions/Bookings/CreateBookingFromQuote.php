<?php

namespace App\Actions\Bookings;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Quote;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

/**
 * Copies an accepted quote into a booking (status pending deposit). Idempotent:
 * returns the existing booking when the quote was already converted.
 */
class CreateBookingFromQuote
{
    public function handle(Quote $quote): Booking
    {
        return DB::transaction(function () use ($quote) {
            if ($existing = Booking::where('quote_id', $quote->id)->first()) {
                return $existing;
            }

            $quote->loadMissing(['contact', 'lead', 'departure']);

            $startDate = $quote->start_date ?? $quote->departure?->start_date;
            $dueDays = (int) Setting::get('booking.balance_due_days', config('payments.balance_due_days', 30));

            $booking = Booking::create([
                'lead_id' => $quote->lead_id,
                'quote_id' => $quote->id,
                'contact_id' => $quote->contact_id,
                'company_id' => $quote->lead?->company_id ?? $quote->contact?->company_id,
                'trip_id' => $quote->trip_id,
                'tier_id' => $quote->tier_id,
                'trip_departure_id' => $quote->trip_departure_id,
                'owner_id' => $quote->lead?->owner_id ?? $quote->created_by_id,
                'status' => BookingStatus::PendingDeposit,
                'start_date' => $startDate,
                'end_date' => $quote->end_date ?? $quote->departure?->end_date,
                'pax' => max(1, (int) $quote->pax),
                'currency' => $quote->currency,
                'total' => $quote->total,
                'deposit_amount' => (float) $quote->deposit_amount > 0 ? $quote->deposit_amount : $quote->total,
                'amount_paid' => 0,
                'balance_due_on' => $startDate ? $startDate->copy()->subDays($dueDays) : null,
            ]);

            if ($contact = $quote->contact) {
                $booking->travellers()->create([
                    'is_lead' => true,
                    'first_name' => $contact->first_name,
                    'last_name' => $contact->last_name,
                    'email' => $contact->email,
                    'phone' => $contact->phone,
                    'nationality' => $contact->country,
                ]);
            }

            return $booking;
        });
    }
}
