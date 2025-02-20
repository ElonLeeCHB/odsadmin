<?php

namespace App\Listeners;

use App\Events\OrderSaved;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Sale\OrderProduct;
use App\Models\Material\Product;
use App\Models\Material\ProductTranslation;
use App\Helpers\Classes\DataHelper;
use App\Models\Sale\Order;
use App\Models\Sale\DateLimit;
use App\Models\Sale\TimeSlotLimit;
use App\Repositories\Eloquent\Sale\OrderPrintingMpdfRepository;
use Carbon\Carbon;

use TCPDF;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

// 訂單所有的變動，insert, update, delete
class HandleOrderSaved
{
    use InteractsWithQueue;

    public function handle(OrderSaved $event)
    {
        // In a real application, you would send an email here
        // Log::info('Order confirmation sent for Order #' . $event->order->id);

        $result = false;
        
        try {

            // 舊訂單。如果是新增訂單，則 $old_order = 空的新模型
            $old_order = $event->old_order ?? new Order;

            // 儲存後的訂單
            $saved_order = $event->saved_order;

            $this->resetQuantityControl($old_order, $saved_order);

            // $this->giftProducts($order);
            // (new OrderPrintingMpdfRepository)->generatePDF($order->id, 'S');

            // return false;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * 關鍵：送達日期是否相同、送達時間段是否相同
     * 
     * 日期相同、時間段相同、數量不同
     * 日期相同、時間段不同、勿略數量異動
     * 日期不同、時間段相同
     * 日期不同、時間段不同
     */
    private function resetQuantityControl($old_order, $saved_order)
    {
        $statuses_of_increase = ['Confirmed', 'CCP'];

        // // 新增數量控制。原本不是新增數量的狀態，改為需要新增數量的狀態
        // if(!in_array($old_order->status_code, $statuses_of_increase) && in_array($saved_order->status_code, $statuses_of_increase)){
        //     $this->increaseOrderProductQuantityInQuantityControl($saved_order);
        // }
        // // 減少數量控制。原本是新增數量的狀態，改為不需新增數量的狀態
        // else if(in_array($old_order->status_code, $statuses_of_increase) && !in_array($saved_order->status_code, $statuses_of_increase)){
        //     $this->decreaseOrderProductQuantityInQuantityControl($saved_order);
        // }
        $old_order->load('orderProducts.productTags');
        $saved_order->load('orderProducts.productTags');


        //日期相同
        if (Carbon::parse($old_order->delivery_date)->toDateString() == Carbon::parse($saved_order->delivery_date)->toDateString()) {

        }else{

        }
        



    }

    public function resetDatelimitsByDate(&$date)
    {
        $datelimits = (new DateLimit)->getCurrentDateLimits($date);

        $orders = Order::whereDate('delivery_date', $date)
                    ->with(['orderProducts' => function($query) {
                        $query->with('productTags');
                    }])
                    ->get();

        $order->load('orderProducts');

        foreach ($order->orderProducts ?? [] as $orderProduct) {
            $should_caculate = false;

            foreach ($orderProduct->productTags ?? [] as $productTag) {
                if($productTag->term_id == 1331){ // 1331=套餐
                    $should_caculate = true;
                    break;
                }
            }
            if($should_caculate == true){
                $time_slot_key = (new TimeSlotLimit)->getTimeSlotKey($order->delivery_date);
                $datelimits['TimeSlots'][$time_slot_key]['OrderedQuantity'] -= $orderProduct->quantity;
                $datelimits['TimeSlots'][$time_slot_key]['AcceptableQuantity'] = $datelimits['TimeSlots'][$time_slot_key]['MaxQuantity'] - $datelimits['TimeSlots'][$time_slot_key]['OrderedQuantity'];
            }
        }
    }

    private function decreaseOrderProductQuantityInQuantityControl($order)
    {
        $order->load('orderProducts.productTags');


        $datelimits = (new DateLimit)->getCurrentDateLimits($order->delivery_date_ymd);

        foreach ($order->order_products ?? [] as $order_product) {
            $should_decrease = false;

            foreach ($order_product->productTags ?? [] as $productTag) {
                if($productTag->term_id == 1331){ // 1331=套餐
                    $should_decrease = true;
                    break;
                }
            }
            if($should_decrease == true){
                $time_slot = (new TimeSlotLimit)->getTimeSlotKey($order->delivery_date);
                $datelimits['TimeSlots'][$time_slot]['OrderedQuantity'] -= $order_product->quantity;
                $datelimits['TimeSlots'][$time_slot]['AcceptableQuantity'] = $datelimits['TimeSlots'][$time_slot]['MaxQuantity'] - $datelimits['TimeSlots'][$time_slot]['OrderedQuantity'];
            }
        }
        
        // 最後重新整理時間段數量
        (new DateLimit)->updateWithFormat($datelimits);
    }

    private function increaseOrderProductQuantityInQuantityControl($order)
    {
        $order->load('orderProducts.productTags');

        $datelimits = (new DateLimit)->getCurrentDateLimits($order->delivery_date_ymd);
        // $datelimits['TimeSlots']

        foreach ($order->order_products ?? [] as $order_product) {
            $should_increase = false;

            foreach ($order_product->productTags ?? [] as $productTag) {
                if($productTag->term_id == 1331){ // 1331=套餐
                    $should_increase = true;
                    break;
                }
            }
            if($should_increase == true){
                $time_slot = (new TimeSlotLimit)->getTimeSlotKey($order->delivery_date);
                $datelimits['TimeSlots'][$time_slot]['OrderedQuantity'] += $order_product->quantity;
                $datelimits['TimeSlots'][$time_slot]['AcceptableQuantity'] = $datelimits['TimeSlots'][$time_slot]['MaxQuantity'] - $datelimits['TimeSlots'][$time_slot]['OrderedQuantity'];
            }
        }
        
        // 最後重新整理時間段數量
        (new DateLimit)->updateWithFormat($datelimits);
    }

    /**
     * 刈包相關的訂單金額達到一千五，送滷味盒
     * 只有在2025年1月存在很短時間。留著以後參考。
     */
    private function giftProducts(Order $order)
    {
        $order_id = $order->id ?? null;
          
        if(!empty($order_id)){

            $guabao_total = OrderProduct::where('name', 'like', '%刈包%')->where('order_id', $order_id)->sum('final_total');

            if($guabao_total >= 1500){
                $gift_product_id = 1803; //1803 = 滷味盒
                
                $gift_product = \App\Models\Material\ProductTranslation::select(['name'])->where('product_id', $gift_product_id)->where('locale', 'zh_Hant')->first();
                $gift_product = DataHelper::toCleanObject($gift_product);

                $maxSortOrder = \App\Models\Sale\OrderProduct::where('order_id', $order_id)->max('sort_order');

                $order_product = new \App\Models\Sale\OrderProduct;
                $order_product->order_id = $order->id;
                $order_product->product_id = $gift_product_id;
                $order_product->name = $gift_product->name;
                $order_product->quantity = 1;
                $order_product->sort_order = $maxSortOrder + 1;
                $order_product->price = 0;
                $order_product->total = 0;
                $order_product->options_total = 0;
                $order_product->final_total = 0;
                $order_product->tax = 0;

                return $order_product->save();
            }
        }
    }
}