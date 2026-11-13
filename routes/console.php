<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// --- Payments / quotes (api agent) ---
Schedule::command('quotes:expire')->dailyAt('00:15')->withoutOverlapping()->onOneServer();
Schedule::command('bookings:send-balance-reminders')->dailyAt('09:00')->timezone('Asia/Kathmandu')->withoutOverlapping()->onOneServer();

// --- Backups (spatie/laravel-backup, config/backup.php) ---
Schedule::command('backup:clean')->dailyAt('01:00')->onOneServer();
Schedule::command('backup:run')->dailyAt('01:30')->onOneServer();
Schedule::command('backup:monitor')->dailyAt('03:00')->onOneServer();

// --- Queue (emails, staff alerts) ---
// Shared hosting can't keep a worker running, so the every-minute scheduler drains the queue instead.
// On a server with a supervised `queue:work` this is harmless: the queue is simply empty.
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')->everyMinute()->withoutOverlapping()->runInBackground();
