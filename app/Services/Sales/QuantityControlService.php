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
    
            return ['data' => $result];

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
    public function updateMaxQuantityByDate($data)
    {
        // 這裡只更新 order_date_limits。不重新掃描 orders 訂單表。
        // 只用於更新上限數量，然後沿用當前的訂單數量，據以計算可訂量。不重新讀取訂單表等相關資料。

        try {
            // 獲取指定日期的資料
            $db_formatted =  (new OrderDateLimitRepository)->getFormattedByDefault($data['Date']);

            $upsert_date = [];

            foreach ($db_formatted['TimeSlots'] as $time_slot => $row) {
                if(isset($data['TimeSlots'][$time_slot])){
                    $maxQuantity = $data['TimeSlots'][$time_slot]['MaxQuantity'];
                }else{
                    $maxQuantity = $row['MaxQuantity'];
                }

                $upsert_date[] = [
                    'Date' => $data['Date'],
                    'TimeSlot' => $time_slot,
                    'MaxQuantity' => $maxQuantity,
                    'OrderedQuantity' => $row['OrderedQuantity'], //照舊
                    'AcceptableQuantity' => $maxQuantity - $row['OrderedQuantity'],
                ];
            }

            OrderDateLimit::upsert($upsert_date, ['Date', 'TimeSlot'], ['MaxQuantity', 'OrderedQuantity', 'AcceptableQuantity']);

            $formatted =  (new OrderDateLimitRepository)->getDbDateLimitsByDate($data['Date']);
            
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
            // 獲取指定日期的資料
            $db_formatted =  (new OrderDateLimitRepository)->getFormattedByDefault($date);

            $insert_data = [];

            foreach ($db_formatted['TimeSlots'] as $time_slot => $row) {
                $insert_data[] = [
                    'Date' => $date,
                    'TimeSlot' => $time_slot,
                    'MaxQuantity' => $row['MaxQuantity'],
                    'OrderedQuantity' => $row['OrderedQuantity'], //照舊
                    'AcceptableQuantity' => $row['MaxQuantity'] - $row['OrderedQuantity'],
                ];
            }

            OrderDateLimit::whereDate('Date', $date)->delete();
            OrderDateLimit::insert($insert_data);

            $formatted =  (new OrderDateLimitRepository)->getFormattedByDefault($date);
            
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
            $result =   (new OrderDateLimitRepository)->getFutureDays($futuredays);

            return ['data' => $result];
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }
}