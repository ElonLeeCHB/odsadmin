<?php

namespace App\Repositories\Eloquent\Sale;

use App\Repositories\Eloquent\Repository;
use App\Helpers\Classes\DateHelper;
use App\Models\Sale\Order;
use App\Models\Sale\OrderProduct;
use App\Models\Sale\OrderProductOption;
use App\Models\Setting\Setting;
use Carbon\Carbon;

/**
 * 訂單轉備料
 * 訂單異動後，將送達日期 delivery_date 寫入 settings 資料表 setting_key='sale_order_queued_delivery_date
 * 預設行為：每20分鐘執行一次排程。讀取 sale_order_queued_delivery_date，逐一執行每一天。寫入內建快取。內含創建時間的欄位。
 * 如果20分鐘之內有執行過，則跳過，等待下一輪執行。
 * 
 * 後台備料表頁，查詢日期時，由 RequisitionService 抓取快取。如果沒有快取就不顯示資料。因為排程本就應該執行，本就應該有快取。不管是每20分鐘或是每小時。
 * 但是可以使用"更新"按鈕做即時更新，立刻更新快取。
 * 
 * 區間查詢的時候，由 RequisitionService 抓取各日快取。如果沒有快取就不顯示資料。因為排程本就應該執行，本就應該有快取。不管是每20分鐘或是每小時。
 * 
 */

class OrderDailyRequisitionRepository
{
    public function handleByDate($required_date, $force_update = 0, $is_return = false)
    {
        $required_date = Carbon::parse($required_date)->format('Y-m-d');

        $cache_key = 'sale_order_requisition_date_' . $required_date;
                
        $statistics = cache()->get($cache_key);

        // 如果快取不存在或快取中的 cache_created_at 超過指定期限
        if ($force_update || !$statistics || !isset($statistics['cache_created_at']) || Carbon::parse($statistics['cache_created_at'])->diffInMinutes(now()) > 60) {
            $statistics = $this->calculateByDate($required_date);
            cache()->put($cache_key, $statistics, 60*24*180);
        }

        if ($is_return == true){
            return $statistics;
        }
    }

    public function getStatisticsByDate($required_date, $force_update)
    {
        return $this->handleByDate($required_date, $force_update, false);
    }

