<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Support\Money;
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

        $mail = (new MailMessage)
            ->subject('Your booking is confirmed — '.$booking->number)
            ->greeting('Namaste '.($booking->contact?->first_name ?? 'traveller').',')
            ->line('Wonderful news: your booking is confirmed. We cannot wait to welcome you to the Himalaya.')
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
            ->salutation('See you in the mountains — the Wonder Himalaya team');
    }
}
