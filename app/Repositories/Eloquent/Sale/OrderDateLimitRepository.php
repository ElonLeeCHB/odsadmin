<?php

namespace App\Repositories\Eloquent\Sale;

use App\Repositories\Eloquent\Repository;
use App\Helpers\Classes\DataHelper;
use App\Models\Sale\Order;
use App\Models\Sale\OrderDateLimit;
use App\Models\Sale\TimeSlotLimit;
use App\Models\Material\ProductTag;
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
    private $time_slot_keys = ["09:00-09:59", "10:00-10:59", "11:00-11:59", "12:00-12:59", "13:00-13:59","14:00-14:59", "15:00-15:59", "16:00-16:59", "17:00-17:59"];
    private $default_limit_count = 200;
    public $default_formatted_time_slots = [];

    // 獲取預設的數量設定。來源： settings.setting_key = pos_timeslotlimits
    public function getDefaultLimits()
    {
        if(empty($this->default_limits)){
            $row = Setting::where('group','pos')->where('setting_key', 'pos_timeslotlimits')->first();
            $this->default_limits = $row->setting_value;
        }

        uksort($this->default_limits , function($a, $b) {
            $startA = explode('-', $a)[0];
            $startB = explode('-', $b)[0];
    
            return strtotime($startA) - strtotime($startB);
        });

        //  確保每個時段都有
        foreach ($this->time_slot_keys as $time_slot_key) {
            if(empty($this->default_limits[$time_slot_key])){
                $this->default_limits[$time_slot_key] = $this->default_limit_count;
            }
        }

        return $this->default_limits;
    }

    public function getDefaultTimeSlotKeys()
    {
        return $this->time_slot_keys;
    }

    // 取得特定陣列格式。傳入的 $rows 必須是 OrderDateLimit Model 的 Collection 或是陣列化。
    public function getFormattedDataFromRowsByDate($date, $rows)
    {
        $rows  = DataHelper::toCleanCollection($rows);

        $result = [];

        foreach ($rows as $row) {
            if($row->Date != $date){
                return ['error' => '本方法只處理單一日期'];
            }

            $result['Date'] = $date;

            foreach ($rows as $row) {
                $result['TimeSlots'][$row->TimeSlot]['MaxQuantity'] = $row->MaxQuantity;
                $result['TimeSlots'][$row->TimeSlot]['OrderedQuantity'] = $row->OrderedQuantity ?? 0;
                $result['TimeSlots'][$row->TimeSlot]['AcceptableQuantity'] = $row->AcceptableQuantity ?? $row->MaxQuantity;
            } 
        }

        //確保每個時段都有
            $default_limits = $this->getDefaultLimits();

            foreach ($default_limits as $time_slot_key => $MaxQuantity) {
                if(!isset($result['TimeSlots'][$time_slot_key])){
                    $result['TimeSlots'][$time_slot_key] = [
                        'Date' => $date,
                        'TimeSlot' => $time_slot_key,
                        'MaxQuantity' => $MaxQuantity,
                        'OrderedQuantity' => 0,
                        'AcceptableQuantity' => $MaxQuantity,
                    ];
                }
            }
        //

        return $result;
    }

    // 取得指定日期的資料
    public function getDbDateLimitsByDate($date)
    {
        $date = Carbon::parse($date)->toDateString();
        $rows = OrderDateLimit::whereDate('Date', $date)->get();

        if($rows->isEmpty()){
            $formatted_data = $this->getDefaultFormattedDataByDate($date); //從 settings 產生資料。但如果 settings 的時間段有缺？
        }else{
            $formatted_data = $this->getFormattedDataFromRowsByDate($date, $rows); //從資料庫而來，但如果當日的時間段有缺？
        }

        $this->adjustFormattedData($formatted_data);

        return $formatted_data;
    }

    public function getDefaultFormattedTimeSlots()
    {
        if(!empty($this->default_formatted_time_slots)){
            return $this->default_formatted_time_slots;
        }

        $default_time_slots = $this->getDefaultLimits();

        $result = [];

        if(!empty($default_time_slots)){
            foreach ($default_time_slots as $time_slot => $value) {
                $result[$time_slot]['MaxQuantity'] = $value;
                $result[$time_slot]['OrderedQuantity'] = 0;
                $result[$time_slot]['AcceptableQuantity'] = $value;
            }
        }

        $this->default_formatted_time_slots = $result;

        return $result;
    }

    // 根據預設的數量基本資料，轉為指定日期的特定陣列
    public function getDefaultFormattedDataByDate($date)
    {        
        $default_time_slots = $this->getDefaultLimits();

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
    }

    // 根據預設的每日基本資料，重設每日的預設
    public function setDefaultDateLimits($date)
    {
        $default_limits = $this->getDefaultFormattedDataByDate($date);

        // 新增記錄
        $upsert_data = [];

        foreach ($default_limits['TimeSlots'] as $time_slot => $row) {
            $upsert_data[] = [
                'Date' => $date,
                'TimeSlot' => $time_slot,
                'MaxQuantity' => $row['MaxQuantity'],
                'OrderedQuantity' => 0,
                'AcceptableQuantity' => $row['AcceptableQuantity'],
            ];
        }

        OrderDateLimit::upsert($upsert_data, ['Date', 'TimeSlot'], ['MaxQuantity', 'OrderedQuantity', 'AcceptableQuantity']);
    }

    // 重設資料陣列。不寫入資料庫
    public function adjustFormattedData(&$formatted_data)
    {
        if(empty($formatted_data['Date'])){
            throw new \Exception("格式錯誤。缺少日期索引！");
        }

        // 預設的時間段
        $default_time_slot_keys = $this->getDefaultTimeSlotKeys();

        //傳入的時間段
        $time_slot_keys = array_keys($formatted_data['TimeSlots']);

        // 避免時間段有缺, 填充有缺的時間段
        $missing_time_slot_keys = array_diff($default_time_slot_keys, $time_slot_keys);

        if(!empty($missing_time_slot_keys)){
            $default_limits = $this->getDefaultLimits();

            foreach ($missing_time_slot_keys as $missing_time_slot_key) {
                $formatted_data['TimeSlots'][$missing_time_slot_key] = [
                    'MaxQuantity' => $default_limits[$missing_time_slot_key],
                    'OrderedQuantity' => 0,
                    'AcceptableQuantity' => $default_limits[$missing_time_slot_key],
                ];
            }
        }

        //計算可訂量
        foreach ($formatted_data['TimeSlots'] as $time_slot_key => $row) {
            $formatted_data['TimeSlots'][$time_slot_key]['AcceptableQuantity'] = $row['MaxQuantity'] - $row['OrderedQuantity'];
        }

        // 從最晚的時間段開始處理
        $time_slot_keys = array_keys($formatted_data['TimeSlots']);
        $current_time_slot_key = last($time_slot_keys);
        
        // 從下午回推到早上
        while(substr($current_time_slot_key,0,2) != '07'){

            // 上一個時間段
            $time_parts = explode('-', $current_time_slot_key);
            $start_time = Carbon::parse($time_parts[0]);
            $previous_time = $start_time->subHour()->format('H:i');
            $previous_time_slot_key = (new TimeSlotLimit)->getTimeSlotKey($previous_time);

            // 可訂量
            if($formatted_data['TimeSlots'][$current_time_slot_key]['AcceptableQuantity'] < 0){
                // 將上一個時間段的可訂量再扣掉當前時間段的可訂量。
                $formatted_data['TimeSlots'][$previous_time_slot_key]['AcceptableQuantity'] -= abs($formatted_data['TimeSlots'][$current_time_slot_key]['AcceptableQuantity']);
                $formatted_data['TimeSlots'][$current_time_slot_key]['AcceptableQuantity'] = 0; // 當前時間段的可訂量設為0
            }

            $current_time_slot_key = $previous_time_slot_key; // 為了繼續處理上一個時間段
        }
    }

    // 根據輸入的資料陣列，更新資料庫
    public function upsertWithFormat(&$formatted_data)
    {
        //先調整數量
        $this->adjustFormattedData($formatted_data);

        $upsert_data = [];

        foreach ($formatted_data['TimeSlots'] as $time_slot => $row) {
            $upsert_data[] = [
                'Date' => $formatted_data['Date'],
                'TimeSlot' => $time_slot,
                'MaxQuantity' => $formatted_data['TimeSlots'][$time_slot]['MaxQuantity'],
                'OrderedQuantity' => $formatted_data['TimeSlots'][$time_slot]['OrderedQuantity'],
                'AcceptableQuantity' => $formatted_data['TimeSlots'][$time_slot]['AcceptableQuantity'],
            ];
        }

        OrderDateLimit::upsert($upsert_data, ['Date', 'TimeSlot'], ['MaxQuantity', 'OrderedQuantity', 'AcceptableQuantity']);
    }

    // 計算指定日期的訂單數量並更新資料庫
    public function refreshOrderedQuantityByDate($date)
    {
        $date = Carbon::parse($date)->format('Y-m-d');
        
        // 獲取指定日期的資料
        $formatted_data =  $this->getDbDateLimitsByDate($date);

        // 既然當日訂單要重算，所以先設歸零。
        foreach ($formatted_data['TimeSlots'] as $time_slot_key => $row) {
            $formatted_data['TimeSlots'][$time_slot_key]['OrderedQuantity'] = 0;
            $formatted_data['TimeSlots'][$time_slot_key]['AcceptableQuantity'] = $formatted_data['TimeSlots'][$time_slot_key]['MaxQuantity'];
        }

        // 訂單資料
        $builder = DB::table('orders as o')
                    ->select('o.id', 'o.delivery_date', 'op.id as order_product_id', 'op.order_id', 'op.product_id', 'op.name', 'op.quantity')
                    ->join('order_products as op', 'o.id', '=', 'op.order_id')
                    ->join('product_tags as pt', 'op.product_id', '=', 'pt.product_id')
                    ->where('pt.term_id', 1331)  //1331=套餐
                    ->whereDate('o.delivery_date', $date)
                    ->whereIn('o.status_code', $this->increase_status_codes)
                    ->orderBy('o.delivery_date');
        
        $customOrders = $builder->get();
        
        $this->updateCustomOrders($customOrders);

        $formatted_data =  $this->getDbDateLimitsByDate($date); //抓 order_date_limits

        if(empty($formatted_data)){
            $formatted_data = $this->getDefaultFormattedDataByDate($date); //抓預設的
        }

        return $formatted_data;
    }

    // 根據訂單id 取得套餐數量
    public function getOrderProductsQuantity($order_id)
    {
        return DB::table('orders as o')
                        ->join('order_products as op', 'o.id', '=', 'op.order_id')
                        ->join('product_tags as pt', 'op.product_id', '=', 'pt.product_id')
                        ->where('pt.term_id', 1331)  // 1331 = 套餐
                        ->where('o.id', $order_id)
                        ->sum('op.quantity');  // 計算 op.quantity 的總和
    }

    //增加訂單數量
    public function increaseByOrder(Order $order)
    {
        if (!$order->relationLoaded('orderProducts')) {
            $order->load('orderProducts');
        }

        $product_ids = $order->orderProducts->pluck('product_id');
        
        $product_ids_in_1331 = ProductTag::where('term_id', 1331)->whereIn('product_id', $product_ids)->pluck('product_id')->toArray();

        $quantity = 0;

        foreach ($order->order_products as $order_product) {
            if(in_array($order_product['product_id'], $product_ids_in_1331)){
                $quantity += $order_product['quantity'];  
            }
        }

        $time_slot_key = (new TimeSlotLimit)->getTimeSlotKey($order->delivery_date);

        $db_formatted =  (new OrderDateLimitRepository)->getDbDateLimitsByDate($order->delivery_date);

        //
        $db_formatted['TimeSlots'][$time_slot_key]['OrderedQuantity'] = $quantity;
        $db_formatted['TimeSlots'][$time_slot_key]['AcceptableQuantity'] = $db_formatted['TimeSlots'][$time_slot_key]['MaxQuantity'] - $quantity;

        $this->updateWithFormattedData($db_formatted);

        $this->makeFutureDays(60);
    }

    // 傳入格式化的陣列，更新資料庫
    public function updateWithFormattedData($db_formatted)
    {
        // 先調整負數數量
        $this->adjustFormattedData($db_formatted);

        // 新增記錄
        $upsert_data = [];

        foreach ($db_formatted['TimeSlots'] as $time_slot => $row) {
            $upsert_data[] = [
                'Date' => $db_formatted['Date'],
                'TimeSlot' => $time_slot,
                'MaxQuantity' => $row['MaxQuantity'],
                'OrderedQuantity' => $row['OrderedQuantity'],
                'AcceptableQuantity' => $row['AcceptableQuantity'],
            ];
        }
        
        OrderDateLimit::upsert($upsert_data, ['Date', 'TimeSlot'], ['MaxQuantity', 'OrderedQuantity', 'AcceptableQuantity']);
    }

    // 產生未來的預設資料 若已有資料則略過
    public function makeFutureDays($futureDays = 60)
    {
        $today = Carbon::today();
        $todaySring = $today->format('Y-m-d');
        $targetDateString = Carbon::today()->addDays($futureDays)->format('Y-m-d');

        $record = OrderDateLimit::where('Date', 'LIKE', "$targetDateString%")->first();
        
        // 如果最後一天已有資料，則假設這段期間都有資料，略過。
        if ($record) {
            return true;
        }

        $records = OrderDateLimit::whereBetween('Date', [$todaySring, $targetDateString]);

        $records = $records->get() //Date 欄位雖然型態是 date 日期，值也是 Y-m-d, 但會被 laravel 自動轉為 Carbon 變成 Y-m-d H:i:s。需要轉換。
                    ->mapWithKeys(function ($item) {
                        return [$item->Date->format('Y-m-d') => $item];
                    });

        $default_limits = $this->getDefaultLimits();

        $upsert_data = [];

        for ($i = 0; $i < $futureDays; $i++) {
            $date = Carbon::today()->addDays($i)->format('Y-m-d');

            if(empty($records[$date])){
                foreach ($default_limits as $time_slot_key => $max) {
                    $upsert_data[] = [
                        'Date' => $date,
                        'TimeSlot' => $time_slot_key,
                        'MaxQuantity' => $max,
                        'OrderedQuantity' => 0,
                        'AcceptableQuantity' => $max,
                    ];
                }
                OrderDateLimit::upsert($upsert_data, ['Date', 'TimeSlot'], ['MaxQuantity', 'OrderedQuantity', 'AcceptableQuantity']);
            }
        }
    }

    // 取得未來多天數的資料
    public function getFutureDays($futureDays = 30)
    {
        $today = Carbon::today();
        $todaySring = $today->format('Y-m-d');
        $targetDateString = Carbon::today()->addDays($futureDays)->format('Y-m-d');

        $records = OrderDateLimit::whereBetween('Date', [$todaySring, $targetDateString])->orderBy('Date');
        $records = $records->get();

        $result = [];

        foreach ($records ?? [] as $row) {
            $date = $row->Date->format('Y-m-d');

            $result[$date][$row->TimeSlot] = [
                    'MaxQuantity' => $row->MaxQuantity,
                    'OrderedQuantity' => $row->OrderedQuantity,
                    'AcceptableQuantity' => $row->AcceptableQuantity,
            ];
        }

        for ($i = 0; $i < $futureDays; $i++) {
            $date = Carbon::today()->addDays($i)->format('Y-m-d');

            if(empty($result[$date])){
                $default_limits = $this->getDefaultLimits();

                $upsert_date = [];

                foreach ($default_limits as $time_slot_key => $max) {
                    $result[$date][$time_slot_key] = [
                        'MaxQuantity' => $max,
                        'OrderedQuantity' => 0,
                        'AcceptableQuantity' => $max,
                    ];

                    $upsert_date[] = [
                        'Date' => $date,
                        'TimeSlot' => $time_slot_key,
                        'MaxQuantity' => $max,
                        'OrderedQuantity' => 0,
                        'AcceptableQuantity' => $max,
                    ];
                }
                
                OrderDateLimit::upsert($upsert_date, ['Date', 'TimeSlot'], ['MaxQuantity', 'OrderedQuantity', 'AcceptableQuantity']);
            }
        }

        return $result;
    }

    // 重設未來訂單
    public function resetFutureOrders()
    {
        // 取得今天的日期
        $today = Carbon::today();

        // 查詢所有 delivery_date 大於今天的訂單
        $builder = DB::table('orders as o')
                    ->select('o.id', 'o.delivery_date', 'op.id as order_product_id', 'op.order_id', 'op.product_id', 'op.name', 'op.quantity')
                    ->join('order_products as op', 'o.id', '=', 'op.order_id')
                    ->join('product_tags as pt', 'op.product_id', '=', 'pt.product_id')
                    ->where('pt.term_id', 1331)  //1331=套餐
                    ->where('delivery_date', '>', $today)
                    ->whereIn('o.status_code', $this->increase_status_codes)
                    ->orderBy('o.delivery_date');

        $customOrders = $builder->get();

        return $this->updateCustomOrders($customOrders);
    }

    // 更新特定格式的的訂單內容
    public function updateCustomOrders($customOrders)
    {
        $all_formatted_data = [];

        //計算 $order->delivery_date 必須是 datetime 並且有時間。預設取 delivery_time_range 的結束時間
        foreach ($customOrders ?? [] as $order) {
            $delivery_date = Carbon::parse($order->delivery_date)->format('Y-m-d');

            $time_slot_key = (new TimeSlotLimit)->getTimeSlotKey($order->delivery_date);

            if(empty($all_formatted_data[$delivery_date])){
                $all_formatted_data[$delivery_date] = $this->getDefaultFormattedDataByDate($delivery_date);
            }

            $all_formatted_data[$delivery_date]['TimeSlots'][$time_slot_key]['OrderedQuantity'] += $order->quantity;
        }
        //以上將所有訂單數量加總完成

        //以下計算 formatted_data 的可訂量。
        $upsert_data = [];

        $dates = [];

        foreach ($all_formatted_data as $delivery_date => $formatted_data) {
            $this->adjustFormattedData($formatted_data);
            // 調整後，不用再算

            foreach ($formatted_data['TimeSlots'] as $time_slot_key => $row) {
                $upsert_data[] = [
                    'Date' => $delivery_date,
                    'TimeSlot' => $time_slot_key,
                    'MaxQuantity' => $row['MaxQuantity'],
                    'OrderedQuantity' => $row['OrderedQuantity'],
                    'AcceptableQuantity' => $row['MaxQuantity'] - $row['OrderedQuantity'], 
                ];

                $dates[] = $delivery_date;
            }
        }
        $dates = array_unique($dates);

        if(!empty($upsert_data)){
            OrderDateLimit::whereIn('Date', $dates)->delete();
            OrderDateLimit::upsert($upsert_data, ['Date', 'TimeSlot'], ['MaxQuantity', 'OrderedQuantity', 'AcceptableQuantity']);
        }
    }


}

