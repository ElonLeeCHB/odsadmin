<?php

namespace App\Repositories\Eloquent\Sale;

use App\Repositories\Eloquent\Repository;
use App\Helpers\Classes\DateHelper;
use App\Helpers\Classes\OrmHelper;
use App\Models\Sale\Order;
use App\Models\Sale\OrderProduct;
use App\Models\Sale\OrderProductOption;
use App\Models\Setting\Setting;
use App\Models\Inventory\Bom;
use App\Models\Inventory\BomProduct;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB; 

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
    public function getStatisticsByDate($required_date, $force_update = 0, $is_return = true)
    {
        $required_date = Carbon::parse($required_date)->format('Y-m-d');

        $cache_key = 'sale_order_requisition_date_' . $required_date;

        // 先取得快取
        $statistics = cache()->get($cache_key);

        // 如果快取不存在或快取中的 cache_created_at 超過指定期限，則重新產生快取
        if (
            $force_update ||
            !$statistics ||
            !isset($statistics['cache_created_at']) ||
            Carbon::parse($statistics['cache_created_at'])->diffInMinutes(now()) > 60
        ) {
            cache()->forget($cache_key);

            $statistics = cache()->remember($cache_key, 60 * 24 * 180, function () use ($required_date) {
                return $this->calculateByDate($required_date);
            });
        }

        if ($is_return == true){
            return $statistics;
        }
    }

    public function calculateByDate($required_date_ymd)
    {
        $requiredDateRawSql = DateHelper::parseDateToSqlWhere('delivery_date', $required_date_ymd);

        if(empty($requiredDateRawSql)){
            return false;
        }

        // 這是材料的 product_id，即 order_product_options.map_product_id
        $sales_ingredients_table_items = $statistics['sales_ingredients_table_items'] = Setting::where('setting_key','sales_ingredients_table_items')->first()->setting_value;

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
                        },
                        'productPosCategories',
                    ]);
            }]);
    
            $orders = $query->get();
    
            if ($orders->isEmpty()) {
                return [];
            }
        // end 訂單

        //3吋潤餅、6吋潤餅的對應
        $sales_wrap_map = Setting::where('setting_key','sales_wrap_map')->first()->setting_value;
        $wrap_ids_needing_halving = array_keys($sales_wrap_map); //3吋潤餅的 id
        //6吋潤餅

        // 材料代號(product_id)
        $big_guabao_ids = [1809,1810,1811,1812,1813,1814,1838,1839,1840];
        $small_guabao_ids = [1664,1665,1666,1667,1668,1669,1672,1688,1689];
        $lumpia6in_ids = [1010,1011,1012,1013,1014,1015,1056,1058,1663];
        $spring_roll_id = 1661;

        // 訂單個別加總
            $order_list = [];
            
            foreach ($orders ?? [] as $key1 => $order) {
                $order->delivery_time_range = str_replace(' ', '', $order->delivery_time_range);
                $delivery_time_range_array = explode('-', $order->delivery_time_range);

                $delivery_time_range_start = substr($delivery_time_range_array[0], 0, 2) . ':' . substr($delivery_time_range_array[0], -2);
                $delivery_time_range_end   = substr($delivery_time_range_array[1], 0, 2) . ':' . substr($delivery_time_range_array[1], -2);

                $order_list[$order->id]['order_id'] = $order->id;
                $order_list[$order->id]['order_code'] = substr($order->code,4,4);
                $order_list[$order->id]['required_datetime'] = $order->delivery_date;
                $order_list[$order->id]['required_date_ymd'] = $required_date_ymd;
                $order_list[$order->id]['delivery_time_range'] = $order->delivery_time_range;
                $order_list[$order->id]['delivery_time_range_start'] = $delivery_time_range_start;
                $order_list[$order->id]['delivery_time_range_end']   = $delivery_time_range_end;
                $order_list[$order->id]['shipping_road_abbr'] = $order->shipping_road_abbr;
                $order_list[$order->id]['order_url'] = route('lang.admin.sale.orders.form', [$order->order_id]);
                $order_list[$order->id]['tooltip'] = '';

                $braisedfood_option_value_ids = [1202,1203,1204];
                
                foreach ($order->orderProducts as $key2 => $orderProduct) {
                    $product_id = $orderProduct->product_id;
                    $printing_category_id = $orderProduct->product->printing_category_id;

                    // 單點，無選項
                        if ($printing_category_id == 1494){
                            $order_list[$order->id]['items'][$product_id]['required_datetime'] = $order->delivery_date;
                            $order_list[$order->id]['items'][$product_id]['delivery_time_range'] = $order->delivery_time_range;
                            $order_list[$order->id]['items'][$product_id]['product_id'] = $orderProduct->product_id;
                            $order_list[$order->id]['items'][$product_id]['product_name'] = $orderProduct->name;
                            $order_list[$order->id]['items'][$product_id]['map_product_id'] = $orderProduct->product_id;
                            $order_list[$order->id]['items'][$product_id]['map_product_name'] = $orderProduct->name;

                            if(empty($order_list[$order->id]['items'][$product_id]['quantity'])){ //若無值預設 = 0
                                $order_list[$order->id]['items'][$product_id]['quantity'] = 0;
                            }
                            
                            $order_list[$order->id]['items'][$product_id]['quantity'] += $orderProduct->quantity;
                        }
                    //


                    foreach ($orderProduct->orderProductOptions ?? [] as $key3 => $orderProductOption) {
                        $map_product_id = $orderProductOption->map_product_id ?? 0;
                        $map_product_name = $orderProductOption->value ?? '';
                        $quantity  = $orderProductOption->quantity;

                        // 滷味小、中、大
                            if (in_array($orderProductOption->option_value_id, $braisedfood_option_value_ids)){
                                $map_product_id = 1804;
                                $map_product_name = '滷味個';

                                if ($orderProductOption->option_value_id == 1202){
                                    $quantity = $orderProductOption->quantity*6;
                                } else if ($orderProductOption->option_value_id == 1203){
                                    $quantity = $orderProductOption->quantity*9;
                                } else if ($orderProductOption->option_value_id == 1204){
                                    $quantity = $orderProductOption->quantity*12;
                                }

                                $order_list[$order->id]['items'][$map_product_id]['required_datetime'] = $order->delivery_date;
                                $order_list[$order->id]['items'][$map_product_id]['delivery_time_range'] = $order->delivery_time_range;
                                $order_list[$order->id]['items'][$map_product_id]['product_id'] = $orderProduct->product_id;
                                $order_list[$order->id]['items'][$map_product_id]['product_name'] = $orderProduct->name;
                                $order_list[$order->id]['items'][$map_product_id]['map_product_id'] = $map_product_id;
                                $order_list[$order->id]['items'][$map_product_id]['map_product_name'] = $map_product_name;
    
                                if(empty($order_list[$order->id]['items'][$map_product_id]['quantity'])){ //若無值預設 = 0
                                    $order_list[$order->id]['items'][$map_product_id]['quantity'] = 0;
                                }
                                
                                $order_list[$order->id]['items'][$map_product_id]['quantity'] += $quantity;
                                continue;
                            }
                        //

                        // 數量加工 

                            // 3吋潤餅/2 = 6吋潤餅
                            if(in_array($map_product_id, $wrap_ids_needing_halving)){

                                $inch_6_product_id = $sales_wrap_map[$map_product_id]['new_product_id'];
                                $inch_6_product_name = $sales_wrap_map[$map_product_id]['new_product_name'];
                                
                                $order_list[$order->id]['items'][$inch_6_product_id]['product_id'] = $orderProduct->product_id;
                                $order_list[$order->id]['items'][$inch_6_product_id]['product_name'] = $orderProduct->name;
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
                                $order_list[$order->id]['items'][$map_product_id]['product_id'] = $orderProduct->product_id;
                                $order_list[$order->id]['items'][$map_product_id]['product_name'] = $orderProduct->name;
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
                            $order_list[$order->id]['items'][$map_product_id]['product_id'] = $orderProduct->product_id;
                            $order_list[$order->id]['items'][$map_product_id]['product_name'] = $orderProduct->name;
                            $order_list[$order->id]['items'][$map_product_id]['map_product_id'] = $map_product_id;
                            $order_list[$order->id]['items'][$map_product_id]['map_product_name'] = $map_product_name;
                        }

                        //如果 null 則 0
                        $order_list[$order->id]['items'][$map_product_id]['quantity'] = ($order_list[$order->id]['items'][$map_product_id]['quantity'] ?? 0) + $orderProductOption->quantity;
                    }                    

                    // 懸浮視窗提示商品內容
                    $order_list[$order->id]['tooltip'] .= '商品'.$orderProduct->sort_order.'：' . $orderProduct->name . '($'.(int)$orderProduct->price.') * ' . $orderProduct->quantity . "<BR>";
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

                    // 一般項目
                    $statistics['allDay'][$map_product_id] = ($statistics['allDay'][$map_product_id] ?? 0) + $item['quantity'];

                    // 特別項目
                    if (in_array($map_product_id, $small_guabao_ids)){ // 小刈包
                        $statistics['allDay_sgb'] = ($statistics['allDay_sgb'] ?? 0) + $item['quantity'];
                    } 
                    else if (in_array($map_product_id, $big_guabao_ids)){ // 大刈包
                        $statistics['allDay_bgb'] = ($statistics['allDay_bgb'] ?? 0) + $item['quantity'];
                    }
                    else if (in_array($map_product_id, $lumpia6in_ids)){  // 6吋潤餅
                        $statistics['allDay_6in'] = ($statistics['allDay_6in'] ?? 0) + $item['quantity'];
                    }
                    else if ($map_product_id == $spring_roll_id){ // 春捲
                        $statistics['allDay_sr'] = ($statistics['allDay_sr'] ?? 0) + $item['quantity'];
                    }

                    //am
                    if($order['delivery_time_range_start'] <= '1300') {
                        // 一般項目
                        $statistics['am'][$map_product_id] = ($statistics['am'][$map_product_id] ?? 0) + $item['quantity'];

                        // 特別項目
                        if (in_array($map_product_id, $small_guabao_ids)){ // 小刈包
                            $statistics['am_sgb'] = ($statistics['am_sgb'] ?? 0) + $item['quantity'];
                        }
                        else if (in_array($map_product_id, $big_guabao_ids)){ // 大刈包
                            $statistics['am_bgb'] = ($statistics['am_bgb'] ?? 0) + $item['quantity'];
                        }
                        else if (in_array($map_product_id, $lumpia6in_ids)){ //6吋潤餅
                            $statistics['am_6in'] = ($statistics['am_6in'] ?? 0) + $item['quantity'];
                        }
                        else if ($map_product_id == $spring_roll_id){ //春捲
                            $statistics['am_sr'] = ($statistics['am_sr'] ?? 0) + $item['quantity'];
                        }
                    }
                    //pm
                    else{
                        // 一般項目
                        $statistics['pm'][$map_product_id] = ($statistics['pm'][$map_product_id] ?? 0) + $item['quantity'];

                        // 特別項目
                        if (in_array($map_product_id, $small_guabao_ids)){ // 小刈包
                            $statistics['pm_sgb'] = ($statistics['pm_sgb'] ?? 0) + $item['quantity'];
                        }
                        else if (in_array($map_product_id, $big_guabao_ids)){ // 大刈包
                            $statistics['pm_bgb'] = ($statistics['pm_bgb'] ?? 0) + $item['quantity'];
                        }
                        else if (in_array($map_product_id, $lumpia6in_ids)){ //6吋潤餅
                            $statistics['pm_6in'] = ($statistics['pm_6in'] ?? 0) + $item['quantity'];
                        }
                        else if ($map_product_id == $spring_roll_id){ //春捲
                            $statistics['pm_sr'] = ($statistics['pm_sr'] ?? 0) + $item['quantity'];
                        }
                    }
                }
            }
        //

        // 全日加總
            // $total_package = 0; //套餐
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

                    $printing_category_id = $orderProduct->product->printing_category_id;      

                    // $product_tag_ids = $product_tag_ids ?? [];
                    //1471=潤餅便當 1472=刈包便當 1473=潤餅盒餐 1474=刈包盒餐 1475=油飯盒 1477=客製便當 1478=客製盒餐
                    $set_meal_printing_category_ids = [1471, 1472, 1473, 1474, 1475, 1477, 1478];

                    // 套餐
                    if (in_array($printing_category_id, $set_meal_printing_category_ids)) {
                        // 便當 1471=潤餅便當 1472=刈包便當 1477=客製便當
                        if (in_array($printing_category_id, [1471, 1472, 1477])) {
                            $total_bento += $orderProduct->quantity;
                        }

                        // 盒餐 1473=潤餅盒餐 1474=刈包盒餐 1478=客製盒餐
                        if (in_array($printing_category_id, [1473, 1474, 1478])) {
                            $total_lunchbox += $orderProduct->quantity;
                        }

                        // 油飯盒 1475=油飯盒
                        if (in_array($printing_category_id, [1475])) {
                            $total_oil_rice_box += $orderProduct->quantity;
                        }
                    }
                }
            }

            $statistics['info'] = [];
            $statistics['info']['total_bento']         = $total_bento;
            $statistics['info']['total_lunchbox']      = $total_lunchbox;
            $statistics['info']['total_oil_rice_box']  = $total_oil_rice_box;
            $statistics['info']['total_set']            = $total_bento + $total_lunchbox + $total_oil_rice_box;

            // $statistics['info']['total_3inlumpia']     = $total_3inlumpia; //含春捲
            // $statistics['info']['total_6inlumpia']     = $total_3inlumpia/2; //不含春捲，要再改
            // $statistics['info']['total_small_guabao']  = $total_small_guabao;
            // $statistics['info']['total_big_guabao']    = $total_big_guabao;
            // $statistics['info']['total_oil_rice_box']  = $total_oil_rice_box;
            // $statistics['info']['total_package']       = $total_bento + $total_lunchbox + $total_oil_rice_box;
            
            $statistics['info']['required_date_ymd']  = $required_date_ymd;
        //

        // $statistics['sales_ingredients_table_items'] = Setting::where('setting_key','sales_ingredients_table_items')->first()->setting_value;
        $statistics['sales_ingredients_table_items'] = $sales_ingredients_table_items;
        
        $statistics['cache_created_at'] = now()->format('Y-m-d H:i:s');

        return $statistics;
    }


    public function getBomItemsByProductId($product_id)
    {
        $product_id = 1848;
        $bom = Bom::query()->where('product_id', $product_id)->where('is_active', 1)->whereDate('effective_date', '<', DB::raw('CURDATE()'))->first();

        if ($bom){
            $bom->load('bomProducts.translation');
            return $bom->bomProducts ?? [];
        }

        return [];
    }
}

