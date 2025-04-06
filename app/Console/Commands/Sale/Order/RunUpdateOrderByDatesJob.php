<?php

namespace App\Console\Commands\Sale\Order;

use Illuminate\Console\Command;

class RunUpdateOrderByDatesJob extends Command
{
    protected $signature = 'job:run-sale-order-updated-dates';
    protected $description = '更新有異動過的日期';
    // php artisan job:run-sale-order-updated-dates

    public function handle()
    {
        try {
            $job = new \App\Jobs\Sale\UpdateOrderByDatesJob();
            $job->handle();

            $this->info( get_class($this) . ' 執行成功');

        } catch (\Throwable $th) {
            $this->info($th->getMessage());
        }
    }
}