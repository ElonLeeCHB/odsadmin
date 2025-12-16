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

        $orders = Order::select(['id', 'code', 'mobile', 'delivery_time_range', 'delivery_date', 'comment', 'extra_comment', 'personal_name', 'payment_company', 'shipping_company', 'quantity_for_control','status_code'])
                    ->with(['orderPacking'])
                    ->whereDate('delivery_date', $delivery_date)
                    ->whereIn('status_code', ['Confirmed', 'CCP'])
                    ->get()
                    ->map(function ($order) {
                        // 設定排序用的時間：優先使用 order_packing.ready_time，否則取 delivery_time_range 開頭
                        $ready_time = $order->orderPacking?->ready_time;

                        if (empty($ready_time) && !empty($order->delivery_time_range)) {
                            // 取 delivery_time_range 的開頭（例如 "09:00 - 10:00" → "09:00"）
                            $ready_time = trim(explode('-', $order->delivery_time_range)[0] ?? '');
                        }

                        // 統一轉為 HH:MM 格式（處理 "0900" → "09:00"）
                        if ($ready_time && strlen($ready_time) === 4 && ctype_digit($ready_time)) {
                            $ready_time = substr($ready_time, 0, 2) . ':' . substr($ready_time, 2, 2);
                        }

                        $order->setAttribute('ready_time', $ready_time);
                        return $order;
                    })
                    ->sortBy('ready_time')
                    ->keyBy('code');

        return $orders->toArray();
    }

    public function save($data, $order_id = null)
    {
        try {
            DB::beginTransaction();

            $order_packing = OrderPacking::with('order:id,delivery_date')->findOrNew($order_id);

            $save_data = $data;

            $save_data['order_id'] = $order_id;

            // shipping_date 使用訂單的 delivery_date
            $save_data['shipping_date'] = $order_packing?->order?->delivery_date
                ? Carbon::parse($order_packing->order->delivery_date)->format('Y-m-d')
                : null;
                
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
            
            unset($save_data['created_at']);
            unset($save_data['updated_at']);

            OrmHelper::saveRow($order_packing, $save_data); //只會修改有傳入的欄位。

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

