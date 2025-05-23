<?php

namespace App\Domains\ApiPosV2\Services\Sale;

use App\Helpers\Classes\DataHelper;
use App\Helpers\Classes\OrmHelper;
use App\Services\Service;
use App\Models\Setting\Setting;
use App\Models\Sale\OrderDateLimit;
use App\Models\Sale\Order;
use App\Repositories\Eloquent\Sale\OrderDateLimitRepository;

class QuantityControlService extends Service
{
    // 預設時間段數量-獲取
    public function getTimeslots()
    {
        return (new OrderDateLimitRepository)->getDefaultLimits();
    }

    // 預設時間段數量-更新
    public function updateTimeslots($content)
    {
        $row = Setting::where('group','pos')->where('setting_key', 'pos_timeslotlimits')->first();

        if ($row) {
            $row->setting_value = json_encode($content);
            $row->save();

            return true;
        }
    }

    // 某日數量資料-獲取
    public function getOrderDateLimitsByDate($date)
    {
        return  (new OrderDateLimitRepository)->getDbDateLimitsByDate($date);
    }

    // 某日數量資料-更新上限
    public function updateMaxQuantityByDate($date, $data)
    {
        // 這裡只更新 order_date_limits。不重新掃描 orders 訂單表。
        // 只用於更新上限數量，然後沿用當前的訂單數量，據以計算可訂量。不重新讀取訂單表等相關資料。

        // 獲取指定日期的資料
        $db_formatted =  (new OrderDateLimitRepository)->getDbDateLimitsByDate($date);

        $upsert_date = [];

        foreach ($db_formatted['TimeSlots'] as $time_slot_key => $row) {
            if(isset($data[$time_slot_key])){
                $maxQuantity = $data[$time_slot_key];
            }else{
                $maxQuantity = $row['MaxQuantity'];
            }

            $upsert_date[] = [
                'Date' => $date,
                'TimeSlot' => $time_slot_key,
                'MaxQuantity' => $maxQuantity,
                'OrderedQuantity' => $row['OrderedQuantity'], //照舊
                'AcceptableQuantity' => $maxQuantity - $row['OrderedQuantity'],
            ];
        }

        OrderDateLimit::upsert($upsert_date, ['Date', 'TimeSlot'], ['MaxQuantity', 'OrderedQuantity', 'AcceptableQuantity']);
        
        return true;
    }

    // 某日數量資料-恢復預設上限
    public function resetDefaultMaxQuantityByDate($date)
    {
        // 只用於更新上限數量，然後沿用當前的訂單數量，據以計算可訂量。不重新讀取訂單表等相關資料。
        
        //預設 date_limit
        $default_limits =  (new OrderDateLimitRepository)->getDefaultLimits();

        // 獲取指定日期的資料
        $db_formatted =  (new OrderDateLimitRepository)->getDbDateLimitsByDate($date);

        $insert_data = [];

        foreach ($db_formatted['TimeSlots'] as $time_slot_key => $row) {
            $insert_data[] = [
                'Date' => $date,
                'TimeSlot' => $time_slot_key,
                'MaxQuantity' => $default_limits[$time_slot_key],
                'OrderedQuantity' => $row['OrderedQuantity'], //照舊
                'AcceptableQuantity' => $default_limits[$time_slot_key] - $row['OrderedQuantity'],
            ];
        }

        OrderDateLimit::whereDate('Date', $date)->delete();
        OrderDateLimit::insert($insert_data);

        return (new OrderDateLimitRepository)->getDbDateLimitsByDate($date);
    }

    // 某日數量資料-重算訂單
    public function refreshOrderedQuantityByDate($date)
    {
        return (new OrderDateLimitRepository)->refreshOrderedQuantityByDate($date);
    }

    // 未來資料
    public function getFutureDays($futuredays)
    {
        $rows =  (new OrderDateLimitRepository)->getFutureDays($futuredays);

        $start_hour = 07;

        foreach ($rows as $date => $time_slots) {
            foreach ($time_slots as $time_slot_key => $row) {
                $cur_start_hour = substr($time_slot_key,0,2);
                if($cur_start_hour < $start_hour){
                    unset($rows[$date][$time_slot_key]);
                }
            }
        }

        return $rows;
    }

    public function resetFutureOrders()
    {
        return (new OrderDateLimitRepository)->resetFutureOrders();
    }

    public function orderList($delivery_date_ymd)
    {
        $start = $delivery_date_ymd . ' 00:00:00';
        $end = $delivery_date_ymd . ' 23:59:59';
    
        $orders = Order::select(['id', 'code', 'delivery_time_range', 'shipping_state_id', 'shipping_city_id', 'shipping_road', 'delivery_date', 'quantity_for_control', 'comment', 'extra_comment'])
                    ->with('shippingState')
                    ->with('shippingCity')
                    ->whereBetween('delivery_date', [$start, $end])
                    ->whereIn('status_code', ['Confirmed', 'CCP'])
                    ->get();

        $orders = $orders->toArray();

        foreach ($orders as $key => $order) {
            unset($orders[$key]['shipping_state']);
            unset($orders[$key]['shipping_city']);
        }
        
        return $orders;
    }

    public function quickSaveOrder($post_data)
    {
        $order_id = $post_data['order_id'];

        $order = Order::select(['id','code','comment'])->find($order_id);
        $order->comment = $post_data['comment'];
        $order->save();

        return $order;
    }
}