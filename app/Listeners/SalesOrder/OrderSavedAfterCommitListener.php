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
use App\Models\Catalog\Product;
use App\Models\Catalog\ProductTranslation;
use App\Models\Setting\Setting;
use App\Helpers\Classes\DataHelper;
use App\Models\Sale\Order;
use Carbon\Carbon;
use App\Events\OrderSavedAfterCommit;
use App\Repositories\Eloquent\Sale\OrderDateLimitRepository;

class OrderSavedAfterCommitListener
{
    use InteractsWithQueue;

    public function handle(OrderSavedAfterCommit $event)
    {
        $this->updateQuantityForControl($event);

        $this->updateQueuedOrderDateSettingKey($event);

        $this->deleteOrderCache($event);
    }


    /** 更新數量控制
     *      訂單更新時，先更新三表內容 orders, order_products, order_product_options
     *      然後再更新權重
     *      然後再將權重寫到 OrderDateLimits
     */
    public function updateQuantityForControl($event)
    {
        $repository = new OrderDateLimitRepository;

        // 舊訂單。如果是新增訂單，則 $old_order = 空的新模型
        $old_order = $event->old_order ?? new Order;

        // 儲存後的訂單
        $saved_order = $event->saved_order;

        // 更新訂單三表的控單數量(權重)
        $repository->updateOrderedQuantityForControlByOrderId($saved_order->id);

        // 處理舊單數量
        if(!empty($old_order->id) && !empty($old_order->delivery_date)){
            // 通常不會需要執行這一段。通常 id 不能改，所以只要舊單存在，則其 id 等於新單 id
            if($old_order->id != $saved_order->id){
                $repository->updateOrderedQuantityForControlByOrderId($saved_order->id);
            }
            
            // 更新 order_date_limits
            $repository->refreshOrderedQuantityByDate($old_order->delivery_date);
        }

        // 處理新單的訂單數量
        $old_date = Carbon::parse($old_order->delivery_date)->format('Y-m-d');
        $new_date = Carbon::parse($saved_order->delivery_date)->format('Y-m-d');
        
        if(in_array($saved_order->status_code, $repository->controlled_status_code)){
            if($old_date != $new_date){ //不同日期才執行新增。如果同日期，會在上一步順便處理。
                $repository->increaseByOrder($saved_order);
            }
        }
    }

    /**
     * 訂單異動後，將送達日期寫入設定資料表， orders.delivery_date, 。讓系統知道這個日期需要處理。例如轉備料表
     */
    public function updateQueuedOrderDateSettingKey($event)
    {
        // 舊訂單。如果是新增訂單，則 $old_order = 空的新模型
        $old_order = $event->old_order ?? new Order;

        // 儲存後的訂單
        $saved_order = $event->saved_order;

        $dates = Setting::where('setting_key','sale_order_dates_for_queued_job')->first();

        $dates = $dates->setting_value ?? [];

        $dates[] = Carbon::parse($old_order->delivery_date)->toDateString();
        $dates[] = Carbon::parse($saved_order->delivery_date)->toDateString();
        
        // 使用 array_unique 去除重複的日期
        $dates = array_unique($dates);
        $dates = json_encode(array_values($dates));

        Setting::updateOrCreate(
            ['setting_key' => 'sale_order_dates_for_queued_job'],  // 查找條件
            [
                'group' => 'sale',
                'location_id' => 0,
                'setting_key' => 'sale_order_dates_for_queued_job',
                'setting_value' => $dates,
                'is_autoload' => 0,
                'is_json' => 1,
                'comment' => ' 訂單異動後，將送達日期寫入設定資料表， orders.delivery_date, 以逗號隔開。讓系統知道這個日期需要處理。例如轉備料表'
            ]
        );
    }

    // 更新訂單快取
    public function deleteOrderCache($event)
    {
        $saved_order = $event->saved_order;

        (new Order)->deleteCacheKeysByIdOrCode($saved_order->id, 'id');
        (new Order)->deleteCacheKeysByIdOrCode($saved_order->code, 'code');
    }

}