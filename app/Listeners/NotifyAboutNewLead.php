<?php

namespace App\Listeners;

use App\Events\LeadCreated;
use App\Models\Setting;
use App\Notifications\EnquiryReceived;
use App\Notifications\NewLeadAlert;
use Illuminate\Support\Facades\Notification;

class NotifyAboutNewLead
{
    public function handle(LeadCreated $event): void
    {
        $lead = $event->lead->loadMissing('contact');

        Notification::route('mail', $lead->contact->email)->notify(new EnquiryReceived($lead));

        if ($salesEmail = Setting::get('notifications.sales_email')) {
            Notification::route('mail', $salesEmail)->notify(new NewLeadAlert($lead));
        }
    }
}
