<?php

namespace App\Actions\Contacts;

use App\Models\Contact;

/** GDPR subject-access export of everything held about a contact. */
class ContactDataExport
{
    /** @return array<string, mixed> */
    public function handle(Contact $contact): array
    {
        $contact->loadMissing(['company', 'leads.trip', 'quotes.items', 'bookings.travellers', 'bookings.trip', 'payments', 'notes']);

        return [
            'exported_at' => now()->toIso8601String(),
            'contact' => $contact->only(['id', 'first_name', 'last_name', 'email', 'phone', 'whatsapp', 'country', 'source', 'marketing_consent', 'consent_at', 'created_at', 'updated_at']),
            'company' => $contact->company?->only(['id', 'name', 'country', 'website', 'email', 'phone']),
            'leads' => $contact->leads->map(fn ($lead) => [
                ...$lead->only(['reference', 'type', 'status', 'start_date', 'end_date', 'travel_month', 'adults', 'children', 'budget', 'message', 'source', 'utm', 'referrer', 'landing_page', 'ip_address', 'user_agent', 'created_at']),
                'trip' => $lead->trip?->title,
            ])->all(),
            'quotes' => $contact->quotes->map(fn ($quote) => [
                ...$quote->only(['number', 'title', 'status', 'start_date', 'end_date', 'pax', 'currency', 'total', 'valid_until', 'created_at']),
                'items' => $quote->items->map->only(['description', 'quantity', 'unit_price', 'total'])->all(),
            ])->all(),
            'bookings' => $contact->bookings->map(fn ($booking) => [
                ...$booking->only(['number', 'status', 'start_date', 'end_date', 'pax', 'currency', 'total', 'amount_paid', 'special_requests', 'emergency_contact', 'created_at']),
                'trip' => $booking->trip?->title,
                'travellers' => $booking->travellers->map(fn ($t) => [
                    'first_name' => $t->first_name,
                    'last_name' => $t->last_name,
                    'email' => $t->email,
                    'phone' => $t->phone,
                    'nationality' => $t->nationality,
                    'date_of_birth' => $t->date_of_birth,
                    'passport_number' => $t->passport_number,
                    'passport_expiry' => $t->passport_expiry,
                    'insurance_provider' => $t->insurance_provider,
                    'insurance_policy' => $t->insurance_policy,
                    'medical_notes' => $t->medical_notes,
                    'dietary' => $t->dietary,
                ])->all(),
            ])->all(),
            'payments' => $contact->payments->map->only(['uuid', 'type', 'amount', 'currency', 'status', 'gateway', 'paid_at', 'created_at'])->all(),
        ];
    }
}
