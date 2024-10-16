<?php

namespace App\Repositories\Eloquent\Inventory;

use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Repository;
use App\Models\Inventory\Warehouse;

class WarehouseRepository extends Repository
{
    public $modelName = "\App\Models\Inventory\Warehouse";
    

	public function updateOrCreate($data)
	{
        DB::beginTransaction();

        try {
            $result = $this->findIdOrFailOrNew($data['warehouse_id']);

            if(empty($result['error']) && !empty($result['data'])){
                $warehouse = $result['data'];
            }else{
                return response(json_encode($result))->header('Content-Type','application/json');
            }
            
			$warehouse->code = $data['code'] ?? '';
			$warehouse->name = $data['name'];
			$warehouse->sort_order = $data['sort_order'] ?? 1000;
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
    

    public function destroy($ids)
    {
        try {
            DB::beginTransaction();

            Warehouse::whereIn('id', $ids)->delete();
            
            DB::commit();

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }
}