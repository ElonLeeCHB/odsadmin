<?php
namespace App\Services\Sales;

use Illuminate\Support\Facades\DB;
use App\Services\Service;
use App\Models\Setting\Setting;
use App\Models\Sale\OrderDateLimit;
use App\Repositories\Eloquent\Sale\OrderDateLimitRepository;

class QuantityControlService extends Service
{
    private $default_time_slots_with_quantity = [];
    private $default_date_time_slots = [];

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
            $result =  (new OrderDateLimitRepository)->getDateLimitsByDate($date);

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
            $db_formatted =  (new OrderDateLimitRepository)->getDateLimitsByDate($data['Date']);

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

            $formatted =  (new OrderDateLimitRepository)->getDateLimitsByDate($data['Date']);
            
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
            $db_formatted =  (new OrderDateLimitRepository)->getDateLimitsByDate($date);

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

            $formatted =  (new OrderDateLimitRepository)->getDateLimitsByDate($date);
            
            return ['data' => $formatted];
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    // 更新訂單數量
    public function refreshOrderedQuantityByDate($date)
    {
        try {
            // 獲取指定日期的資料
            $db_formatted =  (new OrderDateLimitRepository)->getDateLimitsByDate($date);

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
                            ->whereIn('o.status_code', ['CCP', 'Confirmed']);

                $orders = $builder->get();
            //

            // 初始化結果數組
            $result = [];
            $result['Date'] = $date;
            $result['TimeSlots'] = [];

            foreach ($orders as $order) {

                $time_slot_key = (new OrderDateLimitRepository)->getTimeSlotKey($order->delivery_date);

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
            $formatted =  (new OrderDateLimitRepository)->getDateLimitsByDate($date);
            
            return ['data' => $formatted];
        } catch (\Throwable $th) {
            throw new \Exception($th->getMessage());
        }
    }
}