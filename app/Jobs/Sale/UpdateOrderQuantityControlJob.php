<?php

namespace App\Jobs\Sale;

use App\Models\JobLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Sale\OrderDateLimitRepository;

/**
 * 訂單異動後，立即在佇列中執行此工作，寫入訂單表 orders, 訂單商品表 order_products, 訂單商品選項表 order_product_options
 * 此工作不處理 order_date_limites。該任務使用排程定期執行，例如每20分鐘或每小時一次。
 */

class UpdateOrderQuantityControlJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $order_id)
    {
        $this->order_id = $order_id;
    }

    public function handle(): void
    {
        // 執行時鎖定本任務
        $lock = cache()->lock('sale-order-update-quantity-control-job', 60);

        if ($lock->get()) {
            try {
                DB::transaction(function () {
                    (new OrderDateLimitRepository)->updateOrderedQuantityForControlByOrderId($this->order_id);
                });

                (new \App\Repositories\LogToDbRepository)->log(['data'=>'','note'=>'UpdateOrderQuantityControlJob 執行成功']);
                
            } catch (\Throwable $th) {
                DB::rollBack();
                $logData = [
                    'data' => $th->getMessage(),
                    'note' => get_class($this) . ' handle()',
                    'status' => 'error',
                ];
                (new \App\Repositories\LogToDbRepository)->logErrorAfterRequest($logData);
            } finally {
                $lock->release();
            }
        }
    }
}