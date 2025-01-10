<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Sale\OrderProduct;
use App\Helpers\Classes\DataHelper;

class HandleOrderCreated
{
    use InteractsWithQueue;

    public function handle(OrderCreated $event)
    {
        // In a real application, you would send an email here
        // Log::info('Order confirmation sent for Order #' . $event->order->id);

        //刈包相關的訂單金額達到一千五，送滷味盒
            try {
                $product_id = 1803; //1803 = 滷味盒

                $order_id = optional($event->order)->id ?? null;
          
                if(!empty($order_id)){
                    $product = \App\Models\Material\ProductTranslation::select(['name'])->where('product_id', $product_id)->where('locale', 'zh_Hant')->first();
                    $product = DataHelper::toCleanObject($product);

                    $maxSortOrder = \App\Models\Sale\OrderProduct::where('order_id', $order_id)->max('sort_order');

                    $order_product = new \App\Models\Sale\OrderProduct;
                    $order_product->order_id = $event->order->id;
                    $order_product->product_id = $product_id;
                    $order_product->name = $product->name;
                    $order_product->quantity = 1;
                    $order_product->sort_order = $maxSortOrder + 1;
                    $order_product->price = 0;
                    $order_product->total = 0;
                    $order_product->options_total = 0;
                    $order_product->final_total = 0;
                    $order_product->tax = 0;

                    $order_product->save();
                }
            } catch (\Throwable $th) {
                throw $th;
            }
        //




    }
}