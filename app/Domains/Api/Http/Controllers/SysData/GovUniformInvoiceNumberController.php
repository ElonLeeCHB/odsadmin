<?php

namespace App\Domains\Api\Http\Controllers\SysData;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Libraries\TranslationLibrary;
use App\Domains\Admin\Services\SysData\GovUniformInvoiceNumberService;
use App\Domains\Admin\Services\Localization\DivisionService;
use App\Helpers\Classes\TwAddress;

class GovUniformInvoiceNumberController extends Controller
{
    private $lang;

    public function __construct(
        private Request $request
        , private GovUniformInvoiceNumberService $GovUniformInvoiceNumberService
        , private DivisionService $DivisionService
    )
    {

        $groups = [
            'admin/common/common',
            'admin/sysdata/uniform_invoice_number',
        ];
        $this->lang = (new TranslationLibrary())->getTranslations($groups);
    }

    public function details($guin)
    {
        $data = $this->request->all();

        if(strlen($guin) < 8){
            return response(json_encode('長度不足'))->header('Content-Type','application/json');
        }

        $filter_data = [
            'filter_uniform_invoice_no' => $guin,
            'regexp' => false,
        ];

        $record = $this->GovUniformInvoiceNumberService->getRow($filter_data);

        if(!empty($record)){
            $arr = TwAddress::parseGovProvidedAddress($record->address);

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

            $record->address_parts = $arr;
        }


        return response(json_encode($record))->header('Content-Type','application/json');
    }


    public function autocomplete()
    {
        $json = [];

        $filter_uniform_invoice_no = $this->request->filter_payment_tin ?? $this->request->filter_uniform_invoice_no ?? '';

        if(!empty($filter_uniform_invoice_no)){
            if(strlen($filter_uniform_invoice_no) < 7 ){
                return response(json_encode('長度不足'))->header('Content-Type','application/json');
            }
            $filter_data['filter_uniform_invoice_no'] = $filter_uniform_invoice_no;
        }

        if(!empty($this->request->filter_name)){
            $filter_data['filter_name'] = $this->request->filter_name;
        }

        $filter_data['pagination'] = false;
        $filter_data['limit'] = 10;
        $filter_data['connection'] = 'sysdata';

        $rows = $this->GovUniformInvoiceNumberService->getRows($filter_data);
        
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
                    'uniform_invoice_no' => $row->uniform_invoice_no,
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

        $row = $this->GovUniformInvoiceNumberService->setCache($data);

    }

}
