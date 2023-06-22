<?php

namespace App\Domains\Admin\Services\Inventory;

use Illuminate\Support\Facades\DB;
use App\Domains\Admin\Services\Service;

class WarehouseService extends Service
{
    protected $modelName = "\App\Models\Inventory\Warehouse";

	public function updateOrCreate($data)
	{
        DB::beginTransaction();

        try {
            $warehouse = $this->findIdOrFailOrNew($data['warehouse_id']);

			//$warehouse->code = $data['code'];
			$warehouse->name = $data['name'];
			$warehouse->is_inventory = $data['is_inventory'] ?? 1;

			$warehouse->save();

            DB::commit();

            $result['warehouse_id'] = $warehouse->id;
    
            return $result;


        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
	}

}