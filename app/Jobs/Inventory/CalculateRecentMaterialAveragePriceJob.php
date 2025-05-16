<?php

namespace App\Jobs\Inventory;

use App\Helpers\Classes\OrmHelper;
use App\Models\Catalog\Product;
use App\Models\Inventory\ReceivingOrder;
use App\Models\Inventory\ReceivingOrderProduct;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CalculateRecentMaterialAveragePriceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $days;

    public function __construct(int $days = 90)
    {
        $this->days = $days;
    }

    public function handle()
    {
        $startDate = Carbon::now()->subDays($this->days);

        $averages = ReceivingOrderProduct::join('receiving_orders', 'receiving_order_products.receiving_order_id', '=', 'receiving_orders.id')
                    ->where('receiving_order_products.created_at', '>=', $startDate)
                    ->selectRaw('
                        receiving_order_products.product_id,
                        AVG(
                            CASE 
                                WHEN receiving_orders.tax_rate > 0 
                                THEN receiving_order_products.stock_price * (1 + receiving_orders.tax_rate)
                                ELSE receiving_order_products.stock_price
                            END
                        ) as avg_after_tax_price
                    ')
                    ->groupBy('receiving_order_products.product_id')
                    ->get();

        foreach ($averages as $row) {
            Product::where('id', $row->product_id)->update([
                'average_stock_price' => round($row->avg_after_tax_price, 2),
            ]);
        }
    }
}
