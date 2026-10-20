<?php

namespace App\Notifications;

use App\Models\Quote;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** E-mails the traveller a link to their online quote (see App\Actions\Quotes\SendQuote). */
class QuoteSent extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Quote $quote, public ?string $personalMessage = null) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $quote = $this->quote->loadMissing('contact', 'trip', 'creator');

        $mail = (new MailMessage)
            ->subject('Your Wonder Himalaya proposal — '.$quote->title)
            ->greeting('Namaste '.($quote->contact?->first_name ?? 'traveller').',');

        if ($this->personalMessage) {
            $mail->line($this->personalMessage);
        } else {
            $mail->line('Thank you for planning your journey with us. Your personalised proposal is ready.');
        }

        $mail->line('**'.$quote->title.'** — '.Money::format($quote->total, $quote->currency).' total');

        if ($quote->valid_until) {
            $mail->line('This quote is valid until '.$quote->valid_until->format('j F Y').'.');
        }

        return $mail
            ->action('View your quote', route('quotes.show', $quote->uuid))
            ->line('You can accept it and pay the deposit securely online, or simply reply to this e-mail with any questions.')
            ->salutation(($quote->creator?->name ? $quote->creator->name.' — ' : '').'Wonder Himalaya');
    }
}
