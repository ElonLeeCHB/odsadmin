<?php

namespace App\Repositories\Eloquent\Inventory;

use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Repository;
use App\Models\Inventory\ReceivingOrder;
use App\Models\Inventory\ReceivingProduct;

class ReceivingProductRepository extends Repository
{
    public $modelName = "\App\Models\Inventory\ReceivingProduct";


    public function deleteByReceivingOrderId($receiving_order_id)
    {
        try {

            DB::beginTransaction();

            ReceivingProduct::where('receiving_order_id', $receiving_order_id)->delete();

            DB::commit();

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }

    }


}
