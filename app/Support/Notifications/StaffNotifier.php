<?php

namespace App\Support\Notifications;

use App\Models\User;
use App\Notifications\StaffAlert;
use Illuminate\Support\Facades\Notification;

/** Sends a StaffAlert to the people chosen under Settings → Notifications (or only to `$onlyTo`, e.g. a lead's new owner). */
final class StaffNotifier
{
    public static function send(StaffAlert $alert, ?User $onlyTo = null): void
    {
        $event = $alert->event;

        if (! NotificationSettings::enabled($event) || NotificationSettings::channels($event) === []) {
            return;
        }

        $users = $onlyTo ? collect([$onlyTo]) : NotificationSettings::staff($event);
        if ($users->isNotEmpty()) {
            Notification::send($users, $alert);
        }

        if ($onlyTo !== null) {
            return;
        }

        // Extra addresses (e.g. a shared sales inbox) always get the email, once.
        $alreadyEmailed = in_array(NotificationSettings::CHANNEL_MAIL, NotificationSettings::channels($event), true)
            ? $users->pluck('email')->map(fn (string $email) => strtolower($email))->all()
            : [];

        foreach (array_unique((array) NotificationSettings::event($event)['emails']) as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL) && ! in_array(strtolower($email), $alreadyEmailed, true)) {
                Notification::route('mail', $email)->notify($alert);
            }
        }
    }
}
