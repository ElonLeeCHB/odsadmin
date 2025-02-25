<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Repositories\Eloquent\Sale\OrderDateLimitRepository;
use Illuminate\Support\Facades\Log;

class FillOrderDateLimit extends Command
{
    protected $signature = 'app:fill-order-date-limit';
    protected $description = '每天執行，確保未來30天的 OrderDateLimit 存在';

    public function handle()
    {
        try {
            (new OrderDateLimitRepository)->makeFuture30Days();
            $this->info('FillOrderDateLimit 執行完成');
        } catch (\Exception $e) {
            Log::error('app:fill-order-date-limit 執行失敗: ' . $e->getMessage());
            $this->error('執行失敗');
        }
    }
}
