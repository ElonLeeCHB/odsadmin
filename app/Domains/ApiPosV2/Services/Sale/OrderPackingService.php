<?php

namespace App\Domains\ApiPosV2\Services\Sale;

use App\Services\Service;
use App\Models\Sale\Order;
use App\Models\Sale\OrderPacking;
use App\Models\Common\Term;

class OrderPackingService extends Service
{
    public $modelName;
    public $model;
    public $table;
    public $lang;

    public function getListByDeliveryDate($delivery_date)
    {
    
        $orders = Order::select(['id', 'code', 'mobile', 'delivery_time_range', 'delivery_date', 'comment', 'extra_comment', 'personal_name', 'payment_company', 'quantity_for_control','status_code'])
                    ->with(['packing'])
                    ->whereDate('delivery_date', $delivery_date)
                    ->whereIn('status_code', ['Confirmed', 'CCP'])
                    ->get()
                    ->keyBy('code');

        $result = [];

        foreach ($orders as $code => $order) {
            $result[$code] = $order->toArray();
            $result[$code]['packing']['packing_start_time'] = optional($order->packing)->packing_start_time ?? '';
            $result[$code]['packing']['packing_end_time'] = optional($order->packing)->packing_end_time ?? '';
            $result[$code]['packing']['packing_tables'] = optional($order->packing)->packing_tables ?? '';
            $result[$code]['packing']['packing_status_code'] = optional($order->packing)->packing_status_code ?? '';
            $result[$code]['packing']['scheduled_shipping_time'] = optional($order->packing)->scheduled_shipping_time ?? '';
            $result[$code]['packing']['shipping_time'] = optional($order->packing)->shipping_time ?? '';
            $result[$code]['packing']['driver_id'] = optional($order->packing)->driver_id ?? '';
            $result[$code]['packing']['driver_name'] = optional($order->packing)->driver_name ?? '';
            $result[$code]['packing']['driver_fee'] = optional($order->packing)->driver_fee ?? '';
        }

        return $result;
    }

    public function update($order_id, $data)
    {
        $order_packing = OrderPacking::findOrNew($order_id);
            
        // 必須放在前面，先判斷舊狀態
        if ($order_packing->packing_status_code == 'NotPrepared' && $data['packing_status_code'] == 'InPreparation'){
            $order_packing->packing_start_time = date('Y-m-d H:i:s');
        }
        
        if ($order_packing->packing_status_code == 'InPreparation' && $data['packing_status_code'] == 'Prepared'){
            $order_packing->packing_end_time = date('Y-m-d H:i:s');
        }

        $order_packing->order_id = $order_id;
        $order_packing->packing_tables = $data['packing_tables'] ?? null;
        $order_packing->packing_status_code = $data['packing_status_code'] ?? null;
        $order_packing->scheduled_shipping_time = $data['scheduled_shipping_time'] ?? null;
        $order_packing->shipping_time = $data['shipping_time'] ?? null;
        $order_packing->driver_id = $data['driver_id'] ?? null;
        $order_packing->driver_name = $data['driver_name'] ?? null;
        $order_packing->driver_fee = $data['driver_fee'] ?? 0;

        return $order_packing->save();

    }

    public function statuses()
    {
        $statuses = Term::where('taxonomy_code', 'SaleOrderPackingStatus')->where('is_active', 1)->orderBy('sort_order')->get()->keyBy('code');

        foreach ($statuses as $code => $statuse) {
            $result[$code] = $statuse->name;
        }

        return $result;
    }

}

