<?php

namespace App\Notifications;

use App\Models\Lead;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Auto-reply to the traveller. */
class EnquiryReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Lead $lead) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $lead = $this->lead->loadMissing('trip', 'contact');
        $phone = Setting::get('company.phone');

        return (new MailMessage)
            ->subject('We have your enquiry — '.$lead->reference)
            ->greeting('Namaste '.$lead->contact->first_name.',')
            ->line('Thank you for reaching out to Wonder Himalaya. A trip specialist will reply personally within 24 hours (usually much sooner).')
            ->when($lead->trip, fn (MailMessage $m) => $m->line('Trip: **'.$lead->trip->title.'**'))
            ->line('Your reference: **'.$lead->reference.'**')
            ->line('If your plans are urgent, call or WhatsApp us on '.$phone.'.')
            ->salutation('Wander always, wonder often — the Wonder Himalaya team');
    }
}
