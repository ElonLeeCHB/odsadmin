<?php

namespace App\Domains\Api\Http\Controllers\Localization;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Domains\Api\Http\Controllers\ApiController;
use App\Libraries\TranslationLibrary;
use App\Services\Localization\TaxIdNumberService;
use App\Domains\Admin\Services\Localization\DivisionService;
use App\Helpers\Classes\TwAddress;

class TaxIdNumController extends ApiController
{
    protected $lang;

    public function __construct(
        private Request $request
        , private TaxIdNumberService $TaxIdNumberService
        , private DivisionService $DivisionService
    )
    {
        parent::__construct();
    }

    public function detail($tax_id_num)
    {
        $data = $this->request->all();

        $json = [];

        if(strlen($tax_id_num) < 8){
            $json['error'] = '長度不足';
            return response(json_encode($json))->header('Content-Type','application/json');
        }

        $filter_data = [
            'equal_tax_id_num' => $tax_id_num,
            'regexp' => false,
        ];

        $record = $this->TaxIdNumberService->getTaxIdNum($filter_data,1);
        
        if(!empty($record)){
            $arr = TwAddress::parseGovProvidedAddress($record->address);

            if(!empty($arr['divsionL1'])){
                $filter_data = [
                    'equal_level' => 1,
                    'filter_name' => $arr['divsionL1'],
                    'regexp' => false,
                ];
                $divsionL1 = $this->DivisionService->getRow($filter_data);

                $filter_data = [
                    'equal_parent_id' => $divsionL1->id,
                    'equal_level' => 2,
                    'filter_name' => $arr['divsionL2'],
                    'regexp' => false,
                ];
                $divsionL2 = $this->DivisionService->getRow($filter_data);

                $arr['divsionL1_id'] = $divsionL1->id;
                $arr['divsionL2_id'] = $divsionL2->id;
            }

            $record->address_parts = $arr;

            $json = $record;
        }else{
            $json = ['error' => '查無資料'];
        }


        return response(json_encode($json))->header('Content-Type','application/json');
    }


    public function list()
    {
        $json = [];

        if(!empty($this->request->filter_tax_id_num)){
            if(strlen($this->request->filter_tax_id_num) < 8 ){
                return response(json_encode('長度不足'))->header('Content-Type','application/json');
            }
            $filter_data['filter_tax_id_num'] = $this->request->filter_tax_id_num;
        }

        if(!empty($this->request->filter_name)){
            $filter_data['filter_name'] = $this->request->filter_name;
        }

        $filter_data['pagination'] = false;
        $filter_data['limit'] = 10;
        $filter_data['connection'] = 'sysdata';

        $rows = $this->TaxIdNumberService->getTaxIdNums($filter_data);
        
        if(!empty($rows)){
            foreach ($rows as $key => $row) {
                $arr = TwAddress::parseGovProvidedAddress($row->address);

                if(!empty($arr['divsionL1'])){
                    $filter_data = [
                        'filter_level' => 1,
                        'filter_name' => $arr['divsionL1'],
                        'regexp' => false,
                    ];
                    $divsionL1 = $this->DivisionService->getRow($filter_data);

                    $filter_data = [
                        'filter_parent_id' => $divsionL1->id,
                        'filter_level' => 2,
                        'filter_name' => $arr['divsionL2'],
                        'regexp' => false,
                    ];
                    $divsionL2 = $this->DivisionService->getRow($filter_data);

                    $arr['divsionL1_id'] = $divsionL1->id;
                    $arr['divsionL2_id'] = $divsionL2->id;
                }

                $json[] = [
                    //'id' => $row->id,
                    'label' => $row->name,
                    'value' => $row->tax_id_num,
                    'tax_id_num' => $row->tax_id_num,
                    'name' => $row->name,
                    'short_name' => $row->short_name,
                    'address_parts' => $arr,
                ];
            }
        }

        return response(json_encode($json))->header('Content-Type','application/json');
    }


    public function setCache()
    {
        $data = $this->request->all();

        $row = $this->TaxIdNumberService->setCache($data);

    }

}
