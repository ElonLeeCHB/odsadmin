<?php

namespace App\Domains\Admin\Services\Sale;

use App\Helpers\Classes\DataHelper;
use App\Services\Service;
use App\Repositories\Eloquent\Sale\OrderIngredientHourRepository;
use App\Repositories\Eloquent\Sale\OrderIngredientRepository;
use App\Repositories\Eloquent\Inventory\RequirementRepository;
use App\Repositories\Eloquent\Inventory\UnitRepository;
use App\Helpers\Classes\DateHelper;
use App\Helpers\Classes\UnitConverter;
use App\Models\Sale\OrderIngredient;
use App\Models\Sale\OrderIngredientHour;
use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Sale\OrderRepository;
use App\Repositories\Eloquent\Setting\SettingRepository;

/**
 * Requisition 備料表
 * Requirements 需求表
 *
 */
class RequisitionService extends Service
{
    public $modelName = "\App\Models\Sale\OrderIngredient";

    public function __construct(
      protected OrderIngredientHourRepository $OrderIngredientHourRepository
    , protected OrderIngredientRepository $OrderIngredientRepository
    , protected RequirementRepository $RequirementRepository
    , protected UnitRepository $UnitRepository
    , protected OrderRepository $OrderRepository
    )
    {
        $this->repository = $OrderIngredientRepository;
    }

    public function getIngredients($params, $debug = 0)
    {
        $params['with'] = DataHelper::addToArray('product.supplier', $params['with'] ?? []);

        $ingredients = $this->OrderIngredientRepository->getIngredients($params, $debug);

        foreach ($ingredients as $row) {
            $row->product_name = $row->product->name;
            $row->supplier_name = $row->product->supplier->name ?? '';
            $row->supplier_short_name = $row->product->supplier->short_name ?? '';
        }

        return $ingredients;
    }


    /**
     * 根據 Bom 計算料件需求
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

    /**
     * 抓取訂單資料，然後寫入資料表 order_ingredients
     * 這個 function 應該很完美，不再需要任何調整。 2024-10-31
     */
    public function writeIngredientsToDbFromOrders($required_date, $orders)
    {

        try {
            DB::beginTransaction();

            $arr = [];

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

                        if(empty($arr[$required_date][$order->id][$ingredient_product_id]['quantity'])){
                            $arr[$required_date][$order->id][$ingredient_product_id]['quantity'] = 0;
                        }
                        if(empty($arr[$required_date][$order->id][$ingredient_product_id]['original_quantity'])){
                            $arr[$required_date][$order->id][$ingredient_product_id]['original_quantity'] = 0;
                        }

                        $arr[$required_date][$order->id][$ingredient_product_id]['required_date'] = $order->delivery_date;
                        $arr[$required_date][$order->id][$ingredient_product_id]['delivery_time_range'] = $order->delivery_time_range;
                        $arr[$required_date][$order->id][$ingredient_product_id]['product_id'] = $order_product->product_id;
                        $arr[$required_date][$order->id][$ingredient_product_id]['product_name'] = $order_product->name;
                        $arr[$required_date][$order->id][$ingredient_product_id]['ingredient_product_id'] = $ingredient_product_id;
                        $arr[$required_date][$order->id][$ingredient_product_id]['ingredient_product_name'] = $ingredient_product_name;
                        $arr[$required_date][$order->id][$ingredient_product_id]['quantity'] += $order_product_option->quantity;
                    }
                }
            }
            // echo "<pre>",print_r($arr[$required_date][8000],true),"</pre>";exit;


            $upsert_data = [];

            foreach ($arr as $required_date => $rows1) {
                foreach ($rows1 as $order_id => $rows2) {
                    foreach ($rows2 as $ingredient_product_id => $row) {
                        $upsert_data[] = [
                            // 'required_date' => $required_date,
                            'required_date' => $row['required_date'],
                            'delivery_time_range' => $row['delivery_time_range'],
                            'order_id' => $order_id,
                            'ingredient_product_id' => $row['ingredient_product_id'],
                            'ingredient_product_name' => $row['ingredient_product_name'],
                            'quantity' => ceil($row['quantity']),
                        ];
                    }
                }
            }
            OrderIngredient::where('required_date', $required_date)->delete();
            OrderIngredient::upsert($upsert_data, ['order_id','ingredient_product_id']);

            DB::commit();

            return $upsert_data;

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }


    public function getIngredientHoursByDate($required_date)
    {
        $rows = OrderIngredientHour::select('required_time', 'required_date', 'order_id', 'ingredient_product_id', 'ingredient_product_name', DB::raw('SUM(quantity) as quantity'))
            ->groupBy('required_time', 'required_date', 'order_id', 'ingredient_product_id', 'ingredient_product_name')
            ->where('required_date', $required_date)->get();

        return $rows;
    }

}
