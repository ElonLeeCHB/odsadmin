<?php

namespace App\Domains\Api\Http\Controllers\Member;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Domains\Api\Http\Controllers\ApiController;
use App\Domains\Api\Services\Member\MemberService;
use App\Domains\Api\Services\Localization\CountryService;
use App\Domains\Api\Services\Localization\DivisionService;
use App\Domains\Api\Services\Catalog\OptionService;


class MemberController extends ApiController
{
    public function __construct(
        private Request $request
        , private MemberService $MemberService
        , private CountryService $CountryService
        , private DivisionService $DivisionService
        , private OptionService $OptionService
        )
    {}


    public function list()
    {
        $query_data = $this->request->query();

        $filter_data = $this->getQueries($query_data);

        $members = $this->MemberService->getMembers($filter_data);

        $members = $this->MemberService->optimizeRows($members);

        $members = $this->MemberService->unsetRelations($members, ['status']);

        return response(json_encode($members))->header('Content-Type','application/json');
    }


    public function details($member_id)
    {
        $data = $this->request->all();

        $record = $this->MemberService->findIdOrFailOrNew($member_id);

        return response(json_encode($record))->header('Content-Type','application/json');
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

        if(!$json) {
            $result = $this->MemberService->updateOrCreate($data);

            if(empty($result['error'])){
                $json['member_id'] = $result['data']['member_id'];
                $json['success'] = $this->lang->text_success;
            }else{
                $user_id = Auth::user()->id ?? null;
                if(1){
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
        $json = $this->MemberService->getSalutations();

        return response(json_encode($json))->header('Content-Type','application/json');
    }



}
