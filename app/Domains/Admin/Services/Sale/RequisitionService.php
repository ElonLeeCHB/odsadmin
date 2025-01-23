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
use App\Models\Log\LogCronJob;
use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Sale\OrderRepository;
use App\Repositories\Eloquent\Setting\SettingRepository;
use Carbon\Carbon;
use App\Models\Setting\Setting;
use App\Helpers\Classes\CacheSerializeHelper;

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

    /**
     * 2024-11-04
     * 抓取訂單資料，然後寫入資料表 order_ingredients
     * 下面兩個 function 應該很完美，不需要再調整。 2024-10-31
     */
    public function getOrderIngredients($required_date, $force = false)
    {
        $required_date = DateHelper::parse($required_date);
        try {
            $cache_key = 'sale_material_' . $required_date;
            $cache_minutes = 60;// 緩存分鐘數

            if ($force) {
                $statics = $this->calculateOrderIngredients($required_date);

                if(!empty($statics)){
                    cache()->put($cache_key, $statics, 60 * $cache_minutes);
                }
            } else {
                $statics = cache()->remember($cache_key, 60 * $cache_minutes, function () use ($required_date) {
                    $statics = $this->calculateOrderIngredients($required_date);

                    if(!empty($statics)){
                        return $this->calculateOrderIngredients($required_date);
                    }
                });
            }

            return $statics;

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
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
            //獲取訂單
            $required_date = parseDate($required_date);
            $required_date_2ymd = parseDateStringTo6d($required_date);

            $requiredDateRawSql = DateHelper::parseDateToSqlWhere('delivery_date', $required_date);

            if(empty($requiredDateRawSql)){
                return false;
            }

            //需要備料的訂單狀態代號
            $temp_row = (new SettingRepository)->getRow(['equal_setting_key' => 'sales_orders_to_be_prepared_status']);
            $sales_orders_to_be_prepared_status = $temp_row->setting_value; // 必須是陣列

            //訂單
            $filter_data = [
                'with' => [ 'order_products.order_product_options.mapProductTags',
                            'order_products.order_product_options.mapProduct'],
                'whereRawSqls' => [$requiredDateRawSql],
                'whereIn' => ['status_code' => $sales_orders_to_be_prepared_status],
                'pagination' => false,
                'limit' => 0,
                'keyBy' => 'id'
            ];
            $orders = $this->OrderRepository->getRows($filter_data);

            if ($orders->isEmpty()) {
                return [];
            }

            $total_lunchbox = 0; //盒餐
            $total_bento = 0; //便當
            $total_stickyrice = 0; //油飯盒

            foreach($orders as $order_id => $order){
                $order['order_products'] = $order['order_products']->keyBy('id');

                foreach ($order['order_products'] as $order_product_id => $order_product) {
                    if(strpos($order_product->name, '盒餐') !== false ){
                        $total_lunchbox += $order_product->quantity;
                    }else if(strpos($order_product->name, '便當') !== false ){
                        $total_bento += $order_product->quantity;
                    }else if(strpos($order_product->name, '油飯盒') !== false ){
                        $total_stickyrice += $order_product->quantity;
                    }
                }
            }

            $statics['info'] = [];
            $statics['info']['total_lunchbox'] = $total_lunchbox;
            $statics['info']['total_bento'] = $total_bento;
            $statics['info']['total_stickyrice'] = $total_stickyrice;
            $statics['info']['packages'] = $total_lunchbox + $total_bento + $total_stickyrice;

            //其它資料
            $statics['info']['ingredient_products'] = [];
            $statics['info']['ingredient_products'][1734]['ingredient_product_id'] = 1734;
            $statics['info']['ingredient_products'][1734]['ingredient_product_name'] = '蔬菜杯';
            $statics['info']['ingredient_products'][1734]['quantity'] = 0;

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

            //計算並寫入資料庫

                //今天以後的資料，寫入資料庫。昨天以前的資料，禁止改寫。
                $arr = [];

                //跑迴圈以完成加總
                foreach ($orders ?? [] as $key1 => $order) {

                    foreach ($order->order_products as $key2 => $order_product) {
                        foreach ($order_product->order_product_options as $key3 => $order_product_option) {
                            //如果已不存在 product_option_value 則略過。這原因是商品基本資料已刪除某選項。但對舊訂單來說這會有問題。先略過。
                            if(empty($order_product_option->product_option_value)){
                                continue;
                            }

                            // 選項沒有對應的商品代號，略過
                            if(empty($order_product_option->product_option_value->option_value)){
                                continue;
                            }

                            // 選項本身所對應的料件
                            $ingredient_product_id = $order_product_option->map_product_id ?? 0;
                            $ingredient_product_name = $order_product_option->mapProduct->name ?? '';

                            if(empty($ingredient_product_name)){
                                continue;
                            }

                            //quantity

                                $quantity  = $order_product_option->quantity;

                                // 3吋潤餅/2 = 6吋潤餅
                                if(in_array($ingredient_product_id, $wrap_ids_needing_halving)){

                                    $inch_6_product_id = $sales_wrap_map[$ingredient_product_id]['new_product_id'];
                                    $inch_6_product_name = $sales_wrap_map[$ingredient_product_id]['new_product_name'];

                                    $arr[$required_date][$order->id][$inch_6_product_id]['required_date'] = $order->delivery_date;
                                    $arr[$required_date][$order->id][$inch_6_product_id]['delivery_time_range'] = $order->delivery_time_range;
                                    $arr[$required_date][$order->id][$inch_6_product_id]['product_id'] = $order_product->product_id;
                                    $arr[$required_date][$order->id][$inch_6_product_id]['product_name'] = $order_product->name;
                                    $arr[$required_date][$order->id][$inch_6_product_id]['ingredient_product_id'] = $inch_6_product_id;
                                    $arr[$required_date][$order->id][$inch_6_product_id]['ingredient_product_name'] = $inch_6_product_name;

                                    if(empty($arr[$required_date][$order->id][$inch_6_product_id]['quantity'])){
                                        $arr[$required_date][$order->id][$inch_6_product_id]['quantity'] = 0;
                                    }
                                    $arr[$required_date][$order->id][$inch_6_product_id]['quantity'] += ceil(($quantity/2));

                                    continue;
                                }

                                // 極品油飯 = 廚娘油飯*2
                                else if($ingredient_product_id == 1737){ //極品油飯 1737
                                    $ingredient_product_id = 1036; //廚娘油飯 1036
                                    $ingredient_product_name = '廚娘油飯';
                                    $arr[$required_date][$order->id][$ingredient_product_id]['required_date'] = $order->delivery_date;
                                    $arr[$required_date][$order->id][$ingredient_product_id]['delivery_time_range'] = $order->delivery_time_range;
                                    $arr[$required_date][$order->id][$ingredient_product_id]['product_id'] = $order_product->product_id;
                                    $arr[$required_date][$order->id][$ingredient_product_id]['product_name'] = $order_product->name;
                                    $arr[$required_date][$order->id][$ingredient_product_id]['ingredient_product_id'] = $ingredient_product_id;
                                    $arr[$required_date][$order->id][$ingredient_product_id]['ingredient_product_name'] = $ingredient_product_name;

                                    if(empty($arr[$required_date][$order->id][$ingredient_product_id]['quantity'])){
                                        $arr[$required_date][$order->id][$ingredient_product_id]['quantity'] = 0;
                                    }
                                    $arr[$required_date][$order->id][$ingredient_product_id]['quantity'] += ($quantity * 2);
                                    continue;
                                }

                                else if($ingredient_product_id == 1734 || $ingredient_product_id == 1033){ //蔬菜杯 1734 時疏 1033

                                    //注意，這是另外的統計 $statics
                                    $statics['info']['ingredient_products'][1734]['quantity'] += $quantity;

                                    

                                    //鹽水煮蛋 1032
                                        //前端傳來的東西，已知滷牛潤餅便當，有蔬菜杯，並且有蛋。可能有些其它商品丟過來 蔬菜杯但沒有另外的蛋。
                                        //所以拆解時，如果已經有蛋，則蔬菜杯不再拆出蛋。如果沒有蛋，則蔬菜杯要拆出蛋。

                                        $already_has_egg = false;
                                        foreach ($order_product->order_product_options as $key3 => $order_product_option) {
                                            if (str_contains($order_product_option->value, '蛋')) {
                                                $already_has_egg = true;
                                            }
                                        }

                                        if($already_has_egg == false){
                                            $ingredient_product_id = 1032;
                                            $ingredient_product_name = '鹽水煮蛋';
                                            $arr[$required_date][$order->id][$ingredient_product_id]['required_date'] = $order->delivery_date;
                                            $arr[$required_date][$order->id][$ingredient_product_id]['delivery_time_range'] = $order->delivery_time_range;
                                            $arr[$required_date][$order->id][$ingredient_product_id]['product_id'] = $order_product->product_id;
                                            $arr[$required_date][$order->id][$ingredient_product_id]['product_name'] = $order_product->name;
                                            $arr[$required_date][$order->id][$ingredient_product_id]['ingredient_product_id'] = $ingredient_product_id;
                                            $arr[$required_date][$order->id][$ingredient_product_id]['ingredient_product_name'] = $ingredient_product_name;
        
                                            if(empty($arr[$required_date][$order->id][$ingredient_product_id]['quantity'])){
                                                $arr[$required_date][$order->id][$ingredient_product_id]['quantity'] = 0;
                                            }
                                            $arr[$required_date][$order->id][$ingredient_product_id]['quantity'] += $quantity;
                                        }
                                    //

                                    //玉米筍 1785
                                    $ingredient_product_id = 1785;
                                    $ingredient_product_name = '玉米筍';
                                    $arr[$required_date][$order->id][$ingredient_product_id]['required_date'] = $order->delivery_date;
                                    $arr[$required_date][$order->id][$ingredient_product_id]['delivery_time_range'] = $order->delivery_time_range;
                                    $arr[$required_date][$order->id][$ingredient_product_id]['product_id'] = $order_product->product_id;
                                    $arr[$required_date][$order->id][$ingredient_product_id]['product_name'] = $order_product->name;
                                    $arr[$required_date][$order->id][$ingredient_product_id]['ingredient_product_id'] = $ingredient_product_id;
                                    $arr[$required_date][$order->id][$ingredient_product_id]['ingredient_product_name'] = $ingredient_product_name;

                                    if(empty($arr[$required_date][$order->id][$ingredient_product_id]['quantity'])){
                                        $arr[$required_date][$order->id][$ingredient_product_id]['quantity'] = 0;
                                    }
                                    $arr[$required_date][$order->id][$ingredient_product_id]['quantity'] += $quantity;

                                    continue;
                                }
                            //End quantity

                            if(empty($arr[$required_date][$order->id][$ingredient_product_id]['required_date'])){
                                $arr[$required_date][$order->id][$ingredient_product_id]['required_date'] = $order->delivery_date;
                                $arr[$required_date][$order->id][$ingredient_product_id]['delivery_time_range'] = $order->delivery_time_range;
                                $arr[$required_date][$order->id][$ingredient_product_id]['product_id'] = $order_product->product_id;
                                $arr[$required_date][$order->id][$ingredient_product_id]['product_name'] = $order_product->name;
                                $arr[$required_date][$order->id][$ingredient_product_id]['ingredient_product_id'] = $ingredient_product_id;
                                $arr[$required_date][$order->id][$ingredient_product_id]['ingredient_product_name'] = $ingredient_product_name;
                            }

                            //如果 null 則 0
                            if(empty($arr[$required_date][$order->id][$ingredient_product_id]['quantity'])){
                                $arr[$required_date][$order->id][$ingredient_product_id]['quantity'] = 0;
                            }

                            $arr[$required_date][$order->id][$ingredient_product_id]['quantity'] += $order_product_option->quantity;
                        }
                    }
                }

                $order_ingredient_upsert_data = [];

                //重新整理加總完成的資料 (必須經過上面加總完成，才能產出下列陣列，用於資料庫)
                foreach ($arr as $required_date => $rows1) {
                    foreach ($rows1 as $order_id => $rows2) {
                        foreach ($rows2 as $ingredient_product_id => $row) {
                            $order_ingredient_upsert_data[] = [
                                'required_date' => $row['required_date'] ?? '2099-01-01',
                                'delivery_time_range' => $row['delivery_time_range'],
                                'order_id' => $order_id,
                                'ingredient_product_id' => $row['ingredient_product_id'],
                                'ingredient_product_name' => $row['ingredient_product_name'],
                                'quantity' => ceil($row['quantity']),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                    }
                }

                OrderIngredient::where('required_date', 'like', "$required_date%")->delete();
                OrderIngredient::insert($order_ingredient_upsert_data);

                //寫入 DailyIngredient
                $daily_upsert_data = [];
                foreach ($order_ingredient_upsert_data as $row) {
                    $ingredient_product_id = $row['ingredient_product_id'];

                    $daily_upsert_data[$ingredient_product_id]['required_date'] = $row['required_date'];
                    $daily_upsert_data[$ingredient_product_id]['ingredient_product_id'] = $row['ingredient_product_id'];
                    $daily_upsert_data[$ingredient_product_id]['ingredient_product_name'] = $row['ingredient_product_name'];
                    $daily_upsert_data[$ingredient_product_id]['created_at'] = now();
                    $daily_upsert_data[$ingredient_product_id]['updated_at'] = now();

                    if(empty($daily_upsert_data[$ingredient_product_id]['quantity'] )){
                        $daily_upsert_data[$ingredient_product_id]['quantity']  = 0;
                    }
                    $daily_upsert_data[$ingredient_product_id]['quantity'] += $row['quantity'];
                }

                if(!empty($daily_upsert_data)){
                    DailyIngredient::where('required_date', 'like', "$required_date%")->delete();
                    DailyIngredient::insert($daily_upsert_data, ['required_date','ingredient_product_id']);
                    DB::commit();
                }

            //End

            //重新整理陣列 $statics['orders'][$order_id]['items'] 的形式
            $ingredients = &$order_ingredient_upsert_data;

            $statics['allDay']['total'] = 0;
            $statics['am']['total'] = 0;
            $statics['pm']['total'] = 0;

            $statics['allDay']['total_6inch_lumpia'] = 0;
            $statics['am']['total_6inch_lumpia'] = 0;
            $statics['pm']['total_6inch_lumpia'] = 0;

            foreach($ingredients ?? [] as $ingredient){
                $ingredient = (object) $ingredient;

                if(empty($statics['orders'][$ingredient->order_id])){
                    $delivery_time_range_start = str_replace([' ', ':'], '', $ingredient->delivery_time_range);

                    $statics['orders'][$ingredient->order_id] = [
                        'delivery_time_range_start' => substr($delivery_time_range_start,0,4),
                        'required_date' => $ingredient->required_date,
                        'require_date_ymd' => $ingredient->required_date,
                        'delivery_time_range' => $ingredient->delivery_time_range,
                        'source_id' => $ingredient->order_id,
                        'source_id_url' => route('lang.admin.sale.orders.form', [$ingredient->order_id]),
                        'order_code' => substr($orders[$ingredient->order_id]->code,4,4),
                        'shipping_road_abbr' => $orders[$ingredient->order_id]->shipping_road_abbr,
                    ];
                }

                if(empty($statics['orders'][$ingredient->order_id]['items'][$ingredient->ingredient_product_id]['ingredient_product_name'])){
                    $statics['orders'][$ingredient->order_id]['items'][$ingredient->ingredient_product_id]['ingredient_product_name'] = $ingredient->ingredient_product_name;
                }

                if(empty($statics['orders'][$ingredient->order_id]['items'][$ingredient->ingredient_product_id]['quantity'])){
                    $statics['orders'][$ingredient->order_id]['items'][$ingredient->ingredient_product_id]['quantity'] = 0;
                }

                $statics['orders'][$ingredient->order_id]['items'][$ingredient->ingredient_product_id]['quantity'] += $ingredient->quantity;
            }

            // 排序
            if(!empty($statics['orders'] )){
                $statics['orders'] = collect($statics['orders'])->sortBy('delivery_time_range_start')->values()->all();
            }

            //統計

            foreach ($statics['orders'] as $order_id => $order) {
                foreach ($order['items'] as $ingredient_product_id => $item) {

                    //allDay
                    if(empty($statics['allDay'][$ingredient_product_id]['quantity'])){
                        $statics['allDay'][$ingredient_product_id]['quantity'] = 0;
                    }
                    $statics['allDay'][$ingredient_product_id]['quantity'] += $item['quantity'];

                    if(in_array($ingredient_product_id, $sales_6inch_lumpia)){
                        $statics['allDay']['total_6inch_lumpia'] += $item['quantity'];
                    }

                    //am
                    if($order['delivery_time_range_start'] <= '1300') {
                        if(empty($statics['am'][$ingredient_product_id]['quantity'])){
                            $statics['am'][$ingredient_product_id]['quantity'] = 0;
                        }
                        $statics['am'][$ingredient_product_id]['quantity'] += $item['quantity'];

                        if(in_array($ingredient_product_id, $sales_6inch_lumpia)){
                            $statics['am']['total_6inch_lumpia'] += $item['quantity'];
                        }
                    }
                    //pm
                    else{
                        if(empty($statics['pm'][$ingredient_product_id]['quantity'])){
                            $statics['pm'][$ingredient_product_id]['quantity'] = 0;
                        }
                        $statics['pm'][$ingredient_product_id]['quantity'] += $item['quantity'];

                        if(in_array($ingredient_product_id, $sales_6inch_lumpia)){
                            $statics['pm']['total_6inch_lumpia'] += $item['quantity'];
                        }
                    }
                }
            }

            $statics['cache_created_at'] = now();

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
