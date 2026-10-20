<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\Payment;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Balance reminder with a secure payment link (bookings:send-balance-reminders). */
class BalancePaymentDue extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Booking $booking, public Payment $payment) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $booking = $this->booking->loadMissing('trip', 'contact');

        return (new MailMessage)
            ->subject('Balance due for your trip — '.$booking->number)
            ->greeting('Namaste '.($booking->contact?->first_name ?? 'traveller').',')
            ->line('Your adventure is getting close! The remaining balance for '.($booking->trip?->title ?? 'your trip').' is due'
                .($booking->balance_due_on ? ' by **'.$booking->balance_due_on->format('j F Y').'**' : '').'.')
            ->line('**Amount due:** '.Money::format($this->payment->amount, $this->payment->currency))
            ->action('Pay the balance securely', route('pay.show', $this->payment->uuid))
            ->line('Already paid by bank transfer? Please ignore this message — we will update your booking shortly.')
            ->salutation('The Wonder Himalaya team');
    }
}
