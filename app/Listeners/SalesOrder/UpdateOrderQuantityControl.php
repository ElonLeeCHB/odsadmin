<?php
/**
 * 訂單異動後更新數量控制
 * 數量控制自己有基本資料要更新，所以邏輯寫在 OrderDateLimitRepository
 */

namespace App\Listeners\SalesOrder;

use App\Events\OrderSaved;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Sale\OrderProduct;
use App\Models\Material\Product;
use App\Models\Material\ProductTranslation;
use App\Helpers\Classes\DataHelper;
use App\Models\Sale\Order;
use Carbon\Carbon;
use App\Events\OrderSavedAfterCommit;
use App\Repositories\Eloquent\Sale\OrderDateLimitRepository;

class UpdateOrderQuantityControl
{
    use InteractsWithQueue;

    public $increase_status_codes = ['Confirmed', 'CCP'];

    public function handle(OrderSavedAfterCommit $event)
    {
        // 舊訂單。如果是新增訂單，則 $old_order = 空的新模型
        $old_order = $event->old_order ?? new Order;

        // 儲存後的訂單
        $saved_order = $event->saved_order;

        // 處理舊單數量
        if(!empty($old_order->id) && !empty($old_order->delivery_date)){
            (new OrderDateLimitRepository)->refreshOrderedQuantityByDate($old_order->delivery_date);
        }

        // 處理新單訂單數量
        if(in_array($saved_order->status_code, $this->increase_status_codes)){
            (new OrderDateLimitRepository)->increaseByOrder($saved_order);
        }
    }
}