<?php

namespace App\Repositories\Eloquent\Inventory;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Repositories\Eloquent\Repository;
use App\Models\Inventory\Bom;
use App\Models\Inventory\BomProduct;
use App\Helpers\Classes\DataHelper;

class BomRepository extends Repository
{
    public $modelName = "\App\Models\Inventory\Bom";


    public function getBoms($data = [], $debug = 0)
    {
        $boms = parent::getRows($data, $debug);


        // 額外欄位
        
        if(!empty($data['extra_columns'])){
            if(in_array('product_name', $data['extra_columns'])){
                $boms->load('product');
            }
        }

        // // 獲取關聯欄位
        // if(!empty($data['select_relation_columns'])){
        //     $columns = $data['select_relation_columns'];

        //     foreach ($boms as $row) {
        //         if(in_array('product_name', $columns)){
        //             $row->product_name = $row->product->name ?? '-- emtpy --';
        //         }
        //     }
        // }

        foreach ($boms as $row) {

            // 額外欄位 掛載到資料集
            if(!empty($data['extra_columns'])){

                if(in_array('product_name', $data['extra_columns'])){
                    $row->product_name = $row->product->name;
                    unset($row->product);
                }
            }
        }

        return $boms;
    }

    public function saveBom($post_data = [], $debug = 0)
    {
        try{
            DB::beginTransaction();

            $bom_id = $post_data['bom_id'] ?? null;
            $result = parent::saveRow($bom_id, $post_data, $debug);

            if(!empty($result['error'])){
                throw new \Exception($result['error']);
            }

            
            $bom = parent::findIdOrFailOrNew($bom_id);

            if(!empty($post_data['products'])){
                $post_data['bom_id'] = $bom->id;
                $result2 = $this->saveBomProducts($post_data);

                if(!empty($result2['error'])){
                    throw new \Exception($result2['error']);
                }
            }

            DB::commit();


            $bom->refresh();
            $bom->load('bom_products');
            DataHelper::setJsonFromStorage('cache/inventory/BomId_' . $bom->id . '.json', $bom->toArray());
            
            return ['id' => $bom->id];

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        } 
    }


    public function saveBomProducts($post_data)
    {
        $upsert_data = [];

        DB::beginTransaction();

        try{
            $bom_id = $post_data['bom_id'];
            $product_id = $post_data['product_id']; // 主件
    
            foreach ($post_data['products'] as $product) {
                $upsert_data[] = [
                    'id' => $product['id'] ?? null,
                    'bom_id' => $bom_id,
                    'product_id' => $product_id,
                    'sub_product_id' => $product['sub_product_id'],
                    'quantity' => $product['quantity'],
                    'usage_unit_code' => $product['usage_unit_code'],
                    'waste_rate' => $product['waste_rate'] ?? 0,
                    'amount' => $product['amount'] ?? 0,
                ];
            }
    
            if(!empty($upsert_data)){
                BomProduct::where('bom_id', $bom_id)->delete();
                BomProduct::upsert($upsert_data, ['id']);
            }

            DB::commit();

            return true;

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }
    
    
    public function getExtraColumns($row, $columns)
    {
        if(in_array('product_name', $columns)){
            $row->product_name = $row->product->name ?? '';
        }

        return $row;
    }


    public function getBomSubProducts($bom)
    {
        if(!empty($bom->bom_products)){
            foreach ($bom->bom_products as $bom_product) {
                $bom_product->usage_unit_name = $bom_product->sub_product->usage_unit->name ?? '';
                $bom_product->usage_price = $bom_product->sub_product->usage_price ?? 0;
                $bom_product->sub_product_name = $bom_product->sub_product->translation->name ?? '';
                $bom_product->sub_product_specification = $bom_product->sub_product->translation->specification ?? '';
                $bom_product->sub_product_supplier_name = $bom_product->sub_product->supplier->name ?? '';
                $bom_product->sub_product_supplier_short_name = $bom_product->sub_product->supplier->short_name ?? '';
            }
        }

        return $bom->bom_products;
    }

}