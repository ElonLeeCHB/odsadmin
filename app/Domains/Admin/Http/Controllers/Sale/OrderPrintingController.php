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
        $data['lumpiaBentoData']['MainMeal'] = $this->OrderPrintingService->getOptionValuesByProductOption(1001, 1003); // 以招牌潤餅便當 1001 當代表

        // 刈包便當主餐 - 固定欄位
        $data['guabaoBentoData']['MainMeal'] = $this->OrderPrintingService->getOptionValuesByProductOption(1671, 1003); // 以 1671 雞胸刈包便當 當代表
        
        // 油飯盒主餐 - 固定欄位
        $data['oilRiceBoxData']['MainMeal'] = $this->OrderPrintingService->getOptionValuesByProductOption(1696, 1003); // 以控肉油飯盒 1696 當代表

        // 潤餅盒餐的主餐 - 固定欄位
        $data['lumpiaLunchBoxData']['MainMeal'] = $data['lumpiaBentoData']['MainMeal'];

        // 刈包盒餐的主餐 - 固定欄位
        $data['guabaoLunchBoxData']['MainMeal'] = $data['guabaoBentoData']['MainMeal'];

        // 分享餐的主餐 - 固定欄位
        $data['sharingMealData']['MainMeal'] = $this->OrderPrintingService->getOptionValuesByProductOption(1597, 1003);

        // 選項 6吋潤餅主餐 - 固定欄位
        $data['optionSoloLumpia']['MainMeal'] = $this->OrderPrintingService->getOptionValuesByOption(1009);
        // echo "<pre>",print_r($data['optionSoloLumpia']['MainMeal'],true),"</pre>";exit;
        // 飲料 - 固定欄位
        $data['drinkData'] = $this->OrderPrintingService->getDrinks();
        // echo "<pre>",print_r($data['orders'][0],true),"</pre>";exit;
        return view('admin.sale.print_orders', $data);
    }




}