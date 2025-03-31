<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Carbon\Carbon;

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
        // $schedule->command("sale:get-order-ingredient-cache " . Carbon::now()->format('Y-m-d') . " --force_update=1")->hourly()->withoutOverlapping();
        
        // 每小時更新備料表
        $schedule->job(new \App\Jobs\Sale\OrderCalcIngredient)->hourly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
