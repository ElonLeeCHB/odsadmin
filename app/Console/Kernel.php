<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Carbon\Carbon;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     * everyMinute,everyFiveMinutes,everyFifteenMinutes,hourly,daily, weekly, monthly
     * C:\Servers\php\php83\php.exe artisan schedule:run
     * C:\Servers\php\php83\php.exe artisan schedule:work
     */
    // cron('*/20 * * * *');
    protected function schedule(Schedule $schedule): void
    {
        // 定期清除laravel舊快取
        $schedule->command('clear:cache')->daily();
        
        // 定期更新訂單統計
        $schedule->job(new \App\Jobs\Sale\UpdateOrderByDatesJob)->everyFifteenMinutes();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
