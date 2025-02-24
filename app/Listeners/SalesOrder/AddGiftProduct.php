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
use Carbon\Carbon;

// 未使用。需要改寫。

// 訂單所有的變動，insert, update, delete
// 執行到本物件的時候，訂單異動必須完成。如果有交易，必須已經 DB::commit()

class AddGiftProduct
{
    use InteractsWithQueue;

    public function handle($event)
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