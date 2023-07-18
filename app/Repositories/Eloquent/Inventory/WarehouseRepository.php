<?php

namespace App\Repositories\Eloquent\Inventory;

use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Repository;
use App\Models\Inventory\Warehouse;

class WarehouseRepository extends Repository
{
    public $modelName = "\App\Models\Inventory\Warehouse";


    public function delete($warehouse_id)
    {
        try {

            DB::beginTransaction();

            Warehouse::where('id', $warehouse_id)->delete();

            DB::commit();

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }
}