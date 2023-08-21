<?php

namespace App\Domains\Admin\Services\SysData;

use App\Libraries\TranslationLibrary;
use App\Domains\Admin\Services\Service;
use App\Repositories\Eloquent\SysData\GovUniformInvoiceNumberRepository;
use Cache;

class GovUniformInvoiceNumberService extends Service
{
    private $lang;
    protected $modelName = "\App\Models\SysData\GovUniformInvoiceNumber";
    protected $connection = 'sysdata';

	public function __construct(protected GovUniformInvoiceNumberRepository $repository)
	{
        $this->lang = (new TranslationLibrary())->getTranslations(['admin/sysdata/order',]);
	}

    public function getRow($data,$debug=0)
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
                    'limit' => 0,
                    'regexp' => true,
                    'connection' => 'sysdata',
                ];
                $records = $this->getRows($filter_data);

                if(!empty($records)){
                    $records = $records->keyBy('uniform_invoice_no');

                    cache()->put($cacheName, $records, 60*60*24*60); // 有效秒數

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

    public function setCache($data=[], $debug=0)
    {
        $start = 0;

        for ($i=$start; $i<($start+10); $i++) {
            $sufix = sprintf("%02d", $i);
            $cacheName = 'uniform_invoice_no_' . $sufix;
            $records = cache()->get($cacheName);

            if (empty($records)) {
                $filter_data = [
                    'filter_uniform_invoice_no' => '*' . $sufix,
                    'pagination' => false,
                    'limit' => 0,
                    'connection' => 'sysdata',
                ];
                $records = $this->repository->getRows($filter_data);
                $records = $records->keyBy('uniform_invoice_no')->toArray();
    
                Cache::put($cacheName, $records, 60*60*24*365);
                echo '<pre>產生快取：', print_r($cacheName, 1), "</pre>";
            } 


        }
        echo '<pre>', print_r('End', 1), "</pre>"; exit;
    }

}