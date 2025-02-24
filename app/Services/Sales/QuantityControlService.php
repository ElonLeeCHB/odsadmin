<?php
namespace App\Services\Sales;

use App\Services\Service;
use App\Models\Setting\Setting;
use App\Models\Sale\OrderDateLimit;
use App\Repositories\Eloquent\Sale\OrderDateLimitRepository;

class QuantityControlService extends Service
{
    // 完成。絕對不再改
    public function getTimeslots()
    {
        try {
            $result = (new OrderDateLimitRepository)->getDefaultLimits();
    
            return ['data' => $result];

        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    // 完成。絕對不再改
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

    // 完成。絕對不再改
    public function getOrderDateLimitsByDate($date)
    {
        try {
            $result =  (new OrderDateLimitRepository)->getDbDateLimitsByDate($date);

            return ['data' => $result];

        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    // 完成。絕對不再改
    public function updateMaxQuantityByDate($data)
    {
        // 這裡只更新 order_date_limits。不重新掃描 orders 訂單表。
        // 只用於更新上限數量，然後沿用當前的訂單數量，據以計算可訂量。不重新讀取訂單表等相關資料。

        try {
            // 獲取指定日期的資料
            $db_formatted =  (new OrderDateLimitRepository)->getFormattedByDefault($data['Date']);

            $insert_data = [];

            foreach ($db_formatted['TimeSlots'] as $time_slot => $row) {
                if(isset($data['TimeSlots'][$time_slot])){
                    $maxQuantity = $data['TimeSlots'][$time_slot];
                }else{
                    $maxQuantity = $row['MaxQuantity'];
                }
                
                $insert_data[] = [
                    'Date' => $data['Date'],
                    'TimeSlot' => $time_slot,
                    'MaxQuantity' => $maxQuantity,
                    'OrderedQuantity' => $row['OrderedQuantity'], //照舊
                    'AcceptableQuantity' => $maxQuantity - $row['OrderedQuantity'],
                ];
            }

            OrderDateLimit::whereDate('Date', $data['Date'])->delete();
            OrderDateLimit::insert($insert_data);

            $formatted =  (new OrderDateLimitRepository)->getFormattedByDefault($data['Date']);
            
            return ['data' => $formatted];

        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    // 完成。絕對不再改
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

    // 更新訂單數量
    public function refreshOrderedQuantityByDate($date)
    {
        try {
            $formatted =   (new OrderDateLimitRepository)->refreshOrderedQuantityByDate($date);
            
            return ['data' => $formatted];
        } catch (\Throwable $th) {
            throw new \Exception($th->getMessage());
        }
    }
}