<?php

namespace App\Repositories\Eloquent\Inventory;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Repositories\Eloquent\Repository;
use App\Models\Inventory\Unit;

class UnitRepository extends Repository
{
    public $modelName = "\App\Models\Inventory\Unit";


    public function getUnits($data = [], $debug = 0)
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


	public function saveUnit($post_data, $debug = 0)
	{        
        try {
            $unit_id = $post_data['unit_id'] ?? null;

            $result = $this->saveRow($unit_id, $post_data);

            if(!empty($result['error'])){
                throw new \Exception($result['error']);
            }

            return $result;

        } catch (\Exception $ex) {
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
        if(empty($row->id)){
            $row->name = '';
            $row->comment = '';
            $row->code = '';
            $row->master_code = '';
            $row->type = '';
            $row->sort_order = '';
            $row->created_at = '';
            $row->updated_at = '';
            $row->is_active = '';
        }

        $result = $row->toArray();

        if(!empty($result['translation'])){
            unset($result['translation']);
        }

        return (object) $result;
    }
}