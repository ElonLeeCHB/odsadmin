<?php

namespace App\Repositories\Eloquent\Inventory;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Repositories\Eloquent\Repository;
use App\Models\Inventory\Bom;
use App\Models\Inventory\BomProduct;
use App\Models\Catalog\Product;
use App\Helpers\Classes\DataHelper;
use App\Helpers\Classes\OrmHelper;

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

    public function saveBomBundle($data = [], $debug = 0)
    {
        $bom_id = $data['bom_id'] ?? null;

        // $result = parent::saveRow($bom_id, $data, 1);
        $query = Bom::query();
        $bom = OrmHelper::findIdOrFailOrNew($query, $bom_id);

        // 儲存本表 boms
        OrmHelper::saveRow($bom, $data);

        $bom_id = $bom->id;
        $master_product_id = $data['product_id']; // 主件

        // 儲存子表 bom_products
            $input_bom_products = $data['bom_products'] ?? [];

            // 1. 取得目前資料庫中的 BomProduct ID 清單
            $existing_bom_products = BomProduct::where('bom_id', $bom_id)->get()->keyBy('id');
            $existing_bom_product_ids = $existing_bom_products->keys()->all();

            // 2. 初始化 ID 收集器
            $input_bom_product_ids = [];

            // 3. 處理輸入資料
            foreach ($input_bom_products as $input_bom_product) {
                $id = $input_bom_product['id'] ?? null;

                $record_data = [
                    'bom_id' => $bom_id,
                    'product_id' => $master_product_id,
                    'sub_product_id' => $input_bom_product['sub_product_id'],
                    'quantity' => $input_bom_product['quantity'],
                    'usage_unit_code' => $input_bom_product['usage_unit_code'],
                    'waste_rate' => $input_bom_product['waste_rate'] ?? 0,
                    'amount' => $input_bom_product['amount'] ?? 0,
                ];

                if ($id && isset($existing_bom_products[$id])) {
                    // 更新資料
                    $existing_bom_products[$id]->update($record_data);
                    $input_bom_product_ids[] = $id;
                } else {
                    // 新增資料（不使用傳入的 id）
                    BomProduct::create($record_data);
                }
            }

            // 4. 刪除資料庫中有但輸入資料沒有的項目
            $to_delete_bom_product_ids = array_diff($existing_bom_product_ids, $input_bom_product_ids);

            if (!empty($to_delete_ids)) {
                BomProduct::whereIn('id', $to_delete_bom_product_ids)->delete();
            }
        // end 儲存子表 bom_products

        // 將BOM單頭成本回寫料件資料表的 庫存單位成本
            $product = Product::find($bom->product_id);
            $product->stock_price = $bom->total;
            $product->save();
        // end 將BOM單頭成本回寫料件資料表的 庫存單位成本

        $bom->refresh();
        $bom->load('bomProducts');

        return $bom;
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
