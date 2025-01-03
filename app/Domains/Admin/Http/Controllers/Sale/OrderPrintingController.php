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

        return view('admin.sale.print_orders', $data);
    }




}