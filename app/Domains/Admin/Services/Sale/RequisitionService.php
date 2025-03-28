<?php

namespace App\Domains\Admin\Services\Sale;

use App\Helpers\Classes\DataHelper;
use App\Services\Service;
use App\Repositories\Eloquent\Sale\OrderIngredientHourRepository;
use App\Repositories\Eloquent\Sale\OrderIngredientRepository;
use App\Repositories\Eloquent\Sale\DailyIngredientRepository;

use App\Repositories\Eloquent\Inventory\RequirementRepository;
use App\Repositories\Eloquent\Inventory\UnitRepository;
use App\Helpers\Classes\DateHelper;
use App\Helpers\Classes\UnitConverter;
use App\Models\Sale\OrderIngredient;
use App\Models\Sale\DailyIngredient;
use App\Models\Sale\Order;
use App\Models\Log\LogCronJob;
use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Sale\OrderRepository;
use App\Repositories\Eloquent\Setting\SettingRepository;
use Carbon\Carbon;
use App\Models\Setting\Setting;
use App\Helpers\Classes\CacheSerializeHelper;
use App\Helpers\Classes\OrmHelper;

/**
 * Requisition 備料表
 * Requirements 需求表
 *
 */
class RequisitionService extends Service
{
    // public $modelName = "\App\Models\Sale\OrderIngredient";

    public function __construct(
      protected OrderIngredientHourRepository $OrderIngredientHourRepository
      , protected OrderIngredientRepository $OrderIngredientRepository
      , protected DailyIngredientRepository $DailyIngredientRepository
    , protected RequirementRepository $RequirementRepository
    , protected UnitRepository $UnitRepository
    , protected OrderRepository $OrderRepository
    )
    {
        $this->repository = $OrderIngredientRepository;
    }

