<?php
namespace App\Services\Sales;

use App\Services\Service;
use App\Models\Setting\Setting;
use App\Models\Sale\OrderDateLimit;
use App\Repositories\Eloquent\Sale\OrderDateLimitRepository;

class QuantityControlService extends Service
{
    // 預設時間段數量-獲取
    public function getTimeslots()
    {
        try {
            $result = (new OrderDateLimitRepository)->getDefaultLimits();
    
            return $result;

        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    // 預設時間段數量-更新
    public function updateTimeslots($content)
    {
        try {
            $row = Setting::where('group','pos')->where('setting_key', 'pos_timeslotlimits')->first();
    
            if ($row) {
                $row->setting_value = json_encode($content);
                $row->save();

                return true;
            }

        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    // 某日數量資料-獲取
    public function getOrderDateLimitsByDate($date)
    {
        try {
            $result =  (new OrderDateLimitRepository)->getDbDateLimitsByDate($date);

            return ['data' => $result];

        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    // 某日數量資料-更新上限
    public function updateMaxQuantityByDate($date, $data)
    {
        // 這裡只更新 order_date_limits。不重新掃描 orders 訂單表。
        // 只用於更新上限數量，然後沿用當前的訂單數量，據以計算可訂量。不重新讀取訂單表等相關資料。

        try {
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

        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    // 某日數量資料-恢復預設上限
    public function resetDefaultMaxQuantityByDate($date)
    {
        // 只用於更新上限數量，然後沿用當前的訂單數量，據以計算可訂量。不重新讀取訂單表等相關資料。
        
        try {
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

            $formatted =  (new OrderDateLimitRepository)->getDbDateLimitsByDate($date);
            
            return ['data' => $formatted];
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    // 某日數量資料-重算訂單
    public function refreshOrderedQuantityByDate($date)
    {
        try {
            $formatted =   (new OrderDateLimitRepository)->refreshOrderedQuantityByDate($date);
            
            return ['data' => $formatted];
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    // 未來資料
    public function getFutureDays($futuredays)
    {
        try {
            $rows =  (new OrderDateLimitRepository)->getFutureDays($futuredays);

            $start_hour = 10;

            foreach ($rows as $date => $time_slots) {
                foreach ($time_slots as $time_slot_key => $row) {
                    $cur_start_hour = substr($time_slot_key,0,2);
                    if($cur_start_hour < $start_hour){
                        unset($rows[$date][$time_slot_key]);
                    }
                }
            }

            return $rows;

        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    public function resetFutureOrders()
    {
        try {
            $result =   (new OrderDateLimitRepository)->resetFutureOrders();

            return ['data' => $result];
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}