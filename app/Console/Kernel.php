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
        // Commands (立即執行無佇列)

        // 定期清除laravel內建快取
        $schedule->command('cache:clear')->daily();
        
        // 預設刪除 60 天以前的記錄
        $schedule->command('app:delete-logs', ['60'])->daily();

        // job() 會使用佇列
        
        // 定期更新訂單統計
        $schedule->job(new \App\Jobs\Sale\UpdateOrderByDatesJob)->everyMinute();
        
        // 計算近三個月進貨均價
        $schedule->job(new \App\Jobs\Inventory\CalculateRecentMaterialAveragePriceJob)->daily();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