    public function getForm($required_date_ymd, $forceUpdate = 0)
    {

            DB::beginTransaction();

            $required_date_ymd = parseDate($required_date_ymd);
            $required_date_2ymd = parseDateStringTo6d($required_date_ymd);

            $cache_key = 'sale_order_requirements' . $required_date_ymd;

            $cache_minutes = 60;

            if ($forceUpdate){
                cache()->forget($cache_key);
            }

            $statics = cache()->remember($cache_key, 60 * $cache_minutes, function () use ($required_date_ymd) {
                return $this->calculateOrderIngredients($required_date_ymd);
            });
    
            $requiredDateRawSql = DateHelper::parseDateToSqlWhere('delivery_date', $required_date_ymd);
    
            if(empty($requiredDateRawSql)){
                return false;
            }
    
            // 資料庫 訂單
                //需要備料的訂單狀態代號
                $temp_row = (new SettingRepository)->getRow(['equal_setting_key' => 'sales_orders_to_be_prepared_status']);
                $sales_orders_to_be_prepared_status = $temp_row->setting_value; // 必須是陣列
    
                $query = Order::query();
        
                $query->select(['id', 'code', 'location_id', 'delivery_date', 'delivery_time_range', 'personal_name'
                                , 'shipping_road', 'shipping_road_abbr', 'shipping_method'
                                , 'status_code'
                            ]);
                $query->whereIn('status_code', $sales_orders_to_be_prepared_status);
                $query->whereRaw($requiredDateRawSql);
                
                $query->with(['orderProducts' => function ($query) {
                    $query->select(['id', 'order_id', 'product_id', 'name', 'quantity'])
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
    
            // 各訂單個別加總
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
                    $order_list[$order->id]['shipping_road_abbr'] = $order->shipping_road_abbr;
                    $order_list[$order->id]['order_url'] = route('lang.admin.sale.orders.form', [$order->order_id]);
                    
                    foreach ($order->orderProducts as $key2 => $orderOroduct) {
                        foreach ($orderOroduct->orderProductOptions as $key3 => $orderProductOption) {
    
                            // 選項本身所對應的料件
                            $map_product_id = $orderProductOption->map_product_id ?? 0;
                            $opo_value = $orderProductOption->value ?? '';
    
                            // 數量加工 例如 3吋潤餅轉6吋
                                $quantity  = $orderProductOption->quantity;
    
                                // 3吋潤餅/2 = 6吋潤餅
                                if(in_array($map_product_id, $wrap_ids_needing_halving)){
    
                                    $inch_6_product_id = $sales_wrap_map[$map_product_id]['new_product_id'];
                                    $inch_6_product_name = $sales_wrap_map[$map_product_id]['new_product_name'];
                                    
                                    $order_list[$order->id]['items'][$inch_6_product_id]['product_id'] = $orderOroduct->product_id;
                                    $order_list[$order->id]['items'][$inch_6_product_id]['product_name'] = $orderOroduct->name;
                                    $order_list[$order->id]['items'][$inch_6_product_id]['ingredient_product_id'] = $inch_6_product_id;
                                    $order_list[$order->id]['items'][$inch_6_product_id]['ingredient_product_name'] = $inch_6_product_name;
    
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
                                    $order_list[$order->id]['items'][$map_product_id]['ingredient_product_id'] = $map_product_id;
                                    $order_list[$order->id]['items'][$map_product_id]['ingredient_product_name'] = $map_product_name;
    
                                    if(empty($order_list[$order->id][$map_product_id]['quantity'])){
                                        $order_list[$order->id]['items'][$map_product_id]['quantity'] = 0;
                                    }
                                    $order_list[$order->id]['items'][$map_product_id]['quantity'] += ($quantity * 2);
                                    continue;
                                }
                            //
    
                            if(empty($order_list[$order->id]['items'][$map_product_id]['required_datetime'])){
                                $order_list[$order->id]['items'][$map_product_id]['required_datetime'] = $order->delivery_date;
                                $order_list[$order->id]['items'][$map_product_id]['delivery_time_range'] = $order->delivery_time_range;
                                $order_list[$order->id]['items'][$map_product_id]['product_id'] = $orderOroduct->product_id;
                                $order_list[$order->id]['items'][$map_product_id]['product_name'] = $orderOroduct->name;
                                $order_list[$order->id]['items'][$map_product_id]['map_product_id'] = $map_product_id;
                                $order_list[$order->id]['items'][$map_product_id]['opo_value'] = $opo_value;
                            }
    
                            //如果 null 則 0
                            $order_list[$order->id]['items'][$map_product_id]['quantity'] = ($order_list[$order->id]['items'][$map_product_id]['quantity'] ?? 0) + $orderProductOption->quantity;
                        }
                    }
                }
            //
            $statics['order_list'] = $order_list;
    
            // 統計全日、上午、下午
                foreach ($order_list as $order_id => $order) {
                    foreach ($order['items'] as $map_product_id => $item) {
    
                        //allDay
                        if(empty($statics['allDay'][$map_product_id]['quantity'])){
                            $statics['allDay'][$map_product_id]['quantity'] = 0;
                        }
                        $statics['allDay'][$map_product_id]['quantity'] += $item['quantity'];
    
                        //am
                        if($order['delivery_time_range_start'] <= '1300') {
                            $statics['am'][$map_product_id]['quantity'] = ($statics['am'][$map_product_id]['quantity'] ?? 0) + $item['quantity'];
                        }
                        //pm
                        else{
                            $statics['pm'][$map_product_id]['quantity'] = ($statics['pm'][$map_product_id]['quantity'] ?? 0) + $item['quantity'];
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
                                }
                                else if(in_array(1440, $product_tag_ids)){ // 1440 刈包
                                    $total_small_guabao += $orderProduct->quantity;
                                }
                            }
                            else if(in_array(1330, $product_tag_ids)){ // 1330 盒餐
                                $total_lunchbox += $orderProduct->quantity;
    
                                if(in_array(1441, $product_tag_ids)){ // 1441 潤餅
                                    $total_3inlumpia += $orderProduct->quantity;
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
    
                $statics['info'] = [];
                $statics['info']['total_bento']         = $total_bento;
                $statics['info']['total_lunchbox']      = $total_lunchbox;
                $statics['info']['total_oil_rice_box']  = $total_oil_rice_box;
                $statics['info']['total_3inlumpia']     = $total_3inlumpia;
                $statics['info']['total_6inlumpia']     = $total_3inlumpia/2;
                $statics['info']['total_small_guabao']  = $total_small_guabao;
                $statics['info']['total_big_guabao']  = $total_big_guabao;
                $statics['info']['total_oil_rice_box']  = $total_oil_rice_box;
                $statics['info']['total_package']       = $total_bento + $total_lunchbox + $total_oil_rice_box;
            //
    
            
            $statics['cache_created_at'] = now();

            $data['statics'] = $statics;

            DB::commit();
    
            return $statics;
    }

    /**
     * 2024-11-04
     * 抓取訂單資料，然後寫入資料表 order_ingredients
     * 下面兩個 function 應該很完美，不需要再調整。 2024-10-31
     */
    public function getOrderIngredients($required_date, $force = 0)
    {
        try {
            $cache_key = 
        $required_date = DateHelper::parse($required_date);'sale_material_' . $required_date;

            $cache_minutes = 60;

            if ($force){
                cache()->forget($cache_key);
            }

            $statics = cache()->remember($cache_key, 60 * $cache_minutes, function () use ($required_date) {
                return $this->calculateOrderIngredients($required_date);
            });

            return $statics;

        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }

    /**
     * 2024-11-04
     * 計算某日的訂單選項轉為一級材料。
     * 只能是單一日期。
     */
    public function calculateOrderIngredients($required_date)
    {
        try {
            $required_date_ymd = parseDate($required_date);
            $required_date_2ymd = parseDateStringTo6d($required_date_ymd);
    
            $requiredDateRawSql = DateHelper::parseDateToSqlWhere('delivery_date', $required_date_ymd);
    
            if(empty($requiredDateRawSql)){
                return false;
            }
    
            // 訂單
                //需要備料的訂單狀態代號
                $temp_row = (new SettingRepository)->getRow(['equal_setting_key' => 'sales_orders_to_be_prepared_status']);
                $sales_orders_to_be_prepared_status = $temp_row->setting_value; // 必須是陣列
    
                $query = Order::query();
        
                $query->select(['id', 'code', 'location_id', 'delivery_date', 'delivery_time_range', 'personal_name'
                                , 'shipping_road', 'shipping_road_abbr', 'shipping_method'
                                , 'status_code'
                            ]);
                $query->whereIn('status_code', $sales_orders_to_be_prepared_status);
                $query->whereRaw($requiredDateRawSql);
                
                $query->with(['orderProducts' => function ($query) {
                    $query->select(['id', 'order_id', 'product_id', 'name', 'quantity'])
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
    
            $filter_data = [
                'filter_setting_key' => 'sales_6inch_lumpia',
                'filter_location_id' => '0',
                'type' => 'CommaSeparated'
            ];
            $sales_6inch_lumpia = (new SettingRepository)->getSettingValue($filter_data);
    
            // 各訂單個別加總
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
                    $order_list[$order->id]['shipping_road_abbr'] = $order->shipping_road_abbr;
                    $order_list[$order->id]['order_url'] = route('lang.admin.sale.orders.form', [$order->order_id]);
                    
                    foreach ($order->orderProducts as $key2 => $orderOroduct) {
                        foreach ($orderOroduct->orderProductOptions as $key3 => $orderProductOption) {
    
                            // 選項本身所對應的料件
                            $map_product_id = $orderProductOption->map_product_id ?? 0;
                            $opo_value = $orderProductOption->value ?? '';
    
                            //quantity
                                $quantity  = $orderProductOption->quantity;
    
                                // 3吋潤餅/2 = 6吋潤餅
                                if(in_array($map_product_id, $wrap_ids_needing_halving)){
    
                                    $inch_6_product_id = $sales_wrap_map[$map_product_id]['new_product_id'];
                                    $inch_6_product_name = $sales_wrap_map[$map_product_id]['new_product_name'];
                                    
                                    $order_list[$order->id]['items'][$inch_6_product_id]['product_id'] = $orderOroduct->product_id;
                                    $order_list[$order->id]['items'][$inch_6_product_id]['product_name'] = $orderOroduct->name;
                                    $order_list[$order->id]['items'][$inch_6_product_id]['ingredient_product_id'] = $inch_6_product_id;
                                    $order_list[$order->id]['items'][$inch_6_product_id]['ingredient_product_name'] = $inch_6_product_name;
    
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
                                    $order_list[$order->id]['items'][$map_product_id]['ingredient_product_id'] = $map_product_id;
                                    $order_list[$order->id]['items'][$map_product_id]['ingredient_product_name'] = $map_product_name;
    
                                    if(empty($order_list[$order->id][$map_product_id]['quantity'])){
                                        $order_list[$order->id]['items'][$map_product_id]['quantity'] = 0;
                                    }
                                    $order_list[$order->id]['items'][$map_product_id]['quantity'] += ($quantity * 2);
                                    continue;
                                }
                            // End quantity
    
                            if(empty($order_list[$order->id]['items'][$map_product_id]['required_datetime'])){
                                $order_list[$order->id]['items'][$map_product_id]['required_datetime'] = $order->delivery_date;
                                $order_list[$order->id]['items'][$map_product_id]['delivery_time_range'] = $order->delivery_time_range;
                                $order_list[$order->id]['items'][$map_product_id]['product_id'] = $orderOroduct->product_id;
                                $order_list[$order->id]['items'][$map_product_id]['product_name'] = $orderOroduct->name;
                                $order_list[$order->id]['items'][$map_product_id]['map_product_id'] = $map_product_id;
                                $order_list[$order->id]['items'][$map_product_id]['opo_value'] = $opo_value;
                            }
    
                            //如果 null 則 0
                            if(empty($order_list[$order->id]['items'][$map_product_id]['quantity'])){
                                $order_list[$order->id]['items'][$map_product_id]['quantity'] = 0;
                            }
    
                            $order_list[$order->id]['items'][$map_product_id]['quantity'] += $orderProductOption->quantity;
                        }
                    }
                }
            //
            $statics['order_list'] = $order_list;
    
            // 統計全日、上午、下午
                foreach ($order_list as $order_id => $order) {
                    foreach ($order['items'] as $map_product_id => $item) {
    
                        //allDay
                        if(empty($statics['allDay'][$map_product_id]['quantity'])){
                            $statics['allDay'][$map_product_id]['quantity'] = 0;
                        }
                        $statics['allDay'][$map_product_id]['quantity'] += $item['quantity'];
    
                        //am
                        if($order['delivery_time_range_start'] <= '1300') {
                            $statics['am'][$map_product_id]['quantity'] = ($statics['am'][$map_product_id]['quantity'] ?? 0) + $item['quantity'];
                        }
                        //pm
                        else{
                            $statics['pm'][$map_product_id]['quantity'] = ($statics['pm'][$map_product_id]['quantity'] ?? 0) + $item['quantity'];
                        }
                    }
                }
            //
        
            // 全日加總
                $total_package = 0; //套餐
                $total_bento = 0; //便當
                $total_lunchbox = 0; //盒餐
                $total_oil_rice_box = 0; //油飯盒
                $total_big_guabao = 0; //大刈包
                $total_small_guabao = 0; //小刈包
                $total_3inlumpia = 0; //3吋潤餅
                $total_6inlumpia = 0; //6吋潤餅
    
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
                                }
                                else if(in_array(1440, $product_tag_ids)){ // 1440 刈包
                                    $total_small_guabao += $orderProduct->quantity;
                                }
                            }
                            else if(in_array(1330, $product_tag_ids)){ // 1330 盒餐
                                $total_lunchbox += $orderProduct->quantity;
    
                                if(in_array(1441, $product_tag_ids)){ // 1441 潤餅
                                    $total_3inlumpia += $orderProduct->quantity;
                                }
                                else if(in_array(1440, $product_tag_ids)){ // 1440 刈包
                                    $total_small_guabao += $orderProduct->quantity;
                                }
                            }
                            else if(in_array(1443, $product_tag_ids)){ // 1443 油飯盒
                                $total_oil_rice_box += $orderProduct->quantity;
                            }
                        }
                        // 美味單點
                        else if(in_array(1461, $product_tag_ids)){ // 1461 美味單點
                            if(in_array(1461, $product_tag_ids)){ // 1440 刈包
                                $total_big_guabao += $orderProduct->quantity;
                            } else if(in_array(1441, $product_tag_ids)){ // 1441 潤餅
                                $total_3inlumpia += ($orderProduct->quantity*2);
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
    
                $statics['info'] = [];
                $statics['info']['total_bento']         = $total_bento;
                $statics['info']['total_lunchbox']      = $total_lunchbox;
                $statics['info']['total_oil_rice_box']  = $total_oil_rice_box;
                $statics['info']['total_3inlumpia']     = $total_3inlumpia;
                $statics['info']['total_6inlumpia']     = $total_3inlumpia/2;
                $statics['info']['total_small_guabao']  = $total_small_guabao;
                $statics['info']['total_oil_rice_box']  = $total_oil_rice_box;
                $statics['info']['total_package']       = $total_bento + $total_lunchbox + $total_oil_rice_box;
            //

            $statics['cache_created_at'] = now();

            DB::commit();

            return $statics;

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }

    /**
     * 上面兩個函數勿動
     */


     /**
      * 列表頁資料
      * 2024-11-05
      */
     public function getDailyIngredients($params)
     {
        $params['with'] = DataHelper::addToArray('product.supplier', $params['with'] ?? []);

        $ingredients = (new DailyIngredientRepository)->getRecords($params);

        return $ingredients;
     }


     public function exportMatrixList($post_data = [], $debug = 0)
     {
         return (new DailyIngredientRepository)->exportMatrixList($post_data);
     }


    /**
     * 根據 Bom 計算料件需求
     * 本來有用。現在沒用。之後新版系統再參考。
     */
    public function calcRequirementsForDate($required_date)
    {
        $json = [];

        $required_date = DateHelper::parseDate($required_date);

        if($required_date == false){
            $json['error']['required_date'] = '日期錯誤';
        }

        // 獲取備料表
        $params = [
            'equal_required_date' => $required_date,
            'pagination' => false,
            'limit' => 0,
            'has' => 'bom',
            'with' => ['bom.bom_products.sub_product.translation', 'bom.bom_products.sub_product.supplier'],
        ];
        $requisitions = $this->getIngredients($params);

        $requirements = [];

        if(!$json) {
            // 根據bom表計算需求

            $quantity = 0;

            foreach ($requisitions as $requisition) {
                //主件
                $product_id = $requisition->ingredient_product_id;

                foreach ($requisition->bom->bom_products as $bom_product) {
                    $sub_product_id = $bom_product->sub_product_id;

                    if(!isset($requirements[$sub_product_id])){
                        $requirements[$sub_product_id] = [
                            'required_date' => $required_date,
                            'product_id' => $bom_product->sub_product->id,
                            'product_name' => $bom_product->sub_product->name,
                            'usage_quantity' => 0,
                            'usage_unit_code' =>  $bom_product->usage_unit_code,
                            'stock_quantity' => 0,
                            'stock_unit_code' =>  $bom_product->sub_product->stock_unit_code,
                            'supplier_id' =>  $bom_product->sub_product->supplier_id,
                            'supplier_short_name' =>  $bom_product->sub_product->supplier->short_name ?? '',
                            'supplier_own_product_code' =>  $bom_product->sub_product->supplier_own_product_code ?? '',
                        ];
                    }
                    $usage_quantity = $requisition->quantity * $bom_product->quantity;

                    $stock_quantity = UnitConverter::build()->qty($usage_quantity)
                            ->from($bom_product->usage_unit_code)
                            ->to($bom_product->sub_product->stock_unit_code)
                            ->product($product_id)
                            ->get();
                    if (!is_numeric($stock_quantity)) {
                        // 如果不是數字，初始化為0或其他合理值
                        $stock_quantity = 0;
                    }
                    $requirements[$sub_product_id]['stock_quantity'] += $stock_quantity;
                    $requirements[$sub_product_id]['usage_quantity'] += $usage_quantity;
                }
            }

            if(!empty($requirements)){
                return $this->RequirementRepository->saveDailyRequirements($requirements);
            }
        }

        return $json;
    }
}
