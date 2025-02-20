<?php

namespace App\Listeners;

use App\Events\OrderUpdated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Sale\OrderProduct;
use App\Models\Material\Product;
use App\Models\Material\ProductTranslation;
use App\Helpers\Classes\DataHelper;
use App\Models\Sale\Order;
use App\Repositories\Eloquent\Sale\OrderPrintingMpdfRepository;

use TCPDF;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class HandleOrderUpdated
{
    use InteractsWithQueue;

    public function handle(OrderUpdated $event)
    {
        // In a real application, you would send an email here
        // Log::info('Order confirmation sent for Order #' . $event->order->id);
        
        try {
            $order = $event->order;

            $statuses_of_increase = ['Confirmed', 'CCP'];

            $order_products = $order->load('orderProducts');

            echo "<pre>",print_r('HandleOrderUpdated',true),"</pre>";exit;


            if(in_array($order->status_code, $statuses_of_increase)){
                foreach ($order_products ?? [] as $order_product) {
                    // 1123456
                }

            }


            // $this->giftProducts($order);
            // (new OrderPrintingMpdfRepository)->generatePDF($order->id, 'S');

            // return false;
        } catch (\Throwable $th) {
            throw $th;
        }

    }
}