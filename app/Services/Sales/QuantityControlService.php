<?php
namespace App\Services\Sales;

use App\Helpers\Classes\DataHelper;
use App\Helpers\Classes\DateHelper;
use Illuminate\Support\Facades\DB;
use App\Services\Service;
use App\Models\Setting\Setting;
use App\Models\Sale\OrderLimit;
use App\Models\Sale\Order;
use Carbon\Carbon;

class QuantityControlService extends Service
{
    private $default_time_slots_with_quantity = [];
    private $default_date_time_slots = [];

    public function getTimeslots()
    {
        if(empty($this->default_time_slots_with_quantity)){
            $row = Setting::where('group','pos')->where('setting_key', 'pos_timeslotlimits')->first();
            $this->default_time_slots_with_quantity = $row->setting_value;
        }

        return $this->default_time_slots_with_quantity;
    }

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
            throw new \Exception('Error: ' . $th->getMessage());
        }
    }

    public function updateMaxQuantityByDate($data)
    {
        try {
            $current_date_time_slots = (new OrderLimit)->getCurrentOrderLimits($data['Date']);

            $insert_data = [];

            foreach ($current_date_time_slots['TimeSlots'] as $time_slot => $row) {
                if(isset($data['TimeSlots'][$time_slot])){
                    $maxQuantity = $data['TimeSlots'][$time_slot];
                }else{
                    $maxQuantity = 0;
                }

                $acceptableQuantity = $maxQuantity - $row['OrderedQuantity'];
                $acceptableQuantity = ($acceptableQuantity > 0) ? $acceptableQuantity : 0;
                
                $insert_data[] = [
                    'Date' => $data['Date'],
                    'TimeSlot' => $time_slot,
                    'MaxQuantity' => $maxQuantity,
                    'OrderedQuantity' => $row['OrderedQuantity'], //照舊
                    'AcceptableQuantity' => $acceptableQuantity,
                ];
            }

            OrderLimit::whereDate('Date', $data['Date'])->delete();

            OrderLimit::insert($insert_data);

            return true;

        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

                // echo "<pre>",setDefaultOrderlimits($date,true),"</pre>";exit;
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

    // 重設每日上限
    public function resetMaxQuantityByDate($date)
    {
        try {
            (new OrderLimit)->setDefaultOrderlimits($date);

            return (new OrderLimit)->getDefaultOrderlimits($date);
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    // 更新訂單數量，但每日上限不變，
    public function refreshOrderedQuantityByDate($date)
    {
        $current_date_time_slots = $this->getOrderlimitsByDate($date);

        try {
            if (DateHelper::isValid($date)) {

                // 訂單資料
                $builder = DB::table('orders as o')
                            ->select('o.id', 'o.delivery_date', 'op.id as order_product_id', 'op.order_id', 'op.product_id', 'op.name', 'op.quantity')
                            ->join('order_products as op', 'o.id', '=', 'op.order_id')
                            ->join('product_tags as pt', 'op.product_id', '=', 'pt.product_id')
                            ->where('pt.term_id', 1331)  //1331=套餐
                            ->whereDate('o.delivery_date', $date)
                            ->whereIn('o.status_code', ['CCP', 'Confirmed']);

                $orders = $builder->get();

                // 初始化結果數組
                $result = [];
                $result['Date'] = $date;
                $result['TimeSlots'] = [];

                foreach ($orders as $order) {

                    $time_slot = $this->getTimeSlotString($order->delivery_date);


                    if(!isset($array[$time_slot]) || !isset($array[$time_slot]['MaxQuantity']) || !isset($array[$time_slot]['OrderedQuantity'])){
                        $array[$time_slot]['MaxQuantity'] = $current_date_time_slots['TimeSlots'][$time_slot]['MaxQuantity'] ?? 0;
                        $array[$time_slot]['OrderedQuantity'] = 0;
                    }

                    $array[$time_slot]['OrderedQuantity'] += $order->quantity;
                }

                // 上面迴圈必須跑完執行完，才能執行下面的迴圈。

                $upsert_data = [];

                foreach ($array as $time_slot => $row) {
                    $upsert_data[] = [
                        'Date' => $date,
                        'TimeSlot' => $time_slot,
                        'MaxQuantity' => $row['MaxQuantity'],
                        'OrderedQuantity' => $row['OrderedQuantity'],
                        'AcceptableQuantity' => $row['MaxQuantity'] - $row['OrderedQuantity'],
                    ];
                }

                OrderLimit::upsert($upsert_data, ['Date', 'TimeSlot'], ['MaxQuantity', 'OrderedQuantity', 'AcceptableQuantity']);

                // 重新再抓一次然後返回
                return $this->getOrderlimitsByDate($date);
            }
        } catch (\Throwable $th) {
            throw new \Exception($th->getMessage());
        }
    }

    public function getOrderlimitsByDate($date)
    {
        try {    
            $current_date_time_slots = OrderLimit::where('Date', $date)->get();

            $result['Date'] = $date;
    
            // 如果 date_time_slots 是空的，根據預設做更新
            if($current_date_time_slots->isEmpty()){
                (new OrderLimit)->setDefaultOrderlimits($date);

                $current_date_time_slots = OrderLimit::where('Date', $date)->get();
            }

            foreach ($current_date_time_slots as $row) {
                $result['TimeSlots'][$row->TimeSlot]['MaxQuantity'] = $row->MaxQuantity;
                $result['TimeSlots'][$row->TimeSlot]['OrderedQuantity'] = $row->OrderedQuantity ?? 0;
                $result['TimeSlots'][$row->TimeSlot]['AcceptableQuantity'] = $row->AcceptableQuantity ?? $row->MaxQuantity;
            }

            return $result;

        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    private function getTimeSlotString($datetime)
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
}