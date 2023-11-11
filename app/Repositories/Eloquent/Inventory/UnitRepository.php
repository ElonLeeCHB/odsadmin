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

    /**
     * $data['product_id'] can be null
     * $data['from_quantity']
     * $data['from_unit_code']
     * $data['to_unit_code']
     */
    public function calculateQty($params)
    {
        $product_id = $params['product_id'] ?? null;
        $from_unit_code = $params['from_unit_code'];
        $to_unit_code = $params['to_unit_code'];
        $from_quantity = $params['from_quantity'];

        $msg = [];
        
        if(empty($this->standard_unit_codes)){
            $this->setStandardUnitCodes();
        }

        // all standard units
        if(in_array($from_unit_code, $this->standard_unit_codes) && in_array($to_unit_code, $this->standard_unit_codes)){
            $fromUnit = Unit::where('code', $from_unit_code)->first();
            $toUnit = Unit::where('code', $to_unit_code)->first();

            if($fromUnit->base_unit_code !== $toUnit->base_unit_code){
                return ['error' => 'base_unit_code is different!'];
            }
    
            $qty_of_base = $from_quantity * $fromUnit->factor;
            $to_quantity = $qty_of_base / $toUnit->factor;
        }

        else if(!empty($product_id)){
            $product_unit = ProductUnit::where('product_id', $product_id)->where('source_unit_code', $params['from_unit_code'])->where('destination_unit_code', $to_unit_code)->first();

            if(empty($product_unit)){
                return ['error' => 'Cannot find product unit!'];

            }else{
                $arr = $product_unit->toArray();
                unset($arr['source_unit']);
                unset($arr['destination_unit']);

                if(is_numeric($from_quantity) && is_numeric($product_unit->destination_quantity)){
                    $to_quantity = $from_quantity * $product_unit->destination_quantity;
                }else{
                    // Error
                    $msg = 'product_id='.$product_id.', from_quantity='.$from_quantity.', destination_quantity='.$product_unit->destination_quantity;
                    echo '<pre>', print_r($msg, 1), "</pre>"; exit;
                    return ['error' => 'product_id='.$product_id.', from_quantity='.$from_quantity.', destination_quantity='.$product_unit->destination_quantity];
                }

                // $arr['from_quantity'] = $from_quantity;
                // $arr['to_quantity'] = $to_quantity;
                // echo '<pre>product_unit= ', print_r($arr, 1), "</pre>"; 
            }
        }

        if(!empty($to_quantity)){
            return $to_quantity;
        }

        return ['error' => 'Calulation failed'];
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


    /**
     * 單位轉換
     */

    public static function build()
    {
        return self::$converter;
    }

    public function setMeasure($measure)
    {
        self::$converter->measure = $measure;

        return $this;
    }
    public function setQty($qty)
    {
        self::$converter->qty = $qty;

        return $this;
    }

    public function from(string $fromUnitCode)
    {
        self::$converter->fromUnitCode = $fromUnitCode;

        return $this;
    }


    public function to(string $toUnitCode)
    {
        $measure = self::$converter->measure;
        $fromUnitCode = self::$converter->fromUnitCode;
        $qty = self::$converter->qty;

        if(empty($this->standard_unit_codes)){
            $this->setStandardUnitCodes();
        }

        // from and to are all standard units
        if(in_array($this->fromUnitCode, $this->standard_unit_codes) && in_array($this->toUnitCode, $this->standard_unit_codes)){

            $fromUnit = Unit::where('code', $this->fromUnitCode)->first();
            $toUnit = Unit::where('code', $this->toUnitCode)->first();

            if($fromUnit->base_unit_code !== $toUnit->base_unit_code){
                return ['error' => 'base_unit_code is different!'];
            }
    
            $qty_of_base = $this->qty * $fromUnit->factor;
            $toQty = $qty_of_base / $toUnit->factor;

        }
        
        else{
            //ProductUnit::where('product_id', );
        }

        // 未完成


        //$msg = $this->qty . ' ' . $this->fromUnitCode . ' = ' . $toQty . ' ' . $this->toUnitCode;
        
        return $toQty;
    }
}