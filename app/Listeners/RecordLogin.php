<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Login;

/** Keeps the latest and the previous sign-in (time + IP) on the user, shown on their profile page. */
class RecordLogin
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        User::withoutTimestamps(fn () => $user->forceFill([
            'previous_login_at' => $user->last_login_at,
            'previous_login_ip' => $user->last_login_ip,
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ])->saveQuietly());
    }
}
