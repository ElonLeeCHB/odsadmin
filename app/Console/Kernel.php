<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     * everyMinute, daily
     * C:\Servers\php\php83\php.exe artisan schedule:run
     * C:\Servers\php\php83\php.exe artisan schedule:work
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('clear:cache')->monthlyOn(1, '00:30');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
