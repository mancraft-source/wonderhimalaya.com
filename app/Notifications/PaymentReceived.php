<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\Payment;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Receipt e-mailed to the traveller after a successful payment. */
class PaymentReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Payment $payment) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $payment = $this->payment->loadMissing('payable');
        $booking = $payment->payable instanceof Booking ? $payment->payable->loadMissing('trip', 'contact') : null;
        $name = $booking?->contact?->first_name;

        $mail = (new MailMessage)
            ->subject('Payment received — '.Money::format($payment->amount, $payment->currency))
            ->greeting('Namaste'.($name ? ' '.$name : '').',')
            ->line('Thank you — we have received your payment.')
            ->line('**Amount:** '.Money::format($payment->amount, $payment->currency))
            ->line('**Paid on:** '.$payment->paid_at?->format('j M Y, H:i').' (UTC)')
            ->line('**Payment reference:** '.($payment->gateway_reference ?: $payment->uuid));

        if ($booking) {
            $mail->line('**Booking:** '.$booking->number.($booking->trip ? ' — '.$booking->trip->title : ''))
                ->line('**Balance remaining:** '.Money::format($booking->balanceDue(), $booking->currency));
        }

        return $mail->salutation('With gratitude — the Wonder Himalaya team');
    }
}
