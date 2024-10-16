<?php

namespace App\Repositories\Eloquent\Inventory;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Repositories\Eloquent\Repository;
use App\Models\Inventory\Unit;
use App\Models\Catalog\ProductUnit;
use Illuminate\Support\Facades\Storage;
use App\Helpers\Classes\DataHelper;

class UnitRepository extends Repository
{
    public $modelName = "\App\Models\Inventory\Unit";
    protected $qty;
    protected $fromUnitCode;
    protected $toUnitCode;
    protected $measure;
    protected $standard_unit_codes;
    protected $converter;


    public function getAllUnits()
    {
        $filter_data = [
            'equal_is_active' => 1,
            'pagination' => false,
            'limit' => 0,
            'keyBy' => 'code',
        ];
        $rows = $this->getRows($filter_data)->toArray();
        
        return DataHelper::unsetArrayFromArrayList($rows);
    }


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


    public function destroy($ids)
    {
        try {
            DB::beginTransaction();

            Unit::whereIn('id', $ids)->delete();
            
            DB::commit();

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
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

            Storage::deleteDirectory('cache/units');

            return $result;

        } catch (\Exception $ex) {
            return ['error' => $ex->getMessage()];
        }
	}

    public function getCodeKeyedActiveUnits($params = [], $toArray = false)
    {
        $json_path = 'cache/units/CodeKeyedActiveUnits.json';

        if (! Storage::exists($json_path)) {
        
            $data['equal_is_active'] = 1;
            $data['pagination'] = false;
            $data['limit'] = 0;

            if(empty($data['sort'])){
                $data['sort'] = 'code';
                $data['order'] = 'ASC';    
            }

            $rows = $this->getRows($data)->toArray();

            $new_rows = [];

            foreach ($rows as $key => $row) {
                unset($row['translation']);

                $code = $row['code'];
                $row['label'] = $row['code'] . ' '. $row['name'];
                
                if($toArray == true){
                    $new_rows[$code] = (array) $row;
                }else{
                    $new_rows[$code] = (object) $row;
                }
            }

            $rows = $new_rows;

            if(!empty($rows)){
                Storage::put($json_path, json_encode($rows));
                sleep(1);
            }
        }

        return DataHelper::getJsonFromStorage($json_path, $toArray);
    }

    public function getCodeKeyedStandardActiveUnits($params = [], $toArray = false)
    {
        $json_path = 'cache/units/CodeKeyedStandardActiveUnits.json';

        if (! Storage::exists($json_path)) {
        
            $data['equal_is_active'] = 1;
            $data['equal_is_standard'] = 1;
            $data['pagination'] = false;
            $data['limit'] = 0;

            if(empty($data['sort'])){
                $data['sort'] = 'code';
                $data['order'] = 'ASC';    
            }

            $rows = $this->getRows($data)->toArray();

            $new_rows = [];

            foreach ($rows as $key => $row) {
                unset($row['translation']);

                $code = $row['code'];
                $row['label'] = $row['code'] . ' '. $row['name'];
                
                if($toArray == true){
                    $new_rows[$code] = (array) $row;
                }else{
                    $new_rows[$code] = (object) $row;
                }
            }

            $rows = $new_rows;

            if(!empty($rows)){
                Storage::put($json_path, json_encode($rows));
                sleep(1);
            }
        }

        return DataHelper::getJsonFromStorage($json_path, $toArray);
    }

    public function getLocaleKeyedActiveUnits($params = [], $toArray = false)
    {
        $json_path = 'cache/units/LocaleKeyedActiveUnits.json';

        if (! Storage::exists($json_path)) {
            $params['equal_is_active'] = 1;
            $params['pagination'] = false;
            $params['limit'] = 0;
    
            if(empty($params['sort'])){
                $params['sort'] = 'code';
                $params['order'] = 'ASC';    
            }
    
            $rows = $this->getRows($params)->keyBy('name')->toArray();
            
            $new_rows = [];

            foreach ($rows as $key => $row) {
                unset($rows[$key]['translation']);
                
                if($toArray == true){
                    $new_rows[$key] = (array) $row;
                }else{
                    $new_rows[$key] = (object) $row;
                }
            }

            if(!empty($rows)){
                Storage::put($json_path, json_encode($rows));
                sleep(1);
            }
        }

        return DataHelper::getJsonFromStorage($json_path, $toArray);
    }

    public function setStandardUnitCodes()
    {
        $data = [
            'equal_is_standard' => 1,
            'pluck' => 'code',
            'pagination' => false,
            'limit' => 0
        ];
        $result = $this->getRows($data)->toArray();

        $this->standard_unit_codes = $result;

        return $result;
    }
}