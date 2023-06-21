<?php

namespace App\Domains\Api\Http\Controllers\Member;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Libraries\TranslationLibrary;
use App\Domains\Api\Services\Member\MemberService;
use App\Domains\Api\Services\Localization\CountryService;
use App\Domains\Api\Services\Localization\DivisionService;
use App\Domains\Api\Services\Common\OptionService;


class MemberController extends Controller
{
    private $lang;

    public function __construct(
        private Request $request
        , private MemberService $MemberService
        , private CountryService $CountryService
        , private DivisionService $DivisionService
        , private OptionService $OptionService
        )
    {
        $this->lang = (new TranslationLibrary())->getTranslations(['admin/common/common','admin/member/member']);
    }


    public function list()
    {
        $data['lang'] = $this->lang;

        // Prepare link for action
        $queries = [];

        if(!empty($this->request->query('page'))){
            $page = $queries['page'] = $this->request->input('page');
        }else{
            $page = $queries['page'] = 1;
        }

        if(!empty($this->request->query('sort'))){
            $sort = $queries['sort'] = $this->request->input('sort');
        }else{
            $sort = $queries['sort'] = 'id';
        }

        if(!empty($this->request->query('order'))){
            $order = $queries['order'] = $this->request->query('order');
        }else{
            $order = $queries['order'] = 'asc';
        }

        if(!$this->request->has('pagination')){
            $queries['pagination'] = true;
        }else{
            $queries['pagination'] = $this->request->query('pagination');
        }

        if(!empty($this->request->query('limit'))){
            $limit = $queries['limit'] = $this->request->query('limit');
        }

        foreach($this->request->all() as $key => $value){
            if(strpos($key, 'filter_') !== false){
                $queries[$key] = $value;
            }
        }

        // Rows
        $members = $this->MemberService->getMembers($queries);

        if(!empty($members)){
            foreach ($members as $row) {
                $row->edit_url = route('api.member.member.details', array_merge([$row->id], $queries));
            }
        }

        return response(json_encode($members))->header('Content-Type','application/json');
    }


    public function details($member_id)
    {
        $data = $this->request->all();  

        $data['id'] = $member_id;

        $record = $this->MemberService->find($data);

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
                $member = $this->MemberService->getRecord($filter_data);
    
                if(!empty($member)){
                    $json['error']['mobile'] = '這個手機號碼已存在，不可新增。';
                }
            }

            if(!empty($this->request->email)){
                $filter_data = [
                    'filter_email' => trim($this->request->email),
                    'regexp' => false,
                ];
                $member = $this->MemberService->getRecord($filter_data);
    
                if(!empty($member)){
                    $json['error']['email'] = '這個 email 已存在，不可新增。';
                }
            }
        }

        $validator = $this->MemberService->validator($this->request->post());

        if($validator->fails()){
            $messages = $validator->errors()->toArray();
            foreach ($messages as $key => $rows) {
                $json['error'][$key] = $rows[0];
            }
        }

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

    public function autocomplete()
    {
        $json = [];

        //$filter_data['filter_id'] = '>1';

        if(isset($this->request->filter_personal_name) && mb_strlen($this->request->filter_personal_name, 'utf-8') > 1)
        {
            $filter_data['filter_name'] = $this->request->filter_personal_name;
        }

        if(isset($this->request->filter_mobile) && strlen($this->request->filter_mobile) > 2)
        {
            $filter_data['filter_mobile'] = $this->request->filter_mobile;
        }

        if(isset($this->request->filter_telephone) && strlen($this->request->filter_telephone) > 2)
        {
            $filter_data['filter_telephone'] = $this->request->filter_telephone;
        }

        if(isset($this->request->filter_email) && strlen($this->request->filter_email) > 2)
        {
            $filter_data['filter_email'] = $this->request->filter_email;
        }

        if (empty($this->request->sort)) {
            $filter_data['sort'] = 'name';
            $filter_data['order'] = 'ASC';
        }else{
            $filter_data['sort'] = $this->request->sort;
            $filter_data['order'] = $this->request->order;
        }

        if(!empty($this->request->with) )
        {
            $filter_data['with'] = $this->request->with;
        }

        $filter_data['limit'] = 20;
        $filter_data['pagination'] = false;

        $members = $this->MemberService->getRecords($filter_data);

        foreach ($members as $row) {

            $show_text = '';
            if(!empty($this->request->show_column1) && !empty($this->request->show_column2)){
                $col = $this->request->show_column1;
                $show_text = $row->$col;

                $col = $this->request->show_column2;
                $show_text .= '_'.$row->$col;
            }else{
                $show_text = $row->personal_name . '_' . $row->mobile;
            }

            $has_order = 0;
            if(count($row->orders)){
                $has_order = 1;
            }

            $json[] = array(
                'label' => $show_text,
                'value' => $row->id,
                'customer_id' => $row->id,
                'personal_name' => $row->name,
                'salutation_id' => $row->salutation_id,
                'telephone' => $row->telephone,
                'mobile' => $row->mobile,
                'email' => $row->email,
                'payment_company' => $row->payment_company,
                'payment_department' => $row->payment_department,
                'payment_tin' => $row->payment_tin,
                'shipping_personal_name' => $row->shipping_personal_name,
                'shipping_phone' => $row->shipping_phone,
                'shipping_company' => $row->shipping_company,
                'shipping_state_id' => $row->shipping_state_id,
                'shipping_city_id' => $row->shipping_city_id,
                'shipping_road_id' => $row->shipping_road_id,
                'shipping_road' => $row->shipping_road,
                'shipping_address1' => $row->shipping_address1,
                'shipping_lane' => $row->shipping_lane,
                'shipping_alley' => $row->shipping_alley,
                'shipping_no' => $row->shipping_no,
                'shipping_floor' => $row->shipping_floor,
                'shipping_room' => $row->shipping_room,
                'has_order' => $has_order,
            );
        }

        array_unshift($json,[
            'value' => 0,
            'label' => ' -- ',
            'customer_id' => '',
            'personal_name' => '',
            'salutation_id' => '',
            'telephone' => '',
            'mobile' => '',
            'email' => '',
            'payment_company' => '',
            'payment_department' => '',
            'payment_tin' => '',
            'shipping_personal_name' => '',
            'shipping_phone' => '',
            'shipping_company' => '',
            'shipping_state_id' => '',
            'shipping_city_id' => '',
            'shipping_road_id' => '',
            'shipping_road' => '',
            'shipping_address1' => '',
            'shipping_lane' => '',
            'shipping_alley' => '',
            'shipping_no' => '',
            'shipping_floor' => '',
            'shipping_room' => '',
        ]);

        return response(json_encode($json))->header('Content-Type','application/json');
    }
}
