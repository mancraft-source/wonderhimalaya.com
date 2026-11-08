<?php

namespace App\Notifications;

use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\Leads\LeadResource;
use App\Models\Booking;
use App\Models\Lead;
use App\Models\Payment;
use App\Support\Money;
use App\Support\Notifications\NotificationSettings;
use App\Support\UserAgent;

/** Builds the staff alerts; subjects come from the templates under Settings → Notifications. */
final class StaffAlerts
{
    public static function newLead(Lead $lead): StaffAlert
    {
        $lead->loadMissing('trip', 'contact', 'company');
        $contact = $lead->contact;

        return new StaffAlert(
            'new_lead',
            NotificationSettings::render('new_lead', 'subject', [
                'type' => $lead->type->getLabel(), 'reference' => $lead->reference, 'name' => $contact->name,
                'email' => $contact->email, 'trip' => $lead->trip?->title, 'country' => $contact->country,
            ]),
            array_values(array_filter([
                '**'.$contact->name.'** ('.$contact->email.', '.($contact->country ?? 'country n/a').')',
                $lead->company ? 'Company: '.$lead->company->name : null,
                $lead->trip ? 'Trip: '.$lead->trip->title : null,
                'Travellers: '.$lead->travellerCount().($lead->start_date ? ' · Start: '.$lead->start_date->toFormattedDateString() : ''),
                $lead->message ? '> '.str($lead->message)->limit(600) : null,
                $lead->form_page ? 'Submitted from: '.$lead->form_page : null,
                'Source: '.$lead->source.($lead->ip_address ? ' · IP '.$lead->ip_address : '').($lead->user_agent ? ' · '.UserAgent::summary($lead->user_agent) : ''),
            ])),
            'Open in CRM',
            LeadResource::getUrl('view', ['record' => $lead], panel: 'admin'),
            $contact->email,
            $contact->name,
        );
    }

    public static function leadAssigned(Lead $lead): StaffAlert
    {
        $lead->loadMissing('trip', 'contact');

        return new StaffAlert(
            'lead_assigned',
            NotificationSettings::render('lead_assigned', 'subject', ['reference' => $lead->reference, 'name' => $lead->contact?->name, 'trip' => $lead->trip?->title]),
            array_values(array_filter([
                '**'.$lead->contact?->name.'** · '.$lead->type->getLabel().($lead->trip ? ' · '.$lead->trip->title : ''),
                $lead->next_follow_up_at ? 'Follow up by '.$lead->next_follow_up_at->format('j M, H:i') : null,
            ])),
            'Open lead',
            LeadResource::getUrl('view', ['record' => $lead], panel: 'admin'),
        );
    }

    public static function paymentReceived(Payment $payment): StaffAlert
    {
        $payment->loadMissing('contact', 'payable');
        $booking = $payment->payable instanceof Booking ? $payment->payable->loadMissing('trip') : null;
        $name = $payment->contact?->name ?? $payment->payable?->contact?->name;
        $amount = Money::format($payment->amount, $payment->currency);

        return new StaffAlert(
            'payment_received_staff',
            NotificationSettings::render('payment_received_staff', 'subject', ['amount' => $amount, 'name' => $name, 'booking_number' => $booking?->number, 'trip' => $booking?->trip?->title]),
            array_values(array_filter([
                '**'.$amount.'** from '.($name ?? 'a customer').' via '.$payment->gateway,
                $booking ? 'Booking '.$booking->number.($booking->trip ? ' — '.$booking->trip->title : '').' · balance '.Money::format($booking->balanceDue(), $booking->currency) : null,
                'Reference: '.($payment->gateway_reference ?: $payment->uuid),
            ])),
            $booking ? 'Open booking' : 'Open payments',
            $booking ? BookingResource::getUrl('edit', ['record' => $booking], panel: 'admin') : url('/admin/payments'),
        );
    }

    public static function bookingConfirmed(Booking $booking): StaffAlert
    {
        $booking->loadMissing('trip', 'contact');

        return new StaffAlert(
            'booking_confirmed_staff',
            NotificationSettings::render('booking_confirmed_staff', 'subject', [
                'booking_number' => $booking->number, 'trip' => $booking->trip?->title ?? 'trip', 'name' => $booking->contact?->name, 'start_date' => $booking->start_date?->format('j M Y'),
            ]),
            array_values(array_filter([
                '**'.$booking->contact?->name.'** · '.$booking->pax.' '.str('traveller')->plural($booking->pax).($booking->start_date ? ' · starts '.$booking->start_date->format('j M Y') : ''),
                'Paid '.Money::format($booking->amount_paid, $booking->currency).' of '.Money::format($booking->total, $booking->currency),
            ])),
            'Open booking',
            BookingResource::getUrl('edit', ['record' => $booking], panel: 'admin'),
        );
    }
}
