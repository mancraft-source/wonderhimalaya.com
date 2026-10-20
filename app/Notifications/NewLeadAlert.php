<?php

namespace App\Notifications;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Alert to the sales inbox / staff. */
class NewLeadAlert extends Notification implements ShouldQueue
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
        $lead = $this->lead->loadMissing('trip', 'contact', 'company');

        return (new MailMessage)
            ->subject('New '.$lead->type->getLabel().' lead '.$lead->reference.' — '.$lead->contact->name)
            ->line('**'.$lead->contact->name.'** ('.$lead->contact->email.', '.($lead->contact->country ?? 'country n/a').')')
            ->when($lead->company, fn (MailMessage $m) => $m->line('Company: '.$lead->company->name))
            ->when($lead->trip, fn (MailMessage $m) => $m->line('Trip: '.$lead->trip->title))
            ->line('Travellers: '.$lead->travellerCount().($lead->start_date ? ' · Start: '.$lead->start_date->toFormattedDateString() : ''))
            ->when($lead->message, fn (MailMessage $m) => $m->line('> '.str($lead->message)->limit(600)))
            ->action('Open in CRM', url('/admin/leads/'.$lead->id));
    }
}
