<?php

namespace App\Repositories\Eloquent\Inventory;

use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Repository;
use App\Models\Common\Term;
use App\Models\Inventory\MaterialRequirementsDaily;
use App\Models\Common\TermTranslation;
use App\Models\Catalog\Product;
use App\Repositories\Eloquent\Inventory\UnitRepository;
use App\Repositories\Eloquent\Inventory\ReceivingOrderProductRepository;

class MaterialRequirementsDailyRepository extends Repository
{
    public $modelName = "\App\Models\Inventory\MaterialRequirementsDaily";

    public function saveDailyRequirements($data)
    {
        try{
            DB::beginTransaction();

            // reset() 可以取得第一筆
            $first = reset($data);
            $required_date = $first['required_date'];
            
            foreach ($data as $row) {
                $arr = [
                    'required_date' => $row['required_date'],
                    'product_id' => $row['product_id'],
                    'product_name' => $row['product_name'],
                    'stock_unit_code' => $row['stock_unit_code'],
                    'stock_quantity' => $row['stock_quantity'],
                    'supplier_id' => $row['supplier_id'],
                    'supplier_short_name' => $row['supplier_short_name'],
                    'supplier_own_product_code' => $row['supplier_own_product_code'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $upsert_data[] = $arr;
            }

            if(!empty($upsert_data)){
                MaterialRequirementsDaily::where('required_date', $first['required_date'])->delete();
                $result = MaterialRequirementsDaily::upsert($upsert_data, ['required_date', 'product_id']);
            }
            
            DB::commit();

            return $result;

        } catch (\Exception $ex) {
            DB::rollback();
            $msg = ['error' => $ex->getMessage()];
            return $msg;
        } 
    }
}