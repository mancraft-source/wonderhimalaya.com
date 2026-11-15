<?php

use App\Filament\Pages\SystemStatus;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
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

// --- Heartbeat: Settings → System shows "scheduler last ran X ago" from this. ---
Schedule::call(fn () => Cache::forever(SystemStatus::HEARTBEAT_KEY, now()->toIso8601String()))
    ->name('system:heartbeat')
    ->everyMinute();

// --- Housekeeping: CSV/XLSX exports (records + files) are kept 7 days, long enough to download from the bell. ---
// (Filament's Export model uses Prunable without defining prunable(), so `model:prune` would throw.)
Schedule::call(function (): void {
    Export::query()->where('created_at', '<', now()->subDays(7))->each(function (Export $export): void {
        $export->deleteFileDirectory();
        $export->delete();
    });
})->name('exports:clean')->dailyAt('02:30')->onOneServer();
