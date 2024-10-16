<?php

namespace App\Domains\Admin\Services\Counterparty;

use App\Libraries\TranslationLibrary;
use App\Services\Service;
use App\Repositories\Eloquent\SysData\GovUniformInvoiceNumberRepository;
use Cache;

class GovUniformInvoiceNumberService extends Service
{
    protected $modelName = "\App\Models\SysData\GovUniformInvoiceNumber";
    protected $connection = 'sysdata';

	public function __construct(protected GovUniformInvoiceNumberRepository $repository)
	{
        $this->lang = (new TranslationLibrary())->getTranslations(['admin/sysdata/order',]);
	}

    public function getRow($data,$debug=0)
    {
        if(!empty($data['filter_tax_id_num'])){
            $tax_id_num = $data['filter_tax_id_num'];
            $cacheName = 'tax_id_num_' . substr($tax_id_num,-3);
            $records = cache()->get($cacheName);

            if (isset($records[$tax_id_num])) {
                // 若有，則取得指定的記錄
                $record = $records[$tax_id_num];
            } else {
                // 若沒有，則從資料庫或其他資料來源取得資料
                $filter_data = [
                    'filter_tax_id_num' => '*' . substr($tax_id_num,-3),
                    'pagination' => false,
                    'limit' => 0,
                    'regexp' => true,
                    'connection' => 'sysdata',
                ];
                $records = $this->getRows($filter_data);

                if(!empty($records)){
                    $records = $records->keyBy('tax_id_num');

                    cache()->put($cacheName, $records, 60*60*24*60); // 有效秒數

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

    public function setCache($data=[], $debug=0)
    {
        $start = 0;

        for ($i=$start; $i<($start+10); $i++) {
            $sufix = sprintf("%02d", $i);
            $cacheName = 'tax_id_num_' . $sufix;
            $records = cache()->get($cacheName);

            if (empty($records)) {
                $filter_data = [
                    'filter_tax_id_num' => '*' . $sufix,
                    'pagination' => false,
                    'limit' => 0,
                    'connection' => 'sysdata',
                ];
                $records = $this->repository->getRows($filter_data);
                $records = $records->keyBy('tax_id_num')->toArray();

                Cache::put($cacheName, $records, 60*60*24*365);
            }


        }
    }

}
