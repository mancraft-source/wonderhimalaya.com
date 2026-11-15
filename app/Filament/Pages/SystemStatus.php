<?php

namespace App\Filament\Pages;

use App\Jobs\RunBackup;
use App\Support\Permissions;
use App\Support\Seo\SeoSettings;
use App\Support\System\RecentErrors;
use BackedEnum;
use Composer\InstalledVersions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
use Spatie\Backup\BackupDestination\BackupDestination;
use Spatie\Backup\BackupDestination\BackupDestinationFactory;
use Spatie\Backup\Config\Config as BackupConfig;
use Throwable;
use UnitEnum;

/** Settings → System: health of the app, scheduler, queue, mail and backups, recent errors and maintenance actions. */
class SystemStatus extends Page
{
    /** Cache key the scheduled heartbeat (routes/console.php) writes every minute. */
    public const HEARTBEAT_KEY = 'system.scheduler_heartbeat';

    /** Minutes without a heartbeat before the scheduler is reported as not running. */
    public const HEARTBEAT_STALE_MINUTES = 5;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedServerStack;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'System';

    protected static ?int $navigationSort = 90;

    protected static ?string $title = 'System';

    protected static ?string $slug = 'system';

    protected string $view = 'filament.pages.system-status';

    public static function canAccess(): bool
    {
        return auth()->user()?->can(Permissions::SETTINGS) ?? false;
    }

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        return [
            'app' => $this->appStatus(),
            'scheduler' => self::schedulerStatus(),
            'queue' => $this->queueStatus(),
            'mail' => ManageSiteSettings::mailStatus(),
            'backups' => $this->backupStatus(),
            'logErrors' => RecentErrors::fromStorage()->latest(20),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->clearCachesAction(),
            Action::make('seoScore')
                ->label('Re-run SEO analysis')
                ->icon(Heroicon::OutlinedPresentationChartLine)
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Recalculates the stored SEO score of every trip, destination, page and post.')
                ->action(fn () => $this->runArtisan('seo:score', 'SEO analysis updated')),
            Action::make('rebuildSitemap')
                ->label('Rebuild sitemap')
                ->icon(Heroicon::OutlinedMap)
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Clears the cached sitemap.xml; it is rebuilt from the current content on the next request.')
                ->action(function (): void {
                    Cache::forget('sitemap.xml');
                    Notification::make()->title('Sitemap will be rebuilt on its next request')->success()->send();
                }),
        ];
    }

    public function clearCachesAction(): Action
    {
        return Action::make('clearCaches')
            ->label('Clear & rebuild caches')
            ->icon(Heroicon::OutlinedArrowPath)
            ->requiresConfirmation()
            ->modalDescription('Clears the config, route, view and application caches, then rebuilds them. Use this after changing .env or deploying by hand.')
            ->action(function (): void {
                try {
                    Artisan::call('optimize:clear');
                    Artisan::call('optimize');
                    Notification::make()->title('Caches cleared and rebuilt')->success()->send();
                } catch (Throwable $e) {
                    $this->failed('Caches could not be rebuilt', $e);
                }
            });
    }

    public function retryFailedJobsAction(): Action
    {
        return Action::make('retryFailedJobs')
            ->label('Retry failed jobs')
            ->icon(Heroicon::OutlinedArrowUturnRight)
            ->color('gray')
            ->size('sm')
            ->requiresConfirmation()
            ->modalDescription('Puts every failed job back on the queue. They run within a minute or two.')
            ->action(fn () => $this->runArtisan('queue:retry', 'Failed jobs pushed back onto the queue', ['id' => ['all']]));
    }

    public function runBackupAction(): Action
    {
        return Action::make('runBackup')
            ->label('Run backup now')
            ->icon(Heroicon::OutlinedCircleStack)
            ->color('gray')
            ->size('sm')
            ->requiresConfirmation()
            ->modalDescription('Queues a full backup (database and files). It runs in the background and can take a few minutes.')
            ->action(function (): void {
                try {
                    RunBackup::dispatch();
                    Notification::make()->title('Backup queued')->body('It will appear here once it finishes.')->success()->send();
                } catch (Throwable $e) {
                    $this->failed('Backup could not be queued', $e);
                }
            });
    }

    public function sendTestEmailAction(): Action
    {
        return ManageSiteSettings::sendTestEmailAction()->size('sm');
    }

    /** @return array{ok: bool, ranAt: ?Carbon, label: string} */
    public static function schedulerStatus(): array
    {
        $value = Cache::get(self::HEARTBEAT_KEY);
        $ranAt = $value ? Carbon::parse($value) : null;

        if (! $ranAt) {
            return ['ok' => false, 'ranAt' => null, 'label' => 'Never run: the cron job is not set up yet'];
        }

        return [
            'ok' => $ranAt->gt(now()->subMinutes(self::HEARTBEAT_STALE_MINUTES)),
            'ranAt' => $ranAt,
            'label' => 'Last ran '.$ranAt->diffForHumans(),
        ];
    }

    /** @return array<string, string> */
    private function appStatus(): array
    {
        $revisionFile = base_path('REVISION');
        $revision = is_file($revisionFile) ? trim((string) file_get_contents($revisionFile)) : '';

        return [
            'Environment' => (string) app()->environment(),
            'PHP' => PHP_VERSION,
            'Laravel' => app()->version(),
            'Filament' => (string) (InstalledVersions::getPrettyVersion('filament/filament') ?? 'unknown'),
            'Revision' => $revision !== '' ? substr($revision, 0, 12) : 'Not recorded',
            'Debug mode' => config('app.debug') ? 'On' : 'Off',
            'Search indexing' => SeoSettings::indexable() ? 'On' : 'Off',
        ];
    }

    /** @return array{connection: string, pending: ?int, failed: ?int} */
    private function queueStatus(): array
    {
        $connection = (string) config('queue.default');

        return [
            'connection' => $connection,
            'pending' => $this->safeCount(fn (): int => $connection === 'database'
                ? DB::connection(config('queue.connections.database.connection'))->table((string) config('queue.connections.database.table', 'jobs'))->count()
                : throw new \LogicException('Not a database queue')),
            'failed' => $this->safeCount(fn (): int => DB::connection(config('queue.failed.database'))->table((string) config('queue.failed.table', 'failed_jobs'))->count()),
        ];
    }

    /** @return list<array{disk: string, reachable: bool, count: int, newestAt: ?Carbon, newestSize: ?string, usedStorage: ?string}> */
    private function backupStatus(): array
    {
        try {
            $destinations = BackupDestinationFactory::createFromArray(app(BackupConfig::class));
        } catch (Throwable) {
            return [];
        }

        return $destinations->map(function (BackupDestination $destination): array {
            try {
                $reachable = $destination->isReachable();
                $newest = $reachable ? $destination->newestBackup() : null;

                return [
                    'disk' => $destination->diskName(),
                    'reachable' => $reachable,
                    'count' => $reachable ? $destination->backups()->count() : 0,
                    'newestAt' => $newest?->date(),
                    'newestSize' => $newest ? Number::fileSize($newest->sizeInBytes(), 1) : null,
                    'usedStorage' => $reachable ? Number::fileSize($destination->usedStorage(), 1) : null,
                ];
            } catch (Throwable) {
                return ['disk' => $destination->diskName(), 'reachable' => false, 'count' => 0, 'newestAt' => null, 'newestSize' => null, 'usedStorage' => null];
            }
        })->values()->all();
    }

    /** @param  array<string, mixed>  $parameters */
    private function runArtisan(string $command, string $successTitle, array $parameters = []): void
    {
        try {
            $exitCode = Artisan::call($command, $parameters);

            if ($exitCode !== 0) {
                throw new \RuntimeException(trim(Artisan::output()) ?: "{$command} exited with code {$exitCode}");
            }

            Notification::make()->title($successTitle)->body(str(trim(Artisan::output()))->limit(300)->toString() ?: null)->success()->send();
        } catch (Throwable $e) {
            $this->failed("{$command} failed", $e);
        }
    }

    private function failed(string $title, Throwable $e): void
    {
        report($e);

        Notification::make()->title($title)->body(str($e->getMessage())->limit(300)->toString())->danger()->persistent()->send();
    }

    /** @param  \Closure(): int  $counter */
    private function safeCount(\Closure $counter): ?int
    {
        try {
            return $counter();
        } catch (Throwable) {
            return null;
        }
    }
}
