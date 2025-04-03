<?php

namespace App\Console\Commands\Sale\Order;

use Illuminate\Console\Command;
use App\Repositories\Eloquent\Sale\OrderDateLimitRepository;

class UpdateDailyQuantityControllerCommand extends Command
{
    protected $signature = 'sale:order-daily-quantity {required_date}';
    protected $description = '根據 required_date 更新訂單數量控制';

    public function handle()
    {
        $required_date = $this->argument('required_date');
        $required_date_ymd = parseDate($required_date);

        $result = (new OrderDateLimitRepository)->refreshOrderedQuantityByDate($required_date_ymd);

        if ($result) {
            $this->info('執行成功');
        } else {
            $this->error('執行失敗');
        }
    }
}