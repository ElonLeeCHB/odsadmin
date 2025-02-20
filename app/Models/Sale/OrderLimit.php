<?php

namespace App\Models\Sale;

use App\Helpers\Classes\DataHelper;
use Illuminate\Database\Eloquent\Model;
use App\Models\Setting\Setting;
use App\Models\Sale\TimeSlotLimit;
use Carbon\Carbon;
use App\Helpers\Classes\DateHelper;

class OrderLimit extends Model
{
    protected $guarded = [];
    public $timestamps = false;


    /**
     * Functions
     */

    // 取得格式化之後的陣列。$rows 可以是 collection 或是陣列
    public function getFormattedData($rows)
    {
        $rows  = DataHelper::toCleanCollection($rows);

        $result = [];

        foreach ($rows as $row) {
            $result['Date'] = $row->Date;

            foreach ($rows as $row) {
                $result['TimeSlots'][$row->TimeSlot]['MaxQuantity'] = $row->MaxQuantity;
                $result['TimeSlots'][$row->TimeSlot]['OrderedQuantity'] = $row->OrderedQuantity ?? 0;
                $result['TimeSlots'][$row->TimeSlot]['AcceptableQuantity'] = $row->AcceptableQuantity ?? $row->MaxQuantity;
            } 
        }

        return $result;

    }

    public function getCurrentOrderLimits($date)
    {
        $rows = OrderLimit::where('Date', $date)->get();

        if($rows->isEmpty()){
            $result = $this->setDefaultOrderLimits($date);
            $result = $this->getDefaultOrderLimits($date);
        }else{
            $result = $this->getFormattedData($rows);
        }

        return $result;
    }

    // 根據預設的每日基本資料，取得某日的預設
    public function getDefaultOrderLimits($date)
    {
        try {
            $row = Setting::where('group','pos')->where('setting_key', 'pos_timeslotlimits')->first();
            $default_time_slots = $row->setting_value;
    
            $result = [];
    
            if(!empty($default_time_slots)){
                $result['Date'] = $date;
    
                foreach ($default_time_slots as $time_slot => $value) {
                    $result['TimeSlots'][$time_slot]['MaxQuantity'] = $value;
                    $result['TimeSlots'][$time_slot]['OrderedQuantity'] = 0;
                    $result['TimeSlots'][$time_slot]['AcceptableQuantity'] = $value;
                }
            }
    
            return $result;
            
        } catch (\Throwable $th) {
            throw new \Exception($th->getMessage());
        }
    }

    // 根據預設的每日基本資料，重設每日的預設
    public function setDefaultOrderLimits($date)
    {
        try {
            $default_order_limits = $this->getDefaultOrderLimits($date);

            // 新增記錄
            foreach ($default_order_limits['TimeSlots'] as $time_slot => $row) {
                $create_data[] = [
                    'Date' => $date,
                    'TimeSlot' => $time_slot,
                    'MaxQuantity' => $row['MaxQuantity'],
                    'OrderedQuantity' => 0,
                    'AcceptableQuantity' => $row['AcceptableQuantity'],
                ];
            }

            OrderLimit::whereDate('Date', $date)->delete();

            if(!empty($create_data)){
                OrderLimit::insert($create_data);
            }

        } catch (\Throwable $th) {
            throw new \Exception('Error: ' . $th->getMessage());
        }
    }

    // 時間段的格式為 'HH:00-HH:59'
    // $datelimits = [
    //     '09:00-09:59' => ['MaxQuantity' => 200, 'OrderedQuantity' => 200, 'AcceptableQuantity' => 0, ]
    //     '10:00-10:59' => ['MaxQuantity' => 200, 'OrderedQuantity' => 0, 'AcceptableQuantity' => 200, ]
    //     // 其他時間段...
    // ];
    
    /**
     * 重設 $datelimits 陣列
     * 不寫入資料庫
     */
    public function resetOrderLimitsArrayAcceptableQuantity(&$datelimits)
    {
        try {
            $time_slot_keys = array_keys($datelimits['TimeSlots']);
        
            // 從最晚的時間段開始處理
            $time_slot_keys = array_keys($datelimits['TimeSlots']);
            $current_time_slot_key = last($time_slot_keys);
            
            // 從下午回推到早上九點停止
            while(substr($current_time_slot_key,0,2) != '09'){

                // 上一個時間段
                $time_parts = explode('-', $current_time_slot_key);
                $start_time = Carbon::parse($time_parts[0]);
                $previous_time = $start_time->subHour()->format('H:i');
                $previous_time_slot_key = (new TimeSlotLimit)->getTimeSlotKey($previous_time);

                // 可訂量
                $this_AcceptableQuantity     = $datelimits['TimeSlots'][$current_time_slot_key]['AcceptableQuantity'];
                $previous_AcceptableQuantity = $datelimits['TimeSlots'][$previous_time_slot_key]['AcceptableQuantity'];

                if($this_AcceptableQuantity < 0){
                    $datelimits['TimeSlots'][$previous_time_slot_key]['AcceptableQuantity'] += $datelimits['TimeSlots'][$current_time_slot_key]['AcceptableQuantity'];
                    $datelimits['TimeSlots'][$current_time_slot_key]['AcceptableQuantity'] = 0;
                }

                $current_time_slot_key = $previous_time_slot_key;
            }

            return $datelimits;
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    public function updateWithFormat(&$datelimits)
    {
        try {
            //先調整新的數量
            $datelimits = $this->resetOrderLimitsArrayAcceptableQuantity($datelimits);

            //當前資料庫的資料
            $db_date_time_slots = (new OrderLimit)->getCurrentOrderLimits($datelimits['Date']);

            $insert_data = [];

            foreach ($db_date_time_slots['TimeSlots'] as $time_slot => $row) {
                $insert_data[] = [
                    'Date' => $datelimits['Date'],
                    'TimeSlot' => $time_slot,
                    'MaxQuantity' => $datelimits['TimeSlots'][$time_slot]['MaxQuantity'] ?? $db_date_time_slots['TimeSlots'][$time_slot]['MaxQuantity'],
                    'OrderedQuantity' => $datelimits['TimeSlots'][$time_slot]['OrderedQuantity'] ?? $db_date_time_slots['TimeSlots'][$time_slot]['OrderedQuantity'],
                    'AcceptableQuantity' => $datelimits['TimeSlots'][$time_slot]['AcceptableQuantity'] ?? $db_date_time_slots['TimeSlots'][$time_slot]['AcceptableQuantity'],
                ];
            }

            $this->whereDate('Date', $datelimits['Date'])->delete();

            $this->insert($insert_data);

            return true;

        } catch (\Throwable $th) {

            return ['error' => $th->getMessage()];
        }
    }
    
}
