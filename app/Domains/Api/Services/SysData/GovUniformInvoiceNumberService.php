<?php

namespace App\Domains\Api\Services\SysData;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Traits\Model\EloquentTrait;
use App\Libraries\TranslationLibrary;
use App\Domains\Api\Services\Service;

class GovUniformInvoiceNumberService extends Service
{
    use EloquentTrait;

    public $modelName;
    public $model;
    public $table;
    public $lang;

	public function __construct()
	{
        $this->modelName = "\App\Models\SysData\GovUniformInvoiceNumber";
        $this->lang = (new TranslationLibrary())->getTranslations(['admin/sysdata/order',]);
	}

    public function getInvoiceInfo($data=[],$debug=0)
    {
        if(!empty($data['filter_uniform_invoice_no'])){
            $uniform_invoice_no = $data['filter_uniform_invoice_no'];

            $cacheName = 'uniform_invoice_no_' . substr($uniform_invoice_no,-3);

            $records = cache()->get($cacheName);

            if (isset($records[$uniform_invoice_no])) {
                // 若有，則取得指定的記錄
                $record = $records[$uniform_invoice_no];
            } else {
                // 若沒有，則從資料庫或其他資料來源取得資料
                $filter_data = [
                    'filter_uniform_invoice_no' => '*' . substr($uniform_invoice_no,-3),
                    'pagination' => false,
                    '_real_limit' => 100000,
                    'connection' => 'sysdata',
                ];
                $records = $this->getRecords($filter_data);

                if(!empty($records)){
                    $records = $records->keyBy('uniform_invoice_no');

                    cache()->put($cacheName, $records, 60*60*24*365); // 有效秒數

                    if(!empty($records[$uniform_invoice_no])){
                        $record = $records[$uniform_invoice_no];
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