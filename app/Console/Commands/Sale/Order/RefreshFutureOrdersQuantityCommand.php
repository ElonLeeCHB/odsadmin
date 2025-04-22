<?php

namespace App\Console\Commands\Sale\Order;

use Illuminate\Console\Command;

class RefreshFutureOrdersQuantityCommand extends Command
{
    protected $signature = 'job:sale-order-quantity-control-refresh-future-orders';
    protected $description = '更新全部未來訂單';
    // php artisan job:run-sale-order-updated-dates
    
    public function handle()
    {
        try {
            $job = new \App\Jobs\Sale\Order\UpdateQuantityControlFutureOrdersJob();
            $job->handle();

            $this->info( get_class($this) . ' 執行成功');

        } catch (\Throwable $th) {
            $this->info($th->getMessage());
        }
    }
}