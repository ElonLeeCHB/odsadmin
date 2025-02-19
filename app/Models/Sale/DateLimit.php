<?php

namespace App\Models\Sale;

use Illuminate\Database\Eloquent\Model;
use App\Models\Setting\Setting;

class DateLimit extends Model
{
    protected $table = 'datelimits';
    protected $guarded = [];
    public $timestamps = false;


    /**
     * Functions
     */
    
    public function getCurrentDateLimits($date)
    {
        $rows = Datelimit::where('Date', $date)->get();

        if($rows->isEmpty()){
            $result = $this->getDefaultDateLimits($date);
        }else{
            $result['Date'] = $date;

            foreach ($rows as $row) {
                $result['TimeSlots'][$row->TimeSlot]['MaxQuantity'] = $row->MaxQuantity;
                $result['TimeSlots'][$row->TimeSlot]['OrderedQuantity'] = $row->OrderedQuantity ?? 0;
                $result['TimeSlots'][$row->TimeSlot]['AcceptableQuantity'] = $row->AcceptableQuantity ?? $row->MaxQuantity;
            }
        }

        return $result;
    }

    // 根據預設的每日基本資料，取得某日的預設
    public function getDefaultDateLimits($date)
    {
        $row = Setting::where('group','pos')->where('setting_key', 'pos_timeslotlimits')->first();
        $default_time_slots = $row->setting_value;

        $result = [];
        $result['Date'] = $date;

        if(!empty($default_time_slots)){
            foreach ($default_time_slots as $time_slot => $value) {
                $result['TimeSlots'][$time_slot]['MaxQuantity'] = $value;
                $result['TimeSlots'][$time_slot]['OrderedQuantity'] = 0;
                $result['TimeSlots'][$time_slot]['AcceptableQuantity'] = $value;
            }
        }

        return $result;
    }

    // 根據預設的每日基本資料，重設每日的預設
    public function setDefaultDateLimits($date)
    {
        try {
            $default_date_limits = $this->getDefaultDateLimits($date);

            // 新增記錄
            foreach ($default_date_limits['TimeSlots'] as $time_slot => $row) {
                $create_data[] = [
                    'Date' => $date,
                    'TimeSlot' => $time_slot,
                    'MaxQuantity' => $row['MaxQuantity'],
                    'OrderedQuantity' => 0,
                    'AcceptableQuantity' => $row['AcceptableQuantity'],
                ];
            }

            Datelimit::whereDate('Date', $date)->delete();
    
            if(!empty($create_data)){
                if(Datelimit::insert($create_data)){
                    return Datelimit::whereDate('Date', $date)->get()->toArray();
                }
            }

        } catch (\Throwable $th) {
            throw new \Exception('Error: ' . $th->getMessage());
        }
    }
}
