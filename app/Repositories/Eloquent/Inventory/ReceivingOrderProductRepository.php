<?php

namespace App\Repositories\Eloquent\Inventory;

use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Repository;
use App\Models\Inventory\ReceivingOrder;
use App\Models\Inventory\ReceivingOrderProduct;

class ReceivingOrderProductRepository extends Repository
{
    public $modelName = "\App\Models\Inventory\ReceivingOrderProduct";


    public function deleteByReceivingOrderById($receiving_order_id)
    {
        try {

            DB::beginTransaction();

            ReceivingOrderProduct::where('receiving_order_id', $receiving_order_id)->delete();

            DB::commit();

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }

    }


}
