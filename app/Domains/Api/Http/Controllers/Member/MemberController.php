<?php

namespace App\Domains\Api\Http\Controllers\Member;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Domains\Api\Http\Controllers\ApiController;
use App\Domains\Api\Services\Member\MemberService;
use App\Domains\Api\Services\Localization\CountryService;
use App\Domains\Api\Services\Localization\DivisionService;
use App\Domains\Api\Services\Catalog\OptionService;
use App\Http\Resources\Member\MemberCollection;
use App\Http\Resources\Member\MemberResource;


class MemberController extends ApiController
{
    public function __construct(
        private Request $request
        , private MemberService $MemberService
        , private CountryService $CountryService
        , private DivisionService $DivisionService
        , private OptionService $OptionService
        )
    {
        parent::__construct();

        $this->getLang(['admin/common/common','admin/member/member']);
    }


    public function list()
    {
        $members = $this->MemberService->getMembers(request()->query());
        $members = (new MemberCollection($members))->toArray();

        return response(json_encode($members))->header('Content-Type','application/json');
    }


    public function details($member_id)
    {
        $data = $this->request->all();

        $result = $this->MemberService->findIdOrFailOrNew($member_id);

        if(!empty($result['data'])){
            $member = $result['data'];
        }else{
            return response(json_encode($result))->header('Content-Type','application/json');
        }

        $member = $this->MemberService->setMetasToRow($member);

        return response(json_encode($member))->header('Content-Type','application/json');
    }


    public function save()
    {
        $data = $this->request->all();

        $json = [];

        // Check member
        //新增時檢查
        if(empty($data['member_id'])){
            if(!empty($this->request->mobile)){
                $filter_data = [
                    'filter_mobile' => str_replace('-','',$this->request->mobile),
                    'regexp' => false,
                ];
                $member = $this->MemberService->getRow($filter_data);

                if(!empty($member)){
                    $json['error']['mobile'] = '這個手機號碼已存在，不可新增。';
                }
            }

            if(!empty($this->request->email)){
                $filter_data = [
                    'filter_email' => trim($this->request->email),
                    'regexp' => false,
                ];
                $member = $this->MemberService->getRow($filter_data);

                if(!empty($member)){
                    $json['error']['email'] = '這個 email 已存在，不可新增。';
                }
            }
        }

        //$validator = $this->MemberService->validator($this->request->post());

        // if($validator->fails()){
        //     $messages = $validator->errors()->toArray();
        //     foreach ($messages as $key => $rows) {
        //         $json['error'][$key] = $rows[0];
        //     }
        // }

        if(isset($json['error']) && !isset($json['error']['warning'])) {
            $json['error']['warning'] = $this->lang->error_warning;
        }
        // dd($data);
        if(!$json) {
            $result = $this->MemberService->saveMember($data);

            if(empty($result['error'])){
                $json['member_id'] = $result['id'];
                $json['success'] = $this->lang->text_success;
            }else{
                if(config('app.debug')){
                    $json['error'] = $result['error'];
                }else{
                    $json['error'] = $this->lang->text_fail;
                }
            }
        }

       return response(json_encode($json))->header('Content-Type','application/json');
    }


    public function getSalutations()
    {
        $json = $this->MemberService->getCodeKeyedTermsByTaxonomyCode('salutation', toArray:'false');

        return response(json_encode($json))->header('Content-Type','application/json');
    }



}
