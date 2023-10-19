<?php

namespace App\Repositories\Eloquent\Inventory;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Repositories\Eloquent\Repository;
use App\Models\Inventory\Bom;
use App\Models\Inventory\BomProduct;

class BomRepository extends Repository
{
    public $modelName = "\App\Models\Inventory\Bom";


    public function getRows($data = [], $debug = 0)
    {
        $rows = parent::getRows($data, $debug);

        // 獲取關聯欄位
        if(!empty($data['select_relation_columns'])){
            $columns = $data['select_relation_columns'];

            foreach ($rows as $row) {
                if(in_array('product_name', $columns)){
                    $row->product_name = $row->product->name ?? '-- emtpy --';
                }
            }
        }
        

        return $rows;
    }

    public function saveBom($post_data = [], $debug = 0)
    {
        try{
            DB::beginTransaction();

            $bom_id = $post_data['bom_id'] ?? null;
            $bom = parent::findIdOrFailOrNew($bom_id);
            $result = parent::saveRow($bom, $post_data, $debug);

            if(!empty($result['error'])){
                throw new \Exception($result['error']);
            }

            if(!empty($post_data['bom_products'])){
                $post_data['bom_id'] = $bom->id;
                $result2 = $this->saveBomProducts($post_data);

                if(!empty($result2['error'])){
                    throw new \Exception($result2['error']);
                }
            }

            DB::commit();

            return ['id' => $result['id']];

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
            $product_id = $post_data['product_id'];
    
            foreach ($post_data['bom_products'] as $bom_product) {
                $upsert_data[] = [
                    'id' => $bom_product['id'] ?? null,
                    'bom_id' => $bom_id,
                    'product_id' => $product_id,
                    'sub_product_id' => $bom_product['sub_product_id'],
                    'quantity' => $bom_product['quantity'],
                    'unit_code' => $bom_product['unit_code'],
                    'waste_rate' => $bom_product['waste_rate'] ?? 0,
                    'cost' => $bom_product['cost'] ?? 0,
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
            $row->product_name = $row->product->name ?? 'No product name!!';
        }

        return $row;
    }


    public function getBomSubProducts($bom)
    {
        if(!empty($bom->bom_products)){
            foreach ($bom->bom_products as $bom_product) {
                $bom_product->sub_product_name = $bom_product->sub_product->translation->name ?? '';
                $bom_product->sub_product_specification = $bom_product->sub_product->translation->specification ?? '';
            }
        }

        return $bom->bom_products;
    }

}