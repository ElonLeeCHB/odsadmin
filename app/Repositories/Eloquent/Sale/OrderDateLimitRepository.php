<?php

namespace App\Repositories\Eloquent\Sale;

use App\Repositories\Eloquent\Repository;
use App\Helpers\Classes\DataHelper;
use App\Helpers\Classes\DateHelper;
use App\Models\Sale\Order;
use App\Models\Sale\OrderDateLimit;
use App\Models\Setting\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/*
時間段的格式 'HH:00-HH:59'
$datelimits = [
    '09:00-09:59' => ['MaxQuantity' => 200, 'OrderedQuantity' => 200, 'AcceptableQuantity' => 0, ]
    '10:00-10:59' => ['MaxQuantity' => 200, 'OrderedQuantity' => 0, 'AcceptableQuantity' => 200, ]
    // 其他時間段...
];
*/

class OrderDateLimitRepository extends Repository
{
    public $modelName = "\App\Models\Sale\OrderDateLimit";
    public $increase_status_codes = ['Confirmed', 'CCP'];
    private $default_limits = [];

    // 獲取預設的數量設定。來源： settings.setting_key = pos_timeslotlimits
    public function getDefaultLimits()
    {
        if(empty($this->default_limits)){
            $row = Setting::where('group','pos')->where('setting_key', 'pos_timeslotlimits')->first();
            $this->default_limits = $row->setting_value;
        }

        return $this->sortDefaultLimits($this->default_limits);
    }

    // 確保時間段按照時間由早到晚
    public function sortDefaultLimits($default_limits) {
        uksort($default_limits, function($a, $b) {
            $startA = explode('-', $a)[0];
            $startB = explode('-', $b)[0];
    
            return strtotime($startA) - strtotime($startB);
        });
    
        return $default_limits;
    }

    // 取得特定陣列格式。傳入的 $rows 必須是 OrderDateLimit 的 Collection 或是陣列化。
    public function getFormattedDataFromRows($rows)
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

    // 取得指定日期的數量資料
    public function getDateLimitsByDate($date)
    {
        $date = Carbon::parse($date)->toDateString();
        $rows = OrderDateLimit::whereDate('Date', $date)->get();

        if($rows->isEmpty()){
            $result = $this->getDefaultDateLimits($date);
        }else{
            $result = $this->getFormattedDataFromRows($rows);
        }

        return $result;
    }

    // 根據預設的數量基本資料，轉為指定日期的特定陣列
    public function getDefaultDateLimits($date)
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
    public function setDefaultDateLimits($date)
    {
        try {
            $default_limits = $this->getDefaultDateLimits($date);

            // 新增記錄
            foreach ($default_limits['TimeSlots'] as $time_slot => $row) {
                $create_data[] = [
                    'Date' => $date,
                    'TimeSlot' => $time_slot,
                    'MaxQuantity' => $row['MaxQuantity'],
                    'OrderedQuantity' => 0,
                    'AcceptableQuantity' => $row['AcceptableQuantity'],
                ];
            }

            OrderDateLimit::whereDate('Date', $date)->delete();

            if(!empty($create_data)){
                OrderDateLimit::insert($create_data);
            }

        } catch (\Throwable $th) {
            throw new \Exception('Error: ' . $th->getMessage());
        }
    }

    public function getTimeSlotKey($datetime)
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
        return sprintf("%02d:%02d-%02d:%02d", $start_hour, $start_minute, $start_hour, $end_minute);
    }

    // 確保載入關聯
    private function reloadNecessaryRelationships(Order $order)
    {
        if (!$order->relationLoaded('orderProducts')) {
            $order->load(['orderProducts' => function($query) {
                $query->select('id', 'name')
                      ->with(['productTags' => function($query) {
                          $query->select('term_sdfid', 'product_id');
                      }]);
            }]);
        }
    }

    // 執行重設的入口
    public function resetQuantityControl(Order $saved_order, Order $old_order)
    {
        $this->reloadNecessaryRelationships($saved_order);
        $this->reloadNecessaryRelationships($old_order);

        // $this->decreaseByOrder($old_order);
        // echo "<pre>", print_r('decreaseByOrder 結束', true), "</pre>";exit;
        $this->increaseByOrder($saved_order);
    }

    public function getOrderProductsQuantity($order_id)
    {
        return DB::table('orders as o')
                        ->join('order_products as op', 'o.id', '=', 'op.order_id')
                        ->join('product_tags as pt', 'op.product_id', '=', 'pt.product_id')
                        ->where('pt.term_id', 1331)  // 1331 = 套餐
                        ->where('o.id', $order_id)
                        ->sum('op.quantity');  // 計算 op.quantity 的總和
    }

    //未完 減去訂單數量
    public function decreaseByOrder(Order $order)
    {
        if(empty($order->id)){
            return;
        }

        $datelimits = (new OrderDateLimit)->getCurrentDateLimits($order->delivery_date_ymd);

        foreach ($order->order_products ?? [] as $order_product) {
            $should_decrease = false;

            foreach ($order_product->productTags ?? [] as $productTag) {
                if($productTag->term_id == 1331){ // 1331=套餐
                    $should_decrease = true;
                    break;
                }
            }
            if($should_decrease == true){
                $time_slot_key = $this->getTimeSlotKey($order->delivery_date);
                $datelimits['TimeSlots'][$time_slot_key]['OrderedQuantity'] -= $order_product->quantity;
                $datelimits['TimeSlots'][$time_slot_key]['AcceptableQuantity'] = $datelimits['TimeSlots'][$time_slot_key]['MaxQuantity'] - $datelimits['TimeSlots'][$time_slot_key]['OrderedQuantity'];
            }
        }
        
        // 最後重新整理時間段數量
        (new OrderDateLimit)->updateWithFormat($datelimits);
    }

    //撰寫中 增加訂單數量
    public function increaseByOrder(Order $order)
    {
        try {
            $sumQuantity = $this->getOrderProductsQuantity($order->id);
            $date = Carbon::parse($order->delivery_date)->toDateString();
            $time_slot_key = $this->getTimeSlotKey($order->delivery_date);

            // 獲取指定日期的資料
            $db_formatted =  (new OrderDateLimitRepository)->getDateLimitsByDate($date);

            // 增加訂單數量
            $db_formatted['TimeSlots'][$time_slot_key]['OrderedQuantity'] += $sumQuantity;

            $MaxQuantity     = $db_formatted['TimeSlots'][$time_slot_key]['MaxQuantity'];
            $OrderedQuantity = $db_formatted['TimeSlots'][$time_slot_key]['OrderedQuantity'];

            $db_formatted['TimeSlots'][$time_slot_key]['AcceptableQuantity'] = $MaxQuantity - $OrderedQuantity;

            // 調整負數



            //
            
            return true;
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }
}

