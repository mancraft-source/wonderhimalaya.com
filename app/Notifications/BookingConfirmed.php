<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Support\Money;
use App\Support\Notifications\NotificationSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent to the traveller when the deposit (or full amount) confirms the booking. */
class BookingConfirmed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Booking $booking) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $booking = $this->booking->loadMissing('trip', 'contact', 'departure');

        $values = ['first_name' => $booking->contact?->first_name, 'name' => $booking->contact?->name, 'booking_number' => $booking->number, 'trip' => $booking->trip?->title, 'start_date' => $booking->start_date?->format('j F Y')];

        $mail = (new MailMessage)
            ->subject(NotificationSettings::render('booking_confirmed', 'subject', $values))
            ->greeting('Namaste '.($booking->contact?->first_name ?? 'traveller').',')
            ->line(NotificationSettings::render('booking_confirmed', 'intro', $values))
            ->line('**Booking number:** '.$booking->number);

        if ($booking->trip) {
            $mail->line('**Trip:** '.$booking->trip->title);
        }

        if ($booking->start_date) {
            $mail->line('**Starts:** '.$booking->start_date->format('l j F Y'));
        }

        $mail->line('**Travellers:** '.$booking->pax)
            ->line('**Paid so far:** '.Money::format($booking->amount_paid, $booking->currency).' of '.Money::format($booking->total, $booking->currency));

        if ($booking->balanceDue() > 0 && $booking->balance_due_on) {
            $mail->line('The balance of '.Money::format($booking->balanceDue(), $booking->currency).' is due by '.$booking->balance_due_on->format('j F Y').'. We will send you a payment link beforehand.');
        }

        return $mail
            ->line('Next step: please send us each traveller\'s passport and insurance details so we can arrange permits.')
            ->action('Add traveller details', route('bookings.travellers.edit', $booking->uuid))
            ->salutation(NotificationSettings::render('booking_confirmed', 'signature', $values));
    }
}
