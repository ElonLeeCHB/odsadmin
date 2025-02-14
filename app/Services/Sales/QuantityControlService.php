<?php
namespace App\Services\Sales;

use App\Helpers\Classes\DateHelper;
use Illuminate\Support\Facades\DB;
use App\Services\Service;
use App\Models\Setting\Setting;
use App\Models\Sale\Datelimit;

class QuantityControlService extends Service
{

    public function getTimeslot()
    {
        $row = Setting::where('group','pos')->where('setting_key', 'pos_timeslotlimits')->first();

        return $row->setting_value;
    }

    public function updateTimeslot($content)
    {
        try {
            $row = Setting::where('group','pos')->where('setting_key', 'pos_timeslotlimits')->first();
    
            if ($row) {
                $row->setting_value = json_encode($content);
                $row->save();
    
                return true;
            }
        } catch (\Throwable $th) {
            throw new \Exception('Error: ' . $th->getMessage());
        }
    }

    public function updateDatelimits($content)
    {
        try {
            Datelimit::where('Date', $content['Date'])->delete();

            foreach ($content['TimeSlots'] as $key => $limit) {
                $insert_data[] = [
                    'Date' => $content['Date'],
                    'TimeSlot' => $key,
                    'LimitCount' => $limit,
                ];
            }
    
            if(!empty($insert_data)){
                Datelimit::insert($insert_data);
            }

            return true;

        } catch (\Throwable $th) {
            throw new \Exception('Error: ' . $th->getMessage());
        }
    }

    public function getDatelimits($date)
    {
        if(DateHelper::isValidDateOrDatetime($date)){
            $rows = DB::select('SELECT * FROM datelimits WHERE DATE(`Date`) = ? ORDER BY `TimeSlot` ASC', [$date]);

            // datelimits 資料表，前人設計，有重複的問題。使用下面的形式確保消除重複
            foreach ($rows as &$row) {
                $result['Date'] = $row->Date;
                $result['TimeSlots'][$row->TimeSlot] = $row->LimitCount;
            }
        }

        return $result;
    }

}