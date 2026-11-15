<?php

namespace App\Support\Notifications;

use App\Models\User;
use App\Notifications\StaffAlert;
use Illuminate\Support\Facades\Notification;

/**
 * Sends a StaffAlert to the people chosen under Settings → Notifications (or only to `$onlyTo`, e.g. a lead's new owner),
 * skipping anyone who switched the alert off on their profile.
 */
final class StaffNotifier
{
    public static function send(StaffAlert $alert, ?User $onlyTo = null): void
    {
        $event = $alert->event;

        if (! NotificationSettings::enabled($event) || NotificationSettings::channels($event) === []) {
            return;
        }

        // Each person's profile preferences can switch a channel (or the whole alert) off for them.
        $users = ($onlyTo ? collect([$onlyTo]) : NotificationSettings::staff($event))
            ->filter(fn (User $user): bool => NotificationSettings::channelsFor($event, $user) !== [])
            ->values();

        if ($users->isNotEmpty()) {
            Notification::send($users, $alert);
        }

        if ($onlyTo !== null) {
            return;
        }

        // Extra addresses (e.g. a shared sales inbox) always get the email, once.
        $alreadyEmailed = $users
            ->filter(fn (User $user): bool => in_array(NotificationSettings::CHANNEL_MAIL, NotificationSettings::channelsFor($event, $user), true))
            ->map(fn (User $user): string => strtolower($user->email))
            ->all();

        foreach (array_unique((array) NotificationSettings::event($event)['emails']) as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL) && ! in_array(strtolower($email), $alreadyEmailed, true)) {
                Notification::route('mail', $email)->notify($alert);
            }
        }
    }
}
