<?php

namespace App\Domains\Admin\Services\Inventory;

use Illuminate\Support\Facades\DB;
use App\Services\Service;
use App\Repositories\Eloquent\Inventory\WarehouseRepository;

class WarehouseService extends Service
{
    protected $modelName = "\App\Models\Inventory\Warehouse";

	public function __construct(protected WarehouseRepository $WarehouseRepository)
	{
        $this->WarehouseRepository = $WarehouseRepository;
	}

	public function updateOrCreate($data)
	{
        DB::beginTransaction();

        try {
            $warehouse = $this->findIdOrFailOrNew($data['warehouse_id']);
            
			$warehouse->code = $data['code'] ?? '';
			$warehouse->name = $data['name'];
			$warehouse->sort_order = $data['sort_order'] ?? 999;
			$warehouse->is_active = $data['is_active'] ?? 0;
			$warehouse->is_inventory = $data['is_inventory'] ?? 1;
			$warehouse->comment = $data['comment'] ?? '';
            
			$warehouse->save();

            DB::commit();

            $result['warehouse_id'] = $warehouse->id;
    
            return $result;


        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
	}


    public function deleteWarehouse($warehouse_id)
    {
        try {

            $this->WarehouseRepository->delete($warehouse_id);

            return ['success' => true];

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }

}