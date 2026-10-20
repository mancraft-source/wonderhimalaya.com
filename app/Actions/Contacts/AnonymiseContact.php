<?php

namespace App\Actions\Contacts;

use App\Models\Contact;
use Illuminate\Support\Facades\DB;

/**
 * GDPR erasure: blanks personal data on the contact, their enquiries and
 * travellers while keeping financial records (bookings, payments) intact.
 */
class AnonymiseContact
{
    public function handle(Contact $contact): void
    {
        DB::transaction(function () use ($contact): void {
            $contact->forceFill([
                'first_name' => 'Anonymised',
                'last_name' => null,
                'email' => 'anonymised+'.$contact->id.'@invalid.invalid',
                'phone' => null,
                'whatsapp' => null,
                'notes' => null,
                'marketing_consent' => false,
                'consent_at' => null,
            ])->save();

            $contact->leads()->withTrashed()->update([
                'message' => null,
                'ip_address' => null,
                'user_agent' => null,
                'referrer' => null,
                'utm' => null,
            ]);

            foreach ($contact->bookings()->withTrashed()->with('travellers')->get() as $booking) {
                $booking->forceFill(['emergency_contact' => null, 'special_requests' => null])->save();

                foreach ($booking->travellers as $traveller) {
                    $traveller->forceFill([
                        'first_name' => 'Traveller',
                        'last_name' => null,
                        'email' => null,
                        'phone' => null,
                        'date_of_birth' => null,
                        'passport_number' => null,
                        'passport_expiry' => null,
                        'insurance_provider' => null,
                        'insurance_policy' => null,
                        'medical_notes' => null,
                        'dietary' => null,
                    ])->save();
                }
            }

            $contact->notes()->delete();
        });
    }
}
