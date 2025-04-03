<?php

namespace App\Console\Commands\Sale\Order;

use Illuminate\Console\Command;
use App\Repositories\Eloquent\Sale\OrderDateLimitRepository;
use App\Jobs\Sale\UpdateDailyRequisitionJob;

class UpdateDailyRequirementCommand extends Command
{
    protected $signature = 'sale:order-daily-requirement {required_date}';
    protected $description = '根據 required_date 更新訂單料件需求';

    public function handle()
    {
        $required_date = $this->argument('required_date');
        $required_date_ymd = parseDate($required_date);

        

        
        // if ($result) {
        //     $this->info('執行成功');
        // } else {
        //     $this->error('執行失敗');
        // }
    }
}