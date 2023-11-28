<?php

namespace App\Services\Localization;

use App\Services\Service;

class TaxIdNumberService extends Service
{
    protected $modelName = "\App\Models\SysData\TwTaxIdNum";
    protected $connection = 'sysdata';

    //public function getInvoiceInfo($data=[],$debug=0)
    public function getTaxIdNumRow($data = [], $debug = 0)
    {
        $tax_id_num = '';

        if(!empty($data['filter_tax_id_num'])){
            $tax_id_num = $data['filter_tax_id_num'];
        }else if(!empty($data['equal_tax_id_num'])){
            $tax_id_num = $data['equal_tax_id_num'];
        }

        if(!empty($tax_id_num)){

            $last3 = substr($tax_id_num,-3); //統編末3碼

            $cacheName = 'tax_id_num_' . $last3;

            $records = cache()->get($cacheName);

            if (isset($records[$tax_id_num])) {
                // 若有，則取得指定的記錄
                $record = $records[$tax_id_num];
            } else {
                // 若沒有，則從資料庫或其他資料來源取得資料
                $filter_data = [
                    'filter_tax_id_num' => '*' . $last3,
                    'pagination' => false,
                    'limit' => 0,
                    'connection' => 'sysdata',
                ];
                $records = $this->getRows($filter_data);

                if(!empty($records)){
                    $records = $records->keyBy('tax_id_num');

                    cache()->put($cacheName, $records, 60*60*24*365); // 有效秒數

                    if(!empty($records[$tax_id_num])){
                        $record = $records[$tax_id_num];
                    }
                }
            }
        }

        if(!empty($record)){
            return $record;
        }
        
        return false;
    }
    

}