<?php

namespace App\Repositories\Eloquent\Catalog;

use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Repository;
use App\Models\Catalog\ProductUnit;

class ProductUnitRepository extends Repository
{
    public $modelName = "\App\Models\Catalog\ProductUnit";


    public function getProductUnits($data = [], $debug = 0)
    {
        $rows = $this->getRows($data);
        
        return $rows;
    }

    public function destroy($ids)
    {
        try {
            DB::beginTransaction();
            
            ProductUnit::whereIn('id', $ids)->delete();

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }

}