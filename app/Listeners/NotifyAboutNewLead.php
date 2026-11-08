<?php

namespace App\Listeners;

use App\Events\LeadCreated;
use App\Notifications\EnquiryReceived;
use App\Notifications\StaffAlerts;
use App\Support\Notifications\NotificationSettings;
use App\Support\Notifications\StaffNotifier;
use Illuminate\Support\Facades\Notification;

class NotifyAboutNewLead
{
    public function handle(LeadCreated $event): void
    {
        $lead = $event->lead->loadMissing('contact');

        if (NotificationSettings::enabled('enquiry_received')) {
            Notification::route('mail', $lead->contact->email)->notify(new EnquiryReceived($lead));
        }

        StaffNotifier::send(StaffAlerts::newLead($lead));
    }
}
