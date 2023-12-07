<?php

namespace App\Repositories\Eloquent\Localization;

use App\Models\SysData\TwTaxIdNum;
use App\Repositories\Eloquent\Repository;
use App\Repositories\Eloquent\Common\TermRepository;
use App\Helpers\Classes\DataHelper;

class TaxIdNumRepository extends Repository
{
    public $modelName = "\App\Models\SysData\TwTaxIdNum";


    /**
     * 如果有傳入完整統一編號，從 refreshTaxIdNumsJson() 裡面查找。這個方法會先從快取裡面找，如果找不到會去查資料庫。
     * 如果沒有完整統一編號，則使用標準查詢方式。
     */
    public function getTaxIdNum($params = [], $debug = 0)
    {
        if(!empty($params['equal_tax_id_num'])){
            $rows = $this->refreshTaxIdNumsJson($params['equal_tax_id_num']);

            if(count($rows) > 0){
                $row = $rows[$params['equal_tax_id_num']];

                if(!empty($rows[$params['equal_tax_id_num']])){
                    return $row;
                }
            }
        }
        else{
            $params['keyBy'] = 'tax_id_num';
            $row = $this->getRow($params);

            if(!empty($row)){
                return $row;
            }
        }

        return null;
    }


    public function getTaxIdNums($params = [], $debug = 0)
    {
        $params['keyBy'] = 'tax_id_num';
        $rows = $this->getRows($params);

        // if(isset($params['equal_tax_id_num'])){
        //     $equal_tax_id_num = $params['equal_tax_id_num'];
        //     $tax_id_nums = $this->refreshTaxIdNumsJson($equal_tax_id_num);
        // }

        return $rows;
    }

    // 必須傳入完整統一編號
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

