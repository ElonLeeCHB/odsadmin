<?php

namespace App\Models\Sale;

use Illuminate\Database\Eloquent\Model;
use App\Models\Sale\TimeSlotLimit;
use Carbon\Carbon;
use App\Traits\Model\ModelTrait;

class OrderDateLimit extends Model
{
    use ModelTrait;

    protected $guarded = [];
    public $timestamps = false;


    /**
     * Functions
     */

















    
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
            $db_date_time_slots = (new OrderDateLimit)->getOrderLimitsByDate($datelimits['Date']);

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
