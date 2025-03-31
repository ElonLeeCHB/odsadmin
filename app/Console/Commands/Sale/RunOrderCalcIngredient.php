<?php

namespace App\Console\Commands\Sale;

use Illuminate\Console\Command;
use App\Jobs\Sale\OrderCalcIngredient;

class RunOrderCalcIngredient extends Command
{
    protected $signature = 'sale:order-calc-ingredient {required_date} {force_update}';
    protected $description = '根據 required_date 獲取並處理相關資料';

    public function handle()
    {
        $required_date = $this->argument('required_date');
        $required_date_ymd = parseDate($required_date);

        $force_update = $this->option('force_update') ?? 0;

        dispatch(new OrderCalcIngredient($required_date_ymd, $force_update));
    }


}