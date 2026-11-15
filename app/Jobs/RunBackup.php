<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;

/** Runs `backup:run` on the queue, so starting a backup from the admin never times out the request. */
class RunBackup implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 900;

    public function handle(): void
    {
        $exitCode = Artisan::call('backup:run');

        if ($exitCode !== 0) {
            throw new RuntimeException('backup:run failed: '.trim(Artisan::output()));
        }
    }
}
