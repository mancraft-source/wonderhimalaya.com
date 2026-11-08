<?php

namespace App\Notifications;

use App\Models\Lead;
use App\Models\Setting;
use App\Support\Notifications\NotificationSettings;
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
        $values = ['first_name' => $lead->contact->first_name, 'name' => $lead->contact->name, 'reference' => $lead->reference, 'trip' => $lead->trip?->title, 'phone' => $phone];

        return (new MailMessage)
            ->subject(NotificationSettings::render('enquiry_received', 'subject', $values))
            ->greeting('Namaste '.$lead->contact->first_name.',')
            ->line(NotificationSettings::render('enquiry_received', 'intro', $values))
            ->when($lead->trip, fn (MailMessage $m) => $m->line('Trip: **'.$lead->trip->title.'**'))
            ->line('Your reference: **'.$lead->reference.'**')
            ->line('If your plans are urgent, call or WhatsApp us on '.$phone.'.')
            ->salutation(NotificationSettings::render('enquiry_received', 'signature', $values))
            ->when(filled($salesEmail = Setting::get('notifications.sales_email')), fn (MailMessage $m) => $m->replyTo($salesEmail, config('app.name')));
    }
}
