<?php

namespace App\Repositories\Eloquent\Common;

use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Repository;
use App\Models\Common\Unit;
use App\Models\Common\UnitTranslation;
use App\Models\Catalog\ProductUnit;
use App\Helpers\Classes\DataHelper;

class UnitRepository extends Repository
{
    public $modelName = "\App\Models\Common\Unit";
    protected $qty;
    protected $fromUnitCode;
    protected $toUnitCode;
    protected $measure;
    protected $standard_unit_codes;

    public function getKeyedActiveUnits($data = [], $debug=0)
    {
        $data['equal_is_active'] = 1;
        $data['pagination'] = false;
        $data['limit'] = 0;

        if(empty($data['sort'])){
            $data['sort'] = 'code';
            $data['order'] = 'ASC';    
        }

        $rows = $this->getRows($data, $debug)->toArray();

        foreach ($rows as $key => $row) {
            unset($row['translation']);
            $code = $row['code'];
            $row['label'] = $row['code'] . ' '. $row['name'];
            
            $new_rows[$code] = (object) $row;
        }

        return $new_rows;
    }


    public function getLocaleKeyedActiveUnits($data = [], $debug=0)
    {
        $data['equal_is_active'] = 1;
        $data['pagination'] = false;
        $data['limit'] = 0;

        if(empty($data['sort'])){
            $data['sort'] = 'code';
            $data['order'] = 'ASC';    
        }

        $rows = $this->getRows($data, $debug)->keyBy('name')->toArray();
        
        foreach ($rows as $key => $row) {
            unset($rows[$key]['translation']);
        }

        return $rows;
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


    public function delete($unit_id)
    {
        UnitTranslation::where('product_id', $unit_id)->delete();

        Unit::where('id', $unit_id)->delete();
    }


    public function setMeasure($measure)
    {
        $this->measure = $measure;

        return $this;
    }
    public function setQty($qty)
    {
        $this->qty = $qty;

        return $this;
    }

    public function from(string $fromUnitCode)
    {
        $this->fromUnitCode = $fromUnitCode;

        return $this;
    }


    public function to(string $toUnitCode)
    {
        $this->toUnitCode = $toUnitCode;

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

    /**
     * product_id
     * from_quantity
     * from_unit_code
     * to_unit_code
     */
    public function calculateQty($data)
    {
        $product_id = $data['product_id'] ?? null;
        $from_unit_code = $data['from_unit_code'];
        $to_unit_code = $data['to_unit_code'];
        $from_quantity = $data['from_quantity'];

        $msg = [];
        
        if(empty($this->standard_unit_codes)){
            $this->setStandardUnitCodes();
        }

        // from and to are all standard units
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
            $product_unit = ProductUnit::where('product_id', $product_id)->where('source_unit_code', $data['from_unit_code'])->where('destination_unit_code', $to_unit_code)->first();

            if(empty($product_unit)){
                //echo '<pre>', print_r($data, 1), "</pre>"; exit;
                return ['error' => 'Cannot find product unit!'];

            }else{
                $arr = $product_unit->toArray();
                unset($arr['source_unit']);
                unset($arr['destination_unit']);

                if(is_numeric($from_quantity) && is_numeric($product_unit->destination_quantity)){
                    $to_quantity = $from_quantity * $product_unit->destination_quantity;
                }else{
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
}

