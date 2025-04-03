<?php

namespace App\Repositories\Eloquent\Sale;

use App\Repositories\Eloquent\Repository;
use App\Models\Sale\Order;
use App\Models\Sale\OrderProduct;
use App\Models\Sale\OrderProductOption;
use App\Models\Setting\Setting;
use Carbon\Carbon;

/**
 * 料件需求的資料應該比較需要查詢，所以寫入資料庫。備料表用快取就好。
 */
class OrderDailyRequirementRepository
{
    public function handleByDate($required_date, $force_update = 0, $is_return = false)
    {
        $required_date = Carbon::parse($required_date)->format('Y-m-d');

        $cache_key = 'sale_order_requirement_date_' . $required_date;
                
        $statistics = cache()->get($cache_key);

        // 如果快取不存在或快取中的 cache_created_at 超過指定期限
        if ($force_update || !$statistics || !isset($statistics['cache_created_at']) || Carbon::parse($statistics['cache_created_at'])->diffInMinutes(now()) > 60) {
            $statistics = $this->calculateByDate($required_date);
            cache()->put($cache_key, $statistics, 60*24*30);
        }

        $this->toDatabase($statistics);

        if ($is_return == true){
            return $statistics;
        }
    }

    public function toDatabase($statistics)
    {
        // $statictics
    }

    /**
     * 讀取訂單商品選項，經由BOM表計算料件需求。
     * @param $order_id
     * @param $order_product_id
     * @return array
     */
    public function calculateByDate($required_date)
    {

    }




    /**
     * 根據 Bom 計算料件需求
     */
    // public function calcRequirementsForDate($required_date)
    // {
    //     $json = [];

    //     $required_date = DateHelper::parseDate($required_date);

    //     if($required_date == false){
    //         $json['error']['required_date'] = '日期錯誤';
    //     }

    //     // 獲取備料表
    //     $params = [
    //         'equal_required_date' => $required_date,
    //         'pagination' => false,
    //         'limit' => 0,
    //         'has' => 'bom',
    //         'with' => ['bom.bom_products.sub_product.translation', 'bom.bom_products.sub_product.supplier'],
    //     ];
    //     $requisitions = $this->getIngredients($params);

    //     $requirements = [];

    //     if(!$json) {
    //         // 根據bom表計算需求

    //         $quantity = 0;

    //         foreach ($requisitions as $requisition) {
    //             //主件
    //             $product_id = $requisition->ingredient_product_id;

    //             foreach ($requisition->bom->bom_products as $bom_product) {
    //                 $sub_product_id = $bom_product->sub_product_id;

    //                 if(!isset($requirements[$sub_product_id])){
    //                     $requirements[$sub_product_id] = [
    //                         'required_date' => $required_date,
    //                         'product_id' => $bom_product->sub_product->id,
    //                         'product_name' => $bom_product->sub_product->name,
    //                         'usage_quantity' => 0,
    //                         'usage_unit_code' =>  $bom_product->usage_unit_code,
    //                         'stock_quantity' => 0,
    //                         'stock_unit_code' =>  $bom_product->sub_product->stock_unit_code,
    //                         'supplier_id' =>  $bom_product->sub_product->supplier_id,
    //                         'supplier_short_name' =>  $bom_product->sub_product->supplier->short_name ?? '',
    //                         'supplier_own_product_code' =>  $bom_product->sub_product->supplier_own_product_code ?? '',
    //                     ];
    //                 }
    //                 $usage_quantity = $requisition->quantity * $bom_product->quantity;

    //                 $stock_quantity = UnitConverter::build()->qty($usage_quantity)
    //                         ->from($bom_product->usage_unit_code)
    //                         ->to($bom_product->sub_product->stock_unit_code)
    //                         ->product($product_id)
    //                         ->get();
    //                 if (!is_numeric($stock_quantity)) {
    //                     // 如果不是數字，初始化為0或其他合理值
    //                     $stock_quantity = 0;
    //                 }
    //                 $requirements[$sub_product_id]['stock_quantity'] += $stock_quantity;
    //                 $requirements[$sub_product_id]['usage_quantity'] += $usage_quantity;
    //             }
    //         }

    //         if(!empty($requirements)){
    //             return $this->RequirementRepository->saveDailyRequirements($requirements);
    //         }
    //     }

    //     return $json;
    // }
}

