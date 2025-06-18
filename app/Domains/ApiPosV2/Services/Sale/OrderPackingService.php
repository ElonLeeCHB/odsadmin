<?php

namespace App\Domains\ApiPosV2\Services\Sale;

use App\Helpers\Classes\OrmHelper;
use App\Services\Service;
use App\Models\Sale\Order;
use App\Models\Sale\OrderPacking;
use App\Models\Common\Term;
use App\Models\Sale\Driver;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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

    public function save($data, $order_id = null)
    {
        try {
            DB::beginTransaction();

            $order_packing = OrderPacking::findOrNew($order_id);

            $save_data = $data;

            $save_data['order_id'] = $order_id;

            $save_data['shipping_date'] = date('Y-m-d');
                
            // 從非準備中，改為準備中
            if ($order_packing->packing_status_code != 'InPreparation' && $data['packing_status_code'] == 'InPreparation'){
                    $save_data['packing_start_time'] = date('Y-m-d H:i:s');
            }
            
            // 從非準備完成，改為準備完成
            if ($order_packing->packing_status_code != 'Prepared' && $data['packing_status_code'] == 'Prepared'){
                $save_data['packing_end_time'] = date('Y-m-d H:i:s');
            }

            if (!empty($save_data['driver_id'])){
                $save_data['vehicle_type_code'] = Driver::where('id', $save_data['driver_id'])->value('vehicle_type_code');
            }
            
            OrmHelper::saveRow($order_packing, $save_data);

            DB::commit();

            return true;
            
        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }

    public function getStatuses()
    {
        $statuses = Term::where('taxonomy_code', 'SaleOrderPackingStatus')->where('is_active', 1)->orderBy('sort_order')->get()->keyBy('code');

        foreach ($statuses as $code => $statuse) {
            $result[$code] = $statuse->name;
        }

        return $result;
    }

}

