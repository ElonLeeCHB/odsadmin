<?php

namespace App\Domains\Admin\Http\Controllers\Sale;

use App\Domains\Admin\Http\Controllers\BackendController;
use App\Domains\Admin\Services\Sale\OrderPrintingService;

class OrderPrintingController extends BackendController
{
    public function __construct(private OrderPrintingService $OrderPrintingService,)
    {
        if (method_exists(parent::class, '__construct')) {
            parent::__construct();
        }

        $this->getLang(['admin/common/common','admin/sale/order']);
    }

    public function printOrders($order_ids, $print_status)
    {
        $data['lang'] = $this->lang;

        $data['orders'] = $this->OrderPrintingService->getPritingOrderList($order_ids);


        // 潤餅便當的主餐 - 固定欄位
        $data['lumpiaData']['MainMeal'] = $this->OrderPrintingService->getLumpiaBentoMainMeals();

        // 潤餅便當副主餐 - 固定欄位
        $data['lumpiaData']['SecondaryMainMeal'] = $this->OrderPrintingService->getLumpiasBentoSecondaryMainMeals();

        // 潤餅盒餐的主餐 - 固定欄位
        $data['lunchboxData']['MainMeal'] = $data['lumpiaData']['MainMeal'];

        // 刈包便當副主餐 - 固定欄位
        $data['guabaoBentoData']['SecondaryMainMeal'] = $data['lumpiaData']['SecondaryMainMeal'];

        // 油飯盒副主餐 - 固定欄位
        $data['oilRiceBoxData']['SecondaryMainMeal'] = $this->OrderPrintingService->getOilRiceBoxSecondaryMainMeals();

        // 飲料 - 固定欄位
        $data['drinkData'] = $this->OrderPrintingService->getDrinks();


        return view('admin.sale.print_orders', $data);
    }




}