<?php

namespace App\Domains\Admin\Http\Controllers\SysData;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Libraries\TranslationLibrary;
use App\Domains\Admin\Services\SysData\GovUniformInvoiceNumberService;
use App\Helpers\Classes\TwAddress;
use App\Domains\Admin\Services\Localization\DivisionService;

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


    public function autocompleteSingle()
    {
        $json = [];

        $filter_uniform_invoice_no = null;
        if(!empty($this->request->filter_payment_tin)){
            $filter_uniform_invoice_no = $this->request->filter_payment_tin;
        }else{
            $filter_uniform_invoice_no = $this->request->filter_uniform_invoice_no;
        }

        if(!empty($filter_uniform_invoice_no) && strlen($filter_uniform_invoice_no) > 7)
        {
            $filter_data['filter_uniform_invoice_no'] = $filter_uniform_invoice_no;
        }
        
        $filter_data['pagination'] = false;

        $filter_data['connection'] = 'sysdata';

        $row = $this->GovUniformInvoiceNumberService->getRow($filter_data);

        if(empty($row)){
            $json[] = [
                'label' => '查無記錄',
                'value' => '查無記錄',
            ];
        }
        else{
            $row = (object)$row;
            $arr = TwAddress::parseGovProvidedAddress($row->address);
    
            if(!empty($arr['divsionL1'])){
                $divsionL1 = $this->DivisionService->repository->newModel()->where('name', $arr['divsionL1'])->first();
    
                $divsionL2 = $this->DivisionService->repository->newModel() // 不應該有錯，不另做空值判斷
                    ->where('name', $arr['divsionL2'])
                    ->where('parent_id', $divsionL1->id)->first();
                
                $arr['divsionL1_id'] = $divsionL1->id;
                $arr['divsionL2_id'] = $divsionL2->id;
            }
            
            $json[] = array(
                'label' => $row->name,
                'value' => $row->id,
                'organization_id' => $row->id,
                'uniform_invoice_no' => $row->uniform_invoice_no,
                'headquarter_uin' => $row->headquarter_uin,
                'name' => $row->name,
                'short_name' => $row->short_name,
                'original_address' => $row->original_address,
                'address_parts' => $arr,
            );
        }

        return response(json_encode($json))->header('Content-Type','application/json');
    }


    public function setCache()
    {
        $data = $this->request->all();
        
        $row = $this->GovUniformInvoiceNumberService->setCache($data);

        echo '<pre>', print_r(999, 1), "</pre>"; exit;
    }

}