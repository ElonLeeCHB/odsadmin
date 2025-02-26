<?php

namespace App\Repositories\Eloquent\Sale;

use App\Repositories\Eloquent\Repository;
use App\Helpers\Classes\DataHelper;
use App\Models\Sale\Order;
use App\Models\Sale\OrderDateLimit;
use App\Models\Sale\TimeSlotLimit;
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

        return $this->sortTimeSlotKeys($this->default_limits);
    }

    // 確保時間段按照時間由早到晚
    public function sortTimeSlotKeys($default_limit_rows) 
    {
        uksort($default_limit_rows, function($a, $b) {
            $startA = explode('-', $a)[0];
            $startB = explode('-', $b)[0];
    
            return strtotime($startA) - strtotime($startB);
        });
    
        return $default_limit_rows;
    }

    // 取得特定陣列格式。傳入的 $rows 必須是 OrderDateLimit 的 Collection 或是陣列化。
    public function getFormattedDataFromRowsByDate($rows)
    {
        $rows  = DataHelper::toCleanCollection($rows);

        $result = [];

        $date = null;

        foreach ($rows as $row) {
            if(empty($date)){
                $date = $row->Date;
            }

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

        return $result;
    }

    // 取得指定日期的資料
    public function getDbDateLimitsByDate($date)
    {
        $date = Carbon::parse($date)->toDateString();
        $rows = OrderDateLimit::whereDate('Date', $date)->get();

        if($rows->isEmpty()){
            $result = $this->getFormattedByDefault($date);
        }else{
            $result = $this->getFormattedDataFromRowsByDate($rows);
        }

        return $result;
    }

    // 根據預設的數量基本資料，轉為指定日期的特定陣列
    public function getFormattedByDefault($date)
    {
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
    }

    // 根據預設的每日基本資料，重設每日的預設
    public function setDefaultDateLimits($date)
    {
        $default_limits = $this->getFormattedByDefault($date);

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
        $time_slot_keys = array_keys($formatted_data['TimeSlots']);
        
        // 從最晚的時間段開始處理
        $time_slot_keys = array_keys($formatted_data['TimeSlots']);
        $current_time_slot_key = last($time_slot_keys);
        
        // 從下午回推到早上九點停止
        while(substr($current_time_slot_key,0,2) != '09'){

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
        try {
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

            return true;

        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    // 計算指定日期的訂單數量並更新資料庫
    public function refreshOrderedQuantityByDate($date)
    {
        // 獲取指定日期的資料
        $db_formatted =  $this->getDbDateLimitsByDate($date);

        // 訂單資料

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
            //             echo "<pre>",setDefaultOrderlimits($orders,true),"</pre>";exit;
            // // $sql = DB::getQueryLog();

            $builder = DB::table('orders as o')
                        ->select('o.id', 'o.delivery_date', 'op.id as order_product_id', 'op.order_id', 'op.product_id', 'op.name', 'op.quantity')
                        ->join('order_products as op', 'o.id', '=', 'op.order_id')
                        ->join('product_tags as pt', 'op.product_id', '=', 'pt.product_id')
                        ->where('pt.term_id', 1331)  //1331=套餐
                        ->whereDate('o.delivery_date', $date)
                        ->whereIn('o.status_code', $this->increase_status_codes);

            $orders = $builder->get();
        //

        // 初始化結果數組
        $result = [];
        $result['Date'] = $date;
        $result['TimeSlots'] = [];

        $array = [];

        foreach ($orders ?? [] as $order) {

            $time_slot_key = (new TimeSlotLimit)->getTimeSlotKey($order->delivery_date);

            if(!isset($array[$time_slot_key]) || !isset($array[$time_slot_key]['MaxQuantity']) || !isset($array[$time_slot_key]['OrderedQuantity'])){
                $array[$time_slot_key]['MaxQuantity'] = $db_formatted['TimeSlots'][$time_slot_key]['MaxQuantity'] ?? 0;
                $array[$time_slot_key]['OrderedQuantity'] = 0;
            }

            $array[$time_slot_key]['OrderedQuantity'] += $order->quantity;
        }

        // 上面迴圈必須跑完執行完，才能執行下面的迴圈。

        $upsert_data = [];

        foreach ($array as $time_slot_key => $row) {
            $upsert_data[] = [
                'Date' => $date,
                'TimeSlot' => $time_slot_key,
                'MaxQuantity' => $row['MaxQuantity'],
                'OrderedQuantity' => $row['OrderedQuantity'],
                'AcceptableQuantity' => $row['MaxQuantity'] - $row['OrderedQuantity'],
            ];
        }

        OrderDateLimit::upsert($upsert_data, ['Date', 'TimeSlot'], ['MaxQuantity', 'OrderedQuantity', 'AcceptableQuantity']);

        // 重新再抓一次然後返回
        $formatted =  (new OrderDateLimitRepository)->getDbDateLimitsByDate($date);
        
        return $formatted;
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

    //增加訂單數量 訂單商品必須包含 product_tag_ids
    public function increaseByOrder(Order $order)
    {
        $data = request()->all();

        $time_slot_key = (new TimeSlotLimit)->getTimeSlotKey($order->delivery_date);

        $db_formatted =  (new OrderDateLimitRepository)->getDbDateLimitsByDate($order->delivery_date);

        foreach ($data['order_products'] ?? [] as $order_product) { //1331 = 套餐
            if(in_array(1331, $order_product['product_tag_ids'] ?? [])){
                $db_formatted['TimeSlots'][$time_slot_key]['OrderedQuantity'] += $order_product['quantity'];
                $db_formatted['TimeSlots'][$time_slot_key]['AcceptableQuantity'] = $db_formatted['TimeSlots'][$time_slot_key]['MaxQuantity'] - $db_formatted['TimeSlots'][$time_slot_key]['OrderedQuantity'];
            }
        }

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
                'OrderedQuantity' => 0,
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

        if ($record) {
            return true;
        }

        $records = OrderDateLimit::whereBetween('Date', [$todaySring, $targetDateString]);
        $records = $records->get()->keyBy('Date');

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

    // 取得未來數量
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


}

