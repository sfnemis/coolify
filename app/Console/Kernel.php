<?php

namespace App\Console;

use App\Jobs\CheckResaleLicenseJob;
use App\Jobs\CleanupInstanceStuffsJob;
use App\Jobs\DatabaseBackupJob;
use App\Jobs\DockerCleanupJob;
use App\Jobs\InstanceAutoUpdateJob;
use App\Jobs\ProxyCheckJob;
use App\Jobs\ResourceStatusJob;
use App\Models\ScheduledDatabaseBackup;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        //        $schedule->call(fn() => $this->check_scheduled_backups($schedule))->everyTenSeconds();
        if (isDev()) {
            $schedule->command('horizon:snapshot')->everyMinute();
            $schedule->job(new ResourceStatusJob)->everyMinute();
            $schedule->job(new ProxyCheckJob)->everyFiveMinutes();
            $schedule->job(new CleanupInstanceStuffsJob)->everyMinute();

            // $schedule->job(new CheckResaleLicenseJob)->hourly();
            $schedule->job(new DockerCleanupJob)->everyOddHour();
            // $schedule->job(new InstanceAutoUpdateJob(true))->everyMinute();
        } else {
            $schedule->command('horizon:snapshot')->everyFiveMinutes();
            $schedule->job(new CleanupInstanceStuffsJob)->everyMinute()->onOneServer();
            $schedule->job(new ResourceStatusJob)->everyMinute()->onOneServer();
            $schedule->job(new CheckResaleLicenseJob)->hourly()->onOneServer();
            $schedule->job(new ProxyCheckJob)->everyFiveMinutes()->onOneServer();
            $schedule->job(new DockerCleanupJob)->everyTenMinutes()->onOneServer();
            $schedule->job(new InstanceAutoUpdateJob)->everyTenMinutes();
        }
        $this->check_scheduled_backups($schedule);
    }

    private function check_scheduled_backups($schedule)
    {
        ray('check_scheduled_backups');
        $scheduled_backups = ScheduledDatabaseBackup::all();
        if ($scheduled_backups->isEmpty()) {
            ray('no scheduled backups');
            return;
        }
        foreach ($scheduled_backups as $scheduled_backup) {
            if (!$scheduled_backup->enabled) {
                continue;
            }

            if (isset(VALID_CRON_STRINGS[$scheduled_backup->frequency])) {
                $scheduled_backup->frequency = VALID_CRON_STRINGS[$scheduled_backup->frequency];
            }
            $schedule->job(new DatabaseBackupJob(
                backup: $scheduled_backup
            ))->cron($scheduled_backup->frequency);
        }
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
