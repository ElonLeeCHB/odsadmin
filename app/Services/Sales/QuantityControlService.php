<?php
namespace App\Services\Sales;

use App\Helpers\Classes\DateHelper;
use Illuminate\Support\Facades\DB;
use App\Services\Service;
use App\Models\Setting\Setting;
use App\Models\Sale\Datelimit;
use App\Models\Sale\Order;
use Carbon\Carbon;

class QuantityControlService extends Service
{
    private $default_time_slots_with_quantity = [];
    private $default_date_time_slots = [];

    public function getTimeslotLimits()
    {
        if(empty($this->default_time_slots_with_quantity)){
            $row = Setting::where('group','pos')->where('setting_key', 'pos_timeslotlimits')->first();
            $this->default_time_slots_with_quantity = $row->setting_value;
        }

        return $this->default_time_slots_with_quantity;
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
                    'LimitQuantity' => $limit,
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
                $result['TimeSlots'][$row->TimeSlot] = $row->LimitQuantity;
            }
        }

        return $result;
    }
                // echo "<pre>",print_r($date,true),"</pre>";exit;
                // $orders = Order::whereDate('delivery_date', '=', '2025-02-10')->get();

                // $orders = Order::select('id', 'delivery_date')->whereDate('delivery_date', '2025-02-10')
                // ->whereHas('orderProducts.productTags', function($query) {
                //     $query->where('term_id', 1331);  // 確保有 term_id = 1331
                // })
                // ->with(['orderProducts' => function($query) {
                //     $query->whereHas('productTags', function($query) {
                //         $query->where('term_id', 1331);  // 只載入包含 term_id = 1331 的 productTags
                //     });
                // }])
                // ->get();
                //             echo "<pre>",print_r($orders,true),"</pre>";exit;
                // // $sql = DB::getQueryLog();
    public function refreshDatelimitsByDate($date)
    {
        $current_date_time_slots = $this->getCurrentDateTimeSlots($date);

        try {
            if (DateHelper::isValidDateOrDatetime($date)) {
                $rows = DB::select("
                    SELECT o.id, o.delivery_date, op.id AS order_product_id, op.order_id, op.product_id, op.name, op.quantity
                    FROM orders o
                    JOIN order_products op ON o.id = op.order_id
                    JOIN product_tags pt ON op.product_id = pt.product_id
                    WHERE DATE(o.delivery_date) = :delivery_date AND pt.term_id = 1331
                ", [
                    'delivery_date' => $date
                ]);
            
                // 初始化結果數組
                $result['Date'] = $date;
                $result['TimeSlots'] = [];

                foreach ($rows as $row) {
                    $time_slot = $this->getTimeSlotString($row->delivery_date);

                    $current_date_time_slots['Date'] = $date;

                    if(!isset($current_date_time_slots['TimeSlots'][$time_slot]['OrderedQuantity'])){
                        $current_date_time_slots['TimeSlots'][$time_slot]['OrderedQuantity'] = 0;
                    }

                    $current_date_time_slots['TimeSlots'][$time_slot]['OrderedQuantity'] += $row->quantity;

                    $AcceptableQuantity = $current_date_time_slots['TimeSlots'][$time_slot]['MaxQuantity'] - $current_date_time_slots['TimeSlots'][$time_slot]['OrderedQuantity'];
                    $current_date_time_slots['TimeSlots'][$time_slot]['AcceptableQuantity'] = $AcceptableQuantity;
                }

                return $current_date_time_slots;
            }
        } catch (\Throwable $th) {
            throw new \Exception('Error: ' . $th->getMessage());
        }
    }

    private function getCurrentDateTimeSlots($date)
    {
        if (!DateHelper::isValidDateOrDatetime($date)) {
            return [];
        }

        $current_date_time_slots = Datelimit::where('Date', $date)->get();

        $result['Date'] = $date;

        // 如果 date_time_slots 是空的，從設定檔填充預設值
        if(empty($current_date_time_slots)){
            $row = Setting::where('group','pos')->where('setting_key', 'pos_timeslotlimits')->first();
            $default_time_slots = $row->setting_value;
            
            if(!empty($default_time_slots)){
                foreach ($default_time_slots as $time_slot => $value) {
                    $result['TimeSlots'][$time_slot]['MaxQuantity'] = $value;
                    $result['TimeSlots'][$time_slot]['OrderedQuantity'] = 0;
                    $result['TimeSlots'][$time_slot]['AcceptableQuantity'] = $value;
                }

                // 新增記錄
                
                foreach ($default_time_slots as $time_slot => $value) {
                    $create_date[] = [
                        'Date' => $date,
                        'TimeSlot' => $time_slot,
                        'MaxQuantity' => $value,
                        'OrderedQuantity' => 0,
                        'AcceptableQuantity' => $value,
                    ];
                }

                if(!empty($create_date)){
                    Datelimit::insert($create_date);
                }
            }

        }
        // 不是空的
        else{
            foreach ($current_date_time_slots as $row) {
                $time_slot = $this->getTimeSlotString($row->TimeSlot);

                $result['TimeSlots'][$time_slot]['MaxQuantity'] = $row->MaxQuantity;
                $result['TimeSlots'][$time_slot]['OrderedQuantity'] = $row->OrderedQuantity ?? 0;
                $result['TimeSlots'][$time_slot]['AcceptableQuantity'] = $row->AcceptableQuantity ?? $row->MaxQuantity;
            }
        }
        
        return $result;
    }

    public function getTimeSlotString($datetime)
    {
        // 檢查是否為 datetime 格式（例如：2025-02-14 14:30:00）
        if (strtotime($datetime)) {
            // 如果是 datetime 格式，使用 Carbon 解析
            $time = Carbon::parse($datetime);
        } else {
            // 如果是時間格式（H:i）
            $time = Carbon::createFromFormat('H:i', $datetime);
        }
    
        // 提取小時部分
        $hour = (int)$time->format('H');
        
        // 根據小時決定時間區段
        $start_hour = floor($hour / 1) * 1; // 每個區段寬度是 1 小時
        $start_minute = 0;
        $end_minute = 59;
    
        // 格式化時間區段
        return sprintf("%02d%02d-%02d%02d", $start_hour, $start_minute, $start_hour, $end_minute);
    }
}