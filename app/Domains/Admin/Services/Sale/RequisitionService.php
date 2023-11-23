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

/**
 * Requisition 當備料表
 * Requirements 當需求表
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
    )
    {
        $this->repository = $OrderIngredientRepository;
    }

    public function getIngredients($params, $debug = 0)
    {
        $params['with'] = DataHelper::addToArray($params['with'] ?? [], 'product.supplier');

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