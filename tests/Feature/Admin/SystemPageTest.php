<?php

use App\Filament\Pages\SystemStatus;
use App\Jobs\RunBackup;
use App\Models\User;
use App\Support\System\RecentErrors;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->admin()->create();
});

it('is only available to users who manage settings', function () {
    $this->actingAs($this->admin)->get(SystemStatus::getUrl())->assertOk()->assertSee(['Application', 'Scheduler (cron)', 'Queue', 'Email', 'Backups', 'Recent errors']);
    $this->actingAs(User::factory()->sales()->create())->get(SystemStatus::getUrl())->assertForbidden();
    $this->actingAs(User::factory()->role('content')->create())->get(SystemStatus::getUrl())->assertForbidden();
});

it('schedules a heartbeat every minute that records when the scheduler last ran', function () {
    $this->freezeTime();

    /** @var Event $heartbeat */
    $heartbeat = collect(app(Schedule::class)->events())->firstOrFail(fn (Event $event): bool => $event->description === 'system:heartbeat');

    expect($heartbeat->expression)->toBe('* * * * *');

    $heartbeat->run(app());

    expect(SystemStatus::schedulerStatus())->toMatchArray(['ok' => true, 'label' => 'Last ran 0 seconds ago']);
});

it('reports the scheduler as not running when the heartbeat is missing or stale', function () {
    expect(SystemStatus::schedulerStatus())->toMatchArray(['ok' => false, 'ranAt' => null]);

    Cache::forever(SystemStatus::HEARTBEAT_KEY, now()->subMinutes(10)->toIso8601String());

    expect(SystemStatus::schedulerStatus()['ok'])->toBeFalse();

    $this->actingAs($this->admin)->get(SystemStatus::getUrl())->assertSee('Not running');
});

it('clears and rebuilds the caches', function () {
    Artisan::shouldReceive('call')->once()->with('optimize:clear')->andReturn(0);
    Artisan::shouldReceive('call')->once()->with('optimize')->andReturn(0);

    $this->actingAs($this->admin);

    Livewire::test(SystemStatus::class)->callAction('clearCaches')->assertNotified('Caches cleared and rebuilt');
});

it('re-runs the SEO analysis', function () {
    Artisan::shouldReceive('call')->once()->with('seo:score', [])->andReturn(0);
    Artisan::shouldReceive('output')->andReturn('Updated 12 SEO scores.');

    $this->actingAs($this->admin);

    Livewire::test(SystemStatus::class)->callAction('seoScore')->assertNotified('SEO analysis updated');
});

it('reports a failing command instead of claiming success', function () {
    Artisan::shouldReceive('call')->once()->with('seo:score', [])->andReturn(1);
    Artisan::shouldReceive('output')->andReturn('Database unavailable');

    $this->actingAs($this->admin);

    Livewire::test(SystemStatus::class)->callAction('seoScore')->assertNotified('seo:score failed');
});

it('forgets the cached sitemap', function () {
    Cache::put('sitemap.xml', '<urlset/>');

    $this->actingAs($this->admin);

    Livewire::test(SystemStatus::class)->callAction('rebuildSitemap')->assertNotified();

    expect(Cache::has('sitemap.xml'))->toBeFalse();
});

it('retries every failed job', function () {
    Artisan::shouldReceive('call')->once()->with('queue:retry', ['id' => ['all']])->andReturn(0);
    Artisan::shouldReceive('output')->andReturn('');

    $this->actingAs($this->admin);

    Livewire::test(SystemStatus::class)->callAction('retryFailedJobs')->assertNotified('Failed jobs pushed back onto the queue');
});

it('queues a backup instead of running it in the request', function () {
    Queue::fake();

    $this->actingAs($this->admin);

    Livewire::test(SystemStatus::class)->callAction('runBackup')->assertNotified('Backup queued');

    Queue::assertPushed(RunBackup::class);
});

it('runs backup:run from the backup job', function () {
    Artisan::shouldReceive('call')->once()->with('backup:run')->andReturn(0);

    (new RunBackup)->handle();
});

it('sends a test email', function () {
    Mail::fake();

    $this->actingAs($this->admin);

    Livewire::test(SystemStatus::class)
        ->callAction('sendTestEmail', ['to' => 'ops@example.org'])
        ->assertHasNoActionErrors()
        ->assertNotified('Test email sent to ops@example.org');
});

it('reads the newest error entries from the tail of the log files', function () {
    $directory = storage_path('framework/testing/logs-'.uniqid());
    File::ensureDirectoryExists($directory);

    File::put($directory.'/laravel-2026-11-14.log', implode("\n", [
        '[2026-11-14 08:00:00] production.ERROR: Older failure {"exception":"[object] (RuntimeException(code: 0): Older failure at /app/Foo.php:10)',
        '[stacktrace]',
        '#0 /app/Bar.php(20): Foo->run()',
        '"}',
    ])."\n");
    File::put($directory.'/laravel-2026-11-15.log', implode("\n", [
        '[2026-11-15 09:00:00] production.INFO: Just saying hello',
        '[2026-11-15 10:00:00] production.CRITICAL: Payment gateway timed out',
        '#0 /app/Gateway.php(5): call()',
        '[2026-11-15 11:00:00] production.WARNING: Slow query',
    ])."\n");
    touch($directory.'/laravel-2026-11-14.log', now()->subDay()->getTimestamp());

    $errors = (new RecentErrors($directory))->latest(20);

    File::deleteDirectory($directory);

    expect($errors)->toHaveCount(2)
        ->and($errors[0])->toMatchArray(['level' => 'critical', 'message' => 'Payment gateway timed out', 'trace' => '#0 /app/Gateway.php(5): call()'])
        ->and($errors[1]['level'])->toBe('error')
        ->and($errors[1]['message'])->toStartWith('Older failure')
        ->and($errors[1]['trace'])->toContain('[stacktrace]');
});

it('only reads the end of a large log file', function () {
    $directory = storage_path('framework/testing/logs-'.uniqid());
    File::ensureDirectoryExists($directory);
    File::put($directory.'/laravel.log', "[2026-11-15 08:00:00] local.ERROR: Early error\n".str_repeat("filler line\n", 2000)."[2026-11-15 09:00:00] local.ERROR: Late error\n");

    $errors = (new RecentErrors($directory, bytesPerFile: 1024))->latest();

    File::deleteDirectory($directory);

    expect(collect($errors)->pluck('message')->all())->toBe(['Late error']);
});
