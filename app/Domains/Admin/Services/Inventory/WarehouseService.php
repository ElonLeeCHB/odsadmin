<?php

namespace App\Domains\Admin\Services\Inventory;

use DB;
use App\Domains\Admin\Services\Service;
use App\Repositories\Eloquent\Inventory\WarehouseRepository;

class WarehouseService extends Service
{
    private $modelName = "\App\Models\Inventory\Warehouse";
	public $repository;

	public function __construct()
	{
        $this->repository = new WarehouseRepository;
	}


	public function updateOrCreate($data)
	{
        DB::beginTransaction();

        try {
            $warehouse = $this->repository->findIdOrFailOrNew($data['warehouse_id']);

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