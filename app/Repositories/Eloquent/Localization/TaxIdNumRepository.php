<?php

namespace App\Repositories\Eloquent\Localization;

use App\Models\SysData\TwTaxIdNum;
use App\Repositories\Eloquent\Repository;
use App\Repositories\Eloquent\Common\TermRepository;
use App\Helpers\Classes\DataHelper;

class TaxIdNumRepository extends Repository
{
    public $modelName = "\App\Models\SysData\TwTaxIdNum";


    public function getTaxIdNum($data = [], $debug = 0)
    {
        $tax_id_num = '';

        if(!empty($data['filter_tax_id_num'])){
            $tax_id_num = $data['filter_tax_id_num'];
        }else if(!empty($data['equal_tax_id_num'])){
            $tax_id_num = $data['equal_tax_id_num'];
        }

        return $this->refreshTaxIdNumsJson($tax_id_num);
        return $tax_id_nums[$equal_tax_id_num] ?? null;
    }


    public function getTaxIdNums($params = [], $debug = 0)
    {
        
        $rows = $this->getRows($params);

        if(isset($params['equal_tax_id_num'])){
            $equal_tax_id_num = $params['equal_tax_id_num'];
            $tax_id_nums = $this->refreshTaxIdNumsJson($equal_tax_id_num);
        }

        return $tax_id_nums ?? null;
    }

    public function refreshTaxIdNumsJson($tax_id_num)
    {
        $last3 = substr($tax_id_num,-3); //統編末3碼

        $cacheName = 'cache/tax_id_nums/' . $last3 . '.json';

        $tax_id_nums = DataHelper::getJsonFromStoragForCollection($cacheName);

        if(empty($tax_id_nums)){
            $filter_data = [
                'filter_tax_id_num' => '*' . $last3,
                'pagination' => false,
                'limit' => 0,
                'connection' => 'sysdata',
                'keyBy' => 'tax_id_num',
            ];
            $tax_id_nums = $this->getRows($filter_data);

            DataHelper::setJsonToStorage($cacheName, $tax_id_nums->toArray());

            $tax_id_nums = DataHelper::getJsonFromStoragForCollection($cacheName);
        }

        return $tax_id_nums ?? null;
    }
}

