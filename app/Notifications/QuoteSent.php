<?php

namespace App\Notifications;

use App\Models\Quote;
use App\Support\Money;
use App\Support\Notifications\NotificationSettings;
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

        $values = [
            'first_name' => $quote->contact?->first_name, 'name' => $quote->contact?->name, 'quote_title' => $quote->title,
            'total' => Money::format($quote->total, $quote->currency), 'valid_until' => $quote->valid_until?->format('j F Y'),
        ];

        $mail = (new MailMessage)
            ->subject(NotificationSettings::render('quote_sent', 'subject', $values))
            ->greeting('Namaste '.($quote->contact?->first_name ?? 'traveller').',');

        if ($this->personalMessage) {
            $mail->line($this->personalMessage);
        } else {
            $mail->line(NotificationSettings::render('quote_sent', 'intro', $values));
        }

        $mail->line('**'.$quote->title.'** — '.Money::format($quote->total, $quote->currency).' total');

        if ($quote->valid_until) {
            $mail->line('This quote is valid until '.$quote->valid_until->format('j F Y').'.');
        }

        return $mail
            ->action('View your quote', route('quotes.show', $quote->uuid))
            ->line('You can accept it and pay the deposit securely online, or simply reply to this e-mail with any questions.')
            ->salutation(($quote->creator?->name ? $quote->creator->name.' — ' : '').NotificationSettings::render('quote_sent', 'signature', $values));
    }
}
