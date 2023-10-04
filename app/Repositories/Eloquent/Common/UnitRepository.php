<?php

namespace App\Repositories\Eloquent\Common;

use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Repository;
use App\Models\Common\Unit;
use App\Models\Common\UnitTranslation;

class UnitRepository extends Repository
{
    public $modelName = "\App\Models\Common\Unit";


    public function getActiveUnits($data = [], $debug=0)
    {
        $data['equal_is_active'] = 1;

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


    public function delete($unit_id)
    {
        UnitTranslation::where('product_id', $unit_id)->delete();

        Unit::where('id', $unit_id)->delete();
    }
}