    public function calculateByDate($required_date_ymd)
    {
        $requiredDateRawSql = DateHelper::parseDateToSqlWhere('delivery_date', $required_date_ymd);

        if(empty($requiredDateRawSql)){
            return false;
        }

        // 資料庫 訂單
            //需要備料的訂單狀態代號
            $sales_orders_to_be_prepared_status = Setting::where('setting_key', 'sales_orders_to_be_prepared_status')->first()->setting_value;

            $query = Order::query();
    
            $query->select(['id', 'code', 'location_id', 'delivery_date', 'delivery_time_range', 'personal_name'
                            , 'shipping_road', 'shipping_road_abbr', 'shipping_method'
                            , 'status_code'
                        ]);
            $query->whereIn('status_code', $sales_orders_to_be_prepared_status);
            $query->whereRaw($requiredDateRawSql);
            
            $query->with(['orderProducts' => function ($query) {
                $query->select(['id', 'order_id', 'product_id', 'name', 'price', 'quantity', 'sort_order'])
                    ->with([
                        'orderProductOptions' => function ($query) {
                            $query->select([
                                'id', 'order_id', 'order_product_id', 'name', 'value',
                                'quantity', 'option_id', 'option_value_id', 'map_product_id'
                            ]);
                        },
                        'productTags' => function ($query) {
                            $query->select(['product_id', 'term_id']);
                        }]);
                    }
            ]);
    
            $orders = $query->get();
    
            if ($orders->isEmpty()) {
                return [];
            }
        // end 訂單

        //3吋潤餅、6吋潤餅的對應
        $sales_wrap_map = Setting::where('setting_key','sales_wrap_map')->first()->setting_value;
        $wrap_ids_needing_halving = array_keys($sales_wrap_map); //3吋潤餅的 id
        //6吋潤餅

        // 商品代號
        $big_guabao_ids = [1809,1810,1811,1812,1813,1814,1838,1839,1840];
        $small_guabao_ids = [1664,1665,1666,1667,1668,1669,1672,1688,1689];
        $lumpia6in_ids = [1010,1011,1012,1013,1014,1015,1056,1058,1663];
        $spring_roll_id = 1661;

        // 訂單個別加總
            $order_list = [];
            
            foreach ($orders ?? [] as $key1 => $order) {
                $delivery_time_ranges = explode('-', $order->delivery_time_range);

                $order_list[$order->id]['order_id'] = $order->id;
                $order_list[$order->id]['order_code'] = substr($order->code,4,4);
                $order_list[$order->id]['required_datetime'] = $order->delivery_date;
                $order_list[$order->id]['required_date_ymd'] = $required_date_ymd;
                $order_list[$order->id]['delivery_time_range'] = $order->delivery_time_range;
                $order_list[$order->id]['delivery_time_range_start'] = substr($delivery_time_ranges[0],0,2) . ':' . substr($delivery_time_ranges[0],-2) ;
                $order_list[$order->id]['delivery_time_range_end']   = substr($delivery_time_ranges[1],0,2) . ':' . substr($delivery_time_ranges[1],-2) ;
                $order_list[$order->id]['shipping_road_abbr'] = $order->shipping_road_abbr;
                $order_list[$order->id]['order_url'] = route('lang.admin.sale.orders.form', [$order->order_id]);

                $order_list[$order->id]['tooltip'] = '';
                
                foreach ($order->orderProducts as $key2 => $orderOroduct) {
                    foreach ($orderOroduct->orderProductOptions as $key3 => $orderProductOption) {

                        // 選項本身所對應的料件
                        $map_product_id = $orderProductOption->map_product_id ?? 0;
                        $map_product_name = $orderProductOption->value ?? '';
                        $map_product_name = $orderProductOption->value ?? '';

                        // 數量加工 
                            //例如 3吋潤餅轉6吋
                            $quantity  = $orderProductOption->quantity;

                            // 3吋潤餅/2 = 6吋潤餅
                            if(in_array($map_product_id, $wrap_ids_needing_halving)){

                                $inch_6_product_id = $sales_wrap_map[$map_product_id]['new_product_id'];
                                $inch_6_product_name = $sales_wrap_map[$map_product_id]['new_product_name'];
                                
                                $order_list[$order->id]['items'][$inch_6_product_id]['product_id'] = $orderOroduct->product_id;
                                $order_list[$order->id]['items'][$inch_6_product_id]['product_name'] = $orderOroduct->name;
                                $order_list[$order->id]['items'][$inch_6_product_id]['map_product_id'] = $inch_6_product_id;
                                $order_list[$order->id]['items'][$inch_6_product_id]['map_product_name'] = $inch_6_product_name;

                                if(empty($order_list[$order->id]['items'][$inch_6_product_id]['quantity'])){
                                    $order_list[$order->id]['items'][$inch_6_product_id]['quantity'] = 0;
                                }
                                $order_list[$order->id]['items'][$inch_6_product_id]['quantity'] += ceil(($quantity/2));

                                continue;
                            }

                            // 極品油飯 = 廚娘油飯*2
                            else if($map_product_id == 1737){ //極品油飯 1737
                                $map_product_id = 1036; //廚娘油飯 1036
                                $map_product_name = '廚娘油飯';
                                $order_list[$order->id]['items'][$map_product_id]['required_datetime'] = $order->delivery_date;
                                $order_list[$order->id]['items'][$map_product_id]['delivery_time_range'] = $order->delivery_time_range;
                                $order_list[$order->id]['items'][$map_product_id]['product_id'] = $orderOroduct->product_id;
                                $order_list[$order->id]['items'][$map_product_id]['product_name'] = $orderOroduct->name;
                                $order_list[$order->id]['items'][$map_product_id]['map_product_id'] = $map_product_id;
                                $order_list[$order->id]['items'][$map_product_id]['map_product_name'] = $map_product_name;

                                if(empty($order_list[$order->id]['items'][$map_product_id]['quantity'])){
                                    $order_list[$order->id]['items'][$map_product_id]['quantity'] = 0;
                                }
                                $order_list[$order->id]['items'][$map_product_id]['quantity'] += ($quantity * 2);
                                continue;
                            }

                            //最近因前端送來的 order_product_options 裡面的 product_option_id, product_option_value_id 有誤，為求保險，先重新設定。若 order_product_options 正確，下面可取消。
                            // 全素小刈包 
                            else if($map_product_id == 1688){
                                $map_product_id = 1664;
                            }

                            // 奶素小刈包
                            else if($map_product_id == 1689){
                                $map_product_id = 1664;
                            }

                            
                        //

                        if(empty($order_list[$order->id]['items'][$map_product_id]['required_datetime'])){
                            $order_list[$order->id]['items'][$map_product_id]['required_datetime'] = $order->delivery_date;
                            $order_list[$order->id]['items'][$map_product_id]['delivery_time_range'] = $order->delivery_time_range;
                            $order_list[$order->id]['items'][$map_product_id]['product_id'] = $orderOroduct->product_id;
                            $order_list[$order->id]['items'][$map_product_id]['product_name'] = $orderOroduct->name;
                            $order_list[$order->id]['items'][$map_product_id]['map_product_id'] = $map_product_id;
                            $order_list[$order->id]['items'][$map_product_id]['map_product_name'] = $map_product_name;
                        }

                        //如果 null 則 0
                        $order_list[$order->id]['items'][$map_product_id]['quantity'] = ($order_list[$order->id]['items'][$map_product_id]['quantity'] ?? 0) + $orderProductOption->quantity;
                    }

                    $order_list[$order->id]['tooltip'] .= '商品'.$orderOroduct->sort_order.'：' . $orderOroduct->name . '($'.(int)$orderOroduct->price.') * ' . $orderOroduct->quantity . "<BR>";
                }
            }

            // 排序
            if(!empty($order_list)){
                $order_list = collect($order_list)->sortBy('source_idsn')->sortBy('delivery_time_range_end')->values()->all();
            }

            $statistics['order_list'] = $order_list;
        //

        // 統計全日、上午、下午

            foreach ($order_list as $order_id => $order) {
                foreach ($order['items'] as $map_product_id => $item) {

                    //allDay
                    $statistics['allDay'][$map_product_id] = ($statistics['allDay'][$map_product_id] ?? 0) + $item['quantity'];

                    if (in_array($map_product_id, $small_guabao_ids)){ // 小刈包
                        $statistics['allDay_sgb'] = ($statistics['allDay_sgb'] ?? 0) + $item['quantity'];
                    }
                    else if (in_array($map_product_id, $big_guabao_ids)){ // 大刈包
                        $statistics['allDay_bgb'] = ($statistics['allDay_bgb'] ?? 0) + $item['quantity'];
                    } else if (in_array($map_product_id, $lumpia6in_ids)){ //6吋潤餅
                        $statistics['allDay_6in'] = ($statistics['allDay_6in'] ?? 0) + $item['quantity'];
                    }else if ($map_product_id == $spring_roll_id){ //春捲
                        $statistics['allDay_sr'] = ($statistics['allDay_sr'] ?? 0) + $item['quantity'];
                    }

                    //am
                    if($order['delivery_time_range_start'] <= '1300') {
                        $statistics['am'][$map_product_id] = ($statistics['am'][$map_product_id] ?? 0) + $item['quantity'];

                        if (in_array($map_product_id, $small_guabao_ids)){ // 小刈包
                            $statistics['am_sgb'] = ($statistics['am_sgb'] ?? 0) + $item['quantity'];
                        }
                        else if (in_array($map_product_id, $big_guabao_ids)){ // 大刈包
                            $statistics['am_bgb'] = ($statistics['am_bgb'] ?? 0) + $item['quantity'];
                        } else if (in_array($map_product_id, $lumpia6in_ids)){ //6吋潤餅
                            $statistics['am_6in'] = ($statistics['am_6in'] ?? 0) + $item['quantity'];
                        }else if ($map_product_id == $spring_roll_id){ //春捲
                            $statistics['am_sr'] = ($statistics['am_sr'] ?? 0) + $item['quantity'];
                        }
                    }
                    //pm
                    else{
                        $statistics['pm'][$map_product_id] = ($statistics['pm'][$map_product_id] ?? 0) + $item['quantity'];

                        if (in_array($map_product_id, $small_guabao_ids)){ // 小刈包
                            $statistics['pm_sgb'] = ($statistics['pm_sgb'] ?? 0) + $item['quantity'];
                        }
                        else if (in_array($map_product_id, $big_guabao_ids)){ // 大刈包
                            $statistics['pm_bgb'] = ($statistics['pm_bgb'] ?? 0) + $item['quantity'];
                        } else if (in_array($map_product_id, $lumpia6in_ids)){ //6吋潤餅
                            $statistics['pm_6in'] = ($statistics['pm_6in'] ?? 0) + $item['quantity'];
                        }else if ($map_product_id == $spring_roll_id){ //春捲
                            $statistics['pm_sr'] = ($statistics['pm_sr'] ?? 0) + $item['quantity'];
                        }
                    }
                }
            }
        //

        // 全日加總
            $total_package = 0; //套餐
            $total_bento = 0; //便當
            $total_lunchbox = 0; //盒餐
            $total_oil_rice_box = 0; //油飯盒
            $total_3inlumpia = 0; //3吋潤餅
            $total_6inlumpia = 0; //6吋潤餅
            $total_small_guabao = 0; //小刈包
            $total_big_guabao = 0; //大刈包
            $total_spring_roll = 0; //春捲

            foreach($orders as $order_id => $order){

                foreach ($order->orderProducts as $order_product_id => $orderProduct) {
                    if(!empty($orderProduct->productTags)){
                        $product_tag_ids = optional($orderProduct->productTags)->pluck('term_id')->toArray() ?? [];
                    }

                    //1331 套餐, 1330 盒餐, 1329 便當, 1437 素食, 1440 刈包, 1441 潤餅, 1443 油飯盒, 1461 美味單點

                    $product_tag_ids = $product_tag_ids ?? [];
                    
                    // 套餐
                    if(in_array(1331, $product_tag_ids)){ // 1331 套餐

                        if(in_array(1329, $product_tag_ids)){ // 1329 便當
                            $total_bento += $orderProduct->quantity;

                            if(in_array(1441, $product_tag_ids)){ // 1441 潤餅
                                $total_3inlumpia += $orderProduct->quantity;
                                $statistics['test']['3inlumpia'][] = [
                                    'order_id' => $orderProduct->order_id,
                                    'order_product_id' => $orderProduct->id,
                                    'order_product_name' => $orderProduct->name,
                                    'quantity' => $orderProduct->quantity,
                                ];
                            }
                            else if(in_array(1440, $product_tag_ids)){ // 1440 刈包
                                $total_small_guabao += $orderProduct->quantity;
                            }
                        }
                        else if(in_array(1330, $product_tag_ids)){ // 1330 盒餐
                            $total_lunchbox += $orderProduct->quantity;

                            if(in_array(1441, $product_tag_ids)){ // 1441 潤餅
                                $total_3inlumpia += $orderProduct->quantity;
                                $statistics['test']['3inlumpia'][] = [
                                    'order_id' => $orderProduct->order_id,
                                    'order_product_id' => $orderProduct->id,
                                    'order_product_name' => $orderProduct->name,
                                    'quantity' => $orderProduct->quantity,
                                ];
                            }
                            else if(in_array(1440, $product_tag_ids)){ // 1440 刈包
                                $total_small_guabao += $orderProduct->quantity;
                            }
                        }
                        else if(in_array(1443, $product_tag_ids)){ // 1443 油飯盒
                            $total_oil_rice_box += $orderProduct->quantity;
                        }
                    }
                    // 單點
                    else if(in_array(1461, $product_tag_ids)){ // 1461 美味單點
                        if(in_array(1461, $product_tag_ids)){ // 1440 刈包
                            $total_big_guabao += $orderProduct->quantity;
                        } else if(in_array(1441, $product_tag_ids)){ // 1441 潤餅
                            $total_3inlumpia += $orderProduct->quantity * 2;
                        }
                    }
                    //單點 其它商品組
                    else if ($orderProduct->product_id == 1062){
                        foreach ($orderProduct->orderProductOptions as $orderProductOption) {
                            if ($orderProductOption->option_id == 1017){ //1017 = 大刈包
                                $total_big_guabao += $orderProductOption->quantity;
                            } else if ($orderProductOption->option_id == 1009){ //1009 = 6吋潤餅
                                $total_3inlumpia += $orderProductOption->quantity * 2;
                            }
                        }
                    }
                }
            }

            $statistics['info'] = [];
            $statistics['info']['total_bento']         = $total_bento;
            $statistics['info']['total_lunchbox']      = $total_lunchbox;
            $statistics['info']['total_oil_rice_box']  = $total_oil_rice_box;
            $statistics['info']['total_3inlumpia']     = $total_3inlumpia; //含春捲
            $statistics['info']['total_6inlumpia']     = $total_3inlumpia/2; //不含春捲，要再改
            $statistics['info']['total_small_guabao']  = $total_small_guabao;
            $statistics['info']['total_big_guabao']    = $total_big_guabao;
            $statistics['info']['total_oil_rice_box']  = $total_oil_rice_box;
            $statistics['info']['total_package']       = $total_bento + $total_lunchbox + $total_oil_rice_box;
            
            $statistics['info']['required_date_ymd']  = $required_date_ymd;
        //

        $statistics['sales_ingredients_table_items'] = Setting::where('setting_key','sales_ingredients_table_items')->first()->setting_value;
        
        $statistics['cache_created_at']  = now();

        return $statistics;
    }
}

