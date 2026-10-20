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
