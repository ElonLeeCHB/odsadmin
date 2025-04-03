<?php

namespace App\Console\Commands\Sale\Order;

use Illuminate\Console\Command;

class UpdateDailyRequisitionCommand extends Command
{
    protected $signature = 'sale:order-daily-requisition {required_date}';
    protected $description = '根據 required_date 更新訂單備料表';

    public function handle()
    {
        $required_date = $this->argument('required_date');
        $required_date_ymd = parseDate($required_date);

    }
}