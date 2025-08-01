<?php

namespace App\Jobs\Sale\Order;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Setting\Setting;
use Illuminate\Support\Facades\DB;

// 更新全部未來訂單
class UpdateQuantityControlFutureOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // 執行時鎖定本任務
        $lock = cache()->lock('sale-order-update-future-orders-job', 60);

        if ($lock->get()) {
            try {
                DB::transaction(function () {
                    (new \App\Repositories\Eloquent\Sale\OrderDateLimitRepository)->resetFutureOrders();
                });
                
                (new \App\Repositories\Eloquent\SysData\LogRepository)->log(['data'=>'','note'=>'UpdateQuantityControlFutureOrdersJob 執行成功']);

            } catch (\Throwable $th) {
                DB::rollBack();
                (new \App\Repositories\Eloquent\SysData\LogRepository)->logErrorAfterRequest(['data' => $th->getMessage(), 'note' => 'UpdateQuantityControlFutureOrdersJob']);
            } finally {
                $lock->release();
            }
        }
    }
}