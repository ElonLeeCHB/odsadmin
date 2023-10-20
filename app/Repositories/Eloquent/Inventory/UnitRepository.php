<?php

namespace App\Repositories\Eloquent\Inventory;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Repositories\Eloquent\Repository;
use App\Models\Inventory\Unit;

class UnitRepository extends Repository
{
    public $modelName = "\App\Models\Inventory\Unit";


    public function getUnits($data = [], $debug)
    {
        // Sort && Order
        if(isset($data['sort']) && $data['sort'] == 'name'){
            unset($data['sort']);

            if(!isset($data['order'])){
                $data['order'] = 'ASC';
            }
            
            $locale = app()->getLocale();

            $data['orderByRaw'] = "(SELECT name FROM unit_translations WHERE locale='".$locale."' and unit_translations.unit_id = units.id) " . $data['order'];
        }

        $rows = $this->getRows($data, $debug);
        return $rows;
    }


    public function deleteUnitById($id)
    {
        try {

            DB::beginTransaction();

            Unit::where('id', $id)->delete();

            DB::commit();

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }


	public function updateOrCreateUnit($data)
	{
        DB::beginTransaction();
        
        try {
            $unit = $this->findIdOrFailOrNew($data['unit_id']);
            
			$unit->code = $data['code'] ?? '';
			$unit->sort_order = $data['sort_order'] ?? 999;
			$unit->is_active = $data['is_active'] ?? 0;
			$unit->comment = $data['comment'] ?? '';
            
			$unit->save();

            // 儲存多語資料
            if(!empty($data['translations'])){
                $this->saveTranslationData($unit, $data['translations']);
            }

            DB::commit();

            $result['unit_id'] = $unit->id;
    
            return $result;


        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
	}

    // 尋找關聯，並將關聯值賦予記錄
    public function optimizeRow($row)
    {
        // if(!empty($row->status)){
        //     $row->status_name = $row->status->name;
        // }

        return $row;
    }


    // 刪除關聯
    public function sanitizeRow($row)
    {
        $arrOrder = $row->toArray();

        if(!empty($arrOrder['translation'])){
            unset($arrOrder['translation']);
        }

        return (object) $arrOrder;
    }
}