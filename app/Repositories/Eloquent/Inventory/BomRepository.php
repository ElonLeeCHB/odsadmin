<?php

namespace App\Repositories\Eloquent\Inventory;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Repositories\Eloquent\Repository;
use App\Models\Inventory\Bom;
use App\Models\Inventory\BomProduct;
use App\Models\Catalog\Product;
use App\Helpers\Classes\DataHelper;

class BomRepository extends Repository
{
    public $modelName = "\App\Models\Inventory\Bom";


    public function getBoms($data = [], $debug = 0)
    {
        $data = $this->resetQueryData($data);

        $boms = parent::getRows($data, $debug);

        // 額外欄位

        if(!empty($data['extra_columns'])){
            if(in_array('product_name', $data['extra_columns'])){
                $boms->load('product');
            }
        }

        // // 獲取關聯欄位
        if(!empty($data['select_relation_columns'])){
            $columns = $data['select_relation_columns'];

            foreach ($boms as $row) {
                if(in_array('product_name', $columns)){
                    $row->product_name = $row->product->name ?? '';
                }
            }
        }

        // foreach ($boms as $row) {

        //     // 額外欄位 掛載到資料集
        //     if(!empty($data['extra_columns'])){

        //         if(in_array('product_name', $data['extra_columns'])){
        //             $row->product_name = $row->product->name;
        //             unset($row->product);
        //         }
        //     }
        // }

        return $boms;
    }

    public function saveBom($post_data = [], $debug = 0)
    {
        try{

            $bom_id = $post_data['bom_id'] ?? null;
            $result = parent::saveRow($bom_id, $post_data, 1);

            if(!empty($result['error'])){
                throw new \Exception($result['error']);
            }

            $bom_id = $result['id'];

            $result = parent::findIdOrFailOrNew($bom_id);

            if(!empty($result['data'])){
                $bom = $result['data'];
            }else if($result['error']){
                throw new \Exception($result['error']);
            }
            unset($result);

            if(!empty($post_data['products'])){
                $post_data['bom_id'] = $bom->id;
                $result2 = $this->saveBomProducts($post_data);

                if(!empty($result2['error'])){
                    throw new \Exception($result2['error']);
                }
            }

            // 將BOM單頭成本回寫料件資料表的 庫存單位成本
            if(isset($post_data['total']) && $post_data['total'] != ''){
                $product = Product::find($bom->product_id);
                $product->stock_price = $bom->total;
                $product->save();
            }

            $bom->refresh();
            $bom->load('bomProducts');
            DataHelper::setJsonToStorage('cache/inventory/BomId_' . $bom->id . '.json', $bom->toArray());

            return ['data' => ['id' => $bom->id]];

        } catch (\Exception $ex) {
            return ['error' => $ex->getMessage()];
        }
    }


    public function saveBomProducts($post_data)
    {
        $upsert_data = [];

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
                $res1 = BomProduct::where('bom_id', $bom_id)->delete();
                $res2 = BomProduct::upsert($upsert_data, ['id']);
            }

            return true;

        } catch (\Throwable $th) {
            echo "<pre>",print_r($th->getMessage(),true),"</pre>\r\n";exit;
            throw $th;
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

    public function resetQueryData($data)
    {
        // 狀態
        if(!empty($data['filter_status_code']) && $data['filter_status_code'] == 'withoutV'){
            $data['whereNotIn'] = ['status_code' => ['V']];
            unset($data['filter_status_code']);
        }

        return $data;
    }


}
